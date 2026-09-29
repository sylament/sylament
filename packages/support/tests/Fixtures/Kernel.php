<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures;

use League\FlysystemBundle\FlysystemBundle;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Sylament\Support\SylamentSupportBundle;
use Sylament\Support\Tests\Fixtures\Colors\SubstituteBadgePolicy;
use Sylament\Support\Twig\SupportIconAlias;
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
            new FlysystemBundle(),

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

        $container->extension('flysystem', [
            'storages' => [
                'fixture.storage' => [
                    'local' => [
                        'directory' => $this->getCacheDir() . '/storage/fixture',
                    ],
                    'visibility' => 'public',
                    'directory_visibility' => 'public',
                ],
                'second.storage' => [
                    'local' => [
                        'directory' => $this->getCacheDir() . '/storage/second',
                    ],
                ],
            ],
        ]);

        if ($this->environment === 'icons') {
            $container->extension('sylament', [
                'icons' => [
                    SupportIconAlias::MODAL_CLOSE_BUTTON => 'fixture:configured-close',
                    SupportIconAlias::SECTION_COLLAPSE_BUTTON => 'fixture:configured-collapse',
                ],
            ]);
        }

        if ($this->environment === 'filesystem') {
            $container->extension('sylament', [
                'default_filesystem_disk' => 'fixture.storage',
                'temporary_file_url_expiry_minutes' => 5,
            ]);
        }

        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->load('Sylament\\Support\\Tests\\Fixtures\\', __DIR__ . '/*')
                ->exclude([
                    __DIR__ . '/Kernel.php',
                    __DIR__ . '/Doctrine',
                ]);

        // Nothing inside `support` injects the filesystem registry — forms' `FileUpload` is its
        // first consumer — so an unreferenced private definition is removed before a test can
        // reach it through the test container.
        $container->services()
            ->alias('sylament_support.tests.filesystem_registry', FilesystemRegistry::class)
            ->public();

        if ($this->environment === 'color_policy') {
            $container->services()
                ->set(BadgeComponent::class, SubstituteBadgePolicy::class)
                ->public();
        }
    }
}
