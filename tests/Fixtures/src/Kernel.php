<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use League\FlysystemBundle\FlysystemBundle;
use Sylament\Actions\SylamentActionsBundle;
use Sylament\Forms\SylamentFormsBundle;
use Sylament\Infolists\SylamentInfolistsBundle;
use Sylament\Notifications\SylamentNotificationsBundle;
use Sylament\Panels\SylamentPanelsBundle;
use Sylament\Schemas\SylamentSchemasBundle;
use Sylament\Support\SylamentSupportBundle;
use Sylament\Tables\SylamentTablesBundle;
use Sylament\Tests\Infrastructure\DependencyInjection\ComponentListPass;
use Sylament\Tests\Infrastructure\Doctrine\TestPlatform;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Sylament\Widgets\SylamentWidgetsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        getKernelParameters as private getMicroKernelParameters;
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new UXIconsBundle(),
            new StimulusBundle(),
            new LiveComponentBundle(),
            new FlysystemBundle(),

            new SylamentActionsBundle(),
            new SylamentFormsBundle(),
            new SylamentInfolistsBundle(),
            new SylamentNotificationsBundle(),
            new SylamentPanelsBundle(),
            new SylamentSchemasBundle(),
            new SylamentSupportBundle(),
            new SylamentTablesBundle(),
            new SylamentWidgetsBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Keyed by platform too: the container registers SQLite's foreign-key middleware
     * only on SQLite, and nothing tells a compiled container the platform changed.
     */
    public function getCacheDir(): string
    {
        return WorkerDirectory::path() . '/cache/' . $this->environment . '/' . mb_strtolower(TestPlatform::current()->name);
    }

    public function getLogDir(): string
    {
        return WorkerDirectory::path() . '/log';
    }

    /**
     * The container it compiles (and on a cold cache warms) without booting it: no
     * bundle's `boot()` runs, so neither an error handler nor a static instance outlives
     * a read from it.
     */
    public function getCompiledContainer(): ContainerInterface
    {
        if (!$this->container instanceof ContainerInterface) {
            $this->initializeBundles();
            $this->initializeContainer();
        }

        return $this->getContainer();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getKernelParameters(): array
    {
        $parameters = $this->getMicroKernelParameters();

        // Without it FrameworkBundle skips dumping `reference.php` into the config directory,
        // a source directory every run and environment would rewrite.
        unset($parameters['.kernel.config_dir']);

        return $parameters;
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->setParameter('sylament_tests.worker_dir', WorkerDirectory::path());
        $container->addCompilerPass(new ComponentListPass());
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $live = $this->getConfigDir() . '/routes/live_component.php';

        $routes->import($live);
        $routes->import('sylament_panels.route_loader', 'service');
        $routes->import($this->getConfigDir() . '/routes/*.php', exclude: $live);
    }
}
