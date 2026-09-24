<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Reprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditReprint;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListReprints;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A database-generated identifier exposed as an ordinary input on the edit
 * page — the shape whose changed key the database would never have written.
 */
final class ReprintResource extends Resource
{
    protected static ?string $recordClass = Reprint::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('id')->required(),
            TextInput::make('label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('id'), TextColumn::make('label')])
            ->recordActions([EditAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListReprints::route('/'),
            'edit' => EditReprint::route('/{record}/edit'),
        ];
    }
}
