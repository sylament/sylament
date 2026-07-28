<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use LogicException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Sylament\Actions\Action;
use Sylament\Actions\Enums\ActionStatus;
use Sylament\Actions\Exceptions\ActionNotResolvableException;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Support\Exceptions\Cancel;
use Sylament\Support\Exceptions\Halt;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\ComponentToolsTrait;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait HasActions
{
    use ComponentToolsTrait;

    /**
     * @var array<int, array{name: string, arguments: array<mixed>, context: array<mixed>}>
     */
    #[LiveProp(dehydrateWith: 'dehydrateMountedActions')]
    public array $mountedActions = [];

    /**
     * Per-mount form state, one frame per `$mountedActions` index, bound at
     * `->statePath("mountedActionsData.{$actionNestingIndex}")`. A sibling bag
     * rather than Filament's `mountedActions.{i}.data`, because
     * `Sylament\Schemas\State\PropertyAccessor` turns that path into the Symfony
     * property path `mountedActions[0][data][title]`, whose first segment
     * resolves through `getMountedActions()` — Symfony's `ReflectionExtractor`
     * prefers a same-named getter over the property — and hands back resolved
     * `Action` objects, so every read *and* write raises `NoSuchIndexException`.
     * Same precedence trap that already forces `dehydrateWith:`.
     *
     * Writable, so the client can invent an index: presence in this bag is
     * never evidence of a mount — `$mountedActions` alone is, and it stays
     * server-controlled. Never add a `getMountedActionsData()` method; the
     * contract reader below is deliberately named in the singular so it cannot
     * collide.
     *
     * @var array<int, array<string, mixed>>
     */
    #[LiveProp(writable: true)]
    public array $mountedActionsData = [];

    #[LiveProp]
    public ?string $actionsHostId = null;

    /**
     * @var array<string, Action>
     */
    protected array $cachedActions = [];

    /**
     * @var array<Action>|null
     */
    protected ?array $cachedMountedActions = null;

    /**
     * Has-schema answers by nesting index. Not an optimisation:
     * {@see isMountedActionModalOpen()} runs the modal-open gate once per frame
     * per render (`Modals.html.twig:8,12`), so an unmemoised build is
     * O(frames²) schema constructions. It cannot live in `$cachedSchemas` —
     * that map is typed `array<string, Schema>` and the answer we need to cache
     * for a schema-less action is exactly the *absence* of a schema.
     *
     * @var array<int, bool>
     */
    protected array $cachedMountedActionHasSchema = [];

    protected ?Closure $resolveRecordUsing = null;

    public function resolveRecordUsing(?Closure $callback): static
    {
        $this->resolveRecordUsing = $callback;

        return $this;
    }

    protected function getMountedActionRecord(): mixed
    {
        $descriptor = $this->mountedActions === []
            ? null
            : array_last($this->mountedActions);

        $recordKey = $descriptor['context']['recordKey'] ?? null;

        if ($recordKey === null) {
            return null;
        }

        if ($this->resolveRecordUsing === null) {
            throw new LogicException('An action context supplied a recordKey but the host registered no resolveRecordUsing() callback to resolve it.');
        }

        return ($this->resolveRecordUsing)($recordKey);
    }

    /**
     * @param array<mixed> $arguments
     * @param array<mixed> $context
     */
    #[LiveAction]
    public function mountAction(
        #[LiveArg] string $name,
        #[LiveArg] array $arguments = [],
        #[LiveArg] array $context = [],
    ): mixed {
        $this->mountedActions[] = [
            'name' => $name,
            'arguments' => $arguments,
            'context' => $context,
        ];

        // Before anything can render: `live_controller`'s `getDeepData()`
        // dereferences every `[data-model]` path segment unguarded after every
        // render, so a field at `mountedActionsData.0.title` with no index 0 in
        // the dehydrated props is an uncaught TypeError that kills live updates
        // for the whole component.
        $this->mountedActionsData[array_key_last($this->mountedActions)] = [];

        $action = $this->getMountedAction();

        if ($action === null || $action->isDisabled()) {
            $this->unmountAction(cancelParentActions: false);

            return null;
        }

        try {
            $hasSchema = $this->mountedActionHasSchema($action);

            if ($hasSchema) {
                $action->callBeforeFormFilled();
            }

            $schema = $this->getMountedActionSchema(mountedAction: $action);

            $action->mount([
                'form' => $schema,
                'schema' => $schema,
            ]);

            if ($hasSchema) {
                $action->callAfterFormFilled();
            }
        } catch (Halt|Cancel) {
            $this->unmountAction(cancelParentActions: false);

            return null;
        }

        if (! $this->mountedActionShouldOpenModal($action)) {
            return $this->callMountedAction();
        }

        $this->dispatchBrowserEvent('sylament-open-modal', ['id' => $this->getMountedModalId()]);

        return null;
    }

    /**
     * @param array<mixed> $arguments
     */
    #[LiveAction]
    public function callMountedAction(#[LiveArg] array $arguments = []): mixed
    {
        $action = $this->getMountedAction();

        if ($action === null) {
            return null;
        }

        $originalArguments = $action->getArguments();
        $action->mergeArguments($arguments);

        if (filled($record = $this->getMountedActionRecord())) {
            $action->record($record);
        }

        if ($action->isDisabled()) {
            return null;
        }

        $originallyMountedActions = $this->mountedActions;

        $result = null;

        try {
            // Filament's exact pipeline position (`InteractsWithActions.php:251-262`)
            // minus the `afterValidate` wrapper, so M1 · 1 can swap in
            // `getState(afterValidate: …)` here without moving anything else.
            if ($this->mountedActionHasSchema($action)) {
                $action->data($this->getMountedActionSchema(mountedAction: $action)?->getState() ?? []);
            }

            $action->callBefore();
            $result = $action->call();
            $result = $action->callAfter() ?? $result;

            $this->afterActionCalled($action);

            if ($action->getStatus() === ActionStatus::Success) {
                $action->sendSuccessNotification();
            } else {
                $action->sendFailureNotification();
            }
        } catch (Halt) {
            return null;
        } catch (Cancel) {
            // Falls through to unmount — Cancel closes the modal.
        }

        $action->arguments($originalArguments);

        $onlyNamesAndContexts = static fn (array $mountedActions): array => array_map(
            static fn (array $mountedAction): array => [
                'name' => $mountedAction['name'],
                'context' => $mountedAction['context'],
            ],
            $mountedActions,
        );

        // If the action replaced the stack while it was being called, don't unmount
        // the replacement (Filament-exact: names + contexts compared, arguments ignored).
        if ($onlyNamesAndContexts($originallyMountedActions) !== $onlyNamesAndContexts($this->mountedActions)) {
            return null;
        }

        $this->unmountAction();

        return $result;
    }

    protected function afterActionCalled(Action $action): void
    {
    }

    /**
     * @param array<mixed> $arguments
     * @param array<mixed> $context
     */
    public function replaceMountedAction(string $name, array $arguments = [], array $context = []): void
    {
        // The stack's own indices as well as the bag's: a surviving
        // `mountedActionSchema{$i}` would belong to the action being replaced,
        // keyed to the frame its replacement now occupies. The stack is the
        // authoritative half of that union — the bag is client-writable and
        // could arrive missing a slot.
        $staleSchemaIndexes = array_unique([
            ...array_keys($this->mountedActions),
            ...array_keys($this->mountedActionsData),
        ]);

        $this->mountedActions = [];
        $this->cachedMountedActions = null;
        $this->mountedActionsData = [];
        $this->cachedMountedActionHasSchema = [];

        foreach ($staleSchemaIndexes as $actionNestingIndex) {
            $this->forgetMountedActionSchema($actionNestingIndex);
        }

        $this->mountAction($name, $arguments, $context);
    }

    /**
     * Filament's bootedInteractsWithActions() stale-stack drop: a hydrated stack
     * that no longer resolves (renamed action, changed schema) self-heals to closed
     * instead of rendering a broken modal chain.
     *
     * That resolution is also what puts every frame's schema back into
     * `mountedActionSchema{$i}` — {@see resolveActions()} registers one per frame as
     * it walks. A mounted-action schema is cache-only — `HasSchemas::$cachedSchemas`
     * is a plain property, not a `#[LiveProp]` — so on a request that is not a
     * `mountAction()` call nothing else puts it back before
     * `callAfterSchemaStateUpdated()` walks `getCachedSchemas()`, and every field
     * inside an open modal would be skipped: `live()` and `afterStateUpdated()` would
     * silently never fire there. The explicit `#[PostHydrate]` priorities on this hook
     * (10) and on that one (-10) are what order the two; equal priorities would sort
     * by reverse declaration order, which is not a contract worth depending on.
     *
     * Not extra work: the same schemas are built during the render that follows. It
     * also makes the `#[PreDehydrate]` old-state snapshot independent of whether the
     * modal body actually rendered this pass.
     */
    #[PostHydrate(priority: 10)]
    public function afterMountedActionsHydrated(): void
    {
        if ($this->mountedActions === []) {
            return;
        }

        if ($this->cacheMountedActions($this->mountedActions) === []) {
            $this->mountedActions = [];
        }
    }

    #[LiveAction]
    public function unmountAction(#[LiveArg] bool|string|null $cancelParentActions = null): void
    {
        $id = $this->getMountedModalId();

        // No try/catch around this read: getMountedAction() never throws (the
        // resolution loop swallows into an empty cache) — Filament's catch here
        // would be dead code under PHPStan level max.
        $action = $this->getMountedAction();

        if (($cancelParentActions === false) || ($action === null)) {
            array_pop($this->mountedActions);
        } elseif (
            ($cancelParentActions === true)
            || (($cancelParentActions === null) && $action->shouldCancelAllParentActions())
        ) {
            $this->mountedActions = [];
        } else {
            $parentActionToCancelTo = is_string($cancelParentActions)
                ? $cancelParentActions
                : $action->getParentActionToCancelTo();

            while (true) {
                $recentlyClosedParentAction = array_pop($this->mountedActions);

                if (
                    blank($parentActionToCancelTo)
                    || ($recentlyClosedParentAction === null)
                    || ($recentlyClosedParentAction['name'] === $parentActionToCancelTo)
                ) {
                    break;
                }
            }
        }

        while (count($this->cachedMountedActions ?? []) > count($this->mountedActions)) {
            array_pop($this->cachedMountedActions);
        }

        // Every frame index the stack no longer has, dropped in lockstep with it.
        // Selected by index *value*, not by position in the bag: $mountedActions
        // is a server-controlled list, so its valid indices are always
        // 0..count-1, whereas $mountedActionsData is client-writable and can
        // arrive re-ordered or with holes. A frame that still exists must never
        // lose its slot — see the property docblock for why.
        foreach (array_keys($this->mountedActionsData) as $actionNestingIndex) {
            if ($actionNestingIndex < count($this->mountedActions)) {
                continue;
            }

            unset($this->mountedActionsData[$actionNestingIndex], $this->cachedMountedActionHasSchema[$actionNestingIndex]);

            $this->forgetMountedActionSchema($actionNestingIndex);
        }

        $this->dispatchBrowserEvent('sylament-close-modal', ['id' => $id]);
    }

    public function getMountedAction(?int $actionNestingIndex = null): ?Action
    {
        if ($this->mountedActions === []) {
            return null;
        }

        $actionNestingIndex ??= array_key_last($this->mountedActions);

        if (array_key_exists((string) $actionNestingIndex, $this->cachedMountedActions ?? [])) {
            return $this->cachedMountedActions[$actionNestingIndex];
        }

        $cached = $this->cacheMountedActions(array_slice($this->mountedActions, 0, $actionNestingIndex + 1));

        return $cached === [] ? null : array_last($cached);
    }

    /**
     * @return array<Action>
     */
    public function getMountedActions(): array
    {
        if ($this->mountedActions === []) {
            return [];
        }

        $cachedMountedActions = $this->cachedMountedActions ?? [];

        if (array_key_exists(count($this->mountedActions) - 1, $cachedMountedActions)) {
            return $cachedMountedActions;
        }

        return $this->cacheMountedActions($this->mountedActions);
    }

    /**
     * Read one frame of {@see $mountedActionsData}. Deliberately singular:
     * a `getMountedActionsData()` would be picked up by Symfony's
     * `ReflectionExtractor` as the bag property's read accessor, putting a
     * method between the schema state layer and the array it must address by
     * index — the trap documented on the property.
     *
     * @return array<string, mixed>
     */
    public function getMountedActionData(?int $actionNestingIndex = null): array
    {
        if ($this->mountedActions === []) {
            return [];
        }

        $actionNestingIndex ??= array_key_last($this->mountedActions);

        return $this->mountedActionsData[$actionNestingIndex] ?? [];
    }

    /**
     * Filament's `->model()` and `->operation()` arms are not ported: schemas
     * has no model binding and no `Operation` concept, so the per-mount schema
     * carries only its key, its state path and the modal heading level.
     */
    public function getMountedActionSchema(?int $actionNestingIndex = null, ?Action $mountedAction = null): ?Schema
    {
        if ($this->mountedActions === []) {
            return null;
        }

        $actionNestingIndex ??= $mountedAction?->getNestingIndex() ?? array_key_last($this->mountedActions);

        $mountedAction ??= $this->getMountedAction($actionNestingIndex);

        if (! $mountedAction instanceof Action) {
            return null;
        }

        if (! $this instanceof InteractsWithSchemas) {
            // Probed against a schema detached from this host — it can never
            // carry state — purely to tell "this host cannot build schemas" (a
            // wiring bug) from "this action declares none" (normal, and the
            // overwhelmingly common case).
            if ($mountedAction->getSchema(Schema::make()) instanceof Schema) {
                throw new LogicException("Action [{$mountedAction->getName()}] declares a schema but " . static::class . ' is not a schema host — implement ' . InteractsWithSchemas::class . ' and use the ' . HasSchemas::class . ' trait.');
            }

            return null;
        }

        $schemaName = "mountedActionSchema{$actionNestingIndex}";

        if ($this->hasCachedSchema($schemaName)) {
            return $this->getSchema($schemaName);
        }

        return $this->cacheSchema($schemaName, $mountedAction->getSchema(
            $this->makeSchema()
                ->key($schemaName)
                ->statePath("mountedActionsData.{$actionNestingIndex}")
                ->rootHeadingLevel(3),
        ));
    }

    public function mountedActionHasSchema(?Action $mountedAction = null): bool
    {
        if ($this->mountedActions === []) {
            return false;
        }

        $actionNestingIndex = $mountedAction?->getNestingIndex() ?? array_key_last($this->mountedActions);

        return $this->cachedMountedActionHasSchema[$actionNestingIndex] ??= count(
            $this->getMountedActionSchema($actionNestingIndex, $mountedAction)?->getComponents() ?? [],
        ) > 0;
    }

    protected function forgetMountedActionSchema(int $actionNestingIndex): void
    {
        if (! $this instanceof InteractsWithSchemas) {
            return;
        }

        $this->cacheSchema("mountedActionSchema{$actionNestingIndex}", null);
    }

    /**
     * Symfony's PropertyAccessor resolves a property read by trying a same-named
     * getter before the property itself (ReflectionExtractor::getReadInfo() checks
     * get/is/has/can-prefixed methods first). LiveComponentHydrator::dehydrate()
     * reads every #[LiveProp] through that accessor, so without this bypass it would
     * call getMountedActions() — resolved Action objects — instead of this raw,
     * wire-safe descriptor array. Livewire has no such precedence rule, which is why
     * Filament's identically-named property and getter never collide.
     *
     * @return array<int, array{name: string, arguments: array<mixed>, context: array<mixed>}>
     */
    public function dehydrateMountedActions(): array
    {
        return $this->mountedActions;
    }

    /**
     * The single place ActionNotResolvableException is swallowed: an unresolvable
     * stack caches as empty, so reads degrade to null instead of crashing the render.
     *
     * @param array<int, array{name: string, arguments: array<mixed>, context: array<mixed>}> $mountedActions
     * @return array<Action>
     */
    protected function cacheMountedActions(array $mountedActions): array
    {
        try {
            return $this->cachedMountedActions = $this->resolveActions($mountedActions);
        } catch (ActionNotResolvableException) {
            return $this->cachedMountedActions = [];
        }
    }

    /**
     * Resolves stack descriptors root-to-leaf, passing the growing resolved list as
     * the parent chain: index 0 resolves against the host, every deeper frame against
     * the previous action's declared modal actions. Because the walk stops at the
     * first null (see below) and only the root frame can resolve to null — deeper
     * frames throw — the result is either empty or as long as the input, never a
     * list with a hole in it. nestingIndex is stamped from the source array key,
     * which for the array_slice() prefixes this is called with is also the output
     * position; the cache stays positional (Filament-exact quirk).
     *
     * The schemaComponent branch is now taken for any frame carrying the key, and
     * `resolveSchemaComponentAction()` owns the root-frame policy itself: it refuses
     * a deeper frame unless the container named is already in the schema cache. The
     * one case that admits is an action declared inside a mounted-action schema,
     * whose `mountedActionSchema{$i}` container is server-minted and has no declaring
     * method — everything method-discovered stays root-only, so a deeper frame can
     * still never re-root itself against a host schema. Filament branches on the
     * context alone (their `Action::getContext()` never emits schemaComponent for a
     * child frame), but a hand-built stack entry could carry both keys, so the check
     * stays explicit here — it just lives one call down, next to the cache lookup it
     * depends on.
     *
     * Each resolved frame's schema is registered under `mountedActionSchema{$i}` before
     * the walk moves on, which is where Filament registers it too
     * (`InteractsWithActions::resolveActions()`). Per frame rather than after the walk,
     * because the next frame may *be* one of that schema's actions: a descriptor
     * carrying `context['schemaComponent'] = 'mountedActionSchema0'` is resolved by
     * reading exactly the entry the previous iteration just wrote. Registering the
     * whole set afterwards would leave every such frame unresolvable on any request
     * that did not mount it — and, worse, leave *its* schema uncached for
     * `callAfterSchemaStateUpdated()`, silently killing `live()` one modal deeper.
     * Filament gates this on an `$isMounting` flag for its dynamic-property lookups;
     * `resolveActions()` has one caller here and it only ever passes a prefix of
     * `$mountedActions`, so there is no non-mounting path to gate.
     *
     * Two deliberate deltas from Filament. A frame that resolves to null stops the walk
     * (they `continue`): their resolvers throw where ours return null, so continuing
     * would hand the next frame an empty parent chain and re-root it against the host —
     * a client-named child could then resolve as a root action. A stack with a hole is
     * never valid, so stopping is both safer and equivalent for their throwing cases.
     * Filament also boot()s each resolved action here; our components have no boot
     * lifecycle, so that call has no equivalent. Both are docs-noted in Task 9.
     *
     * @param array<int, array{name: string, arguments: array<mixed>, context: array<mixed>}> $actions
     * @return array<Action>
     */
    protected function resolveActions(array $actions): array
    {
        $resolvedActions = [];

        foreach ($actions as $actionNestingIndex => $descriptor) {
            if (blank($descriptor['name'] ?? null)) {
                throw new ActionNotResolvableException('An action tried to resolve without a name.');
            }

            if (filled($descriptor['context']['schemaComponent'] ?? null)) {
                $resolvedAction = $this->resolveSchemaComponentAction($descriptor, $resolvedActions);
            } else {
                $resolvedAction = $this->resolveAction($descriptor, $resolvedActions);
            }

            if ($resolvedAction === null) {
                break;
            }

            if (filled($descriptor['arguments'] ?? [])) {
                $resolvedAction->mergeArguments($descriptor['arguments']);
            }

            $resolvedAction->nestingIndex($actionNestingIndex);

            $resolvedActions[] = $resolvedAction;

            // A host that cannot carry schemas is skipped rather than thrown at: an
            // action that has a schema can never have been mounted on one —
            // mountAction() throws first — so every stack reaching here is
            // schema-less anyway.
            if ($this instanceof InteractsWithSchemas) {
                $this->cacheSchema(
                    "mountedActionSchema{$actionNestingIndex}",
                    $this->getMountedActionSchema($actionNestingIndex, $resolvedAction),
                );
            }
        }

        return $resolvedActions;
    }

    public function mountedActionShouldOpenModal(?Action $mountedAction = null): bool
    {
        return ($mountedAction ?? $this->getMountedAction())?->shouldOpenModal(
            checkForSchemaUsing: $this->mountedActionHasSchema(...),
        ) ?? false;
    }

    public function getAction(string $name): ?Action
    {
        if (array_key_exists($name, $this->cachedActions)) {
            return $this->cachedActions[$name];
        }

        if ((! str_ends_with($name, 'Action')) && method_exists($this, $method = "{$name}Action")) {
            $methodName = $method;
        } elseif (method_exists($this, $name)) {
            $methodName = $name;
        } else {
            return null;
        }

        $returnTypeReflection = new ReflectionMethod($this, $methodName)->getReturnType();

        if ($returnTypeReflection === null) {
            return null;
        }

        $returnTypes = $returnTypeReflection instanceof ReflectionUnionType
            ? $returnTypeReflection->getTypes()
            : [$returnTypeReflection];

        $hasActionReturnType = array_any(
            $returnTypes,
            fn (ReflectionType $returnType): bool => $returnType instanceof ReflectionNamedType
                && is_a($returnType->getName(), Action::class, allow_string: true),
        );

        if (! $hasActionReturnType) {
            return null;
        }

        $action = $this->{$methodName}();

        if (! $action instanceof Action) {
            throw new ActionNotResolvableException('Actions must be an instance of ' . Action::class . ". The [{$methodName}] method on the live component returned an instance of [" . get_debug_type($action) . '].');
        }

        return $this->cacheAction($action);
    }

    public function cacheAction(Action $action): Action
    {
        $action->liveComponent($this);

        return $this->cachedActions[$action->getName()] = $action;
    }

    /**
     * The id must be minted the first time this is reached from inside a LiveAction
     * (as mountAction()/unmountAction() do today via getMountedModalId()), never
     * first reached from the template. AddLiveAttributesSubscriber::onPreRender
     * dehydrates #[LiveProp]s — including this one — before the component's
     * template body renders, so an id first minted during rendering never makes it
     * into that dehydration pass: the next request hydrates $actionsHostId back to
     * null and mints a different value, changing every dialog id and breaking the
     * DOM identity the morph relies on. A mount()-seeded stack or a future
     * URL-driven default action would violate this if it read the id for the first
     * time from the template rather than from a LiveAction.
     */
    public function getActionsHostId(): string
    {
        return $this->actionsHostId ??= bin2hex(random_bytes(4));
    }

    public function getMountedModalId(?int $actionNestingIndex = null): string
    {
        $actionNestingIndex ??= ($this->mountedActions === []) ? 0 : array_key_last($this->mountedActions);

        return "sy-{$this->getActionsHostId()}-action-{$actionNestingIndex}";
    }

    /**
     * Sylament-only declarative-choreography helper (no Filament equivalent — their
     * client JS decides visibility): the top frame is open when its modal should
     * open; a parent frame stays open only while every deeper resolved frame *that
     * opens a modal of its own* declares overlayParentActions(). The qualifier is
     * load-bearing, not hedging — a modal-less deeper frame (Action::halt() mid-flow)
     * must not close the parent modal that halt() exists to keep open.
     */
    public function isMountedActionModalOpen(int $actionNestingIndex): bool
    {
        if ($this->mountedActions === []) {
            return false;
        }

        if ($actionNestingIndex === array_key_last($this->mountedActions)) {
            return $this->mountedActionShouldOpenModal();
        }

        foreach ($this->getMountedActions() as $mountedAction) {
            if (($mountedAction->getNestingIndex() ?? 0) <= $actionNestingIndex) {
                continue;
            }

            if (! $this->mountedActionShouldOpenModal($mountedAction)) {
                continue;
            }

            if (! $mountedAction->shouldOverlayParentActions()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{name: string, arguments: array<mixed>, context: array<mixed>} $descriptor
     * @param array<Action> $parentActions
     */
    protected function resolveAction(array $descriptor, array $parentActions): ?Action
    {
        if ($parentActions !== []) {
            $parentAction = array_last($parentActions);

            return $parentAction->getModalAction($descriptor['name'])
                ?? throw new ActionNotResolvableException("Action [{$descriptor['name']}] was not found for action [{$parentAction->getName()}].");
        }

        return $this->getAction($descriptor['name']);
    }

    /**
     * Resolve the descriptor against the host schema its `context['schemaComponent']`
     * key names: the segment before the first dot is the schema name, the rest is
     * the nested container key handed to `Schema::getAction()`. Unresolvable
     * client references — unknown schema name, unknown action — return null so the
     * mount no-ops; a host that cannot carry schemas at all is a wiring bug and
     * throws.
     *
     * The schema cache is consulted first, and a registered name takes the branch
     * whole: it skips the discovery guard, and it is the one thing a non-root frame
     * may resolve through. Both concessions are the same argument — only
     * `getMountedActionSchema()` registers a cache-only schema, under a
     * server-minted `mountedActionSchema{$i}`, so the client can name one but never
     * mint one. That is what makes an action declared *inside* a mounted-action
     * schema resolvable at all: its container has no declaring method, and it sits
     * at frame 1 or deeper. Filament reaches the same case through a bare
     * `getSchema($name)` (`vendor/filament/actions/src/Concerns/InteractsWithActions.php:643`).
     *
     * Everything else keeps the old contract. A non-root frame naming a
     * method-discovered schema still returns null — a nested frame must descend
     * through its parent's declared modal actions, never re-root itself against a
     * host-declared schema. And the discovery path keeps the full shape guard,
     * which ports Filament's `cacheSchema()` check: the candidate `{$name}()` /
     * `{$name}Schema()` method must be schema-shaped — first parameter typed
     * `Schema`, or parameterless with a `Schema` return type. It runs here because
     * `HasSchemas::getSchema()` throws on unknown names and invokes whatever method
     * discovery finds; with a client-writable context key, the shape guard is what
     * keeps every other host method from being remotely invocable.
     *
     * @param array{name: string, arguments: array<mixed>, context: array<mixed>} $descriptor
     * @param array<Action> $parentActions
     */
    protected function resolveSchemaComponentAction(array $descriptor, array $parentActions): ?Action
    {
        if (! $this instanceof InteractsWithSchemas) {
            throw new LogicException('An action context supplied a schemaComponent key but ' . static::class . ' is not a schema host — implement ' . InteractsWithSchemas::class . ' and use the ' . HasSchemas::class . ' trait.');
        }

        $key = $descriptor['context']['schemaComponent'] ?? null;

        if (! is_string($key)) {
            return null;
        }

        $dotPosition = mb_strpos($key, '.');
        $schemaName = ($dotPosition === false) ? $key : mb_substr($key, 0, $dotPosition);
        $nestedContainerKey = ($dotPosition === false) ? null : mb_substr($key, $dotPosition + 1);

        $isCachedSchema = $this->hasCachedSchema($schemaName);

        if (($parentActions !== []) && (! $isCachedSchema)) {
            return null;
        }

        if (! $isCachedSchema) {
            $methodName = match (true) {
                method_exists($this, $schemaName) => $schemaName,
                method_exists($this, "{$schemaName}Schema") => "{$schemaName}Schema",
                default => null,
            };

            if ($methodName === null) {
                return null;
            }

            $methodReflection = new ReflectionMethod($this, $methodName);
            $firstParameter = $methodReflection->getParameters()[0] ?? null;

            if ($firstParameter === null) {
                $returnType = $methodReflection->getReturnType();

                if (! $returnType instanceof ReflectionNamedType || ! is_a($returnType->getName(), Schema::class, allow_string: true)) {
                    return null;
                }
            } else {
                $parameterType = $firstParameter->getType();

                if (! $parameterType instanceof ReflectionNamedType) {
                    return null;
                }

                $parameterTypeName = $parameterType->getName();

                if (! class_exists($parameterTypeName) || ! is_a($parameterTypeName, Schema::class, allow_string: true)) {
                    return null;
                }
            }
        }

        $action = $this->getSchema($schemaName)->getAction($descriptor['name'], $nestedContainerKey);

        return $action?->liveComponent($this);
    }
}
