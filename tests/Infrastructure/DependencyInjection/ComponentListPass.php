<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\DependencyInjection;

use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Exports the class of every service tagged `sylament.component` as a parameter,
 * fixtures left out, because a compiled container keeps no tags.
 */
final class ComponentListPass implements CompilerPassInterface
{
    public const string PARAMETER = 'sylament_tests.components';

    public function process(ContainerBuilder $container): void
    {
        $components = [];

        foreach (array_keys($container->findTaggedServiceIds('sylament.component')) as $id) {
            $class = $container->getDefinition($id)->getClass()
                ?? throw new LogicException(sprintf('The component service "%s" names no class.', $id));

            if (str_starts_with($class, 'Sylament\\Tests\\')) {
                continue;
            }

            $components[] = $class;
        }

        $components = array_values(array_unique($components));
        sort($components);

        $container->setParameter(self::PARAMETER, $components);
    }
}
