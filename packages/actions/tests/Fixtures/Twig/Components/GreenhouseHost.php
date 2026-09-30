<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\AssignedNote;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Bulb;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Cover;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Graft;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Membership;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Planter;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Roster;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Seedling;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Trellis;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Identity\RecordKey;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The replicate host over an assigned identifier of every flavour: the record
 * class is a prop, a key resolves through the codec, and each arm of the
 * action is its own action — a modal naming the key fields it declares for
 * the class, a modal naming none, no modal, a hook that keys the replica, and
 * a keyed modal whose hook re-keys the copy.
 */
#[AsLiveComponent(name: 'GreenhouseHost', template: '@ActionsFixtures/components/GreenhouseHost.html.twig')]
class GreenhouseHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * `Planter` declares one half of its key on purpose, so its keyed modal
     * leaves the other half blank.
     */
    private const array KEY_FIELDS = [
        AssignedNote::class => ['id'],
        Bulb::class => ['name'],
        Cover::class => [],
        Graft::class => ['stage'],
        Membership::class => ['role'],
        Planter::class => ['bed'],
        Redirect::class => ['host', 'path'],
        Seedling::class => ['tag'],
        Ticket::class => ['code'],
        Trellis::class => [],
    ];

    private const array PAYLOAD_FIELDS = [
        AssignedNote::class => ['title'],
        Bulb::class => ['bloom'],
        Cover::class => ['note'],
        Graft::class => ['label'],
        Membership::class => ['label'],
        Planter::class => ['crop'],
        Redirect::class => ['target'],
        Seedling::class => ['variety'],
        Ticket::class => ['title'],
        Trellis::class => ['label'],
    ];

    /**
     * @var class-string
     */
    #[LiveProp]
    public string $recordClass = Membership::class;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->resolveRecordUsing(fn (string $key): ?object => $this->entityManager->find(
            $this->recordClass,
            RecordKey::toIdentifier($this->entityManager, $this->recordClass, $key) ?? [],
        ));
    }

    /**
     * @return class-string
     */
    public function getDefaultActionRecordClass(Action $action): string
    {
        return $this->recordClass;
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function replicateAction(): Action
    {
        return ReplicateAction::make()
            ->schema(fn (): array => array_map(
                ActionSchemaField::make(...),
                [...self::KEY_FIELDS[$this->recordClass], ...self::PAYLOAD_FIELDS[$this->recordClass]],
            ));
    }

    public function replicatePayloadAction(): Action
    {
        return ReplicateAction::make('replicatePayload')
            ->schema(fn (): array => array_map(ActionSchemaField::make(...), self::PAYLOAD_FIELDS[$this->recordClass]));
    }

    public function replicateBareAction(): Action
    {
        return ReplicateAction::make('replicateBare');
    }

    public function replicateHookedAction(): Action
    {
        return ReplicateAction::make('replicateHooked')
            ->beforeReplicaSaved(function (object $replica): void {
                if ($replica instanceof Membership) {
                    $replica->setRole('viewer');
                }
            });
    }

    /**
     * A keyed modal whose hook sets the key after the modal has mapped one.
     */
    public function replicateKeyedHookedAction(): Action
    {
        return ReplicateAction::make('replicateKeyedHooked')
            ->schema(fn (): array => array_map(
                ActionSchemaField::make(...),
                [...self::KEY_FIELDS[$this->recordClass], ...self::PAYLOAD_FIELDS[$this->recordClass]],
            ))
            ->beforeReplicaSaved(function (object $replica): void {
                if ($replica instanceof Membership) {
                    $replica->setRole('viewer');
                }
            });
    }

    /**
     * A bare replicate over a key made only of associations that follows the
     * taken-key refusal's advice: the roster is left out and the hook sets
     * another one.
     */
    public function replicateRehomedAction(): Action
    {
        return ReplicateAction::make('replicateRehomed')
            ->excludeAttributes(['roster'])
            ->beforeReplicaSaved(function (object $replica): void {
                if ($replica instanceof Trellis) {
                    $replica->setRoster($this->entityManager->find(Roster::class, 2));
                }
            });
    }
}
