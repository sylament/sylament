<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Tables\Contracts\InteractsWithTable;

trait InteractsWithSelectedRecords
{
    protected bool|Closure $canAccessSelectedRecords = false;

    protected int $totalSelectedRecordsCount = 0;

    protected int $successfulSelectedRecordsCount = 0;

    public function accessSelectedRecords(bool|Closure $condition = true): static
    {
        $this->canAccessSelectedRecords = $condition;

        return $this;
    }

    public function canAccessSelectedRecords(): bool
    {
        return (bool) $this->evaluate($this->canAccessSelectedRecords);
    }

    public function hasCanAccessSelectedRecords(): bool
    {
        return $this->canAccessSelectedRecords !== false;
    }

    /**
     * The selected records — hydrated, or their keys alone under
     * `fetchSelectedRecords(false)`. Reading them mints the two counters the
     * failure reports and `getStatus()` work off.
     *
     * @return list<object|array<string, mixed>>|list<string>
     */
    public function getSelectedRecords(): array
    {
        $records = $this->getSelectedRecordsHost()->getSelectedTableRecords($this->shouldFetchSelectedRecords());

        $this->totalSelectedRecordsCount = count($records);
        $this->successfulSelectedRecordsCount = $this->totalSelectedRecordsCount;

        return $records;
    }

    /**
     * The same selection as a query, for a bulk action that writes without
     * hydrating. It mints no counters — the row count is the write's answer.
     */
    public function getSelectedRecordsQuery(): QueryBuilder
    {
        return $this->getSelectedRecordsHost()->getSelectedTableRecordsQuery($this->shouldFetchSelectedRecords());
    }

    /**
     * The table host both readers go through: the access flag first, then the
     * host's shape, so an action reaching for a selection it never asked for
     * and one mounted on a host that has no table fail differently.
     */
    private function getSelectedRecordsHost(): InteractsWithTable
    {
        if (!$this->canAccessSelectedRecords()) {
            throw new LogicException("The action [{$this->getName()}] is attempting to access the selected records from the table, but it is not using [accessSelectedRecords()], so they are not available.");
        }

        $host = $this->getLiveComponent();

        if (!$host instanceof InteractsWithTable) {
            throw new LogicException("The action [{$this->getName()}] accesses selected records but its live component is not a table host — implement " . InteractsWithTable::class . '.');
        }

        return $host;
    }

    public function reportBulkProcessingFailure(): void
    {
        $this->successfulSelectedRecordsCount--;
    }

    public function reportBulkProcessingSuccessfulRecordsCount(int $count): void
    {
        $this->successfulSelectedRecordsCount = $count;
    }

    public function reportCompleteBulkProcessingFailure(): void
    {
        $this->successfulSelectedRecordsCount = 0;
    }

    public function getTotalSelectedRecordsCount(): int
    {
        return $this->totalSelectedRecordsCount;
    }
}
