<?php

declare(strict_types=1);

namespace Sylament\Tables\Columns\Concerns;

trait HasRecord
{
    /**
     * @var object|array<string, mixed>|null
     */
    protected object|array|null $record = null;

    protected ?string $recordKey = null;

    /**
     * @param object|array<string, mixed> $record
     */
    public function record(object|array $record): static
    {
        $this->record = $record;

        return $this;
    }

    /**
     * The key the table addresses this cell's record by — `int` is admitted
     * because PHP re-keys a numeric-string array key to an integer, so that is
     * what a template loop hands back.
     */
    public function recordKey(string|int|null $key): static
    {
        $this->recordKey = $key === null ? null : (string) $key;

        return $this;
    }

    public function getRecordKey(): ?string
    {
        return $this->recordKey;
    }

    /**
     * @return object|array<string, mixed>|null
     */
    public function getRecord(): object|array|null
    {
        return $this->record;
    }
}
