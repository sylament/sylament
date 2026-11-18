<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\CharmSatchel;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListManyToManySatchels;

/**
 * The many-to-many parent {@see UnvettedKeyCharmResource} nests under, listed and nothing more.
 */
final class ManyToManySatchelResource extends Resource
{
    protected static ?string $recordClass = CharmSatchel::class;

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListManyToManySatchels::route('/'),
        ];
    }
}
