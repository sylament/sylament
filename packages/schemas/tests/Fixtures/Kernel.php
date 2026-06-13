<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures;

use Sylament\Schemas\SylamentSchemasBundle;
use Sylament\Support\SylamentSupportBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),

            new SylamentSchemasBundle(),
            new SylamentSupportBundle(),
        ];
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'SECRET',
            'test' => true,
        ]);

        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Sylament\\Schemas\\Tests\\Fixtures\\', __DIR__ . '/*')
                ->exclude([__DIR__ . '/Kernel.php']);
    }
}
