<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizerMountHost;

it('hands a closure the table and the live component of the column it summarizes', function (): void {
    $host = new SummarizerMountHost();
    $hostTable = Table::make($host);
    $sum = Sum::make()->label(static fn (Table $table, InteractsWithTable $liveComponent): string => $table === $hostTable && $liveComponent === $host ? 'Reached' : 'Missed');

    TextColumn::make('position')->table($hostTable)->summarize($sum);

    $crawler = $this->renderViewComponent($sum->query($this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar')));

    expect($crawler)->toContainElement(Selectors::summarizerLabel('text'), 'Reached');
});

it('refuses to name its column, naming its class, until it summarizes one', function (): void {
    expect(fn (): mixed => Sum::make()->getColumn())
        ->toThrow(LogicException::class, 'The summarizer [' . Sum::class . '] is not attached to a column.');
});
