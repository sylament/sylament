<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The paginating manager: two rows a page with the extreme links on, so the
 * partial pulls the first/last chevrons the panels fixture icon set only
 * carries for this case.
 */
#[AsLiveComponent]
final class PaginatedTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $badge = '3';

    protected static ?string $badgeColor = 'danger';

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('title')])
            ->paginated([2])
            ->extremePaginationLinks();
    }
}
