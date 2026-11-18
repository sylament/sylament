<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;

it('renders the page row and the all-table row each while summaries() keeps it on', function (array $variants, int $pageRows, int $allRows): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => $variants])->render()->crawler();

    expect($crawler->filter(Selectors::summaryRow('page')))->toHaveCount($pageRows)
        ->and($crawler->filter(Selectors::summaryRow('all')))->toHaveCount($allRows);
})->with([
    'both' => [[], 1, 1],
    'the all-table row alone' => [['noPageRow'], 0, 1],
    'the page row alone' => [['noAllRow'], 1, 0],
]);

it('keeps the band while a column the user toggled off or the developer hid is summarized, giving that column no cell and no figure', function (array $variants, bool $toggledOff): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $tester = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly', ...$variants]]);

    if ($toggledOff) {
        $tester->toggleAllTableColumns(false);
    }

    $crawler = $tester->render()->crawler();
    $all = $crawler->filter(Selectors::summaryRow('all'));

    expect($all->filter(Selectors::SUMMARY_HEADING)->text())->toBe('All larder jars')
        ->and((int) $all->filter(Selectors::SUMMARY_HEADING)->attr('colspan'))->toBe($crawler->filter(Selectors::COLUMN_HEADER)->count())
        ->and($crawler->filter(Selectors::headerCell('position')))->toHaveCount(0)
        ->and($all->filter(Selectors::summarizer('text')))->toHaveCount(0)
        ->and(preg_grep('/\bSUM\(/', $this->statementsRun()))->toBe([]);
})->with([
    'toggled off' => [[], true],
    'hidden' => [['positionHidden'], false],
]);

it('renders no band for a table nothing summarizes, nor while it is reordering', function (array $variants, bool $reordering): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $tester = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => $variants]);
    $summarized = $tester->render()->crawler();

    if ($reordering) {
        $tester->call('toggleTableReordering');
    }

    $crawler = $tester->render()->crawler();

    expect($summarized->filter(Selectors::summaryRow('all')))->toHaveCount($reordering ? 1 : 0)
        ->and($crawler->filter(Selectors::ROW))->toHaveCount($reordering ? 3 : 2)
        ->and($crawler->filter(Selectors::summaryRow('header') . ', ' . Selectors::summaryRow('page') . ', ' . Selectors::summaryRow('all')))->toHaveCount(0)
        ->and(preg_grep('/\b(SUM|MIN)\(|\bDISTINCT a0_\.seal\b/', $this->statementsRun()))->toBe([]);
})->with([
    'nothing summarized' => [['unsummarized'], false],
    'reordering' => [['reorderable'], true],
]);
