<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Page;
use Sylament\Panels\Panel;
use Sylament\Panels\Tests\Fixtures\Pages\SettingsPage;

it('exposes title, heading fallback, and navigation statics', function (): void {
    $page = new class extends Page {
        protected static ?string $title = 'Dashboard';

        protected static ?string $navigationLabel = 'Home';

        protected static ?int $navigationSort = 2;
    };

    expect($page->getTitle())->toBe('Dashboard')
        ->and($page->getHeading())->toBe('Dashboard')
        ->and($page->getSubheading())->toBeNull()
        ->and($page::getNavigationLabel())->toBe('Home')
        ->and($page::getNavigationSort())->toBe(2)
        ->and($page::getNavigationIcon())->toBeNull()
        ->and($page::getNavigationGroup())->toBeNull();
});

it('defaults the optional navigation statics to null', function (): void {
    $page = new class extends Page {};

    expect($page->getSubheading())->toBeNull()
        ->and($page::getNavigationIcon())->toBeNull()
        ->and($page::getNavigationGroup())->toBeNull()
        ->and($page::getNavigationSort())->toBeNull();
});

it('derives slug, route path, route name and component name from the class', function (): void {
    $page = new class extends Page {};

    expect($page::getSlug())->not->toBe('')
        ->and($page::getRoutePath())->toBe('/' . $page::getSlug());

    expect(SettingsPage::getSlug())->toBe('settings-page')
        ->and(SettingsPage::getRoutePath())->toBe('/settings-page')
        ->and(SettingsPage::getRouteName(Panel::make()->id('admin')))->toBe('sylament.admin.pages.settings-page')
        ->and(SettingsPage::getComponentName())->toBe('Sylament:Panels:Tests:Fixtures:Pages:SettingsPage');
});

it('falls back to the humanized class basename for title and navigation label', function (): void {
    expect(SettingsPage::getNavigationLabel())->toBe('Settings');

    $page = new SettingsPage();

    expect($page->getTitle())->toBe('Settings Page')
        ->and($page->getHeading())->toBe('Settings Page');
});

it('builds one navigation item wired to the page route', function (): void {
    $panel = Panel::make()->id('admin');
    $items = SettingsPage::getNavigationItems($panel);

    expect($items)->toHaveCount(1)
        ->and($items[0])->toBeInstanceOf(NavigationItem::class)
        ->and($items[0]->getLabel())->toBe('Settings')
        ->and($items[0]->getGroup())->toBe('Content');
});

it('defaults the view to the shipped page chrome template', function (): void {
    $page = new SettingsPage();

    expect($page->getView())->toBe('@SylamentPanels/partials/Page/index.html.twig');
});
