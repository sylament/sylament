<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection\Compiler;

use Sylament\Support\DependencyInjection\Contracts\StaticInstance;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Throwable;

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

            $definition->addTag(StaticInstance::class);
        }
    }

    private function implementsStaticInstance(string $class): bool
    {
        try {
            return is_a($class, StaticInstance::class, true);
        } catch (Throwable) {
            return false;
        }
    }
}
