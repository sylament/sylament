<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\EditAction;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Membership;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Identity\RecordKey;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Record-key rules outside a create, over three assigned keys — a scalar pair,
 * an association beside a scalar, a single string: a replicate whose modal
 * opens on the original's key, an edit whose hook moves a key the modal never
 * shows, and a key field locked with `disabledOn('edit')` under a plain edit, a
 * renamed edit and a replicate.
 */
#[AsLiveComponent(name: 'CupboardKeyHost', template: '@ActionsFixtures/components/CupboardKeyHost.html.twig')]
class CupboardKeyHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    private const array KEY_FIELDS = [
        Redirect::class => ['host', 'path'],
        Membership::class => ['role'],
        Ticket::class => ['code'],
    ];

    private const array PAYLOAD_FIELDS = [
        Redirect::class => ['target'],
        Membership::class => ['label'],
        Ticket::class => ['title'],
    ];

    /**
     * @var class-string
     */
    #[LiveProp]
    public string $recordClass = Redirect::class;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->resolveRecordUsing(fn (string $key): ?object => $entityManager->find(
            $this->recordClass,
            RecordKey::toIdentifier($entityManager, $this->recordClass, $key) ?? [],
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
            ->schema(fn (): array => $this->getFields([...self::KEY_FIELDS[$this->recordClass], ...self::PAYLOAD_FIELDS[$this->recordClass]]));
    }

    /**
     * The key derived by the hook, after the modal has mapped the original's.
     */
    public function replicateDerivedAction(): Action
    {
        return ReplicateAction::make('replicateDerived')
            ->schema(fn (): array => $this->getFields([...self::KEY_FIELDS[$this->recordClass], ...self::PAYLOAD_FIELDS[$this->recordClass]]))
            ->beforeReplicaSaved(static function (object $replica): void {
                match (true) {
                    $replica instanceof Redirect => $replica->setPath($replica->getPath() . '-copy'),
                    $replica instanceof Membership => $replica->setRole($replica->getRole() . '-copy'),
                    $replica instanceof Ticket => $replica->setCode($replica->getCode() . '-copy'),
                    default => null,
                };
            });
    }

    /**
     * A payload-only modal whose before() hook moves a key half.
     */
    public function editMovedAction(): Action
    {
        return EditAction::make('editMoved')
            ->schema(fn (): array => $this->getFields(self::PAYLOAD_FIELDS[$this->recordClass]))
            ->before(static function (object $record): void {
                match (true) {
                    $record instanceof Redirect => $record->setPath('/moved'),
                    $record instanceof Membership => $record->setRole('moved'),
                    $record instanceof Ticket => $record->setCode('T-moved'),
                    default => null,
                };
            });
    }

    /**
     * A plain edit modal whose key fields are `disabledOn('edit')`.
     */
    public function editAction(): Action
    {
        return EditAction::make()
            ->schema(fn (): array => $this->getLockedKeyFields());
    }

    /**
     * The same modal under another name, which is another operation.
     */
    public function editRenamedAction(): Action
    {
        return EditAction::make('editRenamed')
            ->schema(fn (): array => $this->getLockedKeyFields());
    }

    /**
     * A replicate modal over the fields an edit locks.
     */
    public function replicateLockedAction(): Action
    {
        return ReplicateAction::make('replicateLocked')
            ->schema(fn (): array => $this->getLockedKeyFields());
    }

    /**
     * @return list<ActionSchemaField>
     */
    private function getLockedKeyFields(): array
    {
        return [
            ...array_map(static fn (string $name): ActionSchemaField => ActionSchemaField::make($name)->disabledOn('edit'), self::KEY_FIELDS[$this->recordClass]),
            ...$this->getFields(self::PAYLOAD_FIELDS[$this->recordClass]),
        ];
    }

    /**
     * @param list<string> $names
     *
     * @return list<ActionSchemaField>
     */
    private function getFields(array $names): array
    {
        return array_map(ActionSchemaField::make(...), $names);
    }
}
