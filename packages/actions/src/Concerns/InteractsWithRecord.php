<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use function Sylament\Support\filled;

trait InteractsWithRecord
{
    protected mixed $record = null;

    public function record(mixed $record): static
    {
        $this->record = $record;

        return $this;
    }

    public function hasRecord(): bool
    {
        return filled($this->record);
    }

    public function getRecord(): mixed
    {
        $record = $this->evaluate($this->record);

        if ($record !== null) {
            return $record;
        }

        if (method_exists($this, 'getGroup')) {
            return $this->getGroup()?->getRecord();
        }

        return null;
    }
}
