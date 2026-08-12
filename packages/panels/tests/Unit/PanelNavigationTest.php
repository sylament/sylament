<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Panel;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;

it('derives grouped, sorted navigation from pages and extra items', function (): void {
    $panel = Panel::make()
        ->id('admin')
        ->navigationGroups(['Content'])
        ->navigationItems([
            NavigationItem::make('External')->url('#')->sort(5),
        ])
        ->pages([SettingsPage::class]);

    $navigation = $panel->getNavigation();

    expect($navigation)->toHaveCount(2)
        ->and($navigation[0]->getLabel())->toBeNull()
        ->and($navigation[0]->getItems()[0]->getLabel())->toBe('External')
        ->and($navigation[1]->getLabel())->toBe('Content')
        ->and($navigation[1]->getItems()[0]->getLabel())->toBe('Settings');
});

it('keeps registered group objects intact by cloning per call', function (): void {
    $registered = NavigationGroup::make('Content')->collapsed();
    $panel = Panel::make()->id('admin')->navigationGroups([$registered])->pages([SettingsPage::class]);

    $first = $panel->getNavigation();
    $second = $panel->getNavigation();

    expect($registered->getItems())->toBe([])
        ->and($first[0])->not->toBe($second[0])
        ->and($first[0]->getItems())->toHaveCount(1);
});

it('stamps the panel collapsible default onto every group', function (): void {
    $panel = Panel::make()
        ->id('admin')
        ->collapsibleNavigationGroups(false)
        ->pages([SettingsPage::class]);

    expect($panel->getNavigation()[0]->isCollapsible())->toBeFalse();
});

it('drops hidden items and groups that end up empty', function (): void {
    $panel = Panel::make()->id('admin')->navigationItems([
        NavigationItem::make('Ghost')->url('#')->hidden(),
    ]);

    expect($panel->getNavigation())->toBe([]);
});
