<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Shortcut;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * One artist's shortcuts: a relation-scoped table over the owning side of a
 * many-to-many onto a scalar-pair target, carrying every surface the lane has
 * to reach at that width — the row key, a confirming record action, both
 * selection strategies, the relation the aggregate column counts and the
 * relation search narrows, and — under `joinsHits` — a fetch join of that same
 * collection on a page of one, which is the two-step keyed page — and, under
 * `hitsAlias: 'hits'`, the same fetch join under the searchable column's own
 * head, which a search refuses.
 *
 * `#[ORM\OrderBy(['label' => SortDirection::Ascending])]` on `Artist::$shortcuts` is what gives
 * the table a default sort with no `defaultSort()` of its own, and the seed
 * labels run against the identifier order on purpose.
 */
#[AsLiveComponent(name: 'ArtistShortcutsTable', template: '@TablesFixtures/components/ArtistShortcutsTable.html.twig')]
class ArtistShortcutsTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public int $artistId = 1;

    #[LiveProp]
    public bool $joinsHits = false;

    #[LiveProp]
    public string $hitsAlias = 'shortcutHit';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        $table
            ->relationship(function (): ?Relationship {
                $artist = $this->entityManager->find(Artist::class, $this->artistId);

                return $artist instanceof Artist ? new Relationship($artist, 'shortcuts') : null;
            })
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('hits.agent')->bulleted()->searchable(),
                TextColumn::make('hits_count')->counts('hits')->sortable(),
            ])
            ->recordActions([
                Action::make('promote')
                    ->requiresConfirmation()
                    ->action(function (Shortcut $record): void {
                        $record->setLabel($record->getLabel() . ' (promoted)');

                        $this->entityManager->flush();
                    }),
            ])
            ->toolbarActions([DeleteBulkAction::make()->fetchSelectedRecords(false)]);

        if (!$this->joinsHits) {
            return $table->paginated([10]);
        }

        $alias = $this->hitsAlias;

        return $table
            ->modifyQueryUsing(static fn (QueryBuilder $query): QueryBuilder => $query->addSelect($alias)->leftJoin('record.hits', $alias))
            ->paginated([1]);
    }
}
