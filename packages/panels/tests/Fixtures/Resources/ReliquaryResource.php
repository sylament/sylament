<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Reliquary;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListReliquaries;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The many-to-many parent {@see RelicResource} nests under.
 */
final class ReliquaryResource extends Resource
{
    protected static ?string $recordClass = Reliquary::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListReliquaries::route('/'),
        ];
    }
}
