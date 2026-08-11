<?php

declare(strict_types=1);

namespace Sylament\Schemas;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Schemas\State\RecordTypeReader;
use Sylament\Schemas\Twig\Extension\SchemaExtension;
use Sylament\Support\SylamentBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

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
            ->load(__NAMESPACE__ . '\\Components\\', __DIR__ . '/Components/*.php')
            ->autowire()
            ->autoconfigure();

        // `ColorManager` resolves a policy from the service container by class-string, and a
        // private service is not reachable through `Container::get()`, so the glob stays public.
        $services
            ->load(__NAMESPACE__ . '\\Colors\\Components\\', __DIR__ . '/Colors/Components/*.php')
            ->autowire()
            ->public();

        $services
            ->set(Schema::class)
            ->autowire()
            ->autoconfigure();

        $services
            ->set('sylament_schemas.schema_extension', SchemaExtension::class)
            ->autowire()
            ->tag('twig.extension');

        $services
            ->set('sylament_schemas.property_accessor', PropertyAccessor::class)
            ->autowire()
            ->alias(PropertyAccessor::class, 'sylament_schemas.property_accessor');

        // Not autowired: `PropertyTypeExtractorInterface` is only a service once the application
        // enables `framework.property_info`, and the chained extractor it aliases reads PHPDoc
        // ahead of the mutator. Leaving the argument unset pins the reflection-only extractor.
        $services
            ->set('sylament_schemas.record_type_reader', RecordTypeReader::class)
            ->alias(RecordTypeReader::class, 'sylament_schemas.record_type_reader');

        $services
            ->set('sylament_schemas.association_reader', AssociationReader::class)
            ->args([service(EntityManagerInterface::class)->nullOnInvalid()])
            ->alias(AssociationReader::class, 'sylament_schemas.association_reader');
    }
}
