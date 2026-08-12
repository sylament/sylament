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
use Symfony\UX\Icons\UXIconsBundle;
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

            new SylamentPanelsBundle(),
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

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components',
            'defaults' => [],
        ]);

        // The `Invoker` resolves a closure parameter from the real service container by
        // class-string, and FrameworkBundle's `RequestStack` alias is private, so the rig
        // republishes it the way an application injecting it into a closure has to.
        $container->services()
            ->alias(RequestStack::class, 'request_stack')
            ->public();
    }
}
