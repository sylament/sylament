<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('sylament', [
        'colors' => [
            'brand' => '#7c3aed',
            'brand-alt' => Color::Rose,
        ],
    ]);
};
