<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;
use LogicException;
use Sylament\Schemas\Components\Component;
use Symfony\Component\PropertyAccess\Exception\AccessException;

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

    /**
     * The record-relative dotted paths this schema maps, keyed to their leaf
     * components. Containers (components nesting path-bearing components)
     * contribute their leaves, never themselves; the root state path prefix is
     * stripped by {@see Component::getRecordStatePath()}.
     *
     * @return array<string, Component>
     */
    protected function getRecordPathMap(): array
    {
        $map = [];

        foreach ($this->getFlatComponents(withActions: false) as $component) {
            if (! $component->hasStatePath()) {
                continue;
            }

            if ($this->hasPathBearingDescendants($component)) {
                continue;
            }

            $recordPath = $component->getRecordStatePath();

            if ($recordPath === null) {
                continue;
            }

            $map[$recordPath] = $component;
        }

        return $map;
    }

    /**
     * Every component declaring itself a nullable branch, keyed by its
     * record-relative path and ordered outermost first, so an outer branch that
     * nulls short-circuits the inner ones beneath it.
     *
     * @return array<string, Component>
     */
    protected function getNullableBranches(): array
    {
        $branches = [];

        foreach ($this->getFlatComponents(withActions: false) as $component) {
            if (! $component->isNullableBranch()) {
                continue;
            }

            $recordPath = $component->getRecordStatePath();

            if ($recordPath === null) {
                continue;
            }

            $branches[$recordPath] = $component;
        }

        uksort($branches, static fn (int|string $a, int|string $b): int => mb_substr_count((string) $a, '.') <=> mb_substr_count((string) $b, '.'));

        return $branches;
    }

    /**
     * Whether any component nested under this one carries a state path — at any
     * depth, through any child-schema key. A leaf decorated with a path-less
     * child schema (`helperText()`, `belowContent()`, …) owns none, so it stays
     * a leaf.
     */
    private function hasPathBearingDescendants(Component $component): bool
    {
        foreach ($component->getChildSchemas() as $childSchema) {
            foreach ($childSchema->getFlatComponents(withActions: false) as $descendant) {
                if ($descendant->hasStatePath()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Read this schema's declared leaf paths off a record, traversing its
     * object graph. Unreadable and uninitialized properties are skipped, as
     * are object-valued leaves — the null-fill pass supplies those keys; a
     * null or scalar intermediate supplies null.
     *
     * @return array<string, mixed>
     */
    protected function readStateFromRecord(object $record): array
    {
        $accessor = $this->getPropertyAccessor();
        $state = [];

        foreach (array_keys($this->getRecordPathMap()) as $recordPath) {
            try {
                $value = $accessor->getFromRecord($record, $recordPath);
            } catch (AccessException) {
                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $accessor->setInArray($state, $recordPath, $value);
        }

        return $state;
    }

    /**
     * Whether the dehydrated bag still carries the branch at `$branchPath` — at
     * least one of the leaf paths the schema declares beneath it is present.
     *
     * A component with no declared leaf beneath it is a leaf rather than a
     * branch, and a branch whose every leaf the dehydration walk pruned (a
     * hidden subtree, `dehydrated(false)`) is absent rather than blank. Neither
     * may be nulled: the bag holds no evidence about what the submission meant
     * there, so writing null would discard a record value the form never showed.
     * Testing presence at the branch path itself is not enough — pruning unsets
     * only the leaf key and leaves the emptied parent behind.
     *
     * @param array<string, mixed> $state
     * @param list<string>         $recordPaths
     */
    private function hasDehydratedBranchLeaf(array $state, string $branchPath, array $recordPaths): bool
    {
        $accessor = $this->getPropertyAccessor();
        $prefix = $branchPath . '.';

        foreach ($recordPaths as $recordPath) {
            if (! str_starts_with($recordPath, $prefix)) {
                continue;
            }

            if ($accessor->hasInArray($state, $recordPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate through {@see getState()}, then write every leaf state path the
     * schema declares onto the bound instance — or onto a fresh one built from
     * the declared class — and return it. A failing validation throws before
     * anything is written; a `dehydrated(false)` leaf is pruned from the state
     * and therefore never mapped. A {@see Component::nullWhenBlank()} branch
     * whose state should not exist is nulled whole before the leaf walk, which
     * then skips every path beneath it — so a discarded branch is never built
     * back up by one of its own leaves.
     */
    public function getData(): object
    {
        $record = $this->getRecord();

        if (! is_object($record)) {
            $record = $this->getRecordInstance()
                ?? throw new LogicException('Schema::getData() needs record() — a bound instance to map onto, or a declared class to construct.');
        }

        $accessor = $this->getPropertyAccessor();
        $state = $this->getState();
        $recordPaths = array_map(strval(...), array_keys($this->getRecordPathMap()));

        $nulledPrefixes = [];

        foreach ($this->getNullableBranches() as $branchPath => $component) {
            $branchPath = (string) $branchPath;

            foreach ($nulledPrefixes as $prefix) {
                if (str_starts_with($branchPath, $prefix)) {
                    continue 2;
                }
            }

            if (! $this->hasDehydratedBranchLeaf($state, $branchPath, $recordPaths)) {
                continue;
            }

            $branchState = $accessor->getFromArray($state, $branchPath);

            /** @var array<string, mixed> $branchState */
            $branchState = is_array($branchState) ? $branchState : [];

            if (! $component->shouldNullBranch($branchState)) {
                continue;
            }

            $accessor->setOnRecord($record, $branchPath, null);
            $nulledPrefixes[] = $branchPath . '.';
        }

        foreach ($recordPaths as $recordPath) {
            foreach ($nulledPrefixes as $prefix) {
                if (str_starts_with($recordPath, $prefix)) {
                    continue 2;
                }
            }

            if (! $accessor->hasInArray($state, $recordPath)) {
                continue;
            }

            $accessor->setOnRecord($record, $recordPath, $accessor->getFromArray($state, $recordPath));
        }

        return $record;
    }
}
