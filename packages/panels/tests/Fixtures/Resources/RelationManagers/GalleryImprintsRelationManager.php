<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Actions\AttachAction;
use Sylament\Actions\DetachAction;
use Sylament\Actions\DetachBulkAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * One gallery's imprints: a single-identifier owner over a many-to-many whose
 * records are keyed by a pair of strings, carrying the three actions that
 * write the join table. It names no related resource, so it mints no URL and
 * can be mounted by hand on a panel that is not the default one.
 */
#[AsLiveComponent]
final class GalleryImprintsRelationManager extends RelationManager
{
    protected static string $relationship = 'imprints';

    /**
     * Which arm of the bulk detach runs: hydrated records, or the selection's
     * keys alone — the second is the one a composite key has to survive.
     */
    #[LiveProp]
    public bool $fetchesSelectedRecords = true;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('house')->required(),
            TextInput::make('year')->required(),
            TextInput::make('label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('house'), TextColumn::make('year'), TextColumn::make('label')])
            ->headerActions([AttachAction::make()->preloadRecordSelect()])
            ->recordActions([DetachAction::make()])
            ->toolbarActions([DetachBulkAction::make()->fetchSelectedRecords($this->fetchesSelectedRecords)]);
    }
}
