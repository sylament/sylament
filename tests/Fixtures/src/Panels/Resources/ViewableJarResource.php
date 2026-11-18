<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Panels\Resources\Resource;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditViewableJar;

/**
 * Jars as a resource nesting under nothing, with one record page registered as both its view and
 * its edit page, and row actions to edit and view, edit declared first.
 */
final class ViewableJarResource extends Resource
{
    protected static ?string $recordClass = LarderJar::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label'),
            ])
            ->recordActions([EditAction::make(), ViewAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'view' => EditViewableJar::route('/{record}'),
            'edit' => EditViewableJar::route('/{record}/edit'),
        ];
    }
}
