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
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Residency;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\ResidencyRider;
use Sylament\Tables\Tests\Fixtures\Doctrine\Entity\Venue;
use Sylament\Tables\Tests\Fixtures\Doctrine\Handle;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The same shape as {@see CompositeOwnerRelationshipTableNotes} over an owner
 * whose first identifier half is an association rather than a scalar, so the
 * owner has to be looked up through its own target before the relation can be
 * scoped to it — and a record of that relation acted on from a cold identity
 * map.
 */
#[AsLiveComponent(name: 'CompositeOwnerRelationshipTableRiders', template: '@TablesFixtures/components/CompositeOwnerRelationshipTableRiders.html.twig')]
class CompositeOwnerRelationshipTableRiders implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public string $ownerVenue = 'north.hall';

    #[LiveProp]
    public string $ownerSeason = 'summer-2026';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship(function (): ?Relationship {
                $venue = $this->entityManager->find(Venue::class, new Handle($this->ownerVenue));

                $residency = $venue instanceof Venue
                    ? $this->entityManager->find(Residency::class, ['venue' => $venue, 'season' => $this->ownerSeason])
                    : null;

                return $residency instanceof Residency ? new Relationship($residency, 'riders') : null;
            })
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('position'),
            ])
            ->recordActions([
                Action::make('promote')
                    ->requiresConfirmation()
                    ->action(function (ResidencyRider $record): void {
                        $record->setName($record->getName() . ' (promoted)');

                        $this->entityManager->flush();
                    }),
            ])
            ->paginated([10]);
    }
}
