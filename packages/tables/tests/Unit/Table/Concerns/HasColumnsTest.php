<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;

it('caches a column action instance into the flat action map, bound to the table', function (): void {
    $action = Action::make('view');

    $table = Table::make(new TableHost())
        ->columns([TextColumn::make('title')->action($action)]);

    expect($table->getAction('view'))->toBe($action)
        ->and($action->getTable())->toBe($table);
});

it('caches nothing for a column whose action is a closure or absent', function (): void {
    $table = Table::make(new TableHost())
        ->columns([
            TextColumn::make('title')->action(fn (): null => null),
            TextColumn::make('album')->action('handleAlbum'),
            TextColumn::make('id'),
        ]);

    expect($table->getFlatActions())->toBe([]);
});

it('keeps a replaced column set action cached, as Filament does', function (): void {
    $table = Table::make(new TableHost())
        ->columns([TextColumn::make('title')->action(Action::make('view'))])
        ->columns([TextColumn::make('album')->action(Action::make('audit'))]);

    expect(array_keys($table->getFlatActions()))->toBe(['view', 'audit'])
        ->and(array_keys($table->getColumns()))->toBe(['album']);
});
