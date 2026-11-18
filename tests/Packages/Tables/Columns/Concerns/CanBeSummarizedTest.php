<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Average;
use Sylament\Tables\Columns\Summarizers\Count;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\Summarizers\Summarizer;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;

it('takes one summarizer or a list, keyed by id where one has it, and hands back the visible ones pointed at the query it is given', function (): void {
    $column = TextColumn::make('position')
        ->summarize(Sum::make())
        ->summarize([Average::make('mean'), Count::make()->hidden()]);
    $query = $this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar');
    $visible = $column->getSummarizers($query);

    expect(array_keys($column->getSummarizers()))->toBe([0, 'mean', 1])
        ->and(array_keys($visible))->toBe([0, 'mean'])
        ->and(array_map(static fn (Summarizer $summarizer): mixed => [$summarizer->getColumn(), $summarizer->getQuery()], $visible))->each->toBe([$column, $query])
        ->and($column->hasSummary($query))->toBeTrue()
        ->and(TextColumn::make('position')->summarize(Count::make()->hidden())->hasSummary($query))->toBeFalse();
});

it("answers the page row, the all-table row and a group's row from one summarizer, each over its own records", function (): void {
    $this->seed(new LarderJar('Apricot', seal: JarSeal::Wax, position: 1), new LarderJar('Bramble', seal: JarSeal::Screw, position: 2), new LarderJar('Cherry', seal: JarSeal::Wax, position: 4));

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly'], 'tableGrouping' => 'seal'])->render()->crawler();

    expect($this::textSummaries($crawler->filter(Selectors::groupSummaryRow('screw'))))->toBe(['Sum 2'])
        ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('page'))))->toBe(['Sum 3'])
        ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('all'))))->toBe(['Sum 7']);
});
