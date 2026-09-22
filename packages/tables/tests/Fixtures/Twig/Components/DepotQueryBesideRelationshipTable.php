<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DissociateBulkAction;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Depot;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Gauge;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The one shape whose by-key keys are not owner-scoped: a table declaring
 * `query()` beside `relationship()`, where the query wins and
 * {@see Table::isRelationshipBaseQuery()} answers false. The query selects the
 * single-table root, so a plain {@see Gauge} the relationship query would never
 * have listed is plucked, while the action still dissociates the leaf
 * collection's mapped-by.
 */
#[AsLiveComponent(name: 'DepotQueryBesideRelationshipTable', template: '@TablesFixtures/components/DepotQueryBesideRelationshipTable.html.twig')]
class DepotQueryBesideRelationshipTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $owner = $this->entityManager->find(Depot::class, 1);

                return $owner === null ? null : new Relationship($owner, 'dials');
            })
            ->query($this->entityManager->createQueryBuilder()->select('gauge')->from(Gauge::class, 'gauge'))
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->toolbarActions([DissociateBulkAction::make()->fetchSelectedRecords(false)])
            ->paginated([10]);
    }
}
