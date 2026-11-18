<?php

declare(strict_types=1);

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

it('refuses to hand a closure the table of a column mounted to none', function (): void {
    expect(fn (): mixed => TextColumn::make('name')->state(fn (Table $table): string => 'Lamp')->getState())
        ->toThrow(LogicException::class, 'The column [name] is not mounted to a table.');
});
