<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Doctrine\FixtureManagerRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(EntityManagerInterface::class)
        ->factory(EntityManagerFactory::create(...))
        ->public();

    // A page host is no table, so a write action on one reaches its entity
    // manager through the registry rather than the table's query.
    $container->services()
        ->alias(ManagerRegistry::class, FixtureManagerRegistry::class);
};
