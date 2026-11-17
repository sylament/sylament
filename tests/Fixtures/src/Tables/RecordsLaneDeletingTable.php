<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of array rows with no query, whose toolbar deletes the selection by fetching
 * it (`delete`) and by key (`deleteByKey`).
 */
#[AsLiveComponent(name: 'Tables:RecordsLaneDeletingTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class RecordsLaneDeletingTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                ['__key' => 'lamp', 'name' => 'Lamp'],
                ['__key' => 'vase', 'name' => 'Vase'],
            ])
            ->columns([
                TextColumn::make('name'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                DeleteBulkAction::make('deleteByKey')->fetchSelectedRecords(false),
            ]);
    }
}
