<?php

declare(strict_types=1);

namespace Sylament\Support;

use Sylament\Support\DependencyInjection\Compiler\StaticInstancePass;
use Sylament\Support\DependencyInjection\StaticInstanceInterface;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class SylamentSupportBundle extends SylamentBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new StaticInstancePass());
    }

    public function boot(): void
    {
        parent::boot();

        $this->bootStaticInstances();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->configureServices($configurator);
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->prependAssetMapper($container);
    }

    private function bootStaticInstances(): void
    {
        $container = $this->container;
        if (!$container instanceof ContainerInterface) {
            return;
        }

        $registry = $container->get(StaticInstanceRegistry::class);
        if (!$registry instanceof StaticInstanceRegistry) {
            return;
        }

        $registry->bootStaticInstances();
    }

    private function configureServices(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services
            ->set('sylament_support.static_instance_registry', StaticInstanceRegistry::class)
            ->args([
                '$services' => tagged_iterator(StaticInstanceInterface::class),
            ])
            ->alias(StaticInstanceRegistry::class, 'sylament_support.static_instance_registry')
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
                    __DIR__ . '/../assets/dist' => '@sylament/support',
                ],
            ],
        ]);
    }
}
