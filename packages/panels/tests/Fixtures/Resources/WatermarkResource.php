<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Watermark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateWatermark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditWatermark;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListWatermarks;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A record whose constructor takes its two-field key, with both key fields an
 * ordinary required input on both pages.
 */
final class WatermarkResource extends Resource
{
    protected static ?string $recordClass = Watermark::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('mill')->required(),
            TextInput::make('pattern')->required(),
            TextInput::make('note'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('mill'), TextColumn::make('pattern'), TextColumn::make('note')]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListWatermarks::route('/'),
            'create' => CreateWatermark::route('/create'),
            'edit' => EditWatermark::route('/{record}/edit'),
        ];
    }
}
