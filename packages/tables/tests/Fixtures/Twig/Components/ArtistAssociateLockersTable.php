<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\AssociateAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
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
 * The associate host for a one-to-many onto a target whose single identifier
 * converts: the column holds `l1`/`l2` and a record mints `L1`/`L2`, so the
 * option keys the modal offers and the keys a submission is resolved against
 * are the same string only when the projected row is keyed through its field's
 * own DBAL type.
 */
#[AsLiveComponent(name: 'ArtistAssociateLockersTable', template: '@TablesFixtures/components/ArtistAssociateLockersTable.html.twig')]
class ArtistAssociateLockersTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    #[LiveProp]
    public int $artistId = 1;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $artist = $this->entityManager->find(Artist::class, $this->artistId);

                return $artist instanceof Artist ? new Relationship($artist, 'keptLockers') : null;
            })
            ->recordTitleAttribute('label')
            ->columns([TextColumn::make('label')])
            ->headerActions([AssociateAction::make()])
            ->paginated([10]);
    }
}
