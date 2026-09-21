<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The same two classes from the inverse side: the composite record owns the
 * join table, so this table reaches its rows through a mapping that names a
 * `mappedBy` — the arm a subselect keyed by the owner alone would list wrong.
 * It declares no action, because what it exists to pin is the row set.
 */
#[AsLiveComponent(name: 'ArtistBilledShortcutsTable', template: '@TablesFixtures/components/ArtistBilledShortcutsTable.html.twig')]
class ArtistBilledShortcutsTable implements InteractsWithTable
{
    use DefaultActionTrait;
    use HasTable;

    #[LiveProp]
    public int $artistId = 1;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $artist = $this->entityManager->find(Artist::class, $this->artistId);

                return $artist instanceof Artist ? new Relationship($artist, 'billedShortcuts') : null;
            })
            ->columns([TextColumn::make('label')])
            ->paginated([10]);
    }
}
