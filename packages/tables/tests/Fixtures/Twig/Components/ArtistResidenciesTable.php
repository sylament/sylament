<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\State\Relationship;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Artist;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The derived-identity flavour of the same lane: one artist's residencies,
 * whose first identifier half is an association onto the `Handle`-keyed
 * {@see \Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue}. Its record
 * action is what asks the relation layer to resolve a row of that width from a
 * key alone.
 */
#[AsLiveComponent(name: 'ArtistResidenciesTable', template: '@TablesFixtures/components/ArtistResidenciesTable.html.twig')]
class ArtistResidenciesTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
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

                return $artist instanceof Artist ? new Relationship($artist, 'residencies') : null;
            })
            ->columns([TextColumn::make('label')])
            ->recordActions([
                Action::make('promote')
                    ->requiresConfirmation()
                    ->action(function (Residency $record): void {
                        $record->setLabel($record->getLabel() . ' (promoted)');

                        $this->entityManager->flush();
                    }),
            ])
            ->paginated([10]);
    }
}
