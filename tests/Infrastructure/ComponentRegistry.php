<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure;

use LogicException;
use ReflectionClass;
use Sylament\Tests\Fixtures\Kernel;
use Sylament\Tests\Infrastructure\DependencyInjection\ComponentListPass;

/**
 * The registered components, as datasets keyed by short class name, for a case that
 * must run over every component offering something. Pest resolves a dataset while it
 * collects the suite, before any test exists, so the list is read once per process
 * from a kernel of its own that compiles its container and never boots.
 */
final class ComponentRegistry
{
    /**
     * @var list<class-string>|null
     */
    private static ?array $components = null;

    /**
     * @param interface-string $contract
     * @return array<string, class-string>
     */
    public static function componentsImplementing(string $contract): array
    {
        if (!interface_exists($contract)) {
            throw new LogicException(sprintf('"%s" is not an interface.', $contract));
        }

        return self::keyedByShortName(array_filter(
            self::components(),
            static fn (string $class): bool => is_subclass_of($class, $contract),
        ));
    }

    /**
     * Through the component's parents and through the traits its traits use.
     *
     * @param trait-string $trait
     * @return array<string, class-string>
     */
    public static function componentsUsing(string $trait): array
    {
        if (!trait_exists($trait)) {
            throw new LogicException(sprintf('"%s" is not a trait.', $trait));
        }

        return self::keyedByShortName(array_filter(
            self::components(),
            static fn (string $class): bool => self::uses(new ReflectionClass($class), $trait),
        ));
    }

    /**
     * @return list<class-string>
     */
    private static function components(): array
    {
        if (self::$components !== null) {
            return self::$components;
        }

        $environment = $_SERVER['APP_ENV'] ?? 'test';

        if (!is_string($environment)) {
            throw new LogicException('APP_ENV is not a string.');
        }

        $parameter = new Kernel($environment, true)->getCompiledContainer()->getParameter(ComponentListPass::PARAMETER);

        if (!is_array($parameter)) {
            throw new LogicException(sprintf('The "%s" parameter is not a list.', ComponentListPass::PARAMETER));
        }

        $components = [];

        foreach ($parameter as $class) {
            if (!is_string($class) || !class_exists($class)) {
                throw new LogicException(sprintf('The "%s" parameter holds "%s", which is not a class.', ComponentListPass::PARAMETER, is_string($class) ? $class : get_debug_type($class)));
            }

            $components[] = $class;
        }

        return self::$components = $components;
    }

    /**
     * @param ReflectionClass<object> $type
     */
    private static function uses(ReflectionClass $type, string $trait): bool
    {
        foreach ($type->getTraits() as $used) {
            if ($used->getName() === $trait || self::uses($used, $trait)) {
                return true;
            }
        }

        $parent = $type->getParentClass();

        return $parent !== false && self::uses($parent, $trait);
    }

    /**
     * @param array<class-string> $classes
     * @return array<string, class-string>
     */
    private static function keyedByShortName(array $classes): array
    {
        $keyed = [];

        foreach ($classes as $class) {
            $name = new ReflectionClass($class)->getShortName();

            if (isset($keyed[$name])) {
                throw new LogicException(sprintf('"%s" and "%s" share the short name "%s", so a dataset keyed by it would drop one.', $keyed[$name], $class, $name));
            }

            $keyed[$name] = $class;
        }

        return $keyed;
    }
}
