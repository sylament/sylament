<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sylament\Actions\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Actions\Tests\Fixtures\Doctrine\FixtureManagerRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(EntityManagerInterface::class)
        ->factory(EntityManagerFactory::create(...))
        ->public();

    $services->alias(ManagerRegistry::class, FixtureManagerRegistry::class);
};
