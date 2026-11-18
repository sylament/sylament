<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Allotment;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditAllotment;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListAllotments;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\SeedlingsRelationManager;

/**
 * An allotment, keyed by its garden and a number, whose record page registers one group holding
 * one manager twice.
 */
final class AllotmentResource extends Resource
{
    protected static ?string $recordClass = Allotment::class;

    public function getPages(): array
    {
        return [
            'index' => ListAllotments::route('/'),
            'edit' => EditAllotment::route('/{record}/edit'),
        ];
    }

    public function getRelations(): array
    {
        return [
            'beds' => RelationGroup::make('Beds', [SeedlingsRelationManager::class, SeedlingsRelationManager::class]),
        ];
    }
}
