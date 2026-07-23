<?php

declare(strict_types=1);

namespace Sylament\Schemas;

use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\Twig\Extension\SchemaExtension;
use Sylament\Support\SylamentBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class SylamentSchemasBundle extends SylamentBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->configureServices($configurator);
    }

    private function configureServices(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services
            ->set('sylament_schemas.schema_extension', SchemaExtension::class)
            ->args([
                '$componentExtension' => service('sylament_support.component_extension')
            ])
            ->tag('twig.extension');

        $services
            ->set('sylament_schemas.property_accessor', PropertyAccessor::class)
            ->args([
                '$accessor' => service(PropertyAccessorInterface::class),
            ])
            ->alias(PropertyAccessor::class, 'sylament_schemas.property_accessor');
    }
}
