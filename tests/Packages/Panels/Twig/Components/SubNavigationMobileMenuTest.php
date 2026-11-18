<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('lists every band item in one menu under the label of the page shown, with no group headers, marking only that page current', function (): void {
    $menu = $this->visit('/navigation-band/banded')->filter(Selectors::SUB_NAVIGATION_MENU);

    expect($menu->filter(SupportSelectors::DROPDOWN_TRIGGER . ' button')->innerText())->toBe('Overview')
        ->and($menu->filter(SupportSelectors::DROPDOWN_ITEM)->each(fn (Crawler $item): array => [
            $item->filter('.sy-dropdown-item-label')->text(),
            $item->attr('href'),
            $item->attr('target'),
            $item->attr('aria-current'),
        ]))->toBe([
            ['Pinned', '/pinned', null, null],
            ['Drafts', '/drafts', '_blank', null],
            ['Overview', '/navigation-band/banded', null, 'page'],
            ['Older', '/older', null, null],
            ['Old', '/old', null, null],
            ['Quarterly', '/navigation-band/top-banded', null, null],
            ['Monthly', '/monthly', null, null],
        ])
        ->and($menu->text())->not->toContain('Archive')->not->toContain('Reports');
});

it('labels its trigger with the first item while no item is the page shown', function (): void {
    $navigation = [NavigationGroup::make()->items([NavigationItem::make('Today')->url('/today'), NavigationItem::make('Later')->url('/later')])];

    $trigger = $this->renderTwigComponent('SylamentPanels:SubNavigationMobileMenu', ['navigation' => $navigation])->crawler()->filter(SupportSelectors::DROPDOWN_TRIGGER . ' button');

    expect($trigger->innerText())->toBe('Today');
});
