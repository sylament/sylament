<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Twig\PanelsIconAlias;
use Sylament\Support\Icons\IconManager;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

uses(InteractsWithTwigComponents::class);

/**
 * @return array<NavigationGroup>
 */
function sidebarFixtureNavigation(): array
{
    return [
        NavigationGroup::make('Content')->items([
            NavigationItem::make('Articles')->url('/admin/articles')->icon('heroicons:document-text')->badge('12')->isActiveWhen(fn (): bool => true),
            NavigationItem::make('Hidden')->hidden(),
        ]),
        NavigationGroup::make('System')->collapsed()->items([
            NavigationItem::make('Settings')->url('/admin/settings'),
        ]),
    ];
}

/**
 * The `d` attribute of a fixture icon. Both mini chevrons carry the same `0 0 20 20` viewBox,
 * so the path data is the only thing separating up from down in rendered markup.
 */
function sidebarChevronPathData(string $icon): string
{
    $svg = (string) file_get_contents(dirname(__DIR__, 2) . '/Fixtures/assets/icons/heroicons/' . $icon . '.svg');

    expect(preg_match('/ d="([^"]+)"/', $svg, $matches))->toBe(1);

    return $matches[1];
}

it('renders groups, items, active state, and badge', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Sidebar', [
        'navigation' => sidebarFixtureNavigation(),
    ]);

    expect($html)
        ->toContain('id="sy-pa-sidebar"')
        ->toContain('data-sylament--panels--sidebar-target="sidebar"')
        ->toContain('<nav class="sy-pa-sidebar-nav" aria-label="Navigation">')
        ->toContain('<ul class="sy-pa-sidebar-nav-groups">')
        ->toContain('sy-pa-sidebar-item')
        ->toContain('sy-active')
        ->toContain('href="/admin/articles"')
        ->toContain('aria-current="page"')
        ->toContain('>12<')
        ->not->toContain('Hidden');
});

it('renders the mobile brand header above the navigation', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Sidebar', [
        'navigation' => sidebarFixtureNavigation(),
        'brandName' => 'Demo Panel',
    ]);

    expect($html)
        ->toMatch('/<div class="sy-pa-sidebar-header-ctn">\s*<header class="sy-pa-sidebar-header">\s*<div class="sy-pa-sidebar-header-logo-ctn">/')
        ->toContain('<span class="sy sy-pa-logo">Demo Panel</span>')
        ->toMatch('/sy-pa-sidebar-header-ctn.+sy-pa-sidebar-nav/s')
        ->not->toContain('brandname=')
        ->not->toContain('brandName=');
});

it('renders collapse state and aria wiring on groups', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Sidebar', [
        'navigation' => sidebarFixtureNavigation(),
    ]);

    expect($html)
        ->toContain('sy-collapsed')
        ->toContain('data-group-label="System"')
        ->toContain('aria-controls="sy-pa-sidebar-group-items-1"')
        ->toContain('data-action="sylament--panels--sidebar#toggleGroup"')
        ->toMatch('/<button\s+class="sy-pa-sidebar-group-btn" type="button" aria-expanded="false"/');
});

it('draws the group chevron through an overridable icon alias', function (): void {
    $icons = $this::getContainer()->get(IconManager::class);

    expect($icons)->toBeInstanceOf(IconManager::class);
    $icons->register([PanelsIconAlias::SIDEBAR_GROUP_COLLAPSE_BUTTON => 'images/chevron.svg']);

    $html = (string) $this->renderTwigComponent('SylamentPanels:Sidebar', [
        'navigation' => sidebarFixtureNavigation(),
    ]);

    expect($html)->toContain('<img src="images/chevron.svg" alt="" class="sy sy-icon sy-size-md sy-pa-sidebar-group-chevron" />');
});

it('draws the group chevron pointing up, the way Filament does', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Sidebar', [
        'navigation' => sidebarFixtureNavigation(),
    ]);

    expect($html)
        ->toContain(sidebarChevronPathData('chevron-up-20-solid'))
        ->not->toContain(sidebarChevronPathData('chevron-down-20-solid'));
});

it('marks only the active item with aria-current', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Sidebar', [
        'navigation' => sidebarFixtureNavigation(),
    ]);

    expect(mb_substr_count($html, 'aria-current="page"'))->toBe(1);
});

it('renders a non-collapsible group label as a plain element with no disclosure wiring', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Sidebar', [
        'navigation' => [
            NavigationGroup::make('Content')->collapsible(false)->items([
                NavigationItem::make('Articles')->url('/admin/articles'),
            ]),
        ],
    ]);

    expect($html)
        ->toMatch('/<div\s+class="sy-pa-sidebar-group-btn"\s*>/')
        ->not->toContain('aria-expanded')
        ->not->toContain('aria-controls')
        ->not->toContain('toggleGroup')
        ->not->toContain('disabled');
});
