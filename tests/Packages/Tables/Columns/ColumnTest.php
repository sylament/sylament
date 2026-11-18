<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\Selectors as SchemasSelectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\ColumnChromeTable;
use Sylament\Tests\Fixtures\Tables\ColumnStateTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('refuses a column made without a name', function (): void {
    $message = 'Column of class [' . TextColumn::class . '] must have a unique name, passed to the [make()] method.';

    expect(fn (): TextColumn => TextColumn::make(''))->toThrow(LogicException::class, $message)
        ->and(fn (): TextColumn => TextColumn::make())->toThrow(LogicException::class, $message);
});

it('hands a closure its column, record, row loop, state, table and live host by name, and the column under any name by its type', function (): void {
    $cell = $this->createLiveComponent(ColumnStateTable::class)->render()->crawler()->filter(Selectors::cell('vase', 'injected'));

    expect($cell->text())->toBe('injected, Vase, row 2, its table, this host, the same column')
        ->and($cell->attr('data-state'))->toBe('injected, Vase, row 2, its table, this host, the same column');
});

it('gives a closure its record only by name, and nothing for a table under another name', function (): void {
    $lamp = new Product('Lamp');

    expect(TextColumn::make('name')->record($lamp)->state(fn (Product $record): string => $record->name)->getState())->toBe('Lamp')
        ->and(fn (): mixed => TextColumn::make('name')->record($lamp)->state(fn (Product $product): string => $product->name)->getState())
        ->toThrow(RuntimeException::class, '[$product] was unresolvable')
        ->and(fn (): mixed => TextColumn::make('name')->state(fn (Table $grid): string => 'Lamp')->getState())
        ->toThrow(RuntimeException::class, '[$grid] was unresolvable');
});

it("marks each cell and header cell with its column's name, kebabbing each dotted segment on its own", function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('author.name')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::headerCell('author-name')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::headerCell('released-at')))->toHaveCount(1)
        ->and($crawler->filter(Selectors::cell('lamp', 'author.name'))->matches('[class~="sy-ta-cell-author.name"]'))->toBeTrue()
        ->and($crawler->filter(Selectors::cell('lamp', 'authorName'))->matches('[class~="sy-ta-cell-author-name"]'))->toBeTrue();
});

it("puts a column's width, growth and header wrapping on its header cell, growing only a column told to and given no width", function (): void {
    $crawler = $this->createLiveComponent(ColumnChromeTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::headerCell('sku'))->attr('style'))->toBe('width: 120px')
        ->and($crawler->filter(Selectors::headerCell('sku'))->matches(SchemasSelectors::GROWABLE))->toBeFalse()
        ->and($crawler->filter(Selectors::headerCell('note'))->matches(SchemasSelectors::GROWABLE))->toBeTrue()
        ->and($crawler->filter(Selectors::headerCell('name'))->matches(SchemasSelectors::GROWABLE))->toBeFalse()
        ->and($crawler->filter(Selectors::headerCell('name'))->attr('style'))->toBeNull()
        ->and($crawler->filter(Selectors::headerCell('summary'))->matches(SupportSelectors::WRAPPED))->toBeTrue()
        ->and($crawler->filter(Selectors::headerCell('name'))->matches(SupportSelectors::WRAPPED))->toBeFalse();
});
