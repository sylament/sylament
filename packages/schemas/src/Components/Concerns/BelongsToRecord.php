<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

use function Sylament\Support\filled;

trait BelongsToRecord
{
    use CanAccessAssociations;

    /** @var object|array<string, mixed>|class-string|null */
    protected object|array|string|null $record = null;

    protected ?Closure $loadStateFromRelationshipsUsing = null;

    protected ?Closure $saveRelationshipsUsing = null;

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
     * Whether this component carries a binding of its own rather than inheriting
     * one — the unevaluated property, so a class-string and a closure both count.
     * Neither getter can answer it: {@see getRecord()} is null for a class-string
     * binding and {@see getRecordClass()} walks the container chain.
     */
    public function hasOwnRecord(): bool
    {
        return $this->record !== null;
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

    /**
     * The record class of the nearest node framing this component — its own
     * binding ignored, so a component asking what record its path is indexed
     * against gets the answer the frame gives, not its own.
     *
     * @return class-string|null
     */
    public function getNearestRecordClass(): ?string
    {
        return $this->getNearestRecordFrame()?->getRecordClass();
    }

    /**
     * This component's state path relative to the node framing it — the twin of
     * {@see getRecordStatePath()}, which is always root-framed. Null when the
     * component carries no path of its own or its path does not nest under the
     * frame's, which is a caller's signal to fall back rather than to index the
     * frame's record with a path that does not belong to it.
     */
    public function getNearestRecordStatePath(): ?string
    {
        $frame = $this->getNearestRecordFrame();

        if (($frame === null) || ! $this->hasStatePath()) {
            return null;
        }

        $path = $this->getStatePath();

        if (! filled($path)) {
            return null;
        }

        $framePath = $frame->getStatePath();

        if (! filled($framePath)) {
            return $path;
        }

        $prefix = $framePath . '.';

        return str_starts_with($path, $prefix) ? mb_substr($path, mb_strlen($prefix)) : null;
    }

    /**
     * The first node above this component — container schemas and the components
     * they hang off alike, this component excluded — that binds a record of its
     * own, else the root container. Null only while the container back-reference
     * is unbound. One walk, so {@see getNearestRecordClass()} and
     * {@see getNearestRecordStatePath()} cannot name different frames.
     */
    private function getNearestRecordFrame(): Schema|Component|null
    {
        if (! isset($this->container)) {
            return null;
        }

        $container = $this->getContainer();

        while (! $container->hasOwnRecord()) {
            $parentComponent = $container->getParentComponent();

            if ($parentComponent === null) {
                return $container;
            }

            if ($parentComponent->hasOwnRecord()) {
                return $parentComponent;
            }

            $container = $parentComponent->getContainer();
        }

        return $container;
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
}
