<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('twig', [
        'paths' => [
            dirname(__DIR__, 2) . '/templates' => 'Fixtures',
        ],
    ]);

    // TwigBundle registers `importmap()` only behind a `willBeAvailable()` check that refuses
    // asset-mapper as a dev-only package beside a production twig-bundle.
    $container->import('@TwigBundle/Resources/config/importmap.php');
};
