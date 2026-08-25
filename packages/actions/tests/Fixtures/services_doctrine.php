<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylament\Actions\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Actions\Tests\Fixtures\Doctrine\FixtureManagerRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(EntityManagerInterface::class)
        ->factory(EntityManagerFactory::create(...))
        ->public();

    $services->set(FixtureManagerRegistry::class)
        ->args([service(EntityManagerInterface::class)]);

    $services->alias(ManagerRegistry::class, FixtureManagerRegistry::class);
};
