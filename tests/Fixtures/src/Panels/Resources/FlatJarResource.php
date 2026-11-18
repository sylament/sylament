<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Actions\EditAction;
use Sylament\Panels\Resources\Resource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditFlatJar;

/**
 * Jars as a resource nesting under nothing, with an edit page of its own.
 */
final class FlatJarResource extends Resource
{
    protected static ?string $recordClass = LarderJar::class;

    protected static ?string $slug = 'flat-jars';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'edit' => EditFlatJar::route('/{record}/edit'),
        ];
    }
}
