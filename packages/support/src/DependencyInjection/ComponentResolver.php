<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection;

use Psr\Container\ContainerInterface;
use Sylament\Support\Components\Component;

/**
 * Builds component instances for the `make()` factories. When the container
 * knows the class — i.e. the app registered it, so autoconfiguration tagged it
 * — it comes from a locator of non-shared definitions, fully autowired and
 * fresh per call. Otherwise it is constructed directly, which is the path for
 * Sylament's own components (they live in vendor and are never registered) and
 * for every kernel-free Unit test.
 */
final class ComponentResolver implements StaticInstanceInterface
{
    use HasStaticInstance;

    public function __construct(
        private readonly ?ContainerInterface $components = null,
    ) {
    }

    public static function resolve(): static
    {
        if (!self::$instance instanceof self) {
            self::setInstance(new self());
        }

        return self::getInstance();
    }

    /**
     * @template T of Component
     * @param class-string<T> $class
     * @return T
     */
    public function instantiate(string $class): Component
    {
        if ($this->components?->has($class) !== true) {
            return new $class();
        }

        $component = $this->components->get($class);

        assert($component instanceof $class);

        return $component;
    }
}
