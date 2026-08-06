<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Integration;

use Sylament\Tables\Tests\Fixtures\Twig\Components\BrowsableSongsTable;
use Sylament\Tables\Twig\Extension\TableExtension;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Environment;

/*
 * The browsing chrome of the shipped `Table` partial — sortable headers, the
 * search toolbar and the pagination footer — rendered through the booted
 * kernel's Twig against a host that turns all three on at once, and asserted
 * with the Crawler so a renamed class or a flattened wrapper fails here rather
 * than silently downstream in the stylesheet or the demo's browser pass.
 */

function browsingTwig(): Environment
{
    $twig = test()->getContainer()->get('twig');

    assert($twig instanceof Environment);

    return $twig;
}

function browsableTableHtml(int $page = 1): string
{
    $twig = browsingTwig();

    if ($page === 1) {
        return $twig->render('@TablesFixtures/browsable_table_page.html.twig');
    }

    $component = new BrowsableSongsTable();
    $component->tablePage = $page;

    return (string) $twig->getExtension(TableExtension::class)->renderTable($twig, ['this' => $component]);
}

function songsTableHtml(): string
{
    return browsingTwig()->render('@TablesFixtures/table_extension_page.html.twig');
}

it('renders a sortable header as an aria-annotated live action button', function (): void {
    $crawler = new Crawler(browsableTableHtml());

    $sortableHeader = $crawler->filter('th.sy-ta-header-cell')->first();
    $button = $sortableHeader->filter('button.sy-ta-header-cell-sort-btn');

    expect($sortableHeader->attr('aria-sort'))->toBe('none')
        ->and($button->count())->toBe(1)
        ->and($button->attr('data-action'))->toContain('live#action')
        ->and($button->attr('data-live-action-param'))->toBe('sortTable')
        ->and($button->attr('data-live-column-param'))->toBe('title');
});

it('renders an unsortable header without a button', function (): void {
    $crawler = new Crawler(browsableTableHtml());

    expect($crawler->filter('th.sy-ta-header-cell')->eq(1)->filter('button')->count())->toBe(0)
        ->and($crawler->filter('th.sy-ta-header-cell')->eq(1)->attr('aria-sort'))->toBeNull();
});

it('renders the search field bound to tableSearch with the debounce', function (): void {
    $crawler = new Crawler(browsableTableHtml());
    $input = $crawler->filter('.sy-ta-header-toolbar .sy-ta-search-field input.sy-ta-search-input');

    expect($input->count())->toBe(1)
        ->and($input->attr('data-model'))->toBe('debounce(500)|tableSearch')
        ->and($input->attr('type'))->toBe('search')
        ->and($input->attr('placeholder'))->toBe('Search');
});

it('renders the pagination footer with overview, gapped window, and per-page select', function (): void {
    $crawler = new Crawler(browsableTableHtml());

    expect($crawler->filter('nav.sy-pagination')->count())->toBe(1)
        ->and($crawler->filter('.sy-pagination-overview')->text())->toContain('Showing 1 to 5 of 43 results');

    $items = $crawler->filter('.sy-pagination-items button.sy-pagination-item');
    expect($items->count())->toBeGreaterThanOrEqual(6)
        ->and($crawler->filter('.sy-pagination-items .sy-pagination-gap')->count())->toBe(1);

    $active = $crawler->filter('.sy-pagination-item-active');
    expect($active->attr('aria-current'))->toBe('page')
        ->and(mb_trim($active->text()))->toBe('1');

    $pageTwo = $items->reduce(fn (Crawler $node): bool => mb_trim($node->text()) === '2')->first();
    expect($pageTwo->attr('data-live-action-param'))->toBe('gotoTablePage')
        ->and($pageTwo->attr('data-live-page-param'))->toBe('2');

    $select = $crawler->filter('select.sy-pagination-per-page-select');
    expect($select->attr('data-model'))->toBe('tableRecordsPerPage')
        ->and($select->filter('option[selected]')->attr('value'))->toBe('5')
        ->and($select->filter('option')->last()->text())->toContain('All');
});

it('renders no footer and no toolbar for a plain unpaginated, unsearchable table', function (): void {
    $html = songsTableHtml();

    expect($html)->not->toContain('sy-pagination')
        ->and($html)->not->toContain('sy-ta-header-toolbar');
});

it('keeps the footer beside the empty state on an overflow page', function (): void {
    $crawler = new Crawler(browsableTableHtml(page: 99));

    expect($crawler->filter('.sy-ta-empty-state')->count())->toBe(1)
        ->and($crawler->filter('nav.sy-pagination')->count())->toBe(1);
});
