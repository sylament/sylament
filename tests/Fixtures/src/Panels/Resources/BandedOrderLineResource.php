<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditBandedOrderLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListBandedOrderLines;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ViewBandedOrderLine;

/**
 * Composite-keyed {@see OrderLine}s titled by their note, whose record pages show a band of the edit page and a page
 * registered as the view page.
 */
final class BandedOrderLineResource extends Resource
{
    protected static ?string $recordClass = OrderLine::class;

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
            'index' => ListBandedOrderLines::route('/'),
            'edit' => EditBandedOrderLine::route('/{record}/edit'),
            'view' => ViewBandedOrderLine::route('/{record}'),
        ];
    }

    public function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([EditBandedOrderLine::class, ViewBandedOrderLine::class]);
    }
}
