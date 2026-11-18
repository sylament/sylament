<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it("is the drawer the sidebar controller opens, its navigation landmark holding one group per navigation group in the panel's order", function (): void {
    $page = $this->visit('/shell/shelf');

    expect($page->filter(Selectors::SIDEBAR . '[data-sylament--panels--sidebar-target="sidebar"] ' . Selectors::NAVIGATION)->count())->toBe(1)
        ->and($page->filter(Selectors::NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP)->each(fn (Crawler $group): ?string => $group->attr('data-group-label')))->toBe([null, 'Library', 'Archive', 'Help']);
});

it("numbers each group's item list by its position, a group without a label included, and points each disclosure at its own list", function (): void {
    $page = $this->visit('/shell/shelf');
    $lists = $page->filter(Selectors::NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP)->each(fn (Crawler $group): ?string => $group->filter('ul')->attr('id'));

    expect($lists)->toBe(array_map(Selectors::navigationGroupItemsId(...), [0, 1, 2, 3]))
        ->and($page->filter(Selectors::NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP_TOGGLE)->each(fn (Crawler $toggle): array => [$toggle->attr('aria-controls'), $toggle->closest(Selectors::NAVIGATION_GROUP)?->filter('ul')->attr('id')]))
        ->toBe([[Selectors::navigationGroupItemsId(1), Selectors::navigationGroupItemsId(1)], [Selectors::navigationGroupItemsId(2), Selectors::navigationGroupItemsId(2)]]);
});
