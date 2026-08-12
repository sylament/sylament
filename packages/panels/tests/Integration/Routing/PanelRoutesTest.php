<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Dashboard;
use Symfony\Component\Routing\RouterInterface;

it('exposes the panel routes through the framework router', function (): void {
    $router = $this::getContainer()->get(RouterInterface::class);
    $collection = $router->getRouteCollection();

    $dashboard = $collection->get('sylament.admin.pages.dashboard');

    expect($dashboard?->getPath())->toBe('/admin')
        ->and($dashboard->getDefault('_sylament_page'))->toBe(Dashboard::class)
        ->and($collection->get('sylament.admin.pages.settings-page')?->getPath())->toBe('/admin/settings-page');
});
