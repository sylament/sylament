<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Concerns\TableHost;

it('drops the toggle memo and the table visible-column cache when the column set is replaced', function (): void {
    $host = new TableHost(fn (Table $table): Table => $table->columns([
        TextColumn::make('title'),
        TextColumn::make('plays')->toggleable(),
    ]));
    $host->tableColumns = [
        ['type' => 'column', 'name' => 'plays', 'isToggled' => true, 'isToggledHiddenByDefault' => false],
    ];

    expect($host->isTableColumnToggledHidden('plays'))->toBeFalse()
        ->and(array_keys($host->getTable()->getVisibleColumns()))->toBe(['title', 'plays']);

    new ReflectionMethod($host, 'setTableColumns')->invoke($host, [
        ['type' => 'column', 'name' => 'plays', 'isToggled' => false, 'isToggledHiddenByDefault' => false],
    ]);

    expect($host->isTableColumnToggledHidden('plays'))->toBeTrue()
        ->and(array_keys($host->getTable()->getVisibleColumns()))->toBe(['title']);
});
