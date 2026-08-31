<?php

declare(strict_types=1);

namespace Sylament\Panels\DependencyInjection\Compiler;

use Sylament\Panels\GlobalSearch\GlobalSearchProviderRegistry;
use Sylament\Panels\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class GlobalSearchProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $providers = [];

        foreach (array_keys($container->findTaggedServiceIds('sylament.panels.global_search_provider')) as $id) {
            $definition = $container->findDefinition($id);

            if ($definition->isAbstract()) {
                continue;
            }

            $class = $definition->getClass() ?? $id;

            if (! is_subclass_of($class, GlobalSearchProvider::class)) {
                continue;
            }

            $providers[$class] = new Reference($id);
        }

        if ($container->hasDefinition(GlobalSearchProviderRegistry::class)) {
            $container->findDefinition(GlobalSearchProviderRegistry::class)
                ->setArgument('$providers', ServiceLocatorTagPass::register($container, $providers));
        }
    }
}
