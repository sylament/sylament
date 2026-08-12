<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

uses(InteractsWithTwigComponents::class);

it('renders the full shell with stable landmarks', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Layout', [
        'navigation' => [NavigationGroup::make('Content')->items([NavigationItem::make('Articles')->url('/a')])],
        'brandName' => 'Demo Admin',
    ], content: '<p>Body</p>');

    expect($html)
        ->toContain('data-controller="sylament--panels--sidebar"')
        ->toContain('href="#sy-pa-main"')
        ->toContain('id="sy-pa-main"')
        ->toContain('id="sy-pa-sidebar"')
        ->toContain('id="sy-pa-topbar"')
        ->toContain('Demo Admin')
        ->toContain('<p>Body</p>')
        ->toContain('data-sylament--panels--sidebar-target="overlay"');
});

it('wires the hamburger to the sidebar controller', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Topbar', ['brandName' => 'X']);

    expect($html)
        ->toContain('sylament--panels--sidebar#toggle')
        ->toContain('aria-controls="sy-pa-sidebar"');
});

it('renders header heading and subheading', function (): void {
    $html = (string) $this->renderTwigComponent('SylamentPanels:Header', [
        'heading' => 'Articles',
        'subheading' => 'All records',
    ]);

    expect($html)->toContain('Articles')->toContain('All records')->toContain('sy-pa-header');
});
