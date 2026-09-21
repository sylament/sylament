<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Colophon;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditColophon;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListColophons;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The single derived identity: the flavour that slips past a
 * composite-identifier guard, because Doctrine reports it as not composite
 * while its one identifier value is a record.
 */
final class ColophonResource extends Resource
{
    protected static ?string $recordClass = Colophon::class;

    protected static ?string $recordTitleAttribute = 'note';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('note'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('note')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    /**
     * @return array<string|array<string>>
     */
    public function getGloballySearchableAttributes(): array
    {
        return ['note'];
    }

    public function getPages(): array
    {
        return [
            'index' => ListColophons::route('/'),
            'edit' => EditColophon::route('/{record}/edit'),
        ];
    }
}
