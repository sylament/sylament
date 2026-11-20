<?php

declare(strict_types=1);

namespace Sylament\Notifications;

use Sylament\Notifications\Delivery\LiveDeliverySubscriber;
use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Notifications\Testing\NotificationRecorder;
use Sylament\Support\DependencyInjection\Compiler\RemoveTestOnlyServicesPass;
use Sylament\Support\SylamentBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class SylamentNotificationsBundle extends SylamentBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->configureComponents($configurator);
        $this->configureColorPolicies($configurator);
        $this->configureDelivery($configurator);
        $this->configureTwigComponents($configurator);
        $this->configureTesting($configurator);
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

    private function configureDelivery(ContainerConfigurator $configurator): void
    {
        $configurator->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->set(SessionNotifications::class)
            ->set(LiveDeliverySubscriber::class);
    }

    private function configureTwigComponents(ContainerConfigurator $configurator): void
    {
        $configurator->services()
            ->load(__NAMESPACE__ . '\\Twig\\Components\\', __DIR__ . '/Twig/Components/')
            ->autowire()
            ->autoconfigure();
    }

    private function configureTesting(ContainerConfigurator $configurator): void
    {
        $configurator->services()
            ->set(NotificationRecorder::class)
            ->decorate(SessionNotifications::class)
            ->args([service('.inner')])
            ->public()
            ->tag('kernel.reset', ['method' => 'reset'])
            ->tag(RemoveTestOnlyServicesPass::TAG);
    }
}
