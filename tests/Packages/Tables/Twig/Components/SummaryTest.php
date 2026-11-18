<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\GroupableLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;
use Symfony\Component\DomCrawler\Crawler;

it('closes the records with a header row naming the summarized columns, a row for the page and one for every record, each keyed under the host', function (): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $tester = $this->createLiveComponent(SummarizedJarTable::class);
    $hostId = $tester->component()->getTableHostId();
    $crawler = $tester->render()->crawler();
    $header = $crawler->filter(Selectors::summaryRow('header'));

    expect($crawler->filter(Selectors::BODY_ROW)->each(static fn (Crawler $row): string => (string) $row->attr('id')))->toBe([
        $crawler->filter(Selectors::ROW)->eq(0)->attr('id'),
        $crawler->filter(Selectors::ROW)->eq(1)->attr('id'),
        "{$hostId}.table.summary.header",
        "{$hostId}.table.summary.page",
        "{$hostId}.table.summary.all",
    ])
        ->and($header->children()->each(static fn (Crawler $cell): string => $cell->nodeName() . ' ' . $cell->attr('scope') . ' ' . $cell->text()))->toBe(['th col Summary', 'th col Position', 'th col Sealed on', 'th col Seal', 'th col Larder'])
        ->and($crawler->filter(Selectors::summaryRow('page') . ' ' . Selectors::SUMMARY_HEADING)->text())->toBe('This page')
        ->and($crawler->filter(Selectors::summaryRow('all') . ' ' . Selectors::SUMMARY_HEADING)->text())->toBe('All larder jars');
});

it('heads the band Summary on its row for every record once the page row is off', function (): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['noPageRow']])->render()->crawler();

    expect($crawler->filter(Selectors::summaryRow('header')))->toHaveCount(0)
        ->and($crawler->filter(Selectors::summaryRow('all') . ' ' . Selectors::SUMMARY_HEADING)->text())->toBe('Summary');
});

it('leaves a blank cell, no column header, over a column nothing summarizes or whose every summarizer is hidden, the row for every record off too', function (array $variants, array $header): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $cells = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => $variants])->render()->crawler()->filter(Selectors::summaryRow('header'))->children();

    expect($cells->each(static fn (Crawler $cell): string => $cell->nodeName() . ' ' . $cell->text()))->toBe($header);
})->with([
    'nothing summarized there' => [['positionOnly'], ['th Summary', 'th Position', 'td ', 'td ', 'td ']],
    'every summarizer hidden, the row for every record off' => [['sealedOnHidden', 'noAllRow'], ['th Summary', 'th Position', 'td ', 'th Seal', 'th Larder']],
]);

it("stamps each header cell with its column's responsive pair", function (): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $cells = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['responsive']])->render()->crawler()->filter(Selectors::summaryRow('header'))->children();

    expect($cells->eq(0)->matches('[class~="lg:sy-ta-hidden"]'))->toBeTrue()
        ->and($cells->eq(1)->matches('[class~="md:sy-ta-visible"]'))->toBeTrue()
        ->and($cells->eq(2)->matches('[class*="sy-ta-hidden"], [class*="sy-ta-visible"]'))->toBeFalse();
});

it('gives the grouped column no cell in the band while only the groups show', function (): void {
    $this->seed(new LarderJar('Apricot', seal: JarSeal::Wax), new LarderJar('Bramble', seal: JarSeal::Screw));

    $crawler = $this->createLiveComponent(GroupableLarderJarTable::class, ['variants' => ['groupsOnly', 'summarized'], 'tableGrouping' => 'seal'])->render()->crawler();
    $all = $crawler->filter(Selectors::summaryRow('all'));

    expect($all->children()->first()->text())->toBe('Summary')
        ->and($this::textSummaries($all))->toBe(['Count 2'])
        ->and($all->children()->count())->toBe($crawler->filter(Selectors::COLUMN_HEADER)->count());
});
