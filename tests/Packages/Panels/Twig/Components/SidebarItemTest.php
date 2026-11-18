<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it("marks the item of the page shown as the current page in its active icon, every other item linking plainly under its own", function (): void {
    $shelf = fn (string $uri): Crawler => $this->visit($uri)->filter(Selectors::NAVIGATION . ' a[href="/shell/shelf"]');

    $current = $shelf('/shell/shelf');
    $other = $shelf('/shell/settings');

    expect([$current->filter(Selectors::NAVIGATION_ITEM_LABEL)->text(), $current->attr('aria-current'), $current->closest('li')?->matches(SupportSelectors::ACTIVE), $current->filter('svg')->text()])
        ->toBe(['Active Icon Shelf Page', 'page', true, 'Heart'])
        ->and([$other->filter(Selectors::NAVIGATION_ITEM_LABEL)->text(), $other->attr('aria-current'), $other->closest('li')?->matches(SupportSelectors::ACTIVE), $other->filter('svg')->text()])
        ->toBe(['Active Icon Shelf Page', null, false, 'Star']);
});

it('draws its icon while active when it has no active icon of its own', function (): void {
    $item = NavigationItem::make('Today')->icon('support:star')->isActiveWhen(fn (): bool => true)->url('/today');

    expect($this->renderTwigComponent('SylamentPanels:SidebarItem', ['item' => $item])->crawler()->filter('a[aria-current="page"] svg')->text())->toBe('Star');
});

it('opens an item set to a new tab in one', function (): void {
    $page = $this->visit('/shell/shelf');

    expect($page->filter(Selectors::NAVIGATION . ' a[href="https://example.com/handbook"]')->attr('target'))->toBe('_blank')
        ->and($page->filter(Selectors::NAVIGATION . ' a[href="/inbox"]')->attr('target'))->toBeNull();
});

it('shows a badge it is given, 0 included, in its colour or primary, and none on an item without one', function (): void {
    $page = $this->visit('/shell/shelf');
    $badge = fn (string $href, string $color): Crawler => $page->filter(Selectors::NAVIGATION . " a[href=\"{$href}\"] " . SupportSelectors::BADGE . SupportSelectors::color($color) . ' ' . SupportSelectors::BADGE_LABEL);

    expect($badge('/inbox', 'primary')->text())->toBe('0')
        ->and($badge('/alerts', 'danger')->text())->toBe('3')
        ->and($page->filter(Selectors::NAVIGATION . ' a[href="/shell/settings"]')->count())->toBe(1)
        ->and($page->filter(Selectors::NAVIGATION . ' a[href="/shell/settings"] ' . SupportSelectors::BADGE)->count())->toBe(0);
});
