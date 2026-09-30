<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;
use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Sylament\Actions\Concerns\CanMutateRecordData;
use Sylament\Actions\Replication\RecordReplicator;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\AssociationReader;
use Sylament\Support\Evaluation\ClosureParameter;

use function Sylament\Support\except;

class ReplicateAction extends Action
{
    use CanCustomizeProcess;
    use CanMutateRecordData;

    protected ?Closure $beforeReplicaSaved = null;

    /**
     * @var array<string>|Closure|null
     */
    protected array|Closure|null $excludedAttributes = null;

    protected ?object $replica = null;

    public static function getDefaultName(): ?string
    {
        return 'replicate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Replicate');

        $this->modalHeading(fn (): string => ($title = $this->getRecordTitle()) === null ? 'Replicate' : "Replicate {$title}");

        $this->modalSubmitActionLabel('Replicate');

        $this->successNotificationTitle('Replicated');

        $this->icon('heroicons:square-2-stack-20-solid');
        $this->groupedIcon('heroicons:square-2-stack-20-solid');

        $this->fillForm(fn (Schema $schema): array => $this->getRecordDataForFill($schema, $this->getRecordForFill(), $this->getExcludedAttributes() ?? []));

        $this->action(function (): void {
            $record = $this->getRecordOrFail();
            $schema = $this->getMountedSchema();

            $this->process($this->replicateRecord(...), ['record' => $record, 'schema' => $schema, 'form' => $schema]);

            $this->success();
        });
    }

    public function beforeReplicaSaved(?Closure $callback): static
    {
        $this->beforeReplicaSaved = $callback;

        return $this;
    }

    /**
     * @param array<string>|Closure|null $attributes mapped field or association names left uncopied
     */
    public function excludeAttributes(array|Closure|null $attributes): static
    {
        $this->excludedAttributes = $attributes;

        return $this;
    }

    public function callBeforeReplicaSaved(): void
    {
        $this->evaluate($this->beforeReplicaSaved);
    }

    /**
     * @return array<string>|null
     */
    public function getExcludedAttributes(): ?array
    {
        return $this->evaluate($this->excludedAttributes);
    }

    public function getReplica(): ?object
    {
        return $this->replica;
    }

    public function getSchema(Schema $schema): ?Schema
    {
        // By default, the schema's record would be the original that is being
        // replicated. Since the schema is used to create a new replica, its
        // record is the record class instead, so it does not behave as if the
        // original were edited rather than a new record created.
        return parent::getSchema($schema)?->record($this->getRecordClass());
    }

    /**
     * The default persistence step `using()` replaces: copy the record through
     * Doctrine's metadata, map the modal's validated state onto the copy — or,
     * with no modal, the mutated field bag — then flush it. The modal's record
     * check reads the manager the copy is written through. A replica whose
     * assigned key is incomplete is refused by name before anything is written:
     * before `persist()` where no filler could complete it, and after it —
     * once every prePersist callback and listener has run — where one was
     * exempted and left a key half blank. A key a `beforeReplicaSaved()` hook
     * leaves is checked again: a modal answers a taken one under its field, and
     * without a modal a taken key is refused by name before `persist()`.
     *
     * @param array<string, mixed> $data
     */
    private function replicateRecord(array $data, ?Schema $schema, object $record): object
    {
        $manager = $this->getEntityManagerForRecord($record);
        $replicator = new RecordReplicator($manager);
        $excluded = $this->getExcludedAttributes() ?? [];
        $replica = $replicator->replicate($record, $excluded);
        $this->replica = $replica;

        if ($schema instanceof Schema) {
            $schema->setAssociationReader(new AssociationReader($manager));
            $schema->record($replica)->getData($data);
        } elseif ($data === [] && $this->mutateRecordDataUsing instanceof Closure) {
            $replicator->setFieldValues($replica, $this->mutateRecordData(except($replicator->getFieldValues($record), $excluded)));
        }

        $this->callBeforeReplicaSaved();

        if ($schema instanceof Schema && $this->beforeReplicaSaved instanceof Closure) {
            $schema->getLiveComponentOrNull()?->validateSchemaRecord($schema, $replica);
        }

        $metadata = $manager->getClassMetadata($replica::class);
        $reader = new AssociationReader($manager);

        if (
            $reader->hasIncompleteAssignedIdentifier($replica)
            || ($metadata->isIdentifierNatural() && ! $this->mayAssignIdentifierOnPersist($manager, $metadata) && RecordKey::values($manager, $replica) !== null && RecordKey::find($manager, $replica) === null)
        ) {
            throw $this->refuseIncompleteReplica($replica);
        }

        if (! $schema instanceof Schema && $reader->findTakenIdentifiers($replica) !== []) {
            throw $this->refuseTakenReplica($replica);
        }

        $manager->persist($replica);

        if ($metadata->isIdentifierNatural() && RecordKey::find($manager, $replica) === null) {
            try {
                $manager->detach($replica);
            } catch (ORMInvalidArgumentException) {
                // Doctrine can neither detach nor remove a persisted entity whose identity hash is '': it stays scheduled, and nothing flushes after the refusal.
            }

            throw $this->refuseIncompleteReplica($replica);
        }

        $manager->flush();

        return $replica;
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function mayAssignIdentifierOnPersist(EntityManagerInterface $manager, ClassMetadata $metadata): bool
    {
        return $metadata->hasLifecycleCallbacks(Events::prePersist)
            || isset($metadata->entityListeners[Events::prePersist])
            || $manager->getEventManager()->hasListeners(Events::prePersist);
    }

    private function refuseIncompleteReplica(object $replica): LogicException
    {
        return new LogicException(sprintf(
            'The [%s] action built a replica of [%s] whose identifier is incomplete, so it cannot be saved as a new record. Put every identifier field the copy leaves unset in its schema() — enabled and dehydrated — or set it in beforeReplicaSaved().',
            $this->getName(),
            $replica::class,
        ));
    }

    private function refuseTakenReplica(object $replica): LogicException
    {
        return new LogicException(sprintf(
            'The [%s] action built a replica of [%s] whose key another record already holds, so it cannot be saved as a new record. Give the copy a key of its own: ask for it in schema(), or set it in beforeReplicaSaved() — an identifier association the copy keeps is left out with excludeAttributes().',
            $this->getName(),
            $replica::class,
        ));
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'replica' => ClosureParameter::make($this->getReplica()),
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
