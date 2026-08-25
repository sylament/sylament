<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Sylament\Forms\Components\Select;
use Sylament\Schemas\Query\SearchConstraint;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Relationship;

use function Sylament\Support\filled;
use function Sylament\Support\wrap;

class AttachAction extends Action
{
    use CanCustomizeProcess;

    protected Select|Closure|null $modifyRecordSelectUsing = null;

    protected QueryBuilder|Closure|null $modifyRecordSelectOptionsQueryUsing = null;

    protected bool|Closure $canAttachAnother = true;

    protected bool|Closure $isRecordSelectPreloaded = false;

    protected bool|Closure $isMultiple = false;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $recordSelectSearchColumns = null;

    protected bool|Closure|null $isSearchForcedCaseInsensitive = null;

    public static function getDefaultName(): ?string
    {
        return 'attach';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Attach');

        $this->modalHeading(fn (): string => ($label = $this->getTitleCaseRecordLabel()) === null ? 'Attach' : "Attach {$label}");

        $this->modalSubmitActionLabel('Attach');

        $this->modalWidth('lg');

        $this->extraModalFooterActions(fn (): array => $this->canAttachAnother() ? [
            $this->makeModalSubmitAction('attachAnother', arguments: ['another' => true])->label('Attach & attach another'),
        ] : []);

        $this->successNotificationTitle('Attached');

        $this->defaultColor('gray');

        $this->schema(fn (): array => [$this->getRecordSelect()]);

        $this->action(function (array $arguments, array $data, Schema $schema): void {
            $relationship = $this->getRelationshipOrFail();
            $records = $this->findRecordsToAttach($data);

            if (count($records) === 1) {
                $this->record($records[0]);
            }

            $this->process($this->attachRecords(...), ['relationship' => $relationship, 'records' => $records]);

            if ($arguments['another'] ?? false) {
                $this->callAfter();
                $this->sendSuccessNotification();
                $this->record(null);
                $schema->fill();
                $this->halt();

                return;
            }

            $this->success();
        });
    }

    public function recordSelect(?Closure $callback): static
    {
        $this->modifyRecordSelectUsing = $callback;

        return $this;
    }

    public function recordSelectOptionsQuery(?Closure $callback): static
    {
        $this->modifyRecordSelectOptionsQueryUsing = $callback;

        return $this;
    }

    public function attachAnother(bool|Closure $condition = true): static
    {
        $this->canAttachAnother = $condition;

        return $this;
    }

    public function canAttachAnother(): bool
    {
        return (bool) $this->evaluate($this->canAttachAnother);
    }

    public function preloadRecordSelect(bool|Closure $condition = true): static
    {
        $this->isRecordSelectPreloaded = $condition;

        return $this;
    }

    public function isRecordSelectPreloaded(): bool
    {
        return (bool) $this->evaluate($this->isRecordSelectPreloaded);
    }

    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    /**
     * @param list<string>|Closure|null $columns
     */
    public function recordSelectSearchColumns(array|Closure|null $columns): static
    {
        $this->recordSelectSearchColumns = $columns;

        return $this;
    }

    /**
     * @return list<string>|null
     */
    public function getRecordSelectSearchColumns(): ?array
    {
        return $this->evaluate($this->recordSelectSearchColumns);
    }

    public function forceSearchCaseInsensitive(bool|Closure|null $condition = true): static
    {
        $this->isSearchForcedCaseInsensitive = $condition;

        return $this;
    }

    public function isSearchForcedCaseInsensitive(): ?bool
    {
        return $this->evaluate($this->isSearchForcedCaseInsensitive);
    }

    /**
     * Filament installs `options()` unconditionally, its closure answering null
     * when the select is not preloaded. Here it is installed only for the
     * preloaded arm, because the port's `Select::hasFullOptionMap()` reads any
     * `options()` as the whole truth about a legal value and would then validate
     * a searched pick against an empty map.
     */
    public function getRecordSelect(): Select
    {
        $select = Select::make('recordId')
            ->label('Record')
            ->required()
            ->multiple($this->isMultiple())
            ->searchable($this->getRecordSelectSearchColumns() ?? true)
            ->getSearchResultsUsing(fn (Select $component, string $search): array => $this->getRecordSelectOptions($component->getOptionsLimit(), $search, $component->getSearchColumns()))
            ->getOptionLabelUsing(fn (mixed $value): ?string => (is_int($value) || is_string($value)) ? ($this->getRecordSelectOptionLabels([$value])[$value] ?? null) : null)
            ->getOptionLabelsUsing(fn (array $values): array => $this->getRecordSelectOptionLabels(array_values(array_filter($values, static fn (mixed $value): bool => is_int($value) || is_string($value)))))
            ->dynamicOptions(fn (): ?bool => $this->isRecordSelectPreloaded() ? null : false)
            ->hiddenLabel();

        if ($this->isRecordSelectPreloaded()) {
            $select->options(fn (Select $component): array => $this->getRecordSelectOptions($component->getOptionsLimit()));
        }

        return $this->evaluate($this->modifyRecordSelectUsing, ['select' => $select]) ?? $select;
    }

    /**
     * The relationship the picked records join — the table's, since an attach
     * has nothing to attach to without one.
     */
    protected function getRelationshipOrFail(): Relationship
    {
        return $this->getTable()?->getRelationship()
            ?? throw new LogicException(sprintf('The action [%s] attaches records to a relationship, which only a table declaring relationship() can supply.', $this->getName()));
    }

    /**
     * Every target record not already related, under recordSelectOptionsQuery()'s
     * scope — built from the relationship, never from the table's query, so the
     * table's own scopes cannot reach records that are outside it by definition.
     */
    protected function getRecordSelectOptionsQuery(?string $search = null): QueryBuilder
    {
        $query = $this->getAssociationReader()->createRelationshipCandidateQuery($this->getRelationshipOrFail());

        return $this->evaluate($this->modifyRecordSelectOptionsQueryUsing, ['query' => $query, 'search' => $search], [QueryBuilder::class => $query]) ?? $query;
    }

    /**
     * The select's options: searched over the given columns (or the title
     * attribute), limited, ordered by the title attribute unless the query
     * orders itself, and read as one projection when the title is a plain
     * attribute — else hydrated and titled record by record.
     *
     * @param list<string>|null $searchColumns
     *
     * @return array<int|string, string>
     */
    protected function getRecordSelectOptions(int $optionsLimit, ?string $search = null, ?array $searchColumns = null): array
    {
        $reader = $this->getAssociationReader();
        $targetClass = $reader->describeRelationship($this->getRelationshipOrFail())->targetClass;
        $identifier = $reader->getIdentifierFieldName($targetClass);
        $titleAttribute = $this->getRecordTitleAttribute();
        $query = $this->getRecordSelectOptionsQuery($search);

        if ($query->getMaxResults() === null) {
            $query->setMaxResults($optionsLimit);
        }

        $columns = $searchColumns ?? ($titleAttribute === null ? [] : [$titleAttribute]);

        if (filled($search) && $columns !== []) {
            $metadata = $query->getEntityManager()->getClassMetadata($targetClass);
            $comparisons = [];

            foreach ($columns as $column) {
                if (! $metadata->hasField($column)) {
                    throw new LogicException(sprintf('The action [%s] searches [%s] on [%s], which maps no such field.', $this->getName(), $column, $targetClass));
                }

                $comparisons[] = SearchConstraint::like($query, "record.{$column}", $search, $this->isSearchForcedCaseInsensitive());
            }

            $query->andWhere($query->expr()->orX(...$comparisons));
        }

        if ($titleAttribute !== null && $query->getDQLPart('orderBy') === []) {
            $query->orderBy("record.{$titleAttribute}");
        }

        if (
            $titleAttribute !== null
            && ! $this->hasCustomRecordTitle()
            && ($this->hasCustomRecordTitleAttribute() || ! ($this->getTable()?->hasCustomRecordTitle() ?? false))
        ) {
            /** @var list<array{sy_key: int|string, sy_title: mixed}> $rows */
            $rows = $query->select("record.{$identifier} AS sy_key, record.{$titleAttribute} AS sy_title")->getQuery()->getArrayResult();
            $options = [];

            foreach ($rows as $row) {
                $options[$row['sy_key']] = is_scalar($row['sy_title']) ? (string) $row['sy_title'] : '';
            }

            return $options;
        }

        /** @var list<mixed> $records */
        $records = $query->getQuery()->getResult();
        $options = [];

        foreach ($records as $record) {
            if (is_object($record)) {
                $options[$reader->getRecordKey($record)] = (string) $this->getRecordTitle($record);
            }
        }

        return $options;
    }

    /**
     * @param list<int|string> $values
     *
     * @return array<int|string, string>
     */
    protected function getRecordSelectOptionLabels(array $values): array
    {
        $labels = [];

        foreach ($this->findCandidatesByKey($values) as $record) {
            $labels[$this->getAssociationReader()->getRecordKey($record)] = (string) $this->getRecordTitle($record);
        }

        return $labels;
    }

    /**
     * The submitted key(s), re-resolved through the candidate query — a key the
     * select never offered is simply absent, and refused.
     *
     * @param array<mixed> $data
     *
     * @return list<object>
     */
    protected function findRecordsToAttach(array $data): array
    {
        $keys = array_values(array_filter(wrap($data['recordId'] ?? null), static fn (mixed $value): bool => is_int($value) || is_string($value)));
        $records = $this->findCandidatesByKey($keys);

        if (count($records) !== count($keys)) {
            throw new LogicException(sprintf('The action [%s] was asked to attach a record its select never offered — the key is outside recordSelectOptionsQuery()\'s scope or already attached.', $this->getName()));
        }

        return $records;
    }

    /**
     * @param list<int|string> $keys
     *
     * @return list<object>
     */
    private function findCandidatesByKey(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $reader = $this->getAssociationReader();
        $identifier = $reader->getIdentifierFieldName($reader->describeRelationship($this->getRelationshipOrFail())->targetClass);

        /** @var list<mixed> $records */
        $records = $this->getRecordSelectOptionsQuery()
            ->andWhere("record.{$identifier} IN (:sylament_selected_record_keys)")
            ->setParameter('sylament_selected_record_keys', $keys)
            ->getQuery()
            ->getResult();

        return array_values(array_filter($records, is_object(...)));
    }

    /**
     * The default persistence step `using()` replaces: make each picked record
     * one of the owner's, then flush once.
     *
     * @param list<object> $records
     */
    private function attachRecords(Relationship $relationship, array $records): void
    {
        $reader = $this->getAssociationReader();

        foreach ($records as $record) {
            $reader->attach($relationship, $record);
        }

        $this->getEntityManagerForRecord($relationship->owner)->flush();
    }
}
