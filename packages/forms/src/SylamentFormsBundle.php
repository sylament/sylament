<?php

declare(strict_types=1);

namespace Sylament\Forms;

use Sylament\Support\SylamentBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class SylamentFormsBundle extends SylamentBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->services()
            ->load(__NAMESPACE__ . '\\Components\\', __DIR__ . '/Components/*.php')
            ->autowire()
            ->autoconfigure();
    }
}
