<?php

declare(strict_types=1);

namespace Sylament\Panels\DependencyInjection\Compiler;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ResourcePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $resources = [];

        foreach (array_keys($container->findTaggedServiceIds('sylament.panels.resource')) as $id) {
            $definition = $container->findDefinition($id);

            if ($definition->isAbstract()) {
                continue;
            }

            $class = $definition->getClass() ?? $id;

            if (! is_subclass_of($class, Resource::class)) {
                continue;
            }

            $resources[$class] = new Reference($id);
        }

        if ($container->hasDefinition(ResourceRegistry::class)) {
            $container->findDefinition(ResourceRegistry::class)
                ->setArgument('$resources', ServiceLocatorTagPass::register($container, $resources));
        }
    }
}
