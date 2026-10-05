<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Relic;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateRelic;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditRelic;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * A composite record nested under a many-to-many parent, whose create page
 * joins the parent's collection before the mapping and before persist().
 */
final class RelicResource extends Resource
{
    protected static ?string $recordClass = Relic::class;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $parentResource = ReliquaryResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('vault'),
            TextInput::make('niche'),
            TextInput::make('label'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('label')]);
    }

    public function getPages(): array
    {
        return [
            'create' => CreateRelic::route('/create'),
            'edit' => EditRelic::route('/{record}/edit'),
        ];
    }
}
