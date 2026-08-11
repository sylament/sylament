<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\State\AssociationReader;
use Symfony\Contracts\Service\Attribute\Required;

use function Sylament\Support\filled;

trait BelongsToRecord
{
    /** @var object|array<string, mixed>|class-string|null */
    protected object|array|string|null $record = null;

    protected ?Closure $loadStateFromRelationshipsUsing = null;

    protected ?Closure $saveRelationshipsUsing = null;

    protected ?AssociationReader $associationReader = null;

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
        if (! $this->hasStatePath()) {
            return null;
        }

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

    /**
     * Take over how this component's record path is read by `fill()`. A path
     * that declares this bypasses the record read entirely — the object-leaf
     * skip included — so an association can supply state a property read
     * cannot.
     */
    public function loadStateFromRelationshipsUsing(?Closure $callback): static
    {
        $this->loadStateFromRelationshipsUsing = $callback;

        return $this;
    }

    /**
     * Take over how this component's record path is written by `getData()`. The
     * hook owns the write, so neither `setOnRecord()` nor its object-collection
     * refusal runs for that path.
     */
    public function saveRelationshipsUsing(?Closure $callback): static
    {
        $this->saveRelationshipsUsing = $callback;

        return $this;
    }

    public function hasLoadStateFromRelationships(): bool
    {
        return $this->loadStateFromRelationshipsUsing !== null;
    }

    public function hasSaveRelationships(): bool
    {
        return $this->saveRelationshipsUsing !== null;
    }

    /**
     * @return mixed the state to fill at this component's record path
     */
    public function loadStateFromRelationships(object $record): mixed
    {
        return $this->evaluate($this->loadStateFromRelationshipsUsing, [
            'record' => $record,
        ], [
            $record::class => $record,
        ]);
    }

    public function saveRelationships(object $record, mixed $state): void
    {
        $this->evaluate($this->saveRelationshipsUsing, [
            'record' => $record,
            'state' => $state,
        ], [
            $record::class => $record,
        ]);
    }

    #[Required]
    public function setAssociationReader(AssociationReader $associationReader): void
    {
        $this->associationReader = $associationReader;
    }

    public function getAssociationReader(): AssociationReader
    {
        return $this->associationReader ??= new AssociationReader();
    }
}
