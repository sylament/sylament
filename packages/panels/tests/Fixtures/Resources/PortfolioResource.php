<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Portfolio;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditPortfolio;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListPortfolios;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * One form over every relationship arm a save writes: two relationship
 * repeaters — one keyed by the owner and a typed code, one by the database —
 * and a singular group over a related record with an assigned key, its title
 * mapped before its serial.
 */
final class PortfolioResource extends Resource
{
    protected static ?string $recordClass = Portfolio::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title'),
            Repeater::make('sheets')->relationship()->components([
                TextInput::make('code'),
                TextInput::make('caption'),
            ]),
            Repeater::make('clippings')->relationship()->components([
                TextInput::make('text'),
            ]),
            Group::make([
                TextInput::make('title'),
                TextInput::make('serial'),
            ])->relationship('seal'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListPortfolios::route('/'),
            'edit' => EditPortfolio::route('/{record}/edit'),
        ];
    }
}
