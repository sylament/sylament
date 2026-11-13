<?php

declare(strict_types=1);

use Sylament\Tests\Infrastructure\WorkerLogger;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Log\Logger;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
        ->load('Sylament\\Tests\\Fixtures\\', '../')
            ->exclude([
                '../Kernel.php',
                '../config',
                '../templates',
                '../icons',
                '../assets',
                '../importmap.php',
                '../*/Entity',
                '../*/Selectors.php',
            ]);

    $container->services()
        ->set('logger', Logger::class)
        ->factory(WorkerLogger::create(...))
        ->args(['%kernel.logs_dir%']);
};
