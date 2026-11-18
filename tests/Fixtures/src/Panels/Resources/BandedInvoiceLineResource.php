<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditBandedInvoiceLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListBandedInvoiceLines;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ViewBandedInvoiceLine;

/**
 * Composite-keyed {@see InvoiceLine}s titled by their note, whose record pages show a band of the edit page and a page
 * registered as the view page.
 */
final class BandedInvoiceLineResource extends Resource
{
    protected static ?string $recordClass = InvoiceLine::class;

    protected static ?string $recordTitleAttribute = 'note';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
            'index' => ListBandedInvoiceLines::route('/'),
            'edit' => EditBandedInvoiceLine::route('/{record}/edit'),
            'view' => ViewBandedInvoiceLine::route('/{record}'),
        ];
    }

    public function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([EditBandedInvoiceLine::class, ViewBandedInvoiceLine::class]);
    }
}
