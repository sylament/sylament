<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Doctrine\FixtureManagerRegistry;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    // The recorder rides along on the container-wide manager so an Integration
    // test can assert which statements a live round trip ran, and that a refused
    // key ran none.
    $container->services()
        ->set(RecordingMiddleware::class)
        ->public();

    $container->services()
        ->set(EntityManagerInterface::class)
        ->factory(EntityManagerFactory::create(...))
        ->args([service(RecordingMiddleware::class)])
        ->public();

    // A page host is no table, so a write action on one reaches its entity
    // manager through the registry rather than the table's query.
    $container->services()
        ->alias(ManagerRegistry::class, FixtureManagerRegistry::class);
};
