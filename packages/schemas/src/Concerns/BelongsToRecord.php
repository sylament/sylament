<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;
use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Exceptions\ValidationException;
use Sylament\Schemas\State\ViolationPathTranslator;
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
     * Whether this schema carries a binding of its own rather than inheriting one
     * — the unevaluated property, so a class-string and a closure both count.
     * Neither getter can answer it: {@see getRecord()} is null for a class-string
     * binding and {@see getRecordClass()} walks the parent chain.
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
            return $this->getPropertyAccessor()->constructRecord(
                $this->record,
                'a new record onto the schema',
                'hand the schema a record built with its arguments, through record().',
            );
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
     *                        relationship hook no enclosing owner routes
     *                        ({@see Component::routesNestedRelationshipHookAt()})
     */
    protected function getRecordPathMap(): array
    {
        return $this->collectRecordPaths()[0];
    }

    /**
     * {@see getRecordPathMap()}, beside the paths it dropped beneath a subtree
     * owner — which a violation raised inside that owner's related record is
     * still spoken in.
     *
     * @return array{array<string, Component>, array<string, Component>}
     */
    private function collectRecordPaths(): array
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

            $claimant = $map[$recordPath] ?? null;

            if (($claimant instanceof Component) && ($ownsSubtree || $claimant->ownsRecordSubtree())) {
                throw new LogicException(sprintf(
                    '%s and %s both map record path "%s", which one of them maps wholesale — the walks route a path through a single component, so the other would neither fill nor save. Give one of them a different state path; two components entangling the same relationship() are not merged, so declare it on one of them and nest every field it maps inside that one.',
                    $claimant::class,
                    $component::class,
                    $recordPath,
                ));
            }

            $map[$recordPath] = $component;

            if ($ownsSubtree) {
                $owners[$recordPath] = $component;
            }
        }

        $dropped = [];

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

                $dropped[$path] = $component;

                unset($map[$path]);
            }
        }

        foreach ($hooked as $component) {
            if (in_array($component, $map, true)) {
                continue;
            }

            if (in_array($component, $dropped, true) && $this->isDelegatedToSubtreeOwner($component)) {
                continue;
            }

            $this->refuseUnmappedRelationshipHook($component);
        }

        return [$map, $dropped];
    }

    /**
     * Every relationship hook declared beneath this schema that no nested subtree
     * owner speaks for, keyed by the hook-declaring component's state path
     * relative to this schema's own — the same derivation
     * {@see Component::getRecordStatePath()} takes against the root, so an owner
     * routing its subtree and the mapper vouching for it read one definition of
     * relative. A nested owner is one entry itself when it declares a hook, and
     * everything beneath it belongs to that owner instead; two components
     * sharing a key leave the last one declared, mirroring the map's own
     * overwrite.
     *
     * @return array<string, Component>
     */
    public function getNestedRelationshipHooks(): array
    {
        $ownStatePath = $this->getStatePath();
        $prefix = filled($ownStatePath) ? $ownStatePath . '.' : '';

        $hooks = [];

        foreach ($this->getFlatComponents(withActions: false) as $component) {
            if (! $component->hasLoadStateFromRelationships() && ! $component->hasSaveRelationships()) {
                continue;
            }

            if (! $component->hasStatePath()) {
                continue;
            }

            $statePath = $component->getStatePath();

            if (($statePath === null) || (($prefix !== '') && ! str_starts_with($statePath, $prefix))) {
                continue;
            }

            if ($this->hasSubtreeOwnerBetween($component)) {
                continue;
            }

            $hooks[mb_substr($statePath, mb_strlen($prefix))] = $component;
        }

        return $hooks;
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
                default => 'a component above it owns its record subtree without routing the hooks beneath it. Declare relationship() on that component, move this one out from under it, or drop the hook.',
            },
        ));
    }

    /**
     * Whether the owner that dropped `$component` from the map routes its hook
     * itself. Only the innermost owner is asked — an outer one drops an inner
     * owner's descendants first, and it is the inner owner's walk that would run
     * them — and the vouch is put in that owner's own terms, its record path
     * stripped off the component's. An owner the map could not path never
     * delegated anything.
     */
    private function isDelegatedToSubtreeOwner(Component $component): bool
    {
        $owner = $this->findNearestSubtreeOwner($component);

        if ($owner === null) {
            return false;
        }

        $ownerPath = $owner->getRecordStatePath();
        $componentPath = $component->getRecordStatePath();

        if (($ownerPath === null) || ($componentPath === null)) {
            return false;
        }

        $prefix = $ownerPath . '.';

        if (! str_starts_with($componentPath, $prefix)) {
            return false;
        }

        return $owner->routesNestedRelationshipHookAt(mb_substr($componentPath, mb_strlen($prefix)));
    }

    /**
     * The innermost component above `$component` owning its record subtree —
     * where {@see isInsideOwnerTree()} answers "any ancestor", this answers
     * "which one", which is what separates an inner owner from the outer one
     * whose pass dropped the path first.
     */
    private function findNearestSubtreeOwner(Component $component): ?Component
    {
        $ancestor = $component->getContainer()->getParentComponent();

        while ($ancestor instanceof Component) {
            if ($ancestor->ownsRecordSubtree()) {
                return $ancestor;
            }

            $ancestor = $ancestor->getContainer()->getParentComponent();
        }

        return null;
    }

    /**
     * Whether a component between `$component` and this schema owns its record
     * subtree — the ancestor walk bounded at this schema, so a hook belongs to
     * the innermost owner enclosing it and never to a schema further out.
     */
    private function hasSubtreeOwnerBetween(Component $component): bool
    {
        $container = $component->getContainer();

        while ($container !== $this) {
            $parentComponent = $container->getParentComponent();

            if (!$parentComponent instanceof Component) {
                return false;
            }

            if ($parentComponent->ownsRecordSubtree()) {
                return true;
            }

            $container = $parentComponent->getContainer();
        }

        return false;
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
     * are object-valued leaves other than one the component's state casts
     * flatten to a non-object ({@see Component::isObjectCarriedByStateCasts()}) —
     * the null-fill pass supplies the skipped keys; a
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

            if (is_object($value) && ! $component->isObjectCarriedByStateCasts($value)) {
                continue;
            }

            $accessor->setInArray($state, $recordPath, $value);
        }

        return $state;
    }

    /**
     * Read every relationship-backed component's state back off the bound
     * record, as a fill reads those paths — for the host to call once its flush
     * has run, so an item or a related record the save created is keyed by the
     * identifier the flush gave it and the next save updates that record rather
     * than building it again. A schema bound to no record reads nothing.
     */
    public function loadStateFromRelationships(): void
    {
        $record = $this->getRecord();

        if (! is_object($record)) {
            return;
        }

        foreach ($this->getRecordPathMap() as $component) {
            if (! $component->hasLoadStateFromRelationships()) {
                continue;
            }

            $component->rawState($component->loadStateFromRelationships($record));
            $component->hydrateState(applyDefaults: false);

            foreach ($component->getChildSchemas() as $childSchema) {
                $childSchema->hydrateState(applyDefaults: false);
                $childSchema->callAfterStateHydrated();
                $childSchema->fillStateWithNull();
            }

            $component->callAfterStateHydrated();
            $component->fillStateWithNull();
        }
    }

    /**
     * The same reading {@see HasState::fill()} does, handed back as an array
     * instead of written into the state bag — the seam a fill-side mutator sits
     * in, so the record reaches the form through one hydration pass rather than
     * two.
     *
     * @return array<string, mixed>
     */
    public function getStateFromRecord(object $record): array
    {
        return $this->readStateFromRecord($record);
    }

    /**
     * The inverse of the mapping walk, handed to the host so a violation raised
     * against the mapped record can be spoken back in this schema's state paths.
     */
    public function getRecordViolationPathTranslator(): ViolationPathTranslator
    {
        [$map, $dropped] = $this->collectRecordPaths();

        return new ViolationPathTranslator([...$dropped, ...$map], $this->getStatePath());
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
     * The written record is then validated in its own right, so the constraint
     * metadata it declares — including the class-level ones a per-path run can
     * never reach — is answered against the object the mapping produced rather
     * than against raw state.
     *
     * A caller that has already validated the state and mutated the array it got
     * back hands that array in as `$state`, which is then mapped as-is; `null`
     * reads {@see getState()}, validating first.
     *
     * @param array<string, mixed>|null $state
     *
     * @throws LogicException     when a declared branch names no property, names
     *                            one the record declares non-nullable, or composes
     *                            with a relationship save hook, or when a component
     *                            the record path map excludes declares one
     * @throws ValidationException when the mapped record violates a constraint it
     *                             declares — the record stays mutated, nothing here
     *                             having been flushed
     */
    public function getData(?array $state = null): object
    {
        $record = $this->getRecord();

        if (! is_object($record)) {
            $record = $this->getRecordInstance()
                ?? throw new LogicException('Schema::getData() needs a record it can map onto — record() with a bound instance or a class-string to construct; an array record only supplies state and cannot be mapped onto.');
        }

        $accessor = $this->getPropertyAccessor();
        $state ??= $this->getState();
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

        $this->getLiveComponentOrNull()?->validateSchemaRecord($this, $record);

        return $record;
    }

    /**
     * The leaf paths `$after` adds to `$before` that no component of this
     * schema maps onto the record, in `$after`'s order — a path `$before` does
     * not hold, or one where `$before` held an array and `$after` holds
     * something else. A path a component maps, or one beneath it, is covered.
     *
     * @internal the default write seams, which refuse what it answers
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return list<string>
     */
    public function findUnmappedAdditions(array $before, array $after): array
    {
        $recordPaths = array_map(strval(...), array_keys($this->getRecordPathMap()));

        return array_values(array_filter(
            $this->findAddedPaths($before, $after, ''),
            static fn (string $path): bool => ! array_any($recordPaths, static fn (string $recordPath): bool => $path === $recordPath || str_starts_with($path, $recordPath . '.')),
        ));
    }

    /**
     * @param array<array-key, mixed> $after
     *
     * @return list<string>
     */
    private function findAddedPaths(mixed $before, array $after, string $prefix): array
    {
        $paths = [];

        foreach ($after as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            $isHeld = is_array($before) && array_key_exists($key, $before);
            $held = is_array($before) ? ($before[$key] ?? null) : null;

            if ($isHeld && $held === $value) {
                continue;
            }

            if (is_array($value) && $value !== []) {
                $paths = [...$paths, ...$this->findAddedPaths($held, $value, $path)];

                continue;
            }

            if (! $isHeld || (is_array($held) && ! is_array($value))) {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
