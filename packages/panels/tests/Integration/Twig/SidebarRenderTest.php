<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
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

it('renders groups, items, active state, and badge', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Sidebar', [
        'navigation' => sidebarFixtureNavigation(),
    ]);

    expect($html)
        ->toContain('id="sy-pa-sidebar"')
        ->toContain('data-sylament--panels--sidebar-target="sidebar"')
        ->toContain('sy-pa-sidebar-item')
        ->toContain('sy-active')
        ->toContain('href="/admin/articles"')
        ->toContain('aria-current="page"')
        ->toContain('>12<')
        ->not->toContain('Hidden');
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

it('marks only the active item with aria-current', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Sidebar', [
        'navigation' => sidebarFixtureNavigation(),
    ]);

    expect(substr_count($html, 'aria-current="page"'))->toBe(1);
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
