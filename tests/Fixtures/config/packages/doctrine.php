<?php

declare(strict_types=1);

use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Sylament\Tests\Infrastructure\Doctrine\RecordingMiddleware;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabase;
use Sylament\Tests\Infrastructure\Doctrine\TestDatabaseMiddleware;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service_closure;

return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine', [
        'dbal' => [
            'url' => '%env(' . TestDatabase::URL_VARIABLE . ')%',
            'logging' => false,
            'profiling' => false,
        ],
        'orm' => [
            'entity_managers' => [
                'default' => [
                    'mappings' => [
                        'Fixtures' => [
                            'type' => 'attribute',
                            'dir' => '%kernel.project_dir%/src/Entity',
                            'prefix' => 'Sylament\\Tests\\Fixtures\\Entity',
                        ],
                    ],
                    'metadata_cache_driver' => [
                        'type' => 'service',
                        'id' => 'sylament_tests.metadata_cache',
                    ],
                    'second_level_cache' => [
                        'enabled' => true,
                    ],
                ],
            ],
        ],
    ]);

    $services = $container->services();

    // Not shared: Container::reset() at every kernel shutdown resets each shared service
    // it holds, which would empty the process-wide cache.
    $services->set('sylament_tests.metadata_cache', ArrayAdapter::class)
        ->factory(TestDatabase::metadataCache(...))
        ->share(false);

    // DBAL wraps the driver in the order of the list, which DoctrineBundle sorts by
    // descending priority: the highest priority is the innermost layer.
    $services->set(RecordingMiddleware::class)
        ->abstract()
        ->tag('doctrine.middleware', ['priority' => -100]);

    $services->set(TestDatabaseMiddleware::class)
        ->args([service_closure('doctrine')])
        ->abstract()
        ->tag('doctrine.middleware', ['priority' => 50]);

    if (TestPlatform::current() === TestPlatform::Sqlite) {
        $services->set(EnableForeignKeys::class)
            ->abstract()
            ->tag('doctrine.middleware', ['priority' => 100]);
    }
};
