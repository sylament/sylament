<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('ux_icons', [
        'iconify' => [
            'enabled' => false,
        ],
    ]);
};
