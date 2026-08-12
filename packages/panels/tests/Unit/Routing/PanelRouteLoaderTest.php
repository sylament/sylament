<?php

declare(strict_types=1);

use Sylament\Panels\Controllers\PageController;
use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\PanelRegistry;
use Sylament\Panels\Routing\PanelRouteLoader;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;

function loaderFor(PanelProvider $provider): PanelRouteLoader
{
    return new PanelRouteLoader(new PanelRegistry([$provider]));
}

it('mounts one GET route per page under the panel path', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin')->pages([Dashboard::class, SettingsPage::class]);
        }
    };

    $routes = loaderFor($provider)();

    $dashboard = $routes->get('sylament.admin.pages.dashboard');
    $settings = $routes->get('sylament.admin.pages.settings-page');

    expect($dashboard?->getPath())->toBe('/admin')
        ->and($dashboard->getMethods())->toBe(['GET'])
        ->and($dashboard->getDefault('_controller'))->toBe(PageController::class)
        ->and($dashboard->getDefault('_sylament_panel'))->toBe('admin')
        ->and($dashboard->getDefault('_sylament_page'))->toBe(Dashboard::class)
        ->and($settings?->getPath())->toBe('/admin/settings-page');
});

it('tracks provider and page class files as resources', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin')->pages([Dashboard::class]);
        }
    };

    $resources = array_map(strval(...), loaderFor($provider)()->getResources());

    expect(implode(' ', $resources))->toContain('Dashboard.php');
});

it('rejects a pages() entry that is not a Page subclass', function (): void {
    $provider = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin')->pages([stdClass::class]);
        }
    };

    loaderFor($provider)();
})->throws(LogicException::class, 'stdClass');
