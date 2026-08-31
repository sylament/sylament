<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The manager this record does not show: the only fixture that answers
 * `canViewForRecord()` with `false`, so the page's registration filter has a
 * branch to drop.
 */
#[AsLiveComponent]
final class HiddenTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public static function canViewForRecord(object $ownerRecord, string $pageClass): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }
}
