<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Shelfmark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateShelfmark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditShelfmark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListShelfmarks;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A single assigned string identifier with its key field an ordinary input on
 * both pages, so a create can land on a taken key and an edit can move it.
 */
final class ShelfmarkResource extends Resource
{
    protected static ?string $recordClass = Shelfmark::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required(),
            TextInput::make('label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('code'), TextColumn::make('label')])
            ->recordActions([EditAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListShelfmarks::route('/'),
            'create' => CreateShelfmark::route('/create'),
            'edit' => EditShelfmark::route('/{record}/edit'),
        ];
    }
}
