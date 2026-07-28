<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Sylament\Actions\Twig\Components\Modals;
use Sylament\Actions\Twig\Extension\ActionExtension;
use Sylament\Support\SylamentBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class SylamentActionsBundle extends SylamentBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->configureTwigComponents($configurator);
        $this->configureTwigExtensions($configurator);
    }

    /**
     * Registers the shipped Twig components. `autoconfigure()` lets `#[AsTwigComponent]`
     * supply the `twig.component` tag, so the attribute stays the single source of truth
     * for the component's name and template. The component stack has no class alias, so it
     * is referenced by service id.
     */
    private function configureTwigComponents(ContainerConfigurator $configurator): void
    {
        $configurator->services()
            ->set('sylament_actions.twig_component.modals', Modals::class)
            ->args([
                '$componentStack' => service('ux.twig_component.component_stack'),
            ])
            ->autoconfigure();
    }

    private function configureTwigExtensions(ContainerConfigurator $configurator): void
    {
        $configurator->services()
            ->set('sylament_actions.action_extension', ActionExtension::class)
            ->args([
                '$componentExtension' => service('sylament_support.component_extension'),
            ])
            ->tag('twig.extension');
    }
}
