<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateObservatory;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditObservatory;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListObservatories;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\TelescopesRelationManager;
use Sylament\Tests\Fixtures\SecondaryEntity\Observatory;

/**
 * Observatories, which only the `secondary` manager maps, with a relation manager for their telescopes.
 */
final class ObservatoryResource extends Resource
{
    protected static ?string $recordClass = Observatory::class;

    protected static ?string $slug = 'observatories';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListObservatories::route('/'),
            'create' => CreateObservatory::route('/create'),
            'edit' => EditObservatory::route('/{record}/edit'),
        ];
    }

    public function getRelations(): array
    {
        return [
            'telescopes' => TelescopesRelationManager::class,
        ];
    }
}
