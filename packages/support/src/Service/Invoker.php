<?php

declare(strict_types=1);

namespace Sylament\Support\Service;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
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
     * @template T
     * @param callable(): T $callable
     * @param array<string, mixed> $parameters
     * @return T
     */
    public function call(
        callable $callable,
        array $parameters = [],
        ?callable $resolveNamedParameter = null,
    ): mixed {
        $reflection = $this->getCallableReflection($callable);

        $args = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                $args[] = $parameters[$name];
                continue;
            }

            if ($resolveNamedParameter !== null) {
                $resolvedNamedParameter = $resolveNamedParameter($name);
                if ($resolvedNamedParameter instanceof ClosureParameter) {
                    $args[] = $resolvedNamedParameter->getValue();
                    continue;
                }
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->container->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException("Cannot resolve parameter {$name}");
        }

        return $callable(...$args);
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
