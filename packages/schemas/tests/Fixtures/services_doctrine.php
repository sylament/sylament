<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(EntityManagerInterface::class)
        ->factory([EntityManagerFactory::class, 'create'])
        ->public();
};
