<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\ColophonLine;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateColophonLine;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditColophonLine;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The second shape: a resource nested under a parent whose identifier is one
 * association and nothing else, which Doctrine reports as not composite. It
 * registers no index, so its delete redirect is the one that falls back to a
 * page of the parent.
 */
final class ColophonLineResource extends Resource
{
    protected static ?string $recordClass = ColophonLine::class;

    protected static ?string $recordTitleAttribute = 'text';

    protected static ?string $parentResource = ColophonResource::class;

    protected static bool $isGloballySearchable = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('text')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('text')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateColophonLine::route('/create'),
            'edit' => EditColophonLine::route('/{record}/edit'),
        ];
    }
}
