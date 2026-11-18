<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('wraps a cell in a link to its url, in a new tab when asked', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();
    $wrapper = static fn (string $key, string $column): Crawler => $crawler->filter(Selectors::cell($key, $column) . ' ' . Selectors::CELL_WRAPPER);

    expect($wrapper('lamp', 'link')->matches('a[href="/shelves/top"]:not([target])'))->toBeTrue()
        ->and($wrapper('lamp', 'newTab')->matches('a[href="/shelves/new"][target="_blank"]'))->toBeTrue()
        ->and($wrapper('lamp', 'recordLink')->attr('href'))->toBe('/items/lamp')
        ->and($wrapper('vase', 'recordLink')->attr('href'))->toBe('/items/vase')
        ->and($wrapper('lamp', 'name')->nodeName())->toBe('div');
});

it('moves the links inside the cell when the url closure declares `$state`, and onto the wrapper when it does not', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();
    $hrefs = static fn (Crawler $links): array => $links->each(static fn (Crawler $link): ?string => $link->attr('href'));

    expect($crawler->filter(Selectors::cell('lamp', 'tags') . ' ' . Selectors::CELL_WRAPPER)->nodeName())->toBe('div')
        ->and($hrefs($crawler->filter(Selectors::cell('lamp', 'tags') . ' ' . Selectors::TEXT_COLUMN . ' a')))->toBe(['/tags/oak', '/tags/ash'])
        ->and($crawler->filter(Selectors::cell('lamp', 'renamed') . ' ' . Selectors::CELL_WRAPPER)->matches('a[href="/tags/"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::cell('lamp', 'renamed') . ' ' . Selectors::TEXT_COLUMN . ' a'))->toHaveCount(0);
});

it('leaves a `javascript:` url unlinked, on the wrapper and on an item', function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();
    $items = $crawler->filter(Selectors::cell('lamp', 'unsafeItems') . ' ' . Selectors::TEXT_COLUMN . ' li');

    expect($crawler->filter(Selectors::cell('lamp', 'unsafe') . ' ' . Selectors::CELL_WRAPPER)->nodeName())->toBe('div')
        ->and($crawler->filter(Selectors::cell('lamp', 'unsafe') . ' a'))->toHaveCount(0)
        ->and($items->eq(0)->filter('a')->attr('href'))->toBe('/tags/oak')
        ->and($items->eq(1)->text())->toBe('ash')
        ->and($items->eq(1)->filter('a'))->toHaveCount(0);
});
