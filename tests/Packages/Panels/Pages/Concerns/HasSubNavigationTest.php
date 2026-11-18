<?php

declare(strict_types=1);

use Sylament\Panels\Pages\Enums\SubNavigationPosition;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Pages\BandedPlainPage;
use Sylament\Tests\Fixtures\Panels\Pages\TopBandedPlainPage;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditBandedArticle;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('draws no band for a page that declares no sub-navigation, and one for a page that does', function (): void {
    $plain = $this->visit('/navigation-band/settings');
    $banded = $this->visit('/navigation-band/banded');

    expect($plain->filter(Selectors::PAGE))->toHaveCount(1)
        ->and($plain->filter(Selectors::PAGE . '[class*="sy-pa-page-has-sub-navigation"]'))->toHaveCount(0)
        ->and($plain->filter(Selectors::SUB_NAVIGATION_MENU . ', ' . Selectors::SUB_NAVIGATION . ', ' . Selectors::SUB_NAVIGATION_TABS))->toHaveCount(0)
        ->and($banded->filter(Selectors::pageWithSubNavigation()))->toHaveCount(1)
        ->and($banded->filter(Selectors::SUB_NAVIGATION_MENU))->toHaveCount(1);
});

it('folds its band into groups, the ungrouped items first under no label and then a group per label, each listing its visible items in sort order', function (): void {
    $groups = $this->visit('/navigation-band/banded')->filter(Selectors::SUB_NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP)->each(fn (Crawler $group): array => [
        $group->attr('data-group-label'),
        $group->filter(Selectors::NAVIGATION_ITEM_LABEL)->each(fn (Crawler $label): string => $label->text()),
    ]);

    expect($groups)->toBe([
        [null, ['Overview', 'Pinned', 'Drafts']],
        ['Archive', ['Older', 'Old']],
        ['Reports', ['Quarterly', 'Monthly']],
    ]);
});

it("builds a listed page's item from its navigation statics, linked to the page's route in the panel shown", function (): void {
    $band = $this->visit('/navigation-band/banded')->filter(Selectors::SUB_NAVIGATION);
    $overview = $band->filter('a[href="/navigation-band/banded"]');

    expect([
        $overview->filter(Selectors::NAVIGATION_ITEM_LABEL)->text(),
        $overview->filter('svg')->text(),
        $overview->filter(SupportSelectors::BADGE . SupportSelectors::color('success') . ' ' . SupportSelectors::BADGE_LABEL)->text(),
    ])->toBe(['Overview', 'Star', '5'])
        ->and($band->filter(Selectors::NAVIGATION_GROUP . '[data-group-label="Reports"] a')->attr('href'))->toBe('/navigation-band/top-banded')
        ->and($this->visit('/navigation-start/banded')->filter(Selectors::SUB_NAVIGATION . ' ' . Selectors::NAVIGATION_GROUP . '[data-group-label="Reports"] a')->attr('href'))->toBe('/navigation-start/top-banded');
});

it('marks the item of the page rendering the band, on arrival and after a live request re-renders it', function (): void {
    $current = fn (Crawler $page): array => $page->filter(Selectors::ACTIVE_SUB_NAVIGATION_ITEM . ' ' . Selectors::NAVIGATION_ITEM_LABEL)->each(fn (Crawler $label): string => $label->text());

    expect($current($this->visit('/navigation-band/banded')))->toBe(['Overview'])
        ->and($current($this->createLiveComponent(BandedPlainPage::class, ['panelId' => 'navigation-band'])->call('refresh')->render()->crawler()))->toBe(['Overview']);
});

it("draws the band at the page's own position over its panel's, and at the start for a page built outside the container", function (): void {
    $page = $this->visit('/navigation-band/top-banded');

    expect($page->filter(Selectors::pageWithSubNavigation('top')))->toHaveCount(1)
        ->and($page->filter(Selectors::SUB_NAVIGATION_TABS))->toHaveCount(1)
        ->and($page->filter(Selectors::SUB_NAVIGATION))->toHaveCount(0)
        ->and(new BandedPlainPage()->getSubNavigationPosition())->toBe(SubNavigationPosition::Start);
});

it("refuses to list a resource page in a plain page's band, and to link a page while built outside any panel", function (): void {
    expect(fn (): array => new BandedPlainPage()->generateNavigationItems([EditBandedArticle::class]))
        ->toThrow(LogicException::class, 'The page [' . BandedPlainPage::class . '] lists the resource page [' . EditBandedArticle::class . '] in its sub-navigation')
        ->and(fn (): array => new BandedPlainPage()->generateNavigationItems([TopBandedPlainPage::class]))
        ->toThrow(LogicException::class, 'The page [' . BandedPlainPage::class . '] builds sub-navigation outside any panel, so the route of [' . TopBandedPlainPage::class . '] cannot be generated.');
});

describe('on a record page', function (): void {
    it("draws the band its resource builds, each sibling linked under the record's key by its own resource, and leaves out a page no resource routes", function (): void {
        $this->seed($article = new Article('Draft'));

        $band = $this->visit("/navigation-band/banded-articles/{$article->id}/edit")->filter(Selectors::SUB_NAVIGATION . ' a')->each(fn (Crawler $item): array => [
            $item->filter(Selectors::NAVIGATION_ITEM_LABEL)->text(),
            $item->attr('href'),
            $item->attr('aria-current'),
        ]);
        $unbanded = $this->visit("/admin/articles/{$article->id}/edit");

        expect($band)->toBe([
            ['Edit Banded Article', "/navigation-band/banded-articles/{$article->id}/edit", 'page'],
            ['Edit Top Banded Article', "/navigation-band/top-banded-articles/{$article->id}/edit", null],
        ])
            ->and($unbanded->filter(Selectors::PAGE))->toHaveCount(1)
            ->and($unbanded->filter(Selectors::pageWithSubNavigation()))->toHaveCount(0);
    });

    it("draws the band at the page's own position, else its resource's, else its panel's", function (string $uri, string $position): void {
        $this->seed($article = new Article('Draft'));

        expect($this->visit(sprintf($uri, $article->id))->filter(Selectors::pageWithSubNavigation($position)))->toHaveCount(1);
    })->with([
        "the page's" => ['/navigation-band/top-banded-articles/%d/notes', 'start'],
        "the resource's" => ['/navigation-band/top-banded-articles/%d/edit', 'top'],
        "the panel's" => ['/navigation-band/banded-articles/%d/edit', 'end'],
    ]);
});
