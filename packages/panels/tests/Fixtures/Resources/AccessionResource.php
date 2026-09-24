<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Accession;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditAccession;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListAccessions;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A single identifier a custom generator fills, exposed as an ordinary input
 * on the edit page.
 */
final class AccessionResource extends Resource
{
    protected static ?string $recordClass = Accession::class;

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
            'index' => ListAccessions::route('/'),
            'edit' => EditAccession::route('/{record}/edit'),
        ];
    }
}
