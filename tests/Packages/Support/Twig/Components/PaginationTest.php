<?php

declare(strict_types=1);

use Sylament\Support\Pagination\Paginator;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('tells which records the page shows out of the total, and that it shows none on an empty page', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 3], ['id' => 4]], 30, 2, 2)])->crawler())
        ->toContainElement('nav' . SupportSelectors::STANDALONE_ROOT . '.sy-pagination[aria-label="Pagination"] ' . SupportSelectors::PAGINATION_OVERVIEW, 'Showing 3 to 4 of 30 results')
        ->and($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([], 30, 4, 10)])->crawler())
        ->toContainElement(SupportSelectors::PAGINATION_OVERVIEW, 'Showing 0 results of 30');
});

it('offers a per-page select bound to the table, all named All, the current choice selected, only with more than one choice', function (): void {
    $pagination = $this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 1]], 30, 1, 10), 'pageOptions' => [5, 10, 'all'], 'currentPerPage' => 10])->crawler();

    expect($pagination)->toContainElement('label .sy-pagination-per-page-label', 'Per page')
        ->toContainElement('label select[data-model="tableRecordsPerPage"] option[value="10"][selected]', '10')
        ->toContainElement('label select option[value="all"]:not([selected])', 'All')
        ->and($pagination->filter('select option')->each(fn (Crawler $option): string => $option->attr('value') ?? ''))->toBe(['5', '10', 'all'])
        ->and($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 1]], 30, 1, 10), 'pageOptions' => [5, 10], 'currentPageOptionProperty' => 'perPage'])->crawler())
        ->toContainElement('select[data-model="perPage"] option[value="5"]', '5')
        ->and($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 1]], 30, 1, 10), 'pageOptions' => [10]])->crawler())
        ->toContainElement(SupportSelectors::PAGINATION_OVERVIEW, 'Showing 1 to 1 of 30 results')
        ->not->toContainElement('select');
});

it('pages through a window of numbered buttons, each going to its page, the current one disabled and the gaps hidden from assistive technology', function (): void {
    $pagination = $this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 3], ['id' => 4]], 30, 2, 2)])->crawler();

    expect($pagination)
        ->toContainButtons('.sy-pagination-items button', ['Previous page', 'Go to page 1', 'Go to page 2', 'Go to page 3', 'Go to page 4', 'Go to page 14', 'Go to page 15', 'Next page'])
        ->toContainElement(SupportSelectors::pageButton(2) . '[aria-current="page"][disabled][data-action="live#action"][data-live-action-param="gotoTablePage"][data-live-page-param="2"]', '2')
        ->toContainElement(SupportSelectors::pageButton(14) . ':not([aria-current]):not([disabled])[data-action="live#action"][data-live-action-param="gotoTablePage"][data-live-page-param="14"]', '14')
        ->toContainElement(SupportSelectors::PREVIOUS_PAGE_BUTTON . '[data-action="live#action"][data-live-action-param="gotoTablePage"][data-live-page-param="1"]')
        ->toContainElement(SupportSelectors::NEXT_PAGE_BUTTON . '[data-action="live#action"][data-live-action-param="gotoTablePage"][data-live-page-param="3"]')
        ->toContainElement('.sy-pagination-items [aria-hidden="true"]:not(button)', Paginator::GAP);
});

it('goes back only after the first page and forward only before the last, and offers no pages when there is one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 1]], 3, 1, 1)])->crawler())
        ->toContainButtons('.sy-pagination-items button', ['Go to page 1', 'Go to page 2', 'Go to page 3', 'Next page'])
        ->and($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 3]], 3, 3, 1)])->crawler())
        ->toContainButtons('.sy-pagination-items button', ['Previous page', 'Go to page 1', 'Go to page 2', 'Go to page 3'])
        ->and($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 1]], 1, 1, 10)])->crawler())
        ->toContainElement(SupportSelectors::PAGINATION_OVERVIEW, 'Showing 1 to 1 of 1 results')
        ->not->toContainElement('button');
});

it('offers the first and the last page beside the previous and the next when asked', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 3], ['id' => 4]], 30, 2, 2), 'extremeLinks' => true])->crawler())
        ->toContainButtons('.sy-pagination-items button[rel]', ['Go to the first page', 'Previous page', 'Next page', 'Go to the last page'])
        ->toContainElement(SupportSelectors::FIRST_PAGE_BUTTON . '[data-live-action-param="gotoTablePage"][data-live-page-param="1"]')
        ->toContainElement(SupportSelectors::LAST_PAGE_BUTTON . '[data-live-action-param="gotoTablePage"][data-live-page-param="15"]')
        ->and($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 1]], 30, 1, 2), 'extremeLinks' => true])->crawler())
        ->toContainButtons('.sy-pagination-items button[rel]', ['Next page', 'Go to the last page'])
        ->and($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 3], ['id' => 4]], 30, 2, 2)])->crawler())
        ->toContainButtons('.sy-pagination-items button[rel]', ['Previous page', 'Next page']);
});

it('draws its first, previous, next and last chevrons at the medium icon size', function (): void {
    $chevrons = $this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 3], ['id' => 4]], 30, 2, 2), 'extremeLinks' => true])->crawler();
    $icon = ' ' . SupportSelectors::ICON . SupportSelectors::MEDIUM;

    expect($chevrons)
        ->toContainElement(SupportSelectors::FIRST_PAGE_BUTTON . $icon, 'chevron-double-left-20-solid')
        ->toContainElement(SupportSelectors::PREVIOUS_PAGE_BUTTON . $icon, 'chevron-left-20-solid')
        ->toContainElement(SupportSelectors::NEXT_PAGE_BUTTON . $icon, 'chevron-right-20-solid')
        ->toContainElement(SupportSelectors::LAST_PAGE_BUTTON . $icon, 'chevron-double-right-20-solid');
});

it('pages without a total through the previous and the next alone, saying which records the page shows', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 3], ['id' => 4]], null, 2, 2, hasMoreRecords: true), 'extremeLinks' => true])->crawler())
        ->toContainElement(SupportSelectors::PAGINATION_OVERVIEW, 'Showing 3 to 4 results')
        ->toContainButtons('.sy-pagination-items button', ['Go to the first page', 'Previous page', 'Next page'])
        ->and($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([['id' => 3], ['id' => 4]], null, 2, 2)])->crawler())
        ->toContainButtons('.sy-pagination-items button', ['Previous page'])
        ->and($this->renderTwigComponent('SylamentSupport:Pagination', ['paginator' => new Paginator([], null, 1, 2)])->crawler())
        ->toContainElement(SupportSelectors::PAGINATION_OVERVIEW, 'Showing 0 results')
        ->not->toContainElement('button');
});
