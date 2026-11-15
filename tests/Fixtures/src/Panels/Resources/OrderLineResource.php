<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditOrderLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListOrderLines;
use Sylament\Tests\Fixtures\Schemas\Entity\OrderLine;

/**
 * Its key fields are ordinary inputs, so a request can write the composite key the
 * record is stored under.
 */
final class OrderLineResource extends Resource
{
    protected static ?string $recordClass = OrderLine::class;

    protected static ?string $recordTitleAttribute = 'note';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('order')->integer(),
            TextInput::make('line')->integer(),
            TextInput::make('note'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('note'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListOrderLines::route('/'),
            'edit' => EditOrderLine::route('/{record}/edit'),
        ];
    }
}
