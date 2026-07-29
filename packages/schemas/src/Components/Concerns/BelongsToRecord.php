<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

use function Sylament\Support\filled;

trait BelongsToRecord
{
    /** @var object|array<string, mixed>|class-string|null */
    protected object|array|string|null $record = null;

    /**
     * A bound instance, raw record data, a class-string declaring the class
     * without binding an instance, or — through the `object` arm, since PHP
     * rejects `object|Closure` — a closure returning any of those, re-evaluated on
     * every read.
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
        $record = $this->evaluate($this->record);

        if (is_object($record) && ! $record instanceof Closure) {
            return $record::class;
        }

        if (is_array($record)) {
            return null;
        }

        if (filled($record) && is_string($record)) {
            return $record;
        }

        return isset($this->container) ? $this->getContainer()->getRecordClass() : null;
    }

    /**
     * @return object|array<string, mixed>|null
     */
    public function getRecord(bool $withContainerRecord = true): object|array|null
    {
        $record = $this->evaluate($this->record);

        if ((is_object($record) && ! $record instanceof Closure) || is_array($record)) {
            return $record;
        }

        if (is_string($record)) {
            return null;
        }

        if (! $withContainerRecord) {
            return null;
        }

        return isset($this->container) ? $this->getContainer()->getRecord() : null;
    }

    public function getRecordInstance(): ?object
    {
        $record = $this->evaluate($this->record);

        if ($record === null || is_array($record)) {
            return isset($this->container) ? $this->getContainer()->getRecordInstance() : null;
        }

        if (is_object($record) && ! $record instanceof Closure) {
            return $record;
        }

        if (is_string($record)) {
            return new $record();
        }

        return null;
    }
}
