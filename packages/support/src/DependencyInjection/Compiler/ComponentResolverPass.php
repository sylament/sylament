<?php

declare(strict_types=1);

namespace Sylament\Support\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects every `sylament.component`-tagged service into a class-keyed
 * ServiceLocator for {@see \Sylament\Support\DependencyInjection\ComponentResolver}.
 *
 * Definitions are marked non-shared so each locator `get()` yields a fresh,
 * fully autowired instance — components carry per-instance configuration and
 * must never be shared. This mirrors Symfony UX's `TwigComponentPass`; the
 * class-keyed locator mirrors Symfony Form's `FormPass`.
 *
 * Registered with a plain `addCompilerPass()` so it lands at
 * TYPE_BEFORE_OPTIMIZATION priority 0 — after the priority-100 group that
 * contains `ResolveInstanceofConditionalsPass`, which is what applies the
 * autoconfiguration tag this pass reads.
 */
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
