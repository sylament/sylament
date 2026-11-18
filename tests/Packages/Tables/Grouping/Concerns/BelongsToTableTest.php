<?php

declare(strict_types=1);

use Sylament\Tables\Grouping\Group;
use Sylament\Tables\Table;

it('refuses to hand out the table of a group no table mounted, naming the group, to a closure that asks for it too', function (): void {
    $titledByTable = Group::make('seal')->getTitleFromRecordUsing(static fn (Table $table): string => $table->getDefaultDateDisplayFormat());

    expect(static fn (): Table => Group::make('seal')->getTable())->toThrow(LogicException::class, 'The group [seal] is not mounted to a table.')
        ->and(static fn (): mixed => $titledByTable->getTitle(['seal' => 'wax']))->toThrow(LogicException::class, 'The group [seal] is not mounted to a table.');
});
