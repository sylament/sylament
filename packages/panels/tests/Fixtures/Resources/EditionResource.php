<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Edition;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditEdition;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListEditions;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The identifier one of whose fields is named `id`: the shape a non-table
 * action mints a partial key for, so {@see EditEdition}'s record-bound header
 * action is where that mismatch surfaces.
 */
final class EditionResource extends Resource
{
    protected static ?string $recordClass = Edition::class;

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('id')->required(),
            TextInput::make('locale')->required(),
            TextInput::make('title'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('locale'),
                TextColumn::make('title'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListEditions::route('/'),
            'edit' => EditEdition::route('/{record}/edit'),
        ];
    }
}
