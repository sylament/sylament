<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Panels\Tests\Fixtures\Pages\SubNavigationProbePage;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

beforeEach(function (): void {
    SubNavigationProbePage::probe(null);
});

/**
 * Two loose items — the first of them active — plus one labelled group, so every arm
 * of every variant has something to draw.
 *
 * @return array<NavigationItem|NavigationGroup>
 */
function probeSubNavigation(): array
{
    return [
        NavigationItem::make('Overview')->url('/admin/overview')->isActiveWhen(fn (): bool => true),
        NavigationItem::make('Details')->url('/admin/details'),
        NavigationGroup::make('More')->items([
            NavigationItem::make('Audit')->url('/admin/audit'),
        ]),
    ];
}

it('draws the tabs variant with anchors, one active tab and the group dropdown', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::Top, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();
    $tabs = $crawler->filter('.sy-pa-page-main > nav.sy-tabs.sy-pa-sub-navigation-tabs');

    expect($tabs->count())->toBe(1)
        ->and($tabs->filter('a.sy-tabs-item[aria-current="page"]')->count())->toBe(1)
        ->and($tabs->filter('a.sy-tabs-item[aria-current="page"]')->attr('href'))->toBe('/admin/overview')
        ->and($tabs->filter('a.sy-tabs-item[href="/admin/details"]')->count())->toBe(1)
        ->and($tabs->filter('.sy-dropdown .sy-dropdown-trigger .sy-tabs-item')->text())->toBe('More')
        ->and($tabs->filter('.sy-dropdown .sy-dropdown-panel a.sy-dropdown-item[href="/admin/audit"]')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-sub-navigation-sidebar-ctn')->count())->toBe(0);
});

it('draws the sidebar variant before the content at the start edge', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::Start, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();

    expect($crawler->filter('.sy-pa-page-main > .sy-pa-sub-navigation-sidebar-ctn + .sy-pa-page-content')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-sub-navigation-sidebar-ctn > ul.sy-pa-sub-navigation-sidebar > li.sy-pa-sidebar-group')->count())->toBe(2)
        ->and($crawler->filter('.sy-pa-sub-navigation-tabs')->count())->toBe(0);
});

it('draws the sidebar variant after the content at the end edge', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::End, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();

    expect($crawler->filter('.sy-pa-page-main > .sy-pa-page-content + .sy-pa-sub-navigation-sidebar-ctn')->count())->toBe(1)
        ->and($crawler->filter('.sy-pa-page-main > .sy-pa-sub-navigation-sidebar-ctn + .sy-pa-page-content')->count())->toBe(0);
});

it('keeps the sub-navigation sidebar groups expanded and off the sidebar controller', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::Start, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();
    $sidebar = $crawler->filter('.sy-pa-sub-navigation-sidebar-ctn');

    expect($sidebar->filter('[aria-expanded]')->count())->toBe(0)
        ->and($sidebar->filter('[data-action]')->count())->toBe(0)
        ->and($sidebar->filter('ul#sy-pa-sidebar-group-items-sub-0')->count())->toBe(1)
        ->and($sidebar->filter('ul#sy-pa-sidebar-group-items-sub-1')->count())->toBe(1)
        ->and($sidebar->filter('li.sy-pa-sidebar-item a[aria-current="page"]')->count())->toBe(1);
});

it('draws the mobile dropdown in every position, labelled by the active item', function (SubNavigationPosition $position): void {
    SubNavigationProbePage::probe($position, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();
    $dropdown = $crawler->filter('.sy-pa-page-main > .sy-dropdown.sy-pa-sub-navigation-dropdown');

    expect($dropdown->count())->toBe(1)
        ->and($dropdown->filter('.sy-dropdown-trigger .sy-btn')->text())->toBe('Overview')
        ->and($dropdown->filter('.sy-dropdown-panel a.sy-dropdown-item')->count())->toBe(3)
        ->and($dropdown->filter('.sy-dropdown-panel a.sy-dropdown-item[aria-current="page"]')->count())->toBe(1);
})->with([
    [SubNavigationPosition::Start],
    [SubNavigationPosition::End],
    [SubNavigationPosition::Top],
]);

it('marks the page root with the band and its position', function (SubNavigationPosition $position): void {
    SubNavigationProbePage::probe($position, probeSubNavigation());

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();

    expect($crawler->filter('.sy-pa-page.sy-pa-page-has-sub-navigation.sy-pa-page-has-sub-navigation-' . $position->value)->count())->toBe(1);
})->with([
    [SubNavigationPosition::Start],
    [SubNavigationPosition::End],
    [SubNavigationPosition::Top],
]);

it('draws no band at all for a page with no sub-navigation', function (): void {
    SubNavigationProbePage::probe(SubNavigationPosition::Top);

    $crawler = $this->createLiveComponent(SubNavigationProbePage::getComponentName())->render()->crawler();

    expect($crawler->filter('.sy-pa-page')->count())->toBe(1)
        ->and($crawler->filter('[class*="sy-pa-page-has-sub-navigation"]')->count())->toBe(0)
        ->and($crawler->filter('.sy-pa-sub-navigation-dropdown')->count())->toBe(0)
        ->and($crawler->filter('.sy-pa-sub-navigation-tabs')->count())->toBe(0)
        ->and($crawler->filter('.sy-pa-sub-navigation-sidebar-ctn')->count())->toBe(0);
});
