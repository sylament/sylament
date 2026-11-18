<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Plant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateBarePottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateCustomRowPottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateGroupedRowPottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreatePottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditPottedPlant;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListPottedPlants;

/**
 * Plants, a record built with no constructor arguments under a generated key, whose one field opens on a default; beside
 * its create page it mounts three variants a create page's form row and fill take.
 */
final class PottedPlantResource extends Resource
{
    public const string DEFAULT_NAME = 'Untitled';

    protected static ?string $recordClass = Plant::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->default(self::DEFAULT_NAME),
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
            'index' => ListPottedPlants::route('/'),
            'create' => CreatePottedPlant::route('/create'),
            'edit' => EditPottedPlant::route('/{record}/edit'),
            'create-bare' => CreateBarePottedPlant::route('/create-bare'),
            'create-custom-row' => CreateCustomRowPottedPlant::route('/create-custom-row'),
            'create-grouped-row' => CreateGroupedRowPottedPlant::route('/create-grouped-row'),
        ];
    }
}
