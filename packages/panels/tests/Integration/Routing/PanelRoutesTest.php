<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Dashboard;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\RecordProbePage;
use Symfony\Component\Routing\RouterInterface;

it('exposes the panel routes through the framework router', function (): void {
    $router = $this::getContainer()->get(RouterInterface::class);
    $collection = $router->getRouteCollection();

    $dashboard = $collection->get('sylament.admin.pages.dashboard');

    expect($dashboard?->getPath())->toBe('/admin')
        ->and($dashboard->getDefault('_sylament_page'))->toBe(Dashboard::class)
        ->and($collection->get('sylament.admin.pages.settings-page')?->getPath())->toBe('/admin/settings-page');
});

it('exposes the resource routes with their record parameter through the framework router', function (): void {
    $collection = $this::getContainer()->get(RouterInterface::class)->getRouteCollection();

    $probe = $collection->get('sylament.probe.resources.articles.probe');

    expect($probe?->getPath())->toBe('/probe/articles/{record}/probe')
        ->and($probe->compile()->getVariables())->toBe(['record'])
        ->and($probe->getDefault('_sylament_panel'))->toBe('probe')
        ->and($probe->getDefault('_sylament_page'))->toBe(RecordProbePage::class)
        ->and($collection->get('sylament.probe.resources.articles.index')?->getPath())->toBe('/probe/articles');
});
