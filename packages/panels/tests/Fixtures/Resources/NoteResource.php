<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\ViewAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Note;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListNotes;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The modal-mode resource: it registers an index page and nothing else, so
 * every prebuilt record action on it falls through the url hook and mounts a
 * modal instead of linking to a page it does not have.
 */
final class NoteResource extends Resource
{
    protected static ?string $recordClass = Note::class;

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            TextInput::make('body'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('title')])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                ReplicateAction::make()->schema([TextInput::make('title')->required()]),
                DeleteAction::make(),
            ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListNotes::route('/'),
        ];
    }
}
