<?php

declare(strict_types=1);

namespace Sylament\Notifications;

use Sylament\Support\SylamentBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class SylamentNotificationsBundle extends SylamentBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->configureComponents($configurator);
        $this->configureColorPolicies($configurator);
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->prependAssetMapper($container);
    }

    private function configureComponents(ContainerConfigurator $configurator): void
    {
        $configurator->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->set(Notification::class);
    }

    private function configureColorPolicies(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        // `ColorManager` resolves a policy from the service container by class-string, and a
        // private service is not reachable through `Container::get()`, so both globs stay public.
        $services
            ->load(__NAMESPACE__ . '\\Colors\\Components\\', __DIR__ . '/Colors/Components/*.php')
            ->autowire()
            ->public();

        $services
            ->load(__NAMESPACE__ . '\\Colors\\Components\\', __DIR__ . '/Colors/Components/*/*.php')
            ->autowire()
            ->public();
    }

    private function prependAssetMapper(ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets/dist' => '@sylament/notifications',
                ],
            ],
        ]);
    }
}
