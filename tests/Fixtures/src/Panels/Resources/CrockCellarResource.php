<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListCrockCellars;

/**
 * The larder {@see ShelflessCrockResource} nests under, listed and nothing more.
 */
final class CrockCellarResource extends Resource
{
    protected static ?string $recordClass = Larder::class;

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListCrockCellars::route('/'),
        ];
    }
}
