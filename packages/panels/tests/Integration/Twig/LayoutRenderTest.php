<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Twig\PanelsIconAlias;
use Sylament\Support\Icons\IconManager;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

uses(InteractsWithTwigComponents::class);

it('renders the full shell with stable landmarks', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Layout', [
        'navigation' => [NavigationGroup::make('Content')->items([NavigationItem::make('Articles')->url('/a')])],
        'brandName' => 'Demo Admin',
    ], content: '<p>Body</p>');

    expect($html)
        ->toContain('data-controller="sylament--panels--sidebar"')
        ->toContain('data-action="keydown@window-&gt;sylament--panels--sidebar#closeOnEscape"')
        ->toContain('class="sy sy-pa-body"')
        ->toContain('href="#sy-pa-main"')
        ->toContain('<div class="sy sy-pa-topbar-ctn">')
        ->toContain('<nav id="sy-pa-topbar" class="sy-pa-topbar" aria-label="Topbar">')
        ->toContain('<main id="sy-pa-main" class="sy-pa-main">')
        ->toContain('id="sy-pa-sidebar"')
        ->toContain('Demo Admin')
        ->toContain('<p>Body</p>')
        ->toContain('class="sy-pa-sidebar-overlay" data-action="click-&gt;sylament--panels--sidebar#close"')
        ->toMatch('/sy-pa-topbar-ctn.+<div class="sy-pa-layout">.+sy-pa-sidebar-overlay.+id="sy-pa-sidebar".+<div class="sy-pa-main-ctn">.+<main id="sy-pa-main"/s');
});

it('renders the brand through the logo inside the topbar start slot', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Layout', [
        'navigation' => [],
        'brandName' => 'Demo Admin',
    ], content: '<p>Body</p>');

    expect($html)->toMatch('/<div class="sy-pa-topbar-start">\s*<span class="sy sy-pa-logo">Demo Admin<\/span>\s*<\/div>/');
});

it('drops the logo without a brand name', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Topbar');

    expect($html)
        ->toContain('sy-pa-topbar-start')
        ->not->toContain('sy-pa-logo');
});

it('wires the hamburger to the sidebar controller', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Topbar', ['brandName' => 'X']);

    expect($html)
        ->toContain('sylament--panels--sidebar#toggle')
        ->toContain('aria-controls="sy-pa-sidebar"')
        ->toContain('aria-expanded="false"')
        ->toContain('aria-label="Toggle sidebar"')
        ->toContain('data-sylament--panels--sidebar-target="toggleButton"');
});

it('draws the hamburger through an overridable icon alias', function (): void {
    $icons = $this::getContainer()->get(IconManager::class);

    expect($icons)->toBeInstanceOf(IconManager::class);
    $icons->register([PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON => 'images/menu.svg']);

    $html = (string) $this->renderTwigComponent('SylamentPanels:Topbar', ['brandName' => 'X']);

    expect($html)->toContain('<img src="images/menu.svg" alt="" class="sy sy-icon sy-size-md" />');
});

it('renders header heading and subheading', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Header', [
        'heading' => 'Articles',
        'subheading' => 'All records',
    ]);

    expect($html)->toContain('Articles')->toContain('All records')->toContain('sy-pa-header');
});

it('drops the header wrappers with nothing to put in them', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Header', ['heading' => 'Articles']);

    expect($html)
        ->toContain('sy-pa-header-headings')
        ->not->toContain('sy-pa-header-actions');
});

it('renders the actions block inside the header actions wrapper', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Header', [
        'heading' => 'Articles',
    ], blocks: ['actions' => '<button type="button" id="header-action">Create</button>']);

    expect($html)->toMatch('/<div class="sy-pa-header-actions">\s*<button type="button" id="header-action">Create<\/button>\s*<\/div>/');
});

it('carries the user menu in the topbar end with no end block given', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Topbar', ['brandName' => 'X']);

    expect($html)
        ->toContain('sy-pa-topbar-end')
        ->toContain('sy-pa-user-menu')
        ->toContain('sy-pa-theme-switcher');
});

it('keeps the end block above the user menu', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Topbar', [
        'brandName' => 'X',
    ], blocks: ['end' => '<span id="topbar-end-slot">Slot</span>']);

    expect($html)->toMatch('/<div class="sy-pa-topbar-end">\s*<span id="topbar-end-slot">Slot<\/span>\s*<div\s+class="sy sy-dropdown sy-pa-user-menu"/');
});

it('hangs the theme switcher inside the user menu dropdown panel', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:UserMenu');

    expect($html)
        ->toContain('class="sy sy-dropdown sy-pa-user-menu"')
        ->toContain('aria-label="Open user menu"')
        ->toContain('sy-pa-user-menu-trigger')
        ->toMatch('/class="sy-dropdown-panel".*?<div class="sy-dropdown-list">\s*<div\s+class="sy sy-pa-theme-switcher"/s');
});

it('wires each theme switcher button to the theme controller', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:ThemeSwitcher');

    expect($html)
        ->toContain('data-controller="sylament--panels--theme"')
        ->toContain('role="group"')
        ->toContain('aria-label="Theme"');

    foreach (['light', 'dark', 'system'] as $theme) {
        expect($html)
            ->toContain('data-theme="' . $theme . '"')
            ->toContain('aria-label="Enable ' . $theme . ' theme"');
    }

    expect(mb_substr_count($html, 'data-sylament--panels--theme-target="button"'))->toBe(3)
        ->and(mb_substr_count($html, 'sylament--panels--theme#select'))->toBe(3)
        ->and(mb_substr_count($html, 'aria-pressed="false"'))->toBe(3);
});

it('closes the enclosing dropdown when a theme is picked', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:ThemeSwitcher');

    expect(mb_substr_count($html, 'data-action="sylament--panels--theme#select sylament--support--dropdown#close"'))->toBe(3);
});

it('draws each theme switcher icon through an overridable alias', function (): void {
    $icons = $this::getContainer()->get(IconManager::class);

    expect($icons)->toBeInstanceOf(IconManager::class);
    $icons->register([
        PanelsIconAlias::THEME_SWITCHER_LIGHT_BUTTON => 'images/light.svg',
        PanelsIconAlias::THEME_SWITCHER_DARK_BUTTON => 'images/dark.svg',
        PanelsIconAlias::THEME_SWITCHER_SYSTEM_BUTTON => 'images/system.svg',
        PanelsIconAlias::USER_MENU_TRIGGER => 'images/user.svg',
    ]);

    $html = (string) $this->renderTwigComponent('SylamentPanels:UserMenu');

    expect($html)
        ->toContain('src="images/light.svg"')
        ->toContain('src="images/dark.svg"')
        ->toContain('src="images/system.svg"')
        ->toContain('src="images/user.svg"');
});
