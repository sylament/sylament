<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Terrace;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTerrace;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListTerraces;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\SeedlingsRelationManager;

/**
 * A terrace, keyed by two scalars, whose record page registers one manager in a list.
 */
final class TerraceResource extends Resource
{
    protected static ?string $recordClass = Terrace::class;

    public function getPages(): array
    {
        return [
            'index' => ListTerraces::route('/'),
            'edit' => EditTerrace::route('/{record}/edit'),
        ];
    }

    public function getRelations(): array
    {
        return [SeedlingsRelationManager::class];
    }
}
