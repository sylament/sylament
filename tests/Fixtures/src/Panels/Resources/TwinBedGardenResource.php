<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditTwinBedGarden;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListTwinBedGardens;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\SeedlingsRelationManager;

/**
 * A garden whose record page registers two groups, each holding one manager under the same
 * in-group key.
 */
final class TwinBedGardenResource extends Resource
{
    protected static ?string $recordClass = Garden::class;

    public function getPages(): array
    {
        return [
            'index' => ListTwinBedGardens::route('/'),
            'edit' => EditTwinBedGarden::route('/{record}/edit'),
        ];
    }

    public function getRelations(): array
    {
        return [
            'east' => RelationGroup::make('East', [SeedlingsRelationManager::class]),
            'west' => RelationGroup::make('West', [SeedlingsRelationManager::class]),
        ];
    }
}
