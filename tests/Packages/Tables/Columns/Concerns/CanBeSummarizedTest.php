<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Average;
use Sylament\Tables\Columns\Summarizers\Count;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;

it('keeps every summarizer two summarize() calls give a column, rendering the visible ones', function (): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2));

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly', 'summarizedTwice']])->render()->crawler();

    expect($this::textSummaries($crawler->filter(Selectors::summaryRow('all'))))->toBe(['Sum 3', 'Average 1.5']);
});

it('keys a summarizer by the id it is given and the rest by position, across summarize() calls', function (): void {
    $column = TextColumn::make('position')
        ->summarize(Sum::make())
        ->summarize([Average::make('mean'), Count::make()]);

    expect(array_keys($column->getSummarizers()))->toBe([0, 'mean', 1]);
});

it("answers the page row, the all-table row and a group's row from one summarizer, each over its own records", function (): void {
    $this->seed(new LarderJar('Apricot', seal: JarSeal::Wax, position: 1), new LarderJar('Bramble', seal: JarSeal::Screw, position: 2), new LarderJar('Cherry', seal: JarSeal::Wax, position: 4));

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly'], 'tableGrouping' => 'seal'])->render()->crawler();

    expect($this::textSummaries($crawler->filter(Selectors::groupSummaryRow('screw'))))->toBe(['Sum 2'])
        ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('page'))))->toBe(['Sum 3'])
        ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('all'))))->toBe(['Sum 7']);
});
