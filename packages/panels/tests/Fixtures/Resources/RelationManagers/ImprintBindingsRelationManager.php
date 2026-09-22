<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Actions\AssociateAction;
use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\DissociateAction;
use Sylament\Actions\DissociateBulkAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The one-to-many arm under a scalar-pair owner, carrying every write verb the
 * arm has: create, associate, edit, dissociate, delete and a bulk dissociate
 * over hydrated records.
 */
#[AsLiveComponent]
final class ImprintBindingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bindings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([TextColumn::make('title')])
            ->headerActions([CreateAction::make(), AssociateAction::make()->preloadRecordSelect()])
            ->recordActions([EditAction::make(), DissociateAction::make(), DeleteAction::make()])
            ->toolbarActions([DissociateBulkAction::make()]);
    }
}
