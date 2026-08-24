<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures;

use League\FlysystemBundle\FlysystemBundle;
use Sylament\Actions\SylamentActionsBundle;
use Sylament\Forms\SylamentFormsBundle;
use Sylament\Schemas\SylamentSchemasBundle;
use Sylament\Support\SylamentSupportBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
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
            new FlysystemBundle(),

            new SylamentActionsBundle(),
            new SylamentFormsBundle(),
            new SylamentSchemasBundle(),
            new SylamentSupportBundle(),
        ];
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@LiveComponentBundle/config/routes.php');
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

        $container->extension('twig', [
            'paths' => [
                __DIR__ . '/Twig/templates' => 'FormsFixtures',
            ],
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components',
            'defaults' => [],
        ]);

        // Two local storages under the cache directory: `test.storage` is the field's default —
        // public, and the only one that can mint a URL, since `public_url` is what gives a local
        // adapter one at all — and `other.storage` is the foreign disk the move/copy and the
        // no-URL arms need. `Pest.php` removes the directory after every Integration test.
        $container->extension('flysystem', [
            'storages' => [
                'test.storage' => [
                    'local' => [
                        'directory' => $this->getCacheDir() . '/storage/test',
                    ],
                    'visibility' => 'public',
                    'directory_visibility' => 'public',
                    'public_url' => '/files',
                ],
                'other.storage' => [
                    'local' => [
                        'directory' => $this->getCacheDir() . '/storage/other',
                    ],
                ],
            ],
        ]);

        $container->extension('sylament', [
            'default_filesystem_disk' => 'test.storage',
        ]);

        $container->import(__DIR__ . '/services_doctrine.php');

        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Sylament\\Forms\\Tests\\Fixtures\\', __DIR__ . '/*')
                ->exclude([
                    __DIR__ . '/Kernel.php',
                    __DIR__ . '/Doctrine/Entity',
                    __DIR__ . '/services_doctrine.php',
                ]);
    }
}
