<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\RelationManagers;

use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Garden;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * A garden's plants under a title of its own, with a mount prop a registration sets over the
 * manager's default and two tabs built from it and from the garden: the first keeps the plants
 * whose name starts with the tag.
 */
#[AsLiveComponent]
final class TaggedPlantsRelationManager extends RelationManager
{
    protected static string $relationship = 'plants';

    protected static ?string $title = 'Planted';

    #[LiveProp]
    public ?string $tag = null;

    public static function getDefaultProperties(): array
    {
        return ['tag' => 'M'];
    }

    public function getTabs(): array
    {
        $garden = $this->getOwnerRecord();

        if (!$garden instanceof Garden) {
            throw new LogicException("The tagged plants manager lists a garden's plants.");
        }

        $tag = $this->tag;

        return [
            'tagged' => Tab::make("Starting {$tag}")
                ->query(static fn (QueryBuilder $query): QueryBuilder => $query
                    ->andWhere($query->getRootAliases()[0] . '.name LIKE :tag')
                    ->setParameter('tag', "{$tag}%")),
            'all' => Tab::make("All of {$garden->name}"),
        ];
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name'),
        ]);
    }
}
