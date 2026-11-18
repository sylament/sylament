<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\GroupableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;
use Symfony\Component\DomCrawler\Crawler;

it("leads with a row header over the first column, then each column's cell holding the figures of its visible summarizers", function (): void {
    $this->seed(new LarderJar('Apricot', new DateTimeImmutable('2024-03-05 10:00:00'), position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $page = $this->createLiveComponent(SummarizedJarTable::class)->render()->crawler()->filter(Selectors::summaryRow('page'));
    $cells = $page->children();

    expect($cells->first()->matches(Selectors::SUMMARY_HEADING . ':not([colspan])'))->toBeTrue()
        ->and($cells->first()->text())->toBe('This page')
        ->and($cells)->toHaveCount(5)
        ->and($this::textSummaries($cells->eq(2)))->toBe(['Count 1'])
        ->and($cells->eq(2)->filter(Selectors::summarizerFigure('range'))->text())->toBe('2024-03-05 10:00:00 - 2024-03-05 10:00:00');
});

it('spans the row header across every column up to the first summarized one, so a summarizer on the first column never renders', function (): void {
    $this->seed(new LarderJar('Apricot'), new LarderJar('Bramble'), new LarderJar('Cherry'));

    $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['summarized', 'paged']])->render()->crawler();
    $page = $crawler->filter(Selectors::summaryRow('page'));

    expect($page->children())->toHaveCount(1)
        ->and((int) $page->filter(Selectors::SUMMARY_HEADING)->attr('colspan'))->toBe($crawler->filter(Selectors::COLUMN_HEADER)->count())
        ->and($page->filter(Selectors::summarizer('text')))->toHaveCount(0);
});

it("places a blank cell wherever the record row places its actions and its checkbox", function (string $actionsPosition, string $checkboxPosition): void {
    $this->seed(new LarderJar('Apricot', seal: JarSeal::Wax, position: 1), new LarderJar('Bramble', seal: JarSeal::Wax, position: 2), new LarderJar('Cherry', seal: JarSeal::Wax, position: 4));

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['actions'], 'actionsPosition' => $actionsPosition, 'checkboxPosition' => $checkboxPosition])->render()->crawler();
    $blanks = static fn (Crawler $cells): array => $cells->each(static fn (Crawler $cell): bool => $cell->text() === '');
    $head = $blanks($crawler->filter(Selectors::COLUMN_HEADER));

    expect($head)->toContain(true)
        ->and($blanks($crawler->filter(Selectors::summaryRow('header'))->children()))->toBe($head)
        ->and($blanks($crawler->filter(Selectors::summaryRow('page'))->children()))->toBe($head)
        ->and($blanks($crawler->filter(Selectors::summaryRow('all'))->children()))->toBe($head);
})->with([
    'actions before the cells, checkbox before the cells' => ['BeforeCells', 'BeforeCells'],
    'actions before the cells, checkbox after the cells' => ['BeforeCells', 'AfterCells'],
    'actions before the columns, checkbox before the cells' => ['BeforeColumns', 'BeforeCells'],
    'actions before the columns, checkbox after the cells' => ['BeforeColumns', 'AfterCells'],
    'actions after the columns, checkbox before the cells' => ['AfterColumns', 'BeforeCells'],
    'actions after the columns, checkbox after the cells' => ['AfterColumns', 'AfterCells'],
    'actions after the cells, checkbox before the cells' => ['AfterCells', 'BeforeCells'],
    'actions after the cells, checkbox after the cells' => ['AfterCells', 'AfterCells'],
]);

it("stamps each cell with its column's responsive pair, the row header with the first column's, and pads a blank cell as a cell", function (): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $cells = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['responsive', 'actions'], 'actionsPosition' => 'AfterCells'])->render()->crawler()->filter(Selectors::summaryRow('page'))->children();

    expect($cells->eq(1)->matches(Selectors::SUMMARY_HEADING . '[class~="lg:sy-ta-hidden"]'))->toBeTrue()
        ->and($cells->eq(2)->matches('[class~="md:sy-ta-visible"]'))->toBeTrue()
        ->and($cells->eq(3)->matches('[class*="sy-ta-hidden"], [class*="sy-ta-visible"]'))->toBeFalse()
        ->and($cells->first()->text())->toBe('')
        ->and($cells->first()->matches('td.sy-ta-cell'))->toBeTrue()
        ->and($cells->last()->text())->toBe('')
        ->and($cells->last()->matches('td.sy-ta-cell'))->toBeTrue();
});

it("heads a group's row with the group's title in a cell of its own while only the groups show", function (): void {
    $this->seed(new LarderJar('Apricot', seal: JarSeal::Wax), new LarderJar('Bramble', seal: JarSeal::Wax));

    $row = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['groupsOnly', 'summarized'], 'tableGrouping' => 'seal'])->render()->crawler()->filter(Selectors::groupSummaryRow('wax'));

    expect($row->children()->first()->matches(Selectors::SUMMARY_HEADING))->toBeTrue()
        ->and($row->children()->first()->text())->toBe('wax')
        ->and($this::textSummaries($row->children()->eq(1)))->toBe(['Count 2']);
});
