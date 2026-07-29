<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ComponentResolverPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('sylament_support.component_resolver')) {
            return;
        }

        $components = [];

        foreach (array_keys($container->findTaggedServiceIds('sylament.component', true)) as $id) {
            $definition = $container->findDefinition($id);
            $definition->setShared(false);

            $class = $definition->getClass();

            if ($class === null) {
                continue;
            }

            $components[$class] = new Reference($id);
        }

        $container->getDefinition('sylament_support.component_resolver')
            ->replaceArgument('$components', ServiceLocatorTagPass::register($container, $components));
    }
}
