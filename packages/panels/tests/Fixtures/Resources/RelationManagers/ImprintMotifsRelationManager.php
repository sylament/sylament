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
 * The many-to-many arm under a scalar-pair owner — the side of the join table
 * that carries one column per identifier field. The bulk detach's arm is a
 * wire prop because the by-key one is unreachable while it defaults to
 * fetching, and the by-key one is where an owner's width is read back.
 */
#[AsLiveComponent]
final class ImprintMotifsRelationManager extends RelationManager
{
    protected static string $relationship = 'motifs';

    /**
     * Which arm of the bulk detach runs: hydrated records, or the selection's
     * keys alone.
     */
    #[LiveProp]
    public bool $fetchesSelectedRecords = true;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([TextColumn::make('name')])
            ->headerActions([AttachAction::make()->preloadRecordSelect()])
            ->recordActions([DetachAction::make()])
            ->toolbarActions([DetachBulkAction::make()->fetchSelectedRecords($this->fetchesSelectedRecords)]);
    }
}
