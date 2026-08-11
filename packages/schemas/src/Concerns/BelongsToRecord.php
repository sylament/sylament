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
     * stripped by {@see Component::getRecordStatePath()}. A component owning its
     * record subtree ({@see Component::ownsRecordSubtree()}) is the exception —
     * it maps as a leaf despite its path-bearing descendants, and every path
     * collected beneath it drops out in favour of its own.
     *
     * @return array<string, Component>
     *
     * @throws LogicException when a component declared outside an owner's tree
     *                        carries a state path nesting under the owner's, or
     *                        when a component the map does not carry declares a
     *                        relationship hook
     */
    protected function getRecordPathMap(): array
    {
        $map = [];
        $owners = [];
        $hooked = [];

        foreach ($this->getFlatComponents(withActions: false) as $component) {
            if ($component->hasLoadStateFromRelationships() || $component->hasSaveRelationships()) {
                $hooked[] = $component;
            }

            if (! $component->hasStatePath()) {
                continue;
            }

            $ownsSubtree = $component->ownsRecordSubtree();

            if (! $ownsSubtree && $component->hasPathBearingDescendants()) {
                continue;
            }

            $recordPath = $component->getRecordStatePath();

            if ($recordPath === null) {
                continue;
            }

            $map[$recordPath] = $component;

            if ($ownsSubtree) {
                $owners[$recordPath] = $component;
            }
        }

        foreach ($owners as $ownerPath => $owner) {
            $prefix = $ownerPath . '.';

            foreach ($map as $path => $component) {
                if ($component === $owner) {
                    continue;
                }

                if (! str_starts_with((string) $path, $prefix)) {
                    continue;
                }

                if (! $this->isInsideOwnerTree($component, $owner)) {
                    throw new LogicException(sprintf(
                        '%s declares state path "%s", which nests under "%s" — a path %s maps wholesale, so this component would neither fill nor save. Move it inside that component\'s schema, or move its state path out from under "%s".',
                        $component::class,
                        $path,
                        $ownerPath,
                        $owner::class,
                        $ownerPath,
                    ));
                }

                unset($map[$path]);
            }
        }

        foreach ($hooked as $component) {
            if (! in_array($component, $map, true)) {
                $this->refuseUnmappedRelationshipHook($component);
            }
        }

        return $map;
    }

    /**
     * Both walks consult a relationship hook only for a path the map carries, so
     * a hook on a component the map skips could never fire — no error, no write,
     * no trace. Refuse it at map time instead, naming which of the three
     * exclusions this component met and the way back into the map.
     *
     * @throws LogicException always
     */
    private function refuseUnmappedRelationshipHook(Component $component): never
    {
        throw new LogicException(sprintf(
            '%s declares a relationship hook, but the record path map does not carry it, so the hook could never fire — %s',
            $component::class,
            match (true) {
                $component->getRecordStatePath() === null => 'it names no record path. Give it the statePath() of the association it maps, or drop the hook.',
                $component->hasPathBearingDescendants() && ! $component->ownsRecordSubtree() => 'the map takes the paths of the components nested inside it instead of its own. Declare ownsRecordSubtree() so it maps whole, or drop the hook.',
                default => 'a component above it owns its record subtree and speaks for every path beneath. Move it out from under that component, or drop the hook.',
            },
        ));
    }

    /**
     * Whether `$component` sits in `$owner`'s own component tree — the walk up
     * the container chain that separates a descendant the owner speaks for from
     * a foreign component that merely shares its path prefix.
     */
    private function isInsideOwnerTree(Component $component, Component $owner): bool
    {
        $ancestor = $component->getContainer()->getParentComponent();

        while ($ancestor instanceof Component) {
            if ($ancestor === $owner) {
                return true;
            }

            $ancestor = $ancestor->getContainer()->getParentComponent();
        }

        return false;
    }

    /**
     * Every component declaring itself a nullable branch, keyed by its
     * record-relative path and ordered outermost first, so an outer branch that
     * nulls short-circuits the inner ones beneath it.
     *
     * @return array<string, Component>
     *
     * @throws LogicException when a component declares itself a nullable branch
     *                        without a state path naming the property to null,
     *                        or alongside a relationship save hook
     */
    protected function getNullableBranches(): array
    {
        $branches = [];

        foreach ($this->getFlatComponents(withActions: false) as $component) {
            if (! $component->isNullableBranch()) {
                continue;
            }

            if ($component->hasSaveRelationships()) {
                throw new LogicException(sprintf(
                    '%s declares nullWhenBlank() alongside a relationship save hook — the hook owns what an empty submission means, so the two cannot compose. Drop nullWhenBlank() or the relationship.',
                    $component::class,
                ));
            }

            $recordPath = $component->getRecordStatePath();

            if ($recordPath === null) {
                throw new LogicException(sprintf(
                    '%s declares nullWhenBlank() but carries no state path, so it names no property to null. Give it the statePath() of the branch it wraps, or drop nullWhenBlank().',
                    $component::class,
                ));
            }

            $branches[$recordPath] = $component;
        }

        uksort($branches, static fn (int|string $a, int|string $b): int => mb_substr_count((string) $a, '.') <=> mb_substr_count((string) $b, '.'));

        return $branches;
    }

    /**
     * Read this schema's declared leaf paths off a record, traversing its
     * object graph. Unreadable and uninitialized properties are skipped, as
     * are object-valued leaves — the null-fill pass supplies those keys; a
     * null or scalar intermediate supplies null. A component declaring
     * {@see Component::loadStateFromRelationshipsUsing()} supplies its own
     * value instead, so neither skip applies to its path.
     *
     * @return array<string, mixed>
     */
    protected function readStateFromRecord(object $record): array
    {
        $accessor = $this->getPropertyAccessor();
        $state = [];

        foreach ($this->getRecordPathMap() as $recordPath => $component) {
            $recordPath = (string) $recordPath;

            if ($component->hasLoadStateFromRelationships()) {
                $accessor->setInArray($state, $recordPath, $component->loadStateFromRelationships($record));

                continue;
            }

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
     * A branch that {@see Component::ownsRecordSubtree()} declares no mapped
     * leaf beneath it at all, so it is asked at its own path instead and never
     * reaches this predicate.
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
     * and therefore never mapped. A {@see Component::nullWhenBlank()} branch the
     * state still carries and whose state should not exist is nulled whole
     * before the leaf walk, which then skips its own path and every path beneath
     * it — so a discarded branch is never built back up by one of its own leaves,
     * nor by the wholesale value a {@see Component::ownsRecordSubtree()} branch
     * maps at that very path; a branch the state does not carry is left alone
     * entirely
     * ({@see hasDehydratedBranchLeaf()}). A leaf whose component declares
     * {@see Component::saveRelationshipsUsing()} is handed to that hook instead
     * of written through the accessor, so the hook owns the write and the
     * object-collection refusal never applies to its path — a hook on a
     * component the map does not carry is refused rather than skipped.
     *
     * @throws LogicException when a declared branch names no property, names
     *                        one the record declares non-nullable, or composes
     *                        with a relationship save hook, or when a component
     *                        the record path map excludes declares one
     */
    public function getData(): object
    {
        $record = $this->getRecord();

        if (! is_object($record)) {
            $record = $this->getRecordInstance()
                ?? throw new LogicException('Schema::getData() needs a record it can map onto — record() with a bound instance or a class-string to construct; an array record only supplies state and cannot be mapped onto.');
        }

        $accessor = $this->getPropertyAccessor();
        $state = $this->getState();
        $map = $this->getRecordPathMap();
        $recordPaths = array_map(strval(...), array_keys($map));

        $nulledPrefixes = [];
        $nulledPaths = [];

        foreach ($this->getNullableBranches() as $branchPath => $component) {
            $branchPath = (string) $branchPath;

            foreach ($nulledPrefixes as $prefix) {
                if (str_starts_with($branchPath, $prefix)) {
                    continue 2;
                }
            }

            $ownsSubtree = $component->ownsRecordSubtree();

            if ($ownsSubtree ? ! $accessor->hasInArray($state, $branchPath) : ! $this->hasDehydratedBranchLeaf($state, $branchPath, $recordPaths)) {
                continue;
            }

            if (! $accessor->acceptsNullOnRecord($record::class, $branchPath)) {
                throw new LogicException(sprintf(
                    'Cannot map state path "%s" onto %s: the record declares that property non-nullable, so the nullWhenBlank() on the component that owns it can never apply. Make the property nullable, or drop nullWhenBlank().',
                    $branchPath,
                    $record::class,
                ));
            }

            $branchState = $accessor->getFromArray($state, $branchPath);

            /** @var array<string, mixed> $branchState */
            $branchState = is_array($branchState) ? $branchState : [];

            if (! $component->shouldNullBranch($branchState)) {
                continue;
            }

            $accessor->setOnRecord($record, $branchPath, null);
            $nulledPrefixes[] = $branchPath . '.';
            $nulledPaths[$branchPath] = true;
        }

        foreach ($recordPaths as $recordPath) {
            if (isset($nulledPaths[$recordPath])) {
                continue;
            }

            foreach ($nulledPrefixes as $prefix) {
                if (str_starts_with($recordPath, $prefix)) {
                    continue 2;
                }
            }

            if (! $accessor->hasInArray($state, $recordPath)) {
                continue;
            }

            if ($map[$recordPath]->hasSaveRelationships()) {
                $map[$recordPath]->saveRelationships($record, $accessor->getFromArray($state, $recordPath));

                continue;
            }

            $accessor->setOnRecord($record, $recordPath, $accessor->getFromArray($state, $recordPath));
        }

        return $record;
    }
}
