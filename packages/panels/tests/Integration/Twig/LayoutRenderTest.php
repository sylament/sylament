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
        ->toContain('href="#sy-pa-main"')
        ->toContain('id="sy-pa-main"')
        ->toContain('id="sy-pa-sidebar"')
        ->toContain('id="sy-pa-topbar"')
        ->toContain('Demo Admin')
        ->toContain('<p>Body</p>')
        ->toContain('class="sy-pa-sidebar-overlay" data-action="click-&gt;sylament--panels--sidebar#close"');
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

it('drops the topbar end wrapper with nothing to put in it', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Topbar', ['brandName' => 'X']);

    expect($html)->not->toContain('sy-pa-topbar-end');
});
