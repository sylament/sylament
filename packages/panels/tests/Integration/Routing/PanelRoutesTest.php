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

/**
 * The paths mirror the ones `ResourceNestedUrlTest` hand-builds to pin what a
 * nested URL generates against: what the loader mounts and what a resource
 * generates are the same shapes, and this is where the two are held together.
 */
it('mounts the nested resource routes at the paths a nested url is generated against', function (): void {
    $collection = $this::getContainer()->get(RouterInterface::class)->getRouteCollection();

    $paths = [
        'sylament.nest.resources.chapters.index' => '/nest/chapters',
        'sylament.nest.resources.chapters.edit' => '/nest/chapters/{record}/edit',
        'sylament.nest.resources.chapters.verses.create' => '/nest/chapters/{chapter}/verses/create',
        'sylament.nest.resources.chapters.verses.edit' => '/nest/chapters/{chapter}/verses/{record}/edit',
        'sylament.nest.resources.chapters.verses.lines.index' => '/nest/chapters/{chapter}/verses/{verse}/lines',
        'sylament.nest.resources.chapters.verses.lines.edit' => '/nest/chapters/{chapter}/verses/{verse}/lines/{record}/edit',
    ];

    foreach ($paths as $name => $path) {
        expect($collection->get($name)?->getPath())->toBe($path);
    }

    $verse = $collection->get('sylament.nest.resources.chapters.verses.edit');
    $line = $collection->get('sylament.nest.resources.chapters.verses.lines.edit');
    $chapter = $collection->get('sylament.nest.resources.chapters.edit');

    expect($verse?->compile()->getVariables())->toBe(['chapter', 'record'])
        ->and($verse->getDefault('_sylament_parent_params'))->toBe(['chapter'])
        ->and($verse->getDefault('_sylament_panel'))->toBe('nest')
        ->and($line?->compile()->getVariables())->toBe(['chapter', 'verse', 'record'])
        ->and($line->getDefault('_sylament_parent_params'))->toBe(['chapter', 'verse'])
        ->and($chapter?->compile()->getVariables())->toBe(['record'])
        ->and($chapter->getDefault('_sylament_parent_params'))->toBeNull();
});

/**
 * The parent parameters ride as a route default, and a default the path spends
 * nowhere is one the generator could trail as a query string.
 */
it('keeps the parent parameters off a generated nested url', function (): void {
    $router = $this::getContainer()->get(RouterInterface::class);

    expect($router->generate('sylament.nest.resources.chapters.verses.lines.edit', ['chapter' => '3', 'verse' => '5', 'record' => '7']))
        ->toBe('/nest/chapters/3/verses/5/lines/7/edit');
});
