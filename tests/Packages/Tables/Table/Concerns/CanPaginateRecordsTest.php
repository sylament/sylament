<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\PagedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\RecordsPerPageReadingTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

describe('the page options', function (): void {
    it('offers exactly its page options in the footer, all of them as All, and lists a page that long, or every record and no footer unpaginated', function (array $props, ?array $options, int $rows): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));

        $crawler = $this->createLiveComponent(PagedLarderJarTable::class, $props)->render()->crawler();

        expect($crawler->filter(SupportSelectors::PAGINATION))->toHaveCount($options === null ? 0 : 1)
            ->and($crawler->filter(SupportSelectors::PER_PAGE_SELECT . ' option')->each(static fn (Crawler $option): string => $option->text()))->toBe($options ?? [])
            ->and($crawler->filter(Selectors::ROW))->toHaveCount($rows);
    })->with([
        'its own options' => [['options' => [2, 5, 'all']], ['2', '5', 'All'], 2],
        'the default options' => [[], ['5', '10', '25', '50'], 10],
        'the options shortcut after paginated(false)' => [['variants' => ['shortcut']], ['5', '10'], 10],
        'unpaginated' => [['variants' => ['unpaginated']], null, 11],
    ]);

    it('pages by ten where its options hold ten, else by the first option, and by the default it is given over either', function (array $props, string $selected): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));

        $crawler = $this->createLiveComponent(PagedLarderJarTable::class, $props)->render()->crawler();

        expect($crawler->filter(SupportSelectors::PER_PAGE_SELECT . ' option[selected]')->attr('value'))->toBe($selected)
            ->and($crawler->filter(Selectors::ROW))->toHaveCount((int) $selected);
    })->with([
        'options holding ten' => [['options' => [5, 10, 25]], '10'],
        'options without ten' => [['options' => [2, 5, 'all']], '2'],
        'a default of its own' => [['options' => [5, 10, 25], 'defaultOption' => 5], '5'],
    ]);

    it('defaults an option list mixing the string ten with numbers to that string, paging ten rows by it', function (): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));

        $tester = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => ['10', 25]]);
        $crawler = $tester->render()->crawler();

        expect($crawler->filter(SupportSelectors::PER_PAGE_SELECT . ' option[selected]')->attr('value'))->toBe('10')
            ->and($crawler->filter(Selectors::ROW))->toHaveCount(10)
            ->and($tester->set('tableRecordsPerPage', '10')->render()->crawler()->filter(Selectors::ROW))->toHaveCount(10);
    });
});

it('links the first and the last page only once extreme links are on', function (): void {
    $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));

    $plain = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all']])->call('gotoTablePage', ['page' => 2])->render()->crawler();
    $extreme = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all'], 'variants' => ['extremeLinks']])->call('gotoTablePage', ['page' => 2])->render()->crawler();

    expect($plain->filter(SupportSelectors::PREVIOUS_PAGE_BUTTON))->toHaveCount(1)
        ->and($plain->filter(SupportSelectors::NEXT_PAGE_BUTTON))->toHaveCount(1)
        ->and($plain->filter(SupportSelectors::FIRST_PAGE_BUTTON))->toHaveCount(0)
        ->and($plain->filter(SupportSelectors::LAST_PAGE_BUTTON))->toHaveCount(0)
        ->and($extreme->filter(SupportSelectors::FIRST_PAGE_BUTTON)->attr('data-live-page-param'))->toBe('1')
        ->and($extreme->filter(SupportSelectors::LAST_PAGE_BUTTON)->attr('data-live-page-param'))->toBe('6');
});

describe('the pagination mode', function (): void {
    it('reports the total and numbers every page in the default mode, and offers only the next page with no total in the simple one', function (string $mode, string $overview, int $numberedPages): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));

        $crawler = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all'], 'mode' => $mode])->render()->crawler();

        expect($crawler->filter(SupportSelectors::PAGINATION_OVERVIEW)->text())->toBe($overview)
            ->and($crawler->filter('button[aria-label^="Go to page"]'))->toHaveCount($numberedPages)
            ->and($crawler->filter(SupportSelectors::NEXT_PAGE_BUTTON)->attr('data-live-page-param'))->toBe('2');
    })->with([
        'default' => ['Default', 'Showing 1 to 2 of 11 results', 6],
        'simple' => ['Simple', 'Showing 1 to 2 results', 0],
    ]);

    it('announces the total across all pages in the default mode, and the page count with no total in the simple one', function (string $mode, string $announcement): void {
        $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));

        $crawler = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all'], 'mode' => $mode])->render()->crawler();

        expect($crawler)->toContainElement(Selectors::RESULT_COUNT, $announcement);
    })->with([
        'default' => ['Default', '11 results'],
        'simple' => ['Simple', '2 results'],
    ]);

    it('renders the rows a records() closure returns as it returns them, whatever the mode', function (): void {
        $crawler = $this->createLiveComponent(RecordsPerPageReadingTable::class, ['variants' => ['simple']])
            ->set('tableRecordsPerPage', '5')
            ->render()
            ->crawler();

        expect($crawler->filter(Selectors::ROW)->each(static fn (Crawler $row): string => $row->text()))->toBe(['Ash', 'Birch', 'Cedar', 'Elm', 'Fir', 'Larch', 'Oak'])
            ->and($crawler->filter(SupportSelectors::PAGINATION))->toHaveCount(0);
    });
});

it('lists every record on one page while reordering, unless it pages while reordering', function (array $variants, int $rows, int $footers): void {
    $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number), position: $number), range(1, 11)));

    $tester = $this->createLiveComponent(PagedLarderJarTable::class, ['options' => [2, 5, 'all'], 'variants' => $variants]);

    expect($tester->render()->crawler()->filter(Selectors::ROW))->toHaveCount(2);

    $crawler = $tester->call('toggleTableReordering')->render()->crawler();

    expect($crawler->filter(Selectors::reorderHandle('1')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::ROW))->toHaveCount($rows)
        ->and($crawler->filter(SupportSelectors::PAGINATION))->toHaveCount($footers);
})->with([
    'every record' => [[], 11, 0],
    'the page' => [['whileReordering'], 2, 1],
]);
