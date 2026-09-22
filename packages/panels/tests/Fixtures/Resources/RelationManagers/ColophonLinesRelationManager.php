<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The same arm one flavour over: an owner whose identifier is one association
 * and nothing else. Registered on no page.
 */
#[AsLiveComponent]
final class ColophonLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('text')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            ->columns([TextColumn::make('text')])
            ->headerActions([CreateAction::make()])
            ->recordActions([DeleteAction::make()]);
    }
}
