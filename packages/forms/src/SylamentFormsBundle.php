<?php

declare(strict_types=1);

namespace Sylament\Forms;

use Sylament\Forms\Uploads\TemporaryUploads;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Sylament\Support\SylamentBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class SylamentFormsBundle extends SylamentBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('temporary_file_upload')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('disk')
                            ->info('The storage temporary uploads are held on; null is sylament.default_filesystem_disk.')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('directory')
                            ->info('The directory on that storage.')
                            ->defaultValue('sylament-tmp')
                        ->end()
                        ->booleanNode('cleanup')
                            ->info('Sweep temporary uploads older than 24 hours on every successful upload.')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array{temporary_file_upload: array{disk: ?string, directory: string, cleanup: bool}} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $services = $configurator->services();

        $services
            ->load(__NAMESPACE__ . '\\Components\\', __DIR__ . '/Components/{*,Repeater/*}.php')
            ->autowire()
            ->autoconfigure();

        $services
            ->set('sylament_forms.temporary_uploads', TemporaryUploads::class)
            ->args([
                '$filesystems' => service(FilesystemRegistry::class),
                '$secret' => param('kernel.secret'),
                '$diskName' => $config['temporary_file_upload']['disk'],
                '$directory' => $config['temporary_file_upload']['directory'],
                '$cleanup' => $config['temporary_file_upload']['cleanup'],
            ])
            ->alias(TemporaryUploads::class, 'sylament_forms.temporary_uploads');
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->prependAssetMapper($container);
    }

    private function prependAssetMapper(ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets/dist' => '@sylament/forms',
                ],
            ],
        ]);
    }
}
