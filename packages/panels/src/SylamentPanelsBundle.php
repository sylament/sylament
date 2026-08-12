<?php

declare(strict_types=1);

namespace Sylament\Panels;

use Sylament\Panels\DependencyInjection\Compiler\PageComponentPass;
use Sylament\Panels\Http\PageController;
use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Pages\Page;
use Sylament\Support\SylamentBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

class SylamentPanelsBundle extends SylamentBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(PanelProvider::class)
            ->addTag('sylament.panels.panel_provider');

        $container->registerForAutoconfiguration(Page::class)
            ->addTag('sylament.panels.page');

        $container->addCompilerPass(new PageComponentPass(), priority: 10);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->services()
            ->load(__NAMESPACE__ . '\\Navigation\\', __DIR__ . '/Navigation/*.php')
                ->autowire()
                ->autoconfigure()
            ->set(Panel::class)
                ->autowire()
                ->autoconfigure()
            ->set(PanelRegistry::class)
                ->args([tagged_iterator('sylament.panels.panel_provider')])
            ->set(CurrentPanel::class)
                ->autowire()
            ->set(Dashboard::class)
                ->autowire()
                ->autoconfigure()
            ->set(PageController::class)
                ->autowire()
                ->tag('controller.service_arguments');
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->prependAssetMapper($container);
    }

    private function prependAssetMapper(ContainerBuilder $container): void
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets/dist' => '@sylament/panels',
                ],
            ],
        ]);
    }
}
