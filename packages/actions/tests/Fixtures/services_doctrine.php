<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylament\Actions\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Actions\Tests\Fixtures\Doctrine\FixtureManagerRegistry;
use Sylament\Actions\Tests\Fixtures\Doctrine\RecordingMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // The recorder rides along on the container-wide manager so a test can read
    // the statements a live round trip ran — the one lookup an assigned key
    // costs, and none for a key the identity map already holds.
    $services->set(RecordingMiddleware::class)->public();

    $services->set(EntityManagerInterface::class)
        ->factory(EntityManagerFactory::create(...))
        ->args([service(RecordingMiddleware::class)])
        ->public();

    $services->alias(ManagerRegistry::class, FixtureManagerRegistry::class);
};
