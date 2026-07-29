<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Sylament\Actions\Twig\Extension\ActionExtension;
use Sylament\Support\SylamentBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class SylamentActionsBundle extends SylamentBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->configureComponents($configurator);
        $this->configureTwigComponents($configurator);
        $this->configureTwigExtensions($configurator);
    }

    private function configureComponents(ContainerConfigurator $configurator): void
    {
        $configurator->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->set(Action::class)
            ->set(ActionGroup::class);
    }

    private function configureTwigComponents(ContainerConfigurator $configurator): void
    {
        $configurator->services()
            ->load(__NAMESPACE__ . '\\Twig\\Components\\', __DIR__ . '/Twig/Components/')
            ->autowire()
            ->autoconfigure();
    }

    private function configureTwigExtensions(ContainerConfigurator $configurator): void
    {
        $configurator->services()
            ->set('sylament_actions.action_extension', ActionExtension::class)
            ->autowire()
            ->tag('twig.extension');
    }
}
