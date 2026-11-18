<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('opens the sidebar from one toggle button and ends on the user menu, static throughout on a panel with nothing to search', function (): void {
    $page = $this->visit('/shell/shelf');
    $toggle = $page->filter('[aria-controls="sy-pa-sidebar"]');

    expect($toggle->count())->toBe(1)
        ->and($page->filter(Selectors::TOPBAR . ' button[aria-controls="sy-pa-sidebar"][aria-expanded="false"][aria-label="Toggle sidebar"][data-sylament--panels--sidebar-target="toggleButton"]')->count())->toBe(1)
        ->and($toggle->attr('data-action'))->toBe('sylament--panels--sidebar#toggle')
        ->and($page->filter(Selectors::TOPBAR . ' .sy-pa-topbar-end ' . SupportSelectors::DROPDOWN . SupportSelectors::KEPT_DROPDOWN . ' ' . Selectors::USER_MENU_TRIGGER)->count())->toBe(1)
        ->and($page->filter(Selectors::GLOBAL_SEARCH)->count())->toBe(0)
        ->and($page->filter(Selectors::TOPBAR . ' ' . TablesSelectors::LIVE_PROPS)->count())->toBe(0);
});

it("mounts the global search as the topbar's one live root, beside the user menu and ahead of it, on a panel with a searchable resource", function (): void {
    $page = $this->visit('/admin/articles');
    $end = Selectors::TOPBAR . ' .sy-pa-topbar-end ';

    expect($page->filter(Selectors::TOPBAR . ' ' . TablesSelectors::LIVE_PROPS)->each(fn (Crawler $root): bool => $root->matches($end . Selectors::GLOBAL_SEARCH)))->toBe([true])
        ->and($page->filter(Selectors::GLOBAL_SEARCH . ' ' . Selectors::USER_MENU_TRIGGER)->count())->toBe(0)
        ->and($page->filter($end . Selectors::GLOBAL_SEARCH . ', ' . $end . Selectors::USER_MENU_TRIGGER)->each(fn (Crawler $element): string => $element->matches(Selectors::GLOBAL_SEARCH) ? 'search' : 'user menu'))->toBe(['search', 'user menu']);
});

it("renders an end block in the topbar's end, ahead of the user menu", function (): void {
    $topbar = $this->renderTwigComponent('SylamentPanels:Topbar', blocks: ['end' => '<a href="/help">Help</a>'])->crawler();
    $end = '.sy-pa-topbar-end ';

    expect($topbar->filter($end . 'a[href="/help"], ' . $end . Selectors::USER_MENU_TRIGGER)->each(fn (Crawler $element): string => $element->text() === 'Help' ? 'end' : 'user menu'))->toBe(['end', 'user menu']);
});
