<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\RelationManagers;

use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\Resources\RelationManagers\RelationManager;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Project;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A manager whose tabs name the owner: it can only build them once the owner
 * key has landed, so it pins that the tabs are built after the mount data is
 * assigned rather than while the mount is still running.
 */
#[AsLiveComponent]
final class OwnerNamedTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $owner = $this->getOwnerRecord();

        return [
            'all' => Tab::make($owner instanceof Project ? $owner->getName() : null),
            'first' => Tab::make()
                ->query(fn (QueryBuilder $query): QueryBuilder => $query->andWhere('record.position = 1')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }
}
