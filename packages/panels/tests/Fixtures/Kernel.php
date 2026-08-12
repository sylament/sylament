<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures;

use Sylament\Panels\SylamentPanelsBundle;
use Sylament\Support\SylamentSupportBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new UXIconsBundle(),
            new StimulusBundle(),
            new LiveComponentBundle(),

            new SylamentPanelsBundle(),
            new SylamentSupportBundle(),
        ];
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        // `ux_live_component`'s path is `/{_live_component}/{_live_action}`, so mounted at the
        // root it matches `/admin` before any panel page — hence the prefix the recipe uses.
        $routes->import('@LiveComponentBundle/config/routes.php')->prefix('/_components');
        $routes->import('sylament_panels.route_loader', 'service');
    }

    private function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'SECRET',
            'test' => true,
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/assets' => '',
                ],
                'importmap_path' => __DIR__ . '/importmap.php',
            ],
        ]);

        $container->extension('ux_icons', [
            'icon_dir' => __DIR__ . '/assets/icons',
            'iconify' => [
                'enabled' => false,
            ],
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components',
            'defaults' => [],
        ]);

        // TwigBundle registers the `importmap()` function behind
        // `ContainerBuilder::willBeAvailable('symfony/asset-mapper', ...)`, which refuses a
        // package installed dev-only when its parent (`symfony/twig-bundle`) is a production
        // dependency — exactly this monorepo's shape, and no consuming application's. The rig
        // loads the definitions the check skips so it renders what a real app renders.
        $container->import('@TwigBundle/Resources/config/importmap.php');

        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Sylament\\Panels\\Tests\\Fixtures\\', __DIR__ . '/*')
                ->exclude([__DIR__ . '/Kernel.php', __DIR__ . '/assets', __DIR__ . '/importmap.php']);

        // The `Invoker` resolves a closure parameter from the real service container by
        // class-string, and FrameworkBundle's `RequestStack` alias is private, so the rig
        // republishes it the way an application injecting it into a closure has to.
        $container->services()
            ->alias(RequestStack::class, 'request_stack')
            ->public();
    }
}
