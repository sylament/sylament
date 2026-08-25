<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Sylament\Schemas\Schema;

use function Sylament\Support\only;

class CreateAction extends Action
{
    use CanCustomizeProcess;

    protected bool|Closure $canCreateAnother = true;

    protected Action|Closure|null $modifyCreateAnotherActionUsing = null;

    /**
     * @var array<string, mixed>|Closure|null
     */
    protected array|Closure|null $preserveFormDataWhenCreatingAnotherUsing = null;

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

    public function shouldClearRecordAfter(): bool
    {
        return true;
    }

    /**
     * The default persistence step `using()` replaces: map the validated —
     * and, where `mutateDataUsing()` asked, mutated — state onto a new record
     * of the action's class, then flush it.
     *
     * @param array<string, mixed> $data
     * @param class-string $recordClass
     */
    private function createRecord(array $data, Schema $schema, string $recordClass): object
    {
        $record = $schema->record($recordClass)->getData($data);

        $manager = $this->getEntityManagerForRecord($record);
        $manager->persist($record);
        $manager->flush();

        return $record;
    }
}
