<?php

declare(strict_types=1);

namespace Sylament\Panels\DependencyInjection\Compiler;

use LogicException;
use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\Pages\Page;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PageComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $pages = [];
        $names = [];

        foreach (array_keys($container->findTaggedServiceIds('sylament.panels.page')) as $id) {
            $definition = $container->findDefinition($id);

            if ($definition->isAbstract()) {
                continue;
            }

            $class = $definition->getClass() ?? $id;

            if (! is_subclass_of($class, Page::class)) {
                continue;
            }

            $name = $class::getComponentName();

            if (isset($names[$name])) {
                throw new LogicException(sprintf('Two panel pages derive the live component name "%s": %s and %s.', $name, $names[$name], $id));
            }

            $names[$name] = $id;

            $definition
                ->addTag('twig.component', [
                    'key' => $name,
                    'live' => true,
                    'route' => 'ux_live_component',
                    'method' => 'post',
                    'url_reference_type' => UrlGeneratorInterface::ABSOLUTE_PATH,
                    'expose_public_props' => true,
                    'attributes_var' => 'attributes',
                    'template_from_method' => 'getTemplate',
                ])
                ->addTag('controller.service_arguments');

            $pages[$class] = new Reference($id);
        }

        if ($container->hasDefinition(PageController::class)) {
            $container->findDefinition(PageController::class)
                ->setArgument('$pages', ServiceLocatorTagPass::register($container, $pages));
        }
    }
}
