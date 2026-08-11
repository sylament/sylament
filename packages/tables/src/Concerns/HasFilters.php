<?php

declare(strict_types=1);

namespace Sylament\Tables\Concerns;

use LogicException;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tables\Filters\BaseFilter;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

trait HasFilters
{
    /**
     * The applied filter state — what the query and the indicators read.
     *
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true, onUpdated: 'updatedTableFilters')]
    public array $tableFilters = [];

    /**
     * The draft the filters form binds to while filters are deferred.
     *
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $tableDeferredFilters = [];

    /**
     * One filter's applied slice, or null when it holds nothing or holds
     * something that is not a state map.
     *
     * @return array<string, mixed>|null
     */
    public function getTableFilterState(string $name): ?array
    {
        $state = $this->tableFilters[$this->parseTableFilterName($name)] ?? null;

        return is_array($state) ? $state : null;
    }

    /**
     * The slice the form is bound to — the draft while deferring, the applied
     * state otherwise.
     *
     * @return array<string, mixed>|null
     */
    public function getTableFilterFormState(string $name): ?array
    {
        $bag = $this->getTable()->hasDeferredFilters() ? $this->tableDeferredFilters : $this->tableFilters;

        $state = $bag[$this->parseTableFilterName($name)] ?? null;

        return is_array($state) ? $state : null;
    }

    public function parseTableFilterName(string $name): string
    {
        if (!is_a($name, BaseFilter::class, allow_string: true)) {
            return $name;
        }

        return $name::getDefaultName() ?? $name;
    }

    public function getTableFiltersForm(): Schema
    {
        if (!$this instanceof InteractsWithSchemas) {
            throw new LogicException('Table filters need a schema host: ' . static::class . ' must implement ' . InteractsWithSchemas::class . ' and use the ' . HasSchemas::class . ' trait.');
        }

        if ($this->hasCachedSchema('tableFiltersForm')) {
            return $this->getSchema('tableFiltersForm');
        }

        $table = $this->getTable();

        $schema = $this->makeSchema()
            ->components($table->getFiltersFormSchema())
            ->columns($table->getFiltersFormColumns());

        $recordClass = $table->getRecordClass();

        if ($recordClass !== null) {
            $schema->record($recordClass);
        }

        if ($table->hasDeferredFilters()) {
            $schema->statePath('tableDeferredFilters');
        } else {
            $schema->statePath('tableFilters')->live();
        }

        $this->cacheSchema('tableFiltersForm', $schema);

        return $schema;
    }

    /**
     * Seed each filter's declared default, then — while deferring — copy the
     * draft across so those defaults are applied state from the first render.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    #[PostMount]
    public function mountTableFilters(array $data): array
    {
        $table = $this->getTable();

        if (!$table->isFilterable()) {
            return $data;
        }

        $this->getTableFiltersForm()->fill();

        if ($table->hasDeferredFilters()) {
            $this->tableFilters = $this->tableDeferredFilters;
        }

        return $data;
    }

    /**
     * The filters form is cache-only, so it must be rebuilt above
     * `callAfterSchemaStateUpdated()`'s -10 or the walk finds nothing and every
     * field hook inside it silently never fires.
     */
    #[PostHydrate(priority: 10)]
    public function afterTableFiltersFormHydrated(): void
    {
        if (!$this instanceof InteractsWithSchemas) {
            return;
        }

        if (!$this->getTable()->isFilterable()) {
            return;
        }

        $this->getTableFiltersForm();
    }

    public function updatedTableFilters(mixed $previousValue = null): void
    {
        if ($previousValue === $this->tableFilters) {
            return;
        }

        if ($this->getTable()->hasDeferredFilters()) {
            $this->tableDeferredFilters = $this->tableFilters;
        }

        $this->handleTableFilterUpdates();
    }

    protected function handleTableFilterUpdates(): void
    {
        $this->tablePage = 1;
        $this->flushCachedTableRecords();
    }
}
