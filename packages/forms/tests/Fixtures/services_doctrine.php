<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Forms\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // The recorder rides along on the container-wide manager so a test can read
    // the SQL of a live round trip — and prove that a key which does not decode
    // ran no statement at all.
    $services->set(RecordingMiddleware::class)->public();

    $services->set(EntityManagerInterface::class)
        ->factory(EntityManagerFactory::create(...))
        ->args([service(RecordingMiddleware::class)])
        ->public();
};
