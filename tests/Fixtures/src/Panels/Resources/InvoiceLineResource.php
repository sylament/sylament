<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditInvoiceLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListInvoiceLines;
use Sylament\Tests\Fixtures\Schemas\Entity\InvoiceLine;

/**
 * Its scalar key field is an ordinary input, so a request can write the half of the
 * key that is not the association.
 */
final class InvoiceLineResource extends Resource
{
    protected static ?string $recordClass = InvoiceLine::class;

    protected static ?string $recordTitleAttribute = 'note';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
            'index' => ListInvoiceLines::route('/'),
            'edit' => EditInvoiceLine::route('/{record}/edit'),
        ];
    }
}
