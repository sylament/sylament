<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;

use function Sylament\Support\filled;

trait BelongsToRecord
{
    /** @var object|array<string, mixed>|class-string|null */
    protected object|array|string|null $record = null;

    /**
     * A bound instance, raw record data, a class-string declaring the class
     * without binding an instance, or — through the `object` arm, since PHP
     * rejects `object|Closure` — a closure returning any of those, evaluated once
     * and memoized on the first read.
     *
     * @param object|array<string, mixed>|class-string|null $record
     */
    public function record(object|array|string|null $record = null): static
    {
        $this->record = $record;

        return $this;
    }

    /**
     * @return class-string|null
     */
    public function getRecordClass(): ?string
    {
        $this->record = $this->evaluate($this->record);

        if (is_object($this->record) && ! $this->record instanceof Closure) {
            return $this->record::class;
        }

        if (is_array($this->record)) {
            return null;
        }

        if (filled($this->record) && is_string($this->record)) {
            return $this->record;
        }

        return $this->getParentComponent()?->getRecordClass();
    }

    /**
     * @return object|array<string, mixed>|null
     */
    public function getRecord(bool $withParentComponentRecord = true): object|array|null
    {
        $this->record = $this->evaluate($this->record);

        if ((is_object($this->record) && ! $this->record instanceof Closure) || is_array($this->record)) {
            return $this->record;
        }

        if (is_string($this->record)) {
            return null;
        }

        if (! $withParentComponentRecord) {
            return null;
        }

        return $this->getParentComponent()?->getRecord();
    }

    public function getRecordInstance(): ?object
    {
        $this->record = $this->evaluate($this->record);

        if ($this->record === null || is_array($this->record)) {
            return $this->getParentComponent()?->getRecordInstance();
        }

        if (is_object($this->record) && ! $this->record instanceof Closure) {
            return $this->record;
        }

        if (is_string($this->record)) {
            $recordClass = $this->record;

            return new $recordClass();
        }

        return null;
    }
}
