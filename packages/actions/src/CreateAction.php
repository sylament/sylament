<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use LogicException;
use ReflectionClass;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Sylament\Actions\Concerns\RefusesUnmappedData;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Relationship;

use function Sylament\Support\only;

class CreateAction extends Action
{
    use CanCustomizeProcess;
    use RefusesUnmappedData;

    protected bool|Closure $canCreateAnother = true;

    protected Action|Closure|null $modifyCreateAnotherActionUsing = null;

    /**
     * @var array<string, mixed>|Closure|null
     */
    protected array|Closure|null $preserveFormDataWhenCreatingAnotherUsing = null;

    protected Relationship|Closure|null $getRelationshipUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'create';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (): string => ($label = $this->getRecordLabel()) === null ? 'Create' : "New {$label}");

        $this->modalHeading(fn (): string => ($label = $this->getTitleCaseRecordLabel()) === null ? 'Create' : "Create {$label}");

        $this->modalSubmitActionLabel('Create');

        $this->extraModalFooterActions(fn (): array => $this->canCreateAnother() ? [$this->getCreateAnotherAction()] : []);

        $this->successNotificationTitle('Created');

        $this->groupedIcon('heroicons:plus-20-solid');

        $this->record(null);

        $this->action(function (array $arguments, Schema $schema): void {
            $preserveRawState = [];

            if ($arguments['another'] ?? false) {
                $preserveRawState = $this->evaluate($this->preserveFormDataWhenCreatingAnotherUsing, ['data' => $schema->getRawState()]) ?? [];
            }

            $recordClass = $this->getRecordClass()
                ?? throw new LogicException(sprintf('The action [%s] creates a record but nothing names its class — call recordClass(), host it on a query() table or a resource page, or bind the schema with record().', $this->getName()));

            $record = $this->process($this->createRecord(...), ['recordClass' => $recordClass, 'schema' => $schema, 'form' => $schema]);

            if (! is_object($record)) {
                throw new LogicException(sprintf('The action [%s] expected its process to return the created record, got %s.', $this->getName(), get_debug_type($record)));
            }

            $this->record($record);

            if ($arguments['another'] ?? false) {
                $this->callAfter();
                $this->sendSuccessNotification();
                $this->record(null);

                $schema->record($recordClass)->fill();
                $schema->rawState([...$schema->getRawState(), ...$preserveRawState]);
                $schema->hydrateState(applyDefaults: false);
                $schema->fillStateWithNull();
                $schema->dispatchClientSideStateReset();

                $this->halt();

                return;
            }

            $this->success();
        });
    }

    public function createAnother(bool|Closure $condition = true): static
    {
        $this->canCreateAnother = $condition;

        return $this;
    }

    public function canCreateAnother(): bool
    {
        return (bool) $this->evaluate($this->canCreateAnother);
    }

    public function createAnotherAction(Closure $callback): static
    {
        $this->modifyCreateAnotherActionUsing = $callback;

        return $this;
    }

    public function getCreateAnotherAction(): Action
    {
        $action = $this->makeModalSubmitAction('createAnother', arguments: ['another' => true])
            ->label('Create & create another');

        return $this->evaluate($this->modifyCreateAnotherActionUsing, ['action' => $action]) ?? $action;
    }

    /**
     * @param array<string>|Closure|null $fields
     */
    public function preserveFormDataWhenCreatingAnother(array|Closure|null $fields): static
    {
        $this->preserveFormDataWhenCreatingAnotherUsing = is_array($fields)
            ? static fn (array $data): array => only($data, $fields)
            : $fields;

        return $this;
    }

    public function relationship(?Closure $relationship): static
    {
        $this->getRelationshipUsing = $relationship;

        return $this;
    }

    /**
     * Own closure, then the table's relationship, then the host's default — the
     * relationship the created record joins, or null for a plain create.
     */
    public function getRelationship(): ?Relationship
    {
        return $this->evaluate($this->getRelationshipUsing)
            ?? $this->getTable()?->getRelationship()
            ?? $this->getLiveComponentOrNull()?->getDefaultActionRelationship($this);
    }

    public function shouldClearRecordAfter(): bool
    {
        return true;
    }

    /**
     * The default persistence step `using()` replaces: map the validated — and,
     * where `mutateDataUsing()` asked, mutated — state onto a new record of the
     * action's class, then flush it. A path the hook added that no component of
     * the schema maps is refused by name before anything is built. A
     * relationship in force joins the record to its owner before the state is
     * mapped, so the record check that ends the mapping sees an identifier field
     * the association fills, and again after it, so the relationship still wins
     * over a field the form maps onto the same association; the schema is bound
     * to that record for the mapping alone, and a many-to-many owner's
     * collection gives the record back when the mapping refuses it, since
     * nothing else takes it out again. The record check reads the schema's own
     * reader, rebound to the manager the flush writes through. A record whose
     * assigned key is still blank once `persist()` has run every prePersist
     * listener is refused by name before the flush, handed back by a
     * many-to-many owner and taken back out of the unit of work.
     *
     * @param array<string, mixed> $data
     * @param class-string $recordClass
     *
     * @throws LogicException when the hook added a path no component maps, when the record class requires constructor arguments, or when the persisted record's assigned key is still blank
     */
    private function createRecord(array $data, Schema $schema, string $recordClass): object
    {
        $this->refuseUnmappedData($schema, $data);

        $manager = $this->getEntityManagerForRecord($recordClass);
        $recordReader = $schema->getAssociationReader()->withEntityManager($manager);

        $schema->setAssociationReader($recordReader);

        $relationship = $this->getRelationship();

        if ($relationship instanceof Relationship) {
            $record = $this->getPropertyAccessor()->constructRecord(
                $recordClass,
                sprintf('a new record for the [%s] action', $this->getName()),
                "build it in the action's using() closure.",
            );

            $reader = $this->getAssociationReader();
            $reader->attach($relationship, $record);
            $mapped = false;

            try {
                $schema->record($record)->getData($data);
                $mapped = true;
            } finally {
                $schema->record($recordClass);

                if (! $mapped && $reader->describeRelationship($relationship)->isManyToMany) {
                    $reader->detach($relationship, $record);
                }
            }

            $reader->attach($relationship, $record);
        } else {
            $this->refuseUnbuildableRecordClass($recordClass);

            $record = $schema->record($recordClass)->getData($data);
        }

        $manager->persist($record);

        if ($recordReader->hasBlankAssignedIdentifier($record)) {
            if ($relationship instanceof Relationship && $this->getAssociationReader()->describeRelationship($relationship)->isManyToMany) {
                $this->getAssociationReader()->detach($relationship, $record);
            }

            $manager->detach($record);

            throw new LogicException(sprintf(
                "The [%s] action built a [%s] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a field in the action's schema() — a Hidden one for a value the user should not type — or fill it in a prePersist listener.%s",
                $this->getName(),
                $record::class,
                $schema->isRecordKeyValidated() ? '' : ' The schema switches the key rules off with validateRecordKey(false), so a key field it maps is not refused while blank; switch them back on to have the blank field refused under it.',
            ));
        }

        $manager->flush();

        return $record;
    }

    /**
     * Refuse, naming `using()`, a record class whose constructor requires
     * arguments: the plain arm maps under the class binding, where `getData()`
     * would construct it and refuse with the schema's own advice, which an
     * action cannot follow. `constructRecord()` words the refusal, as it does
     * for the relationship arm.
     *
     * @param class-string $recordClass
     *
     * @throws LogicException when the class's constructor requires arguments
     */
    private function refuseUnbuildableRecordClass(string $recordClass): void
    {
        $constructor = new ReflectionClass($recordClass)->getConstructor();

        if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
            $this->getPropertyAccessor()->constructRecord(
                $recordClass,
                sprintf('a new record for the [%s] action', $this->getName()),
                "build it in the action's using() closure.",
            );
        }
    }
}
