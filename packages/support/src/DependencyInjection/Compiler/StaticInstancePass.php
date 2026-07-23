<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection\Compiler;

use Throwable;
use Sylament\Support\DependencyInjection\StaticInstanceInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class StaticInstancePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();

            if ($class === null) {
                continue;
            }

            if (!$this->implementsStaticInstance($class)) {
                continue;
            }

            $definition->addTag(StaticInstanceInterface::class);
        }
    }

    /**
     * Whether the service class implements {@see StaticInstanceInterface},
     * tolerating classes that cannot be autoloaded. A bundle may register a
     * service whose class extends an optional dependency that is not installed
     * (e.g. LiveComponent's `form.live_collection` extending Symfony Form's
     * `AbstractType` when `symfony/form` is absent); autoloading it throws.
     * Such a class is never a StaticInstance, so skipping it is safe.
     */
    private function implementsStaticInstance(string $class): bool
    {
        try {
            return is_a($class, StaticInstanceInterface::class, true);
        } catch (Throwable) {
            return false;
        }
    }
}
