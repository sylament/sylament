<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;

it('makes with a label and evaluates closures', function (): void {
    expect(NavigationItem::make('Articles')->getLabel())->toBe('Articles')
        ->and(NavigationItem::make(fn (): string => 'Closed')->getLabel())->toBe('Closed');
});

it('exposes url with new-tab flag', function (): void {
    $item = NavigationItem::make('A')->url('/admin/articles', shouldOpenInNewTab: true);

    expect($item->getUrl())->toBe('/admin/articles')
        ->and($item->shouldOpenUrlInNewTab())->toBeTrue();

    expect(NavigationItem::make('B')->getUrl())->toBeNull()
        ->and(NavigationItem::make('B')->shouldOpenUrlInNewTab())->toBeFalse();
});

it('exposes badge, badge color, sort, and group', function (): void {
    $item = NavigationItem::make('A')
        ->badge(fn (): string => '12')
        ->badgeColor('danger')
        ->sort(3)
        ->group('Content');

    expect($item->getBadge())->toBe('12')
        ->and($item->getBadgeColor())->toBe('danger')
        ->and($item->getSort())->toBe(3)
        ->and($item->getGroup())->toBe('Content');

    expect(NavigationItem::make('B')->getSort())->toBe(-1);
});

it('composes hidden and visible with hidden winning', function (): void {
    expect(NavigationItem::make('A')->isVisible())->toBeTrue();
    expect(NavigationItem::make('A')->hidden()->isVisible())->toBeFalse();
    expect(NavigationItem::make('A')->visible(false)->isHidden())->toBeTrue();
    expect(NavigationItem::make('A')->hidden()->visible()->isHidden())->toBeTrue();
});

it('defaults inactive and memoises isActiveWhen', function (): void {
    expect(NavigationItem::make('A')->isActive())->toBeFalse();

    $runs = 0;
    $item = NavigationItem::make('A')->isActiveWhen(function () use (&$runs): bool {
        $runs++;

        return true;
    });

    expect($item->isActive())->toBeTrue()
        ->and($item->isActive())->toBeTrue()
        ->and($runs)->toBe(1);
});

it('swaps to the active icon only when set', function (): void {
    $item = NavigationItem::make('A')->icon('heroicons:document-text')->activeIcon('heroicons:document');

    expect($item->getIcon())->toBe('heroicons:document-text')
        ->and($item->getActiveIcon())->toBe('heroicons:document');

    expect(NavigationItem::make('B')->getActiveIcon())->toBeNull();
});
