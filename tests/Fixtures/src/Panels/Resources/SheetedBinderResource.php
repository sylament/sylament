<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\VettedBinder;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditSheetedBinder;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListSheetedBinders;

/**
 * Binders under a slashed slug and no record title, whose form repeats their sheets' headings through the relationship.
 */
final class SheetedBinderResource extends Resource
{
    protected static ?string $recordClass = VettedBinder::class;

    protected static ?string $slug = 'binders/sheeted';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Repeater::make('sheets')->relationship()->components([
                TextInput::make('heading'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table;
    }

    public function getPages(): array
    {
        return [
            'index' => ListSheetedBinders::route('/'),
            'edit' => EditSheetedBinder::route('/{record}/edit'),
        ];
    }
}
