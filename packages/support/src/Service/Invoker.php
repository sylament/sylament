<?php

declare(strict_types=1);

namespace Sylament\Support\Service;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use Sylament\Support\Data\ClosureParameter;
use Sylament\Support\DependencyInjection\HasStaticInstance;
use Sylament\Support\DependencyInjection\StaticInstanceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Invoker implements StaticInstanceInterface
{
    use HasStaticInstance;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @param array<string, mixed> $namedInjections
     * @param array<class-string, mixed> $typedInjections
     */
    public function call(
        callable $callable,
        array $namedInjections = [],
        array $typedInjections = [],
        ?callable $resolveParameter = null,
    ): mixed {
        $reflection = $this->getCallableReflection($callable);

        $args = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $namedInjections)) {
                $args[] = $namedInjections[$name];
                continue;
            }

            $typeName = $this->getParameterClassName($parameter);

            if ($typeName !== null && array_key_exists($typeName, $typedInjections)) {
                $args[] = $typedInjections[$typeName];
                continue;
            }

            if ($resolveParameter !== null) {
                $resolvedParameter = $resolveParameter($name, $typeName);
                if ($resolvedParameter instanceof ClosureParameter) {
                    $args[] = $resolvedParameter->getValue();
                    continue;
                }
            }

            if ($typeName !== null && $this->container->has($typeName)) {
                $args[] = $this->container->get($typeName);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->isOptional() || $parameter->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new RuntimeException($typeName === null
                ? "Cannot resolve parameter \${$name}"
                : "Cannot resolve parameter {$typeName} \${$name}");
        }

        return $callable(...$args);
    }

    private function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        $class = $parameter->getDeclaringClass();

        if ($class === null) {
            return $name;
        }

        if ($name === 'self') {
            return $class->getName();
        }

        if ($name === 'parent') {
            $parent = $class->getParentClass();

            return $parent === false ? $name : $parent->getName();
        }

        return $name;
    }

    private function getCallableReflection(callable $callable): ReflectionFunctionAbstract
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        if (is_string($callable) && class_exists($callable)) {
            return new ReflectionMethod($callable, '__invoke');
        }

        if (is_string($callable) || $callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        throw new RuntimeException('Can not create reflection for callable');
    }
}
