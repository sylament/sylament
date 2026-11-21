<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Pages\Dashboard;
use Sylament\Tests\Fixtures\Panels\Selectors;

it('serves the dashboard at the panel root', function (): void {
    expect($this->visit('/panel-routing'))->toContainElement(Selectors::PAGE . '[data-live-name-value="Sylament:Panels:Pages:Dashboard"]');
});

it('sorts the dashboard ahead of a page registered before it with no sort', function (): void {
    $labels = $this->visit('/panel-routing')->filter(Selectors::NAVIGATION_ITEM . ' ' . Selectors::NAVIGATION_ITEM_LABEL)->each(fn ($label): string => $label->text());

    expect($labels)->toBe(['Dashboard', 'Slugged Settings Page', 'Changelog']);
});

it('reads its title in the document title, the heading and its navigation label', function (string $uri, string $title): void {
    $page = $this->visit($uri);

    expect($page->filter(Selectors::DOCUMENT_TITLE)->text())->toStartWith($title)
        ->and($page->filter(Selectors::PAGE_HEADING)->text())->toBe($title)
        ->and($page->filter(Selectors::ACTIVE_NAVIGATION_ITEM . ' ' . Selectors::NAVIGATION_ITEM_LABEL)->text())->toBe($title);
})->with([
    'the shipped title' => ['/panel-routing', 'Dashboard'],
    "a subclass's own title" => ['/panel-branded', 'Overview'],
]);

it("draws the home icon in navigation unless the application aliases the dashboard's", function (): void {
    expect($this->visit('/panel-routing'))->toContainElement(Selectors::ACTIVE_NAVIGATION_ITEM . ' svg', 'home');

    $this::bootKernel(['environment' => 'registered_icons']);

    expect($this->visit('/panel-routing'))->toContainElement(Selectors::ACTIVE_NAVIGATION_ITEM . ' svg', 'Star');
});

it('titles the dashboard from the catalogue, in the locale the translator answers', function (): void {
    $this::getContainer()->get('translator')->setLocale('sk');

    expect($this::getContainer()->get(Dashboard::class)->getTitle())->toBe('Nástenka')
        ->and(NavigationItem::make(Dashboard::getNavigationLabel())->getLabel())->toBe('Nástenka');
});
