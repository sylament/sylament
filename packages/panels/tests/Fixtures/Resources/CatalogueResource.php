<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Navigation\NavigationItem;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditCatalogue;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ListCatalogues;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\ManageCatalogueStamps;
use Sylament\Panels\Tests\Fixtures\Resources\RelationManagers\StampsRelationManager;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;

/**
 * The single-identifier owner: its records are the parent of the nested
 * composite resource, the owner a relation manager lists composite records
 * through, and the owner the manage page tables the same association for.
 */
final class CatalogueResource extends Resource
{
    protected static ?string $recordClass = Catalogue::class;

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('title')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function getRelations(): array
    {
        return ['stamps' => StampsRelationManager::class];
    }

    /**
     * @return array<NavigationItem>
     */
    public function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([EditCatalogue::class, ManageCatalogueStamps::class]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListCatalogues::route('/'),
            'edit' => EditCatalogue::route('/{record}/edit'),
            'stamps' => ManageCatalogueStamps::route('/{record}/stamps'),
        ];
    }
}
