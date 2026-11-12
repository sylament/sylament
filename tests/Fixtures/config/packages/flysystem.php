<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('flysystem', [
        'storages' => [
            'primary.storage' => [
                'local' => [
                    'directory' => '%sylament_tests.worker_dir%/storage/primary',
                ],
                'visibility' => 'public',
                'directory_visibility' => 'public',
                'public_url' => '/files',
            ],
            'secondary.storage' => [
                'local' => [
                    'directory' => '%sylament_tests.worker_dir%/storage/secondary',
                ],
            ],
        ],
    ]);
};
