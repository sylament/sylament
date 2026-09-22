<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Variant;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\CreateEditionVariant;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditEditionVariant;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListEditionVariants;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The nested vertical under the parent whose first identifier field is named
 * `id`: the flavour where a parent segment spelled `5` is half a key and has
 * to miss rather than resolve.
 */
final class EditionVariantResource extends Resource
{
    protected static ?string $recordClass = Variant::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $parentResource = EditionResource::class;

    protected static bool $isGloballySearchable = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('name')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListEditionVariants::route('/'),
            'create' => CreateEditionVariant::route('/create'),
            'edit' => EditEditionVariant::route('/{record}/edit'),
        ];
    }
}
