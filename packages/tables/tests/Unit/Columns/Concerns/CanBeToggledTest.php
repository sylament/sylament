<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;

it('is neither toggleable nor toggled-hidden by default', function (): void {
    $column = TextColumn::make('title');
    Table::make(new TableHost())->columns([$column]);

    expect($column->isToggleable())->toBeFalse()
        ->and($column->isToggledHiddenByDefault())->toBeFalse()
        ->and($column->isToggledHidden())->toBeFalse();
});

it('resets the hidden-by-default flag when toggleable() is called after it', function (): void {
    expect(TextColumn::make('title')->toggledHiddenByDefault()->toggleable()->isToggledHiddenByDefault())->toBeFalse()
        ->and(TextColumn::make('title')->toggleable(isToggledHiddenByDefault: true)->isToggledHiddenByDefault())->toBeTrue()
        ->and(TextColumn::make('title')->toggledHiddenByDefault(fn (): bool => true)->isToggledHiddenByDefault())->toBeTrue();
});

it('is never toggleable once hidden', function (): void {
    $column = TextColumn::make('title')->toggleable()->hidden();
    Table::make(new TableHost())->columns([$column]);

    expect($column->isToggleable())->toBeFalse()
        ->and($column->isToggledHidden())->toBeFalse();
});

it('asks the host only for a toggleable column', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table->columns([
        TextColumn::make('plays')->toggleable(),
        TextColumn::make('title'),
    ]));
    $host->tableColumns = [
        ['type' => 'column', 'name' => 'plays', 'isToggled' => false, 'isToggledHiddenByDefault' => false],
        ['type' => 'column', 'name' => 'title', 'isToggled' => true, 'isToggledHiddenByDefault' => null],
    ];

    expect($host->getTable()->getColumn('plays')?->isToggledHidden())->toBeTrue()
        ->and($host->getTable()->getColumn('title')?->isToggledHidden())->toBeFalse();
});
