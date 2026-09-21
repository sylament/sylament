<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Roster;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Seat;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\Relationship;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The relationship arm of the write-path host: the created record is a derived
 * identity whose identifier association is the relationship itself, which is
 * the only shape where the order of attach and persist is observable.
 */
#[AsLiveComponent(name: 'RelationshipCreateHost', template: '@ActionsFixtures/components/RelationshipCreateHost.html.twig')]
class RelationshipCreateHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    #[LiveProp]
    public int $rosterId = 0;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return class-string|null
     */
    public function getDefaultActionRecordClass(Action $action): ?string
    {
        return Seat::class;
    }

    public function getDefaultActionRelationship(Action $action): ?Relationship
    {
        $roster = $this->entityManager->find(Roster::class, $this->rosterId);

        return $roster === null ? null : new Relationship($roster, 'seats');
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function createSeatAction(): Action
    {
        return CreateAction::make('createSeat')
            ->schema([
                ActionSchemaField::make('code'),
                ActionSchemaField::make('label'),
            ]);
    }
}
