<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'Tables:AlignedColumnsTable', template: '@Fixtures/tables/table.html.twig')]
class AlignedColumnsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [
                ['__key' => 'lamp', 'name' => 'Lamp', 'sku' => 'L-1', 'note' => 'Fragile'],
            ])
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('sku')->verticallyAlignStart(),
                TextColumn::make('note')->verticalAlignment('my-align'),
            ]);
    }
}
