<?php

declare(strict_types=1);

use Sylament\Panels\Navigation\NavigationGroup;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('tabs an ungrouped item as a link and a labelled group as a tab opening its items, that tab current while it holds the page shown', function (): void {
    $tabs = $this->visit('/navigation-band/top-banded')->filter(Selectors::SUB_NAVIGATION_TABS);
    $overview = $tabs->filter('a[href="/navigation-band/banded"]');
    $reports = $tabs->filter(SupportSelectors::DROPDOWN_TRIGGER . ' button');

    expect([
        $overview->filter(SupportSelectors::TABS_ITEM_LABEL)->text(),
        $overview->attr('aria-current'),
        $overview->filter('svg')->text(),
        $overview->filter(SupportSelectors::BADGE . SupportSelectors::color('success') . ' ' . SupportSelectors::BADGE_LABEL)->text(),
    ])->toBe(['Overview', null, 'Star', '5'])
        ->and([$reports->text(), $reports->attr('aria-current')])->toBe(['Reports', 'true'])
        ->and($tabs->filter(SupportSelectors::DROPDOWN_ITEM)->each(fn (Crawler $item): array => [$item->text(), $item->attr('href'), $item->attr('aria-current')]))
        ->toBe([['Quarterly', '/navigation-band/top-banded', 'page'], ['Monthly', '/monthly', null]])
        ->and($tabs->filter('[aria-current="page"]'))->toHaveCount(1);
});

it("marks the tab of the page shown as the current page, and no other", function (): void {
    $this->seed($article = new Article('Draft'));

    $tabs = $this->visit("/navigation-band/top-banded-articles/{$article->id}/edit")->filter(Selectors::SUB_NAVIGATION_TABS . ' a')->each(fn (Crawler $tab): array => [$tab->text(), $tab->attr('aria-current')]);

    expect($tabs)->toBe([['Edit Top Banded Article', 'page'], ['Notes', null]]);
});

it("leaves a group's tab plain while it holds no page shown", function (): void {
    $navigation = [NavigationGroup::make('Reports')->items([NavigationItem::make('Monthly')->url('/monthly')])];

    $trigger = $this->renderTwigComponent('SylamentPanels:SubNavigationTabs', ['navigation' => $navigation])->crawler()->filter(SupportSelectors::DROPDOWN_TRIGGER . ' button');

    expect([$trigger->text(), $trigger->attr('aria-current')])->toBe(['Reports', null]);
});
