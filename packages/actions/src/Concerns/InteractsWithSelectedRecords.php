<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;

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
