<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\ConstructorCodedBadge;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateLowercasedBadge;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListLowercasedBadges;

/**
 * Badges, records whose constructor takes their key, created through a `makeRecord()` that lower-cases it, and listed with no edit page.
 */
final class LowercasedBadgeResource extends Resource
{
    protected static ?string $recordClass = ConstructorCodedBadge::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code'),
            TextInput::make('label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListLowercasedBadges::route('/'),
            'create' => CreateLowercasedBadge::route('/create'),
        ];
    }
}
