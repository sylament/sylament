<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders collapsed only while it can collapse, and keeps its toggle when it starts expanded', function (): void {
    $fixed = $this->renderTwigComponent('SylamentPanels:SidebarGroup', [
        'group' => NavigationGroup::make('Fixed')->collapsible(false)->collapsed()->items([NavigationItem::make('Outline')->url('/outline')]),
    ])->crawler()->filter(Selectors::NAVIGATION_GROUP);
    $open = $this->renderTwigComponent('SylamentPanels:SidebarGroup', [
        'group' => NavigationGroup::make('Open')->collapsed(false)->items([NavigationItem::make('Outline')->url('/outline')]),
    ])->crawler()->filter(Selectors::NAVIGATION_GROUP);

    expect([$fixed->attr('data-group-label'), $fixed->matches(SupportSelectors::COLLAPSED), $fixed->filter(Selectors::NAVIGATION_GROUP_TOGGLE)->count()])->toBe(['Fixed', false, 0])
        ->and([$open->matches(SupportSelectors::COLLAPSED), $open->filter(Selectors::NAVIGATION_GROUP_TOGGLE)->attr('aria-expanded')])->toBe([false, 'true']);
});

it('collapses by the default the panel stamps on it, unless it names its own', function (): void {
    $toggles = fn (NavigationGroup $group): int => $this->renderTwigComponent('SylamentPanels:SidebarGroup', [
        'group' => $group->items([NavigationItem::make('Outline')->url('/outline')]),
    ])->crawler()->filter(Selectors::NAVIGATION_GROUP_TOGGLE)->count();

    expect([
        $toggles(NavigationGroup::make('Unstamped')),
        $toggles(NavigationGroup::make('Stamped')->collapsibleFallback(false)),
        $toggles(NavigationGroup::make('Own')->collapsibleFallback(false)->collapsible()),
    ])->toBe([1, 0, 1]);
});

it('lights up only for an active item it shows', function (): void {
    $lit = fn (array $items): bool => $this->renderTwigComponent('SylamentPanels:SidebarGroup', [
        'group' => NavigationGroup::make('Drafts')->items($items),
    ])->crawler()->filter(Selectors::NAVIGATION_GROUP)->matches(SupportSelectors::ACTIVE);

    expect($lit([
        NavigationItem::make('Concealed')->hidden()->activeWhen(fn (): bool => true)->url('/concealed'),
        NavigationItem::make('Outline')->url('/outline'),
    ]))->toBeFalse()
        ->and($lit([NavigationItem::make('Outline')->activeWhen(fn (): bool => true)->url('/outline')]))->toBeTrue();
});
