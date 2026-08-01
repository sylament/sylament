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

    /**
     * This component's state path relative to the record — the root schema's own
     * `statePath` prefix stripped, so it indexes the record graph rather than
     * the host's state bag. Null when the component carries no path.
     */
    public function getRecordStatePath(): ?string
    {
        $path = $this->getStatePath();

        if (! filled($path)) {
            return null;
        }

        $rootPath = $this->getRootContainer()->getStatePath();
        $prefix = filled($rootPath) ? $rootPath . '.' : '';

        return ($prefix !== '' && str_starts_with($path, $prefix))
            ? mb_substr($path, mb_strlen($prefix))
            : $path;
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
