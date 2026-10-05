<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Mill;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListMills;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The parent {@see CountermarkResource} nests under: a listing and nothing
 * else, so a nested create lands back on it.
 */
final class MillResource extends Resource
{
    protected static ?string $recordClass = Mill::class;

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
            'index' => ListMills::route('/'),
        ];
    }
}
