<?php

declare(strict_types=1);

use Sylament\Tables\Columns\ViewColumn;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\TemplatedCellTable;

it('renders its cell from its template, handed the column, its template data and nothing named record or state', function (): void {
    $cell = $this->createLiveComponent(TemplatedCellTable::class)->render()->crawler()->filter(Selectors::cell('io', 'orbit'));

    expect($cell)->toContainElement('[data-variable="record"]', 'Io')
        ->toContainElement('[data-variable="state"]', '1.77 days')
        ->toContainElement('[data-variable="planet"]', 'Jupiter')
        ->toContainElement('[data-variable="record-variable"]', 'undefined')
        ->toContainElement('[data-variable="state-variable"]', 'undefined');
});

it('lands its template inside the cell the table opens and aligns, leaving its own alignment for the template to read, even when the template prints nothing', function (): void {
    $crawler = $this->createLiveComponent(TemplatedCellTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::cell('io', 'orbit'))->matches(SupportSelectors::align('end')))->toBeTrue()
        ->and($crawler->filter(Selectors::cell('io', 'orbit') . ' ' . Selectors::CELL_WRAPPER))->toContainElement('[data-template="orbit"] [data-variable="alignment"]', 'sy-align-end')
        ->and($crawler->filter(Selectors::cell('io', 'blank') . ' ' . Selectors::CELL_WRAPPER)->text())->toBe('');
});

it('refuses to name a template it was never given, naming its class', function (): void {
    expect(fn (): string => ViewColumn::make('orbit')->getTemplate())
        ->toThrow(LogicException::class, 'The [Sylament\Tables\Columns\ViewColumn] column has no template. Call template() on it, or declare a $template property on a subclass.');
});
