<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('sylament', [
        'icons' => [
            'modal.close-button' => 'support:heart',
            'components.text-input.actions.show-password' => 'support:star',
        ],
    ]);
};
