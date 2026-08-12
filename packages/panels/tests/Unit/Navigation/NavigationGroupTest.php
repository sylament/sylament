<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;

it('makes with an optional label and holds items', function (): void {
    $items = [NavigationItem::make('Articles')];
    $group = NavigationGroup::make('Content')->items($items);

    expect($group->getLabel())->toBe('Content')
        ->and($group->getItems())->toBe($items)
        ->and(NavigationGroup::make()->getLabel())->toBeNull()
        ->and(NavigationGroup::make()->getItems())->toBe([]);
});

it('is collapsible by default and only collapsed when collapsible', function (): void {
    expect(NavigationGroup::make('A')->isCollapsible())->toBeTrue()
        ->and(NavigationGroup::make('A')->isCollapsed())->toBeFalse()
        ->and(NavigationGroup::make('A')->collapsed()->isCollapsed())->toBeTrue()
        ->and(NavigationGroup::make('A')->collapsed()->collapsible(false)->isCollapsed())->toBeFalse();
});

it('evaluates closures passed to every getter', function (): void {
    $items = [NavigationItem::make('Articles')];
    $group = NavigationGroup::make(fn (): string => 'Content')
        ->items(fn (): array => $items)
        ->collapsed(fn (): bool => true)
        ->collapsible(fn (): bool => true);

    expect($group->getLabel())->toBe('Content')
        ->and($group->getItems())->toBe($items)
        ->and($group->isCollapsed())->toBeTrue()
        ->and($group->isCollapsible())->toBeTrue()
        ->and($group->collapsible(fn (): bool => false)->isCollapsed())->toBeFalse();
});

it('is active when any visible item is active', function (): void {
    $group = NavigationGroup::make('A')->items([
        NavigationItem::make('One'),
        NavigationItem::make('Two')->isActiveWhen(fn (): bool => true),
    ]);

    expect($group->isActive())->toBeTrue()
        ->and(NavigationGroup::make('B')->items([NavigationItem::make('One')])->isActive())->toBeFalse();
});

it('consults the collapsible fallback only when nothing explicit is set', function (): void {
    expect(NavigationGroup::make('A')->collapsibleFallback(false)->isCollapsible())->toBeFalse()
        ->and(NavigationGroup::make('A')->collapsibleFallback(false)->collapsible()->isCollapsible())->toBeTrue()
        ->and(NavigationGroup::make('A')->collapsibleFallback(true)->collapsible(false)->isCollapsible())->toBeFalse()
        ->and(NavigationGroup::make('A')->isCollapsible())->toBeTrue();
});
