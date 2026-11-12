<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'secret' => 'SECRET',
        'test' => true,
        'session' => [
            'storage_factory_id' => 'session.storage.factory.mock_file',
        ],
        'asset_mapper' => [
            'paths' => [
                dirname(__DIR__, 2) . '/assets' => '',
            ],
            'importmap_path' => dirname(__DIR__, 2) . '/importmap.php',
        ],
    ]);
};
