<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it("lists each band group under its label as a list that never collapses, its ids apart from the panel sidebar's", function (): void {
    $page = $this->visit('/navigation-band/banded');

    expect($page->filter(Selectors::SUB_NAVIGATION . ' .sy-pa-sidebar-group-btn')->each(fn (Crawler $label): array => [$label->nodeName(), $label->text()]))->toBe([['div', 'Archive'], ['div', 'Reports']])
        ->and($page->filter(Selectors::SUB_NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP_TOGGLE))->toHaveCount(0)
        ->and($page->filter(Selectors::SUB_NAVIGATION . ' [id^="sy-pa-sidebar-group-items-"]')->each(fn (Crawler $list): ?string => $list->attr('id')))
        ->toBe(['sy-pa-sidebar-group-items-sub-0', 'sy-pa-sidebar-group-items-sub-1', 'sy-pa-sidebar-group-items-sub-2'])
        ->and($page->filter('#sy-pa-sidebar-group-items-0'))->toHaveCount(1)
        ->and($page->filter(Selectors::NAVIGATION . ' #sy-pa-sidebar-group-items-0'))->toHaveCount(1);
});
