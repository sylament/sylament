<?php

declare(strict_types=1);

namespace Sylament\Schemas\State\Casts;

use Closure;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ObjectStateCast implements StateCast
{
    protected const string PROPERTY_NAME_PATTERN = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/';

    /**
     * @var class-string
     */
    protected string $itemClass;

    protected PropertyAccessorInterface $accessor;

    /**
     * @var ReflectionClass<object>
     */
    protected ReflectionClass $reflection;

    /**
     * The conversion pair overrides either default half; each is invoked
     * directly with the value (`fn (array $item): object`,
     * `fn (object $item): array`), never through the Invoker.
     *
     * @param class-string|string $itemClass
     */
    public function __construct(
        string $itemClass,
        protected ?Closure $denormalizeUsing = null,
        protected ?Closure $normalizeUsing = null,
        ?PropertyAccessorInterface $accessor = null,
    ) {
        if (! class_exists($itemClass) || ! new ReflectionClass($itemClass)->isInstantiable()) {
            throw new LogicException(sprintf(
                'ObjectStateCast needs an instantiable item class and "%s" is not one. Point it at a concrete class with a public constructor.',
                $itemClass,
            ));
        }

        $this->itemClass = $itemClass;
        $this->reflection = new ReflectionClass($itemClass);
        $this->accessor = $accessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @return class-string
     */
    public function getItemClass(): string
    {
        return $this->itemClass;
    }

    public function get(mixed $state): mixed
    {
        if ($state === null || $state instanceof $this->itemClass) {
            return $state;
        }

        if (! is_array($state)) {
            throw new LogicException(sprintf(
                'ObjectStateCast over %s met a %s item on dehydration; a cast item is a value-shaped array or an existing instance.',
                $this->itemClass,
                get_debug_type($state),
            ));
        }

        if ($this->denormalizeUsing instanceof Closure) {
            return ($this->denormalizeUsing)($state);
        }

        return $this->construct($state);
    }

    public function set(mixed $state): mixed
    {
        if ($state === null || is_array($state)) {
            return $state;
        }

        if (! $state instanceof $this->itemClass) {
            throw new LogicException(sprintf(
                'ObjectStateCast over %s met a %s item on fill; record data under a cast repeater holds %s instances or value-shaped arrays.',
                $this->itemClass,
                get_debug_type($state),
                $this->itemClass,
            ));
        }

        if ($this->normalizeUsing instanceof Closure) {
            return ($this->normalizeUsing)($state);
        }

        return $this->deconstruct($state);
    }

    /**
     * @param array<array-key, mixed> $item
     */
    protected function construct(array $item): object
    {
        $arguments = [];
        $consumed = [];

        foreach ($this->reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();
            $supplied = array_key_exists($name, $item);
            $value = $supplied ? $item[$name] : null;

            if ($value === null && (! $supplied || ! $parameter->allowsNull())) {
                if ($parameter->isDefaultValueAvailable()) {
                    $consumed[$name] = true;

                    continue;
                }

                if (! $parameter->allowsNull()) {
                    $this->rejectParameter($parameter, $item);
                }
            }

            $arguments[$name] = $value;
            $consumed[$name] = true;
        }

        $object = $this->reflection->newInstance(...$arguments);

        foreach ($item as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (isset($consumed[$key])) {
                continue;
            }

            // Security: the accessor reads its key as a property *path*, so only a bare
            // name may reach it — anything else would traverse into the object graph.
            if (preg_match(self::PROPERTY_NAME_PATTERN, $key) !== 1) {
                continue;
            }

            if (! $this->accessor->isWritable($object, $key)) {
                continue;
            }

            try {
                $this->accessor->setValue($object, $key, $value);
            } catch (InvalidArgumentException $exception) {
                throw new LogicException(sprintf(
                    'Cannot rebuild %s: property "%s" rejects the dehydrated value (%s). Declare required() on the field that feeds it, or make the property nullable.',
                    $this->itemClass,
                    $key,
                    get_debug_type($value),
                ), $exception->getCode(), previous: $exception);
            }
        }

        return $object;
    }

    /**
     * @return array<string, mixed>
     */
    protected function deconstruct(object $item): array
    {
        $values = [];

        foreach ($this->getNameSet() as $name) {
            if ($this->accessor->isReadable($item, $name)) {
                $values[$name] = $this->accessor->getValue($item, $name);
            }
        }

        return $values;
    }

    /**
     * The one name set both directions share: constructor parameters, public
     * non-static properties, and getter-derived names (`get`/`is`/`has`), so a
     * promoted, a setter-based and a getter-backed item all reach the same keys.
     *
     * @return list<string>
     */
    protected function getNameSet(): array
    {
        $names = [];

        foreach ($this->reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $names[$parameter->getName()] = true;
        }

        foreach ($this->reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (! $property->isStatic()) {
                $names[$property->getName()] = true;
            }
        }

        foreach ($this->reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }

            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $methodName = $method->getName();

            foreach (['get', 'is', 'has'] as $prefix) {
                if (str_starts_with($methodName, $prefix) && mb_strlen($methodName) > mb_strlen($prefix)) {
                    $names[lcfirst(mb_substr($methodName, mb_strlen($prefix)))] = true;

                    break;
                }
            }
        }

        return array_keys($names);
    }

    /**
     * @param array<array-key, mixed> $item
     */
    protected function rejectParameter(ReflectionParameter $parameter, array $item): never
    {
        throw new LogicException(sprintf(
            'Cannot rebuild %s: constructor parameter $%s is non-optional and the dehydrated item supplies %s for it (item keys: %s). Declare required() on the field that feeds it, or give the parameter a default or nullable type.',
            $this->itemClass,
            $parameter->getName(),
            array_key_exists($parameter->getName(), $item) ? 'null' : 'no key',
            implode(', ', array_map(strval(...), array_keys($item))) ?: 'none',
        ));
    }
}
