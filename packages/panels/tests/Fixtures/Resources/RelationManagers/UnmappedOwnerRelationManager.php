<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A manager whose owner is whatever object a test hands it, registered on no
 * page: the way to put an owner Doctrine maps nothing for in front of a
 * container-built host.
 */
#[AsLiveComponent]
final class UnmappedOwnerRelationManager extends RelationManager
{
    protected static string $relationship = 'marks';

    private object $owner;

    public function ownedBy(object $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getOwnerRecord(): object
    {
        return $this->owner;
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('label')]);
    }
}
