<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Tests\Fixtures\Resources\StampResource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * One catalogue's stamps: a single-identifier owner listing composite records,
 * with the three record-writing actions a one-to-many manager carries. It
 * names a related resource, whose URLs resolve the panel the mounting page was
 * requested under rather than one the manager carries itself.
 */
#[AsLiveComponent]
final class StampsRelationManager extends RelationManager
{
    protected static string $relationship = 'stamps';

    protected static ?string $relatedResource = StampResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required(),
            TextInput::make('caption'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('code'), TextColumn::make('caption')])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
