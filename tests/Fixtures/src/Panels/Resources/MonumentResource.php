<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Monument;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListMonuments;

/**
 * The monument {@see MonumentPlaqueResource} nests under, listed and nothing more.
 */
final class MonumentResource extends Resource
{
    protected static ?string $recordClass = Monument::class;

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListMonuments::route('/'),
        ];
    }
}
