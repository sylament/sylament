<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\DependencyInjection;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Exports every shipped constructor, factory and method the container hands an `ObjectManager` or a DBAL
 * `Connection`, as `Class::method()`, because a compiled container keeps no record of what it injected.
 */
final class ManagerInjectionPass implements CompilerPassInterface
{
    public const string PARAMETER = 'sylament_tests.manager_injections';

    public function process(ContainerBuilder $container): void
    {
        $container->setParameter(self::PARAMETER, self::injectionsIn($container));
    }

    /**
     * Shipped is decided by the class that declares the method, never by the service id, so a fixture subclass
     * inheriting a shipped setter is reported under the shipped class.
     *
     * @return list<string>
     */
    public static function injectionsIn(ContainerBuilder $container): array
    {
        $injections = [];

        foreach ($container->getDefinitions() as $definition) {
            $class = $container->getReflectionClass($definition->getClass(), throw: false);

            if (!$class instanceof ReflectionClass) {
                continue;
            }

            if (self::injectsManager($container, $definition->getArguments())) {
                $injections[] = self::factoryOrConstructor($container, $definition, $class);
            }

            foreach ($definition->getMethodCalls() as $call) {
                $method = is_array($call) ? ($call[0] ?? null) : null;
                $arguments = is_array($call) ? ($call[1] ?? null) : null;

                if (!is_string($method) || !is_array($arguments)) {
                    throw new LogicException(sprintf('The service "%s" holds a method call that names no method or arguments.', $class->getName()));
                }

                if (self::injectsManager($container, $arguments)) {
                    $injections[] = self::declarer($class, $method);
                }
            }
        }

        $shipped = array_filter(
            array_unique($injections),
            static fn (string $injection): bool => str_starts_with($injection, 'Sylament\\') && !str_starts_with($injection, 'Sylament\\Tests\\'),
        );
        sort($shipped);

        return $shipped;
    }

    /**
     * @param array<mixed> $arguments
     */
    private static function injectsManager(ContainerBuilder $container, array $arguments): bool
    {
        foreach ($arguments as $argument) {
            if (is_array($argument) && self::injectsManager($container, $argument)) {
                return true;
            }

            if (!$argument instanceof Reference || !$container->has((string) $argument)) {
                continue;
            }

            $target = $container->getReflectionClass($container->findDefinition((string) $argument)->getClass(), throw: false);

            if ($target instanceof ReflectionClass && (is_a($target->getName(), ObjectManager::class, true) || is_a($target->getName(), Connection::class, true))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private static function factoryOrConstructor(ContainerBuilder $container, Definition $definition, ReflectionClass $class): string
    {
        $factory = $definition->getFactory();

        if (null === $factory) {
            return self::declarer($class, '__construct');
        }

        if (is_string($factory)) {
            return $factory . '()';
        }

        [$owner, $method] = $factory;

        if (!is_string($method)) {
            throw new LogicException(sprintf('The service "%s" has a factory that names no method.', $class->getName()));
        }

        $ownerClass = match (true) {
            null === $owner => $class,
            $owner instanceof Reference => $container->getReflectionClass($container->findDefinition((string) $owner)->getClass(), throw: false),
            $owner instanceof Definition => $container->getReflectionClass($owner->getClass(), throw: false),
            is_string($owner) => $container->getReflectionClass($owner, throw: false),
            default => throw new LogicException(sprintf('The service "%s" has a factory on neither a class nor a service.', $class->getName())),
        };

        return $ownerClass instanceof ReflectionClass ? self::declarer($ownerClass, $method) : $method . '()';
    }

    /**
     * The class or trait whose source declares the method, or the class itself when it has no such method (one
     * `__call()` answers).
     *
     * @param ReflectionClass<object> $class
     */
    private static function declarer(ReflectionClass $class, string $method): string
    {
        if (!$class->hasMethod($method)) {
            return $class->getName() . '::' . $method . '()';
        }

        $reflection = new ReflectionMethod($class->getName(), $method);
        $declaringClass = $reflection->getDeclaringClass();

        return ($reflection->getFileName() === $declaringClass->getFileName() ? $declaringClass : self::traitDeclaring($declaringClass, $reflection))->getName() . '::' . $reflection->getName() . '()';
    }

    /**
     * @param ReflectionClass<object> $class
     *
     * @return ReflectionClass<object>
     */
    private static function traitDeclaring(ReflectionClass $class, ReflectionMethod $method): ReflectionClass
    {
        foreach ($class->getTraits() as $trait) {
            if ($trait->hasMethod($method->getName())) {
                return $trait->getFileName() === $method->getFileName() ? $trait : self::traitDeclaring($trait, $method);
            }
        }

        return $class;
    }
}
