<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Sylament\Actions\Concerns\CanMutateRecordData;
use Sylament\Actions\Replication\RecordReplicator;
use Sylament\Schemas\Schema;
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
     * with no modal, the mutated field bag — then flush it.
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
            $schema->record($replica)->getData($data);
        } elseif ($data === [] && $this->mutateRecordDataUsing instanceof Closure) {
            $replicator->setFieldValues($replica, $this->mutateRecordData(except($replicator->getFieldValues($record), $excluded)));
        }

        $this->callBeforeReplicaSaved();

        $manager->persist($replica);
        $manager->flush();

        return $replica;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'replica' => ClosureParameter::make($this->getReplica()),
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
