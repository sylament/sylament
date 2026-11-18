<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Tables\ColumnStateTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("keys each cell by its record's key, reading an integer key as its string form", function (): void {
    $crawler = $this->createLiveComponent(ColumnStateTable::class)->render()->crawler();

    $column = TextColumn::make('name')->record(['name' => 'Lamp'])->recordKey(7);
    $column->getState();

    expect($crawler->filter(Selectors::cell('7', 'name'))->text())->toBe('Lamp')
        ->and($column->record(['name' => 'Vase'])->recordKey('7')->getState())->toBe('Lamp');
});
