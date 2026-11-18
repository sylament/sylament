<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Resource;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Seedling;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\CreateGardenSeedling;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditGardenSeedling;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListGardenSeedlings;

/**
 * A garden's seedlings as a resource nested under the garden's, titled by name, whose record
 * query is rooted at its own alias and hides every seedling named `Hidden …`: a searchable,
 * sortable name column with a row action to edit, and a list, create and edit page of its own.
 */
final class GardenSeedlingResource extends Resource
{
    public const string HIDDEN_PREFIX = 'Hidden';

    protected static ?string $recordClass = Seedling::class;

    protected static ?string $parentResource = GardenResource::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function getRecordQuery(): QueryBuilder
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('seedling')
            ->from(Seedling::class, 'seedling')
            ->andWhere('seedling.name NOT LIKE :hidden_seedling')
            ->setParameter('hidden_seedling', self::HIDDEN_PREFIX . '%');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public function getPages(): array
    {
        return [
            'index' => ListGardenSeedlings::route('/'),
            'create' => CreateGardenSeedling::route('/create'),
            'edit' => EditGardenSeedling::route('/{record}/edit'),
        ];
    }
}
