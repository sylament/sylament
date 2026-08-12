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
        ->toContain('data-action="sylament--panels--sidebar#toggleGroup"');
});
