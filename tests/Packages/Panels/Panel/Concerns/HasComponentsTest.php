<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Routing\PanelRouteLoader;
use Sylament\Tests\Fixtures\Panels\Pages\AttributedSignInPage;
use Sylament\Tests\Fixtures\Panels\Pages\SluggedSettingsPage;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\OrderLineResource;

it('accumulates pages and resources across calls, mounting a repeated entry once and no registered resource it does not list', function (): void {
    $routes = new PanelRouteLoader(new PanelRegistry([new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('panel-components')->path('panel-components')
                ->pages([SluggedSettingsPage::class, Dashboard::class])
                ->pages([Dashboard::class, AttributedSignInPage::class])
                ->resources([ArticleResource::class, ArticleResource::class])
                ->resources([OrderLineResource::class]);
        }
    }]), $this::getContainer()->get(ResourceRegistry::class))();

    expect($routes->get('sylament.panel-components.pages.settings')?->getPath())->toBe('/panel-components/settings')
        ->and($routes->get('sylament.panel-components.pages.dashboard')?->getPath())->toBe('/panel-components')
        ->and($routes->get('sylament.panel-components.pages.sign-in')?->getPath())->toBe('/panel-components/sign-in')
        ->and($routes->get('sylament.panel-components.resources.articles.index')?->getPath())->toBe('/panel-components/articles')
        ->and($routes->get('sylament.panel-components.resources.order-lines.index')?->getPath())->toBe('/panel-components/order-lines')
        ->and($routes->get('sylament.panel-components.resources.scoped-articles.index'))->toBeNull();
});
