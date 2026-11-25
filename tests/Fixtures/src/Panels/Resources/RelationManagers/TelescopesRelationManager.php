<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\RelationManagers;

use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An observatory's telescopes by name.
 */
#[AsLiveComponent]
final class TelescopesRelationManager extends RelationManager
{
    protected static string $relationship = 'telescopes';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name'),
        ]);
    }
}
