<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures;

use Sylament\Support\SylamentSupportBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\UX\Icons\UXIconsBundle;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new UXIconsBundle(),

            new SylamentSupportBundle(),
        ];
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'SECRET',
            'test' => true,
        ]);

        $container->extension('ux_icons', [
            'icon_dir' => __DIR__ . '/assets/icons',
            'iconify' => [
                'enabled' => false,
            ],
        ]);

        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Sylament\\Support\\Tests\\Fixtures\\', __DIR__ . '/*')
                ->exclude([__DIR__ . '/Kernel.php']);
    }
}
