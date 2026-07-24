<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

trait InteractsWithRecord
{
    protected mixed $record = null;

    public function record(mixed $record): static
    {
        $this->record = $record;

        return $this;
    }

    public function getRecord(): mixed
    {
        return $this->evaluate($this->record);
    }
}
