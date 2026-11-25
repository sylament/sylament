<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\DependencyInjection;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\DependencyInjection\Argument\ArgumentInterface;
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
     * inheriting a shipped setter is reported under the shipped class. An inline definition is walked as a
     * definition of its own, so what it is handed is reported under its class.
     *
     * @return list<string>
     */
    public static function injectionsIn(ContainerBuilder $container): array
    {
        $injections = [];
        $definitions = array_values($container->getDefinitions());

        for ($index = 0; $index < count($definitions); $index++) {
            $definition = $definitions[$index];
            $class = self::classOf($container, $definition);

            if (!$class instanceof ReflectionClass && null === $definition->getFactory()) {
                continue;
            }

            array_push($definitions, ...self::inlineDefinitionsIn($definition->getArguments()));

            if (self::injectsManager($container, $definition->getArguments())) {
                $injections[] = self::factoryOrConstructor($container, $definition, $class);
            }

            foreach ($definition->getMethodCalls() as $call) {
                $method = is_array($call) ? ($call[0] ?? null) : null;
                $arguments = is_array($call) ? ($call[1] ?? null) : null;

                if (!is_string($method) || !is_array($arguments)) {
                    throw new LogicException(sprintf('The service "%s" holds a method call that names no method or arguments.', self::nameOf($definition)));
                }

                array_push($definitions, ...self::inlineDefinitionsIn($arguments));

                if (!self::injectsManager($container, $arguments)) {
                    continue;
                }

                if (!$class instanceof ReflectionClass) {
                    throw new LogicException(sprintf('The service "%s" is handed a manager through %s(), but its class does not load, so the pass cannot tell who declares the method.', self::nameOf($definition), $method));
                }

                $injections[] = self::declarer($class, $method);
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
     * The definition's class, or for an inline definition that names only a factory (FrameworkBundle's error
     * renderer holds some), the class its factory method's native return type names. Null when that class does
     * not load, as for the placeholder Symfony leaves of a definition autowiring failed on, which the removing
     * passes drop, or of a service whose optional package is missing, and when the factory declares no class.
     *
     * @return ReflectionClass<object>|null
     *
     * @throws LogicException when the definition names neither a class nor a factory
     */
    private static function classOf(ContainerBuilder $container, Definition $definition): ?ReflectionClass
    {
        $class = $definition->getClass();

        if (null !== $class) {
            return $container->getReflectionClass($class, throw: false);
        }

        $factory = $definition->getFactory()
            ?? throw new LogicException('A service definition names neither a class nor a factory, so the pass cannot tell what it is.');
        $method = is_array($factory) ? ($factory[1] ?? null) : null;
        $owner = is_array($factory) ? self::factoryOwner($container, $definition, $factory, null) : null;

        if (!$owner instanceof ReflectionClass || !is_string($method) || !$owner->hasMethod($method)) {
            return null;
        }

        $type = $owner->getMethod($method)->getReturnType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return in_array(mb_strtolower($type->getName()), ['self', 'static'], true) ? $owner : $container->getReflectionClass($type->getName(), throw: false);
    }

    private static function nameOf(Definition $definition): string
    {
        return $definition->getClass() ?? 'an inline service';
    }

    /**
     * A reference, an inline definition, or one inside an array, a service closure, an iterator or a locator,
     * whose class is an `ObjectManager` or a DBAL `Connection`; a reference to a locator the container built
     * counts by what the locator holds.
     *
     * @param array<mixed> $arguments
     */
    private static function injectsManager(ContainerBuilder $container, array $arguments): bool
    {
        foreach ($arguments as $argument) {
            if (is_array($argument) && self::injectsManager($container, $argument)) {
                return true;
            }

            if ($argument instanceof ArgumentInterface && self::injectsManager($container, $argument->getValues())) {
                return true;
            }

            if ($argument instanceof Definition && self::isManager(self::classOf($container, $argument))) {
                return true;
            }

            if (!$argument instanceof Reference || !$container->has((string) $argument)) {
                continue;
            }

            $target = $container->findDefinition((string) $argument);

            if ($target->hasTag('container.service_locator') && self::injectsManager($container, $target->getArguments())) {
                return true;
            }

            $factory = $target->getFactory();

            if ($target->hasTag('container.service_locator_context') && is_array($factory) && self::injectsManager($container, [$factory[0] ?? null])) {
                return true;
            }

            if (self::isManager(self::classOf($container, $target))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ReflectionClass<object>|null $class
     */
    private static function isManager(?ReflectionClass $class): bool
    {
        return $class instanceof ReflectionClass
            && (is_a($class->getName(), ObjectManager::class, true) || is_a($class->getName(), Connection::class, true));
    }

    /**
     * The inline definitions among the arguments, in arrays, service closures, iterators and locators included.
     *
     * @param array<mixed> $arguments
     *
     * @return list<Definition>
     */
    private static function inlineDefinitionsIn(array $arguments): array
    {
        $definitions = [];

        foreach ($arguments as $argument) {
            if ($argument instanceof Definition) {
                $definitions[] = $argument;
            } elseif (is_array($argument)) {
                array_push($definitions, ...self::inlineDefinitionsIn($argument));
            } elseif ($argument instanceof ArgumentInterface) {
                array_push($definitions, ...self::inlineDefinitionsIn($argument->getValues()));
            }
        }

        return $definitions;
    }

    /**
     * @param ReflectionClass<object>|null $class
     *
     * @throws LogicException when the class or the factory's owner does not load, or the factory names no method
     */
    private static function factoryOrConstructor(ContainerBuilder $container, Definition $definition, ?ReflectionClass $class): string
    {
        $factory = $definition->getFactory();

        if (null === $factory) {
            return self::declarer($class ?? throw new LogicException(sprintf('The service "%s" is handed a manager, but its class does not load.', self::nameOf($definition))), '__construct');
        }

        if (is_string($factory)) {
            return $factory . '()';
        }

        $method = $factory[1] ?? null;

        if (!is_string($method)) {
            throw new LogicException(sprintf('The service "%s" has a factory that names no method.', self::nameOf($definition)));
        }

        $owner = self::factoryOwner($container, $definition, $factory, $class)
            ?? throw new LogicException(sprintf('The service "%s" has a factory whose class does not load, so the pass cannot tell who declares %s().', self::nameOf($definition), $method));

        return self::declarer($owner, $method);
    }

    /**
     * @param array<mixed> $factory
     * @param ReflectionClass<object>|null $class the definition's own class, which a factory with no owner is on
     *
     * @return ReflectionClass<object>|null null when the owner's class does not load
     *
     * @throws LogicException when the factory is on neither a class nor a service
     */
    private static function factoryOwner(ContainerBuilder $container, Definition $definition, array $factory, ?ReflectionClass $class): ?ReflectionClass
    {
        $owner = $factory[0] ?? null;

        return match (true) {
            null === $owner => $class,
            $owner instanceof Reference => $container->has((string) $owner) ? self::classOf($container, $container->findDefinition((string) $owner)) : null,
            $owner instanceof Definition => self::classOf($container, $owner),
            is_string($owner) => $container->getReflectionClass($owner, throw: false),
            default => throw new LogicException(sprintf('The service "%s" has a factory on neither a class nor a service.', self::nameOf($definition))),
        };
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
