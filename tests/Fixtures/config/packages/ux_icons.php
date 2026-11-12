<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('ux_icons', [
        'icon_dir' => dirname(__DIR__, 2) . '/icons',
        'iconify' => [
            'enabled' => false,
        ],
    ]);
};
