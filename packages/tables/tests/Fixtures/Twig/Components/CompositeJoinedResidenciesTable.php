<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Every depth this epic reaches, on one builder over a derived-identity root:
 * a fetch-joined collection, a sortable relationship count, a relation search
 * across a to-many hop and one across a to-one hop that is itself an
 * identifier field.
 *
 * The query carries no order of its own, so a page's only ordering is the
 * sort under test plus the identifier tiebreak, whose `residency.venue` half
 * is the association path a limit walker cannot rewrite.
 */
#[AsLiveComponent(name: 'CompositeJoinedResidenciesTable', template: '@TablesFixtures/components/CompositeJoinedResidenciesTable.html.twig')]
class CompositeJoinedResidenciesTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->entityManager->createQueryBuilder()
                    ->select('residency', 'residencyShows')
                    ->from(Residency::class, 'residency')
                    ->leftJoin('residency.shows', 'residencyShows'),
            )
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('venue.name')->searchable(),
                TextColumn::make('shows.title')->bulleted()->searchable(),
                TextColumn::make('shows_count')->counts('shows')->sortable(),
            ])
            ->paginated([2]);
    }
}
