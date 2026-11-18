<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\RelationManagers\RelationGroup;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Garden;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditGarden;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListGardens;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\HiddenRowsRelationManager;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\SeedlingsRelationManager;
use Sylament\Tests\Fixtures\Panels\Resources\RelationManagers\TaggedPlantsRelationManager;

/**
 * A garden whose record page registers, under string keys, every entry a resource can: a manager
 * by its class, a manager no record shows, a configuration naming a mount prop, a group of two
 * managers whose badge, colour and tooltip read the garden, and a group holding only the hidden
 * manager.
 */
final class GardenResource extends Resource
{
    protected static ?string $recordClass = Garden::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name'),
        ]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListGardens::route('/'),
            'edit' => EditGarden::route('/{record}/edit'),
        ];
    }

    public function getRelations(): array
    {
        return [
            'seedlings' => SeedlingsRelationManager::class,
            'rows' => HiddenRowsRelationManager::class,
            'plants' => TaggedPlantsRelationManager::make(['tag' => 'B']),
            'beds' => RelationGroup::make('Beds', [SeedlingsRelationManager::class, TaggedPlantsRelationManager::class])
                ->badge(static fn (Garden $garden): int => $garden->seedlings->count())
                ->badgeColor(static fn (?string $badge): string => $badge === '1' ? 'warning' : 'gray')
                ->badgeTooltip(static fn (?string $badge): string => "{$badge} sown")
                ->icon('heroicons:rectangle-stack'),
            'fallow' => RelationGroup::make('Fallow', [HiddenRowsRelationManager::class]),
        ];
    }
}
