<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'secret' => 'SECRET',
        'default_locale' => 'en',
        'test' => true,
        'session' => [
            'storage_factory_id' => 'session.storage.factory.mock_file',
        ],
        'asset_mapper' => [
            'paths' => ['assets/'],
        ],
        'translator' => [
            'enabled' => true,
            'fallbacks' => ['en'],
        ],
    ]);
};
