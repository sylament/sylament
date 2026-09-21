<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Gallery;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditGallery;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListGalleries;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\GalleryImprintsRelationManager;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The owner of the many-to-many onto composite records: a single-identifier
 * resource whose edit page embeds the relation manager that attaches and
 * detaches them. It opts out of global search so the key panel's searchable
 * set stays the one the search suite enumerates.
 */
final class GalleryResource extends Resource
{
    protected static ?string $recordClass = Gallery::class;

    protected static ?string $recordTitleAttribute = 'name';

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

    public function getRelations(): array
    {
        return ['imprints' => GalleryImprintsRelationManager::class];
    }

    public function getPages(): array
    {
        return [
            'index' => ListGalleries::route('/'),
            'edit' => EditGallery::route('/{record}/edit'),
        ];
    }
}
