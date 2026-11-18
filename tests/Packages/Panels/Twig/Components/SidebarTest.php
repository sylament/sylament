<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it("is the drawer the sidebar controller opens, its navigation landmark holding one group per navigation group in the panel's order", function (): void {
    $page = $this->visit('/shell/shelf');

    expect($page->filter(Selectors::SIDEBAR . '[data-sylament--panels--sidebar-target="sidebar"] ' . Selectors::NAVIGATION)->count())->toBe(1)
        ->and($page->filter(Selectors::NAVIGATION . ' .sy-pa-sidebar-group')->each(fn (Crawler $group): ?string => $group->attr('data-group-label')))->toBe([null, 'Library', 'Archive', 'Help']);
});

it("numbers each group's item list by its position, a group without a label included, and points each disclosure at its own list", function (): void {
    $page = $this->visit('/shell/shelf');
    $lists = $page->filter(Selectors::NAVIGATION . ' .sy-pa-sidebar-group')->each(fn (Crawler $group): ?string => $group->filter('ul')->attr('id'));

    expect($lists)->toBe(['sy-pa-sidebar-group-items-0', 'sy-pa-sidebar-group-items-1', 'sy-pa-sidebar-group-items-2', 'sy-pa-sidebar-group-items-3'])
        ->and($page->filter(Selectors::NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP_TOGGLE)->each(fn (Crawler $toggle): array => [$toggle->attr('aria-controls'), $toggle->closest('.sy-pa-sidebar-group')?->filter('ul')->attr('id')]))
        ->toBe([['sy-pa-sidebar-group-items-1', 'sy-pa-sidebar-group-items-1'], ['sy-pa-sidebar-group-items-2', 'sy-pa-sidebar-group-items-2']]);
});
