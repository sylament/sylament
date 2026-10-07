---
description: 'The root container of the component model: the object a live component holds, the state root every field binds through, and the grid/flex layout that… (draft)'
ports:
  - vendor/filament/schemas/src/Schema.php
  - vendor/filament/schemas/src/Concerns/
owns:
  - packages/schemas/src/Components/Concerns/CanAccessAssociations.php
  - packages/schemas/src/Concerns/BelongsToParentComponent.php
  - packages/schemas/src/Concerns/CanBeEmbeddedInParentComponent.php
  - packages/schemas/src/Concerns/CanBeInline.php
  - packages/schemas/src/Concerns/CanModifyActions.php
  - packages/schemas/src/Concerns/HasComponents.php
  - packages/schemas/src/Concerns/HasFieldWrapper.php
  - packages/schemas/src/Concerns/HasHeadings.php
  - packages/schemas/src/Concerns/HasKey.php
  - packages/schemas/src/Concerns/HasState.php
  - packages/schemas/src/Schema.php
  - packages/schemas/templates/partials/Schema/
---
# Schema (schemas)

[`Sylament\Schemas\Schema`](../../../packages/schemas/src/Schema.php) — the root container of the component model: the object a live component holds, the state root every field binds through, and the grid/flex layout that renders the child tree.
It extends `support`'s [`ViewComponent`](../support/component.md) and ports [`schemas/src/Schema.php`](../../../vendor/filament/schemas/src/Schema.php), folding the single-use container concerns Filament splits across `Concerns/` — each is a surface row below rather than a file of its own.

The **shared** concerns Schema composes keep their own detail files and are named in prose here without a status row: [`HasColumns`](has-columns.md) (Schema aliases `getAllColumns` → `getOwnAllColumns` and wraps it for parent inheritance), [`HasGap`](has-gap.md) (Schema overrides `hasGap()`), [`HasStateBindingModifiers`](has-state-binding-modifiers.md) (Schema overrides `isLive*()`/`getLiveDebounce()`), the host contract [`InteractsWithSchemas`](interacts-with-schemas.md), the bound-host trait [`BelongsToLiveComponent`](belongs-to-live-component.md), the record binding [`BelongsToRecord`](belongs-to-record.md) (both tiers in one file, and the home of the `fill()`/`getData()` mapping edges), the operation context [`HasOperation`](has-operation.md), and the PropertyAccess state backend [`property-accessor.md`](property-accessor.md) — the one place the `data_get`/`data_set` → Symfony `PropertyAccess` translation is derived, referenced by every state read/write here.
From `support` it composes [`HasAlignment`](../support/has-alignment.md) (the four alignment factories build on it) and `HasExtraAttributes`.
The single-use validation concern [`CanBeValidated`](can-be-validated.md) also keeps its own file rather than folding in here — it spans the root trait and the host-side machinery in [`HasSchemas`](has-schemas.md).

## Surface

Every point of this container, plus one row per folded concern.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Construction + `make()`](../../../packages/schemas/src/Schema.php) | [`Schema::make()`](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [`resolveDefaultClosureDependencyForEvaluationByName` (`$container`/`$liveComponent`/`$record`)](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-model-closure-parameter-and-the-model-type-gate-are-dropped) |
| [`resolveDefaultClosureDependencyForEvaluationByType` (record probe)](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-model-closure-parameter-and-the-model-type-gate-are-dropped) |
| [alignment factories `start`/`end`/`center`/`between`](../../../packages/schemas/src/Schema.php) | [`Schema::start()` …](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [`isLive`/`isLiveOnBlur`/`isLiveDebounced`/`getLiveDebounce` parent-walk overrides](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | in progress | — | [detail](#the-parent-walk-inheritance-overrides-are-untested) |
| [`hasGap`/`isDense` parent-walk overrides](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [`getAllColumns` parent inheritance override](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | in progress | — | [detail](#the-parent-walk-inheritance-overrides-are-untested) |
| [Twig-template rendering (`Schema/index.html.twig`)](../../../packages/schemas/templates/partials/Schema/index.html.twig) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-schema-renders-visible-children-only-without-hidden-placeholders) |
| [`dispatchClientSideStateReset()`](../../../packages/schemas/src/Schema.php) | [`Schema::dispatchClientSideStateReset()`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-root-schema-drops-livewire-partial-render-and-alpine-wiring) |
| Root Livewire/Alpine render wiring (`wire:partial`, `x-data filamentSchema`) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | skipped | — | [diverges](#the-root-schema-drops-livewire-partial-render-and-alpine-wiring) |
| [`BelongsToParentComponent`](../../../packages/schemas/src/Concerns/BelongsToParentComponent.php) | [`Concerns/BelongsToParentComponent.php`](../../../vendor/filament/schemas/src/Concerns/BelongsToParentComponent.php) | done | — | [diverges](#parentcomponent-accepts-null-to-detach) |
| [`CanBeEmbeddedInParentComponent`](../../../packages/schemas/src/Concerns/CanBeEmbeddedInParentComponent.php) | [`Concerns/CanBeEmbeddedInParentComponent.php`](../../../vendor/filament/schemas/src/Concerns/CanBeEmbeddedInParentComponent.php) | done | — | — |
| [`CanBeInline`](../../../packages/schemas/src/Concerns/CanBeInline.php) | [`Concerns/CanBeInline.php`](../../../vendor/filament/schemas/src/Concerns/CanBeInline.php) | done | — | — |
| [`HasComponents`](../../../packages/schemas/src/Concerns/HasComponents.php) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | in progress | — | [detail](#hascomponents-still-lacks-the-lookup-layer) · [diverges](#getcomponents-preserves-the-callers-array-keys-instead-of-reindexing) |
| [`getComponent(string\|Closure $findComponentUsing, bool $withActions = true, bool $withHidden = false, bool $isAbsoluteKey = false)`](../../../packages/schemas/src/Concerns/HasComponents.php) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | done | — | [detail](#hascomponents-still-lacks-the-lookup-layer) |
| [`getFlatComponents(bool $withActions = true, bool $withHidden = true)` — the `withHidden` arm](../../../packages/schemas/src/Concerns/HasComponents.php) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | done | — | [diverges](#the-component-collection-keeps-a-single-cache-and-drops-the-flag-keyed-memoization) |
| [`HasState`](../../../packages/schemas/src/Concerns/HasState.php) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | in progress | — | [detail](#hasstate-still-lacks-constant-state-relationships-and-partial-rendering) · [diverges](#getstate-validates-without-a-prepareforvalidation-mutation-layer) |
| [`fill(object\|array\|null $state = null)` — the object arm](../../../packages/schemas/src/Concerns/HasState.php) | — | done | — | [belongs-to-record.md](belongs-to-record.md#fillobject-reads-only-the-paths-the-schema-declares) |
| [`readStateFromRecord()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [belongs-to-record.md](belongs-to-record.md#reads-are-lenient-writes-are-strict) |
| [`loadStateFromRelationships()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) — the relationship-backed re-read a host runs after its flush | [`Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [belongs-to-record.md](belongs-to-record.md#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter) |
| [`CanAccessAssociations`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php) — `getAssociationReader()` / `setAssociationReader()`, composed so the host's record check reaches the reader through the schema it validates | — | done | — | [association-reader.md](association-reader.md#the-accessor-trait-is-shared-with-the-table-and-action-tiers) |
| [`HasKey`](../../../packages/schemas/src/Concerns/HasKey.php) | [`Concerns/HasKey.php`](../../../vendor/filament/schemas/src/Concerns/HasKey.php) | in progress | — | [detail](#haskey-ports-the-schema-key-chain-minus-the-state-path-fallback) |
| [`HasHeadings`](../../../packages/schemas/src/Concerns/HasHeadings.php) | [`Concerns/HasHeadings.php`](../../../vendor/filament/schemas/src/Concerns/HasHeadings.php) | done | — | — |
| [`CanModifyActions`](../../../packages/schemas/src/Concerns/CanModifyActions.php) | [`Concerns/CanModifyActions.php`](../../../vendor/filament/schemas/src/Concerns/CanModifyActions.php) | done | — | [diverges](#modifyaction-and-modifyactiongroup-guard-a-callbacks-return-type-at-runtime) |
| [`HasFieldWrapper`](../../../packages/schemas/src/Concerns/HasFieldWrapper.php) | [`Concerns/HasFieldWrapper.php`](../../../vendor/filament/schemas/src/Concerns/HasFieldWrapper.php) | done | — | [diverges](#the-field-wrapper-view-is-opaque-data-with-no-closure-arm-and-no-parent-walk) |

Beyond the folded concerns, four of Filament's composed traits are not yet ported at all — `CanBeHidden`, `HasEntryWrapper`, `HasInlineLabels`, and support's `HasDefaultDataFormattingSettings` — and earn their own README rows as they land.
(`CanBeDisabled`, `CanBeValidated`, `BelongsToRecord`, and `HasOperation` are composed and tracked in their own files: [can-be-disabled.md](can-be-disabled.md), [can-be-validated.md](can-be-validated.md), [belongs-to-record.md](belongs-to-record.md), [has-operation.md](has-operation.md).)

## Details

### The parent-walk inheritance overrides are untested

`isLive()`/`isLiveOnBlur()`/`isLiveDebounced()`/`getLiveDebounce()` and `getAllColumns()` each fall through to `getParentComponent()?->…()` when the schema declares no own value — the container's inheritance semantics.
The own-value branches are exercised (state-binding-modifier reads, `SchemaRenderTest` column counts), but no test builds a parent→child schema pair and asserts the child inherits the parent's live mode or column count.
**In:** the override bodies, ported faithfully.
**Left:** direct coverage of the fall-through.
**Next:** add a unit case that parents one schema under another and asserts the inherited `isLive`/`getAllColumns` result.

### `HasComponents` still lacks the lookup layer

`components()`/`getComponents()`/`getFlatComponents()`/`getComponentByStatePath()`/ `getAction()`/`getComponent()` are ported and covered (43 unit cases plus the `SchemaRenderTest` coercion and hidden-filtering paths).
**In:** lazy closure resolution, container assignment, `string`→`Text` and other→`Html` child coercion, visibility filtering, top-level/nested/skip-container state-path lookup, and — matching Filament's unions — `Action`/`ActionGroup` children: each is passed through the container's `modifyAction()`/`modifyActionGroup()` hook, stamped `schemaContainer($this)`, and left uncoerced; `getComponents(bool $withActions = true, bool $withHidden = true)` and `getFlatComponents(bool $withActions = true, bool $withHidden = true)` drop them when `$withActions` is false (the state walks in `HasState`/ `HasSchemas` and `getComponentByStatePath()` all pass `withActions: false`; `getFlatComponents()` appends actions but never descends into them).
Both accessors declare PHPStan conditional return types, so `withActions: false` call sites keep `array<Component>` inference.
`getComponent(string|Closure $findComponentUsing, bool $withActions = true, bool $withHidden = false, bool $isAbsoluteKey = false)` is the single-lookup port — Filament's flat arms only (vendor `Concerns/HasComponents.php:152-212`): a string key relative to this container (`!$isAbsoluteKey` and a filled own `getKey()`) is prefixed with that key before it is looked up; a `Closure` runs as a `first()`-style predicate over `getFlatComponents($withActions, $withHidden)` instead.
The string arm builds its own key→component map from that flat list rather than threading Filament's `withAbsoluteKeys` flag through `getFlatComponents()` (components with a `null` key, and every `Action`/`ActionGroup` — neither carries a key at all — are skipped when the map is built).
**Gap:** Filament's `$skipComponentsChildContainersWhileSearching` arm — the nested-traversal branch that lets a component's own `$get`/`$set` avoid recursing into itself — is not ported here; `getComponentByStatePath()` already carries that parameter for the repeater case, and `getComponent()` would grow it there if a caller needs it.
The container-wide `getAction(string $actionName, ?string $nestedContainerKey = null)` walks the visible components: a direct `Action` child matches by name, a direct `ActionGroup` child by its flat-actions map, a component-registered action only when `$nestedContainerKey` exactly equals the owning component's relative key, and every component's child schemas recursively — keyed child schemas are entered only when the remaining key matches or prefixes them, unkeyed ones pass it through, and a keyed component's inheritance-key prefix is stripped before descending (`str_starts_with`/`mb_substr`, no Laravel `str()`).
Depth-first, first match wins; hidden components are skipped.
`cloneComponents()` is ported and pinned by [`CloneableTest`](../../../packages/schemas/tests/Unit/Concerns/CloneableTest.php) ([diverges](#clonecomponents-never-stamps-the-original-and-always-resets-the-cache)).
**Left:** `getFlatFields()` (depend on Fields), `clearCachedChildSchemas()` (depends on child-schema caching — [repeater.md](../forms/repeater.md)); the `schema()` alias is deliberately not ported ([diverges](#the-schema-alias-is-not-ported)).
**Next:** port the rest as Fields land.
The structural difference it keeps — a single `$cachedComponents` slot instead of Filament's flag-keyed memoization — is permanent and written up in [Divergences](#the-component-collection-keeps-a-single-cache-and-drops-the-flag-keyed-memoization).

### `HasState` still lacks constant state, relationships, and partial rendering

Schema is the state root; this trait assembles the dehydrated bag.
**In** and tested (11 unit + 4 integration cases through a real UX LiveComponent, plus the validation-path cases in [`HasSchemasValidationTest`](../../../packages/schemas/tests/Integration/Concerns/HasSchemasValidationTest.php)): `fill()` → `hydrateState()` → `callAfterStateHydrated()` → `fillStateWithNull()`, including the Sylament-only object arm (`fill($record)` → `readStateFromRecord()` → the unchanged array arm — see [belongs-to-record.md](belongs-to-record.md#fillobject-reads-only-the-paths-the-schema-declares)); `getRawState()`/`rawState()` and `statePath()`/`getStatePath()`/`hasStatePath()` with the absolute-path cache; and both dehydrated reads — the validating `getState(bool $shouldCallHooksBefore = true, ?Closure $afterValidate = null)` (`validate()` on entry, before any dehydration; `$afterValidate` invoked with the final state) and the hook-free, non-validating `getStateSnapshot()`, sharing one assembly (`callBeforeStateDehydrated` — guarded by `$shouldCallHooksBefore`, which the snapshot passes `false` ([below](#getstatesnapshot-never-runs-the-beforestatedehydrated-hooks)) — → `dehydrateState` → `mutateDehydratedState` → unwrap) including `hasDehydratedComponent()`/`buildDehydratedComponentsCache()` and the shared-state-path sibling handling.
**Left**, all unported:

- **Constant state** (`state()`/`constantState()`/`getConstantState()`) — the infolist static-state path; the record fallback chain it reads now exists ([`BelongsToRecord`](belongs-to-record.md)), so it is **blocked** on infolists alone — plus the relationship state paths for its `getStatePathForRelationship()` arm.
- **The relationship layer** (`saveRelationships()`, and `loadStateFromRelationships()` as a pass inside `getState()`) — **skipped**.
  The Doctrine relation layer provides the capability without either walk: the per-component hook pair is [consulted by the two mapping edges](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) — `fill()` and `getData()` already traverse the record path map — and Doctrine's UnitOfWork collapses the two-phase create these root-tier walks exist to sequence, so a third pass would have nothing left to do.
  The load half's post-save re-read is a member of its own, `loadStateFromRelationships()` on [`BelongsToRecord`](belongs-to-record.md#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter), which the host calls after its flush rather than `getState()` calling it.
  [Recorded in renames.md](../renames.md#renames).
- **Partial rendering** (`partiallyRender()`/`shouldPartiallyRender()`/ `fillPartially()`/`hydrateStatePartially()`) — **blocked** on a UX LiveComponent partial-render equivalent; decide whether targeted re-render is a goal before porting.
- `getStateOnly()`/`getStateExcept()` — a trivial `array_intersect_key`/ `array_diff_key` over `getState()`; add when a consumer needs them.
- The recursive public `flushCachedAbsoluteStatePaths()` — only the single protected `flushCachedAbsoluteStatePath()` exists; add the tree-walking flush when post-cache mutation (dynamic repeaters) becomes reachable.

**Next:** port each once the prerequisite named in its bullet lands — bar the skipped one, which has no next step.
Two small additions over Filament's container trait already ship: `hasStatePath()` is defined here (Filament inlines `filled($this->statePath)`), and the shared assembly resets the dehydrated cache on entry so each run rebuilds it.

Two orderings inside that pipeline are load-bearing, and neither is visible in Filament's source, because Filament reaches both for free: it hydrates recursively, firing each component's `afterStateHydrated` hooks at the end of its own descent, where this port runs three separate passes over the schema.
`fill()` therefore fires the hooks **before** `fillStateWithNull()` — the null-fill pass writes null at every unset leaf path, which would clobber a value a hook is about to read (a simple `Repeater`'s scalar item, `tags.0`, against the leaf `tags.0.value`) — and `callAfterStateHydrated()` descends **children-first**, re-reading `getChildSchemas()` at each step, so a component that re-keys its own child state (a nested `Repeater`) never writes at a path its parent has just renamed.
The [Repeater](../forms/repeater.md#the-item-pipeline-runs-children-first-and-before-the-null-fill-pass) is the first component to make either observable; the forms repeater record round-trip pins them.

### `HasKey` ports the schema key chain, minus the state-path fallback

The schema-scope key model — `key()` setter, absolute prefix from the parent component's inheritance key, `getInheritanceKey()` fallback, attribute/JS-char sanitization, and absolute/inheritance-key caching with flush hooks — is ported and covered.
**Left:** two pieces.
`getLiveComponentKey()` (Filament's `getLivewireKey()`, the DOM key from the live-component id) is no longer blocked — the host id it needs shipped as [`getSchemasHostId()`](live-component.md#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id) with the component-tier port, and the schema tier simply has no consumer yet.
More importantly, `getKey()` silently omits Filament's `?? getStatePath(isAbsolute: false)` fallback even though `getStatePath()` is live on the schema via `HasState` and the sibling component-level `HasKey` keeps it — an unflagged, untested deviation, not a recorded decision (commit `7aad138` gives no rationale).
**Next:** reconcile it — either restore `$key = $this->evaluate($this->key) ?? $this->getStatePath(isAbsolute: false)` with a covering test (a keyless schema carrying a state path), or, if the drop is intentional, promote it to a deliberate divergence explaining why a schema key never falls back to its state path while a component key does.
The gap now has a first consumer compensating for it: forms' `Repeater` stamps `->key($itemKey)` alongside `->statePath($itemKey)` on every item schema it clones, because an unkeyed one inherits the repeater's key and collapses every item's field keys — and DOM ids — onto one value ([repeater.md](../forms/repeater.md#every-item-schema-is-keyed-as-well-as-re-pathed)).
Restoring the fallback removes that stamp.
The deprecated `name()`/`getName()` aliases are a separate, deliberate [skip](#the-deprecated-name-and-getname-aliases-are-not-ported).

## Divergences

### The schema() alias is not ported

The root tier follows the component tier: `components()` is the sole spelling, `schema()` is not ported.
One decision, recorded once — [component.md](component.md#the-schema-alias-is-not-ported) carries the reasoning, the [rename table](../renames.md) the row.
(Filament ships the root alias undeprecated too; the earlier "deprecated" label in this file read Filament's docs-level direction into the code and was wrong.)

### Schema is the state root; Filament splits state across the Livewire base and the schema

Filament divides container state between its `Livewire\Component` base (which holds the `$data` array, the updated-lifecycle hooks, and partial rendering) and `Schema`.
Sylament has no Livewire base to inherit: a schema binds to a plain UX LiveComponent — the [`InteractsWithSchemas`](interacts-with-schemas.md) host — reached through [`BelongsToLiveComponent`](belongs-to-live-component.md), and `HasState` reads and writes the host's bound properties through [PropertyAccess](property-accessor.md).
Everything Filament kept on the Livewire base that the schema depends on (the state bag, the updated dispatch) is relocated: state lives on the host's typed properties, and the updated-lifecycle dispatch moves to the host's [`#[PostHydrate]` pass](#schema-level-afterstateupdated-dispatch-moves-to-the-hosts-posthydrate-pass).
The schema becomes the single state root, and the render-time Livewire coupling below drops out with the base class.
Reverses only if a Livewire-shaped base is reintroduced — not planned.

### The root schema drops Livewire partial-render and Alpine wiring

Filament's `renderEmbeddedHtml()` emits `wire:partial` (`schema.{key}`), `x-data="filamentSchema({ livewireId, schemaKey })"`, and `x-on:form-validation-error`/`reset-schema-component-state` on the root div.
Sylament's `Schema/index.html.twig` emits no `wire:partial` and no client-side state object: live binding runs field-side through UX LiveComponent `data-model` attributes plus a full component re-render (proven by `LiveStateTest`), so per-schema partial rendering has no port and reverses only if it becomes a goal.

The two window listeners **do** port, as a Stimulus controller on the root schema element rather than an Alpine `x-on:` pair, and both of their producers now exist: [`validateSchema()`'s throwing branch](can-be-validated.md#the-throwing-branch-announces-form-validation-error-carrying-the-schema-identity) announces `form-validation-error`, and `dispatchClientSideStateReset()` is ported here as a one-method surface on `Schema` announcing `reset-schema-component-state` with `{schemaKey: getKey()}` (Filament sends `livewireId` beside the key; a browser event dispatched on the host's own root has nothing left for an id to disambiguate — the host id that *does* exist, [`getSchemasHostId()`](live-component.md#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id), is a DOM-key prefix, not a listener filter).
Nothing in the port **calls** the reset yet — Filament's callers are `CreateRecord`'s create-another and `CreateAction`, neither of which has landed — so it ships as the announcement half of a wired pair, exercised by a fixture action in `HasSchemasValidationTest`.

### The `$model` closure parameter and the `Model` type gate are dropped

By-name resolution now covers `$container` (the schema itself), `$liveComponent` (the bound host, read from the raw nullable property so a standalone schema injects `null`), and `$record` (`getRecord()`), and the `…ByType` override is wired: a parameter whose type the bound record satisfies receives the record.
Two pieces of Filament's version stay dropped.
`$model` — the class-string parameter — is not injected, part of the [record-only rename](belongs-to-record.md#the-model-closure-parameter-is-not-ported).
And where Filament gates its by-type probe on `is_a($type, Model::class)`, this override probes `$record instanceof $parameterType` because there is no `Model` base class to gate on ([why that is safe](belongs-to-record.md#the-record-type-probe-is-instanceof-not-a-model-gate)).
The self/static-type identity is still handled by the base `EvaluatesClosures` own-class mechanism rather than a `self` arm here.

### `parentComponent()` accepts null to detach

Filament types `parentComponent(Component $component)` non-nullable.
Sylament widens it to `?Component`, so a schema can be reset to root (`isRoot()` true again).
Harmless superset of Filament's contract; reversible if a caller ever needs the non-null guarantee.

### The component collection keeps a single cache and drops the flag-keyed memoization

Filament memoizes `getComponents` (with/without hidden), `getFlatComponents`, and `getComponentByStatePath` under multi-dimensional caches keyed by `withActions`/`withHidden`/`withOriginalKeys`/`withAbsoluteKeys`/`containerKey`/ `spl_object_id`.
Sylament keeps only `$cachedComponents` (the raw resolved list) and recomputes flat/by-state-path each call.
`getComponents(bool $withActions = true, bool $withHidden = true)` matches Filament's `withActions` default but keeps the flipped `withHidden = true` default (Filament `false`; the render pipeline — `Schema::render()`'s empty guard and `getExtraTemplateData()` — passes `withHidden: false` explicitly), drops `withOriginalKeys` (see the key-preservation divergence below), and filters on `isVisible()` rather than `isHidden()`.
`getFlatComponents(bool $withActions = true, bool $withHidden = true)` now threads `$withHidden` through as well — Filament's own parameter, kept with the same flipped default as `getComponents()` (Filament defaults it `false`; every existing caller here relied on the hidden-inclusive shape before this parameter existed, so the port keeps that shape rather than silently changing it).
`getComponent()`/`getSchemaComponent()` keep Filament's `withHidden: false` default and pass it down explicitly, matching Filament's own call sites.
Filament's `withAbsoluteKeys`/`containerKey` parameters and their key-indexed carry are still dropped: the flat list itself stays ordered and unkeyed, and `getComponent()` builds its own key→component map from that list on each call rather than threading a flag through the flattening.
Correct but not memoized; revisit if flat/state-path lookups become hot.

### `cloneComponents()` never stamps the original and always resets the cache

Two corrections while porting [`HasComponents::cloneComponents()`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php).
Filament stamps `container($this)` onto each **original** component child before copying it, mutating an instance the source schema still holds; ours only copies, because `getComponents()` re-stamps `container`/`schemaContainer` when it rebuilds its cache, so the stamp is redundant on the clone and damaging on the original.
And Filament resets its component caches only inside the non-`Closure` arm, so cloning a schema whose `components()` is a closure hands the clone the original's already-built component list — with the *original* as their container; ours resets `$cachedComponents` unconditionally.
Both are pinned by [`CloneableTest`](../../../packages/schemas/tests/Unit/Concerns/CloneableTest.php).
Actions and action groups are shallow `clone`s here and in `cloneChildComponents()`, matching the component tier: `Action` has no `getClone()` yet ([action.md](../actions/action.md)).

### `getState()` validates without a prepareForValidation mutation layer

Filament's two container reads are now both ported — `getState(bool $shouldCallHooksBefore = true, ?Closure $afterValidate = null)` runs `validate()` on entry (throwing [`ValidationException`](can-be-validated.md) before any dehydration) and `getStateSnapshot()` is the validation-free variant.
Of the machinery Filament wraps around its validate call, one piece is ported and two are not.

The mutation layer **is** ported, but as a flat-map walk rather than Livewire's nested rewrite: `Schema::mutateStateForValidation(array $state): array` takes the `absolutePath => value` map the runner has just read, walks the components child-schemas-first (`withActions: false, withHidden: true`, skipping `isNeitherDehydratedNorValidated()`), and hands each path the map holds to the owning component's own [`mutateStateForValidation()`](component.md#the-mutatestateforvalidation-members-mutate-the-runners-copy-of-one-path).
[`validateSchema()`](interacts-with-schemas.md) calls it between reading every path and validating any of them, so a mutated value is what both the presence gate and the validator see.
The signature is **by value** where the dehydration sibling `mutateDehydratedState(array &$state = [])` is by reference: the runner keeps the return, and a by-reference parameter that also returns is a shape this tree has called out before.

Two pieces stay permanently unported.
`prepareForValidation()` — the host-side merge of `getRules()` / `getValidationAttributes()` into one Livewire call — has no counterpart because validation here is per path, not per component ([interacts-with-schemas.md](interacts-with-schemas.md)).
And `pruneStateToMatchKeys()`: Filament's `getState()` keeps only the keys its validation returned — Laravel's `validated()` answers the rule keys alone, and every field registers one, if only `nullable` — and prunes the raw state to them, so a key no field declares never leaves it.
Here the runner validates per declared path and discards its copy, and `getState()` seeds the whole raw bag before the dehydration walk, which prunes only a non-dehydrated component's own path.
So a key no component owns — one a client wrote into a host's writable `data` prop, every nested path of which ux-live-component makes writable — stays in `getState()` and in the `$data` every mutate hook and `using()` closure receives.
Nothing of it reaches a record through the default seams, since `getData()` writes only the record path map's paths, and those seams refuse a path a hook adds that no component maps ([belongs-to-record.md](belongs-to-record.md#a-hook-added-path-no-component-maps-is-answered-for-the-write-seams-to-refuse)); but a `using()` closure that writes `$data` wholesale writes it too — a mass-assignment footgun.
Pruning to declared keys is a known gap, not done.
The `Component::withVisibilityCache(...)` wrapper is likewise absent — no visibility cache exists.
`$afterValidate` is invoked plainly with the final dehydrated state (Filament's `value($afterValidate, $state)` position).
The child-schema `isHiddenAndNotDehydratedWhenHidden()` guards remain un-mirrored, consistent with hidden-guarding living at component level ([can-be-validated.md](can-be-validated.md#the-collection-walk-cannot-skip-directly-hidden-child-schemas)).

### `getStateSnapshot()` never runs the `beforeStateDehydrated` hooks

Filament's `getStateSnapshot()` is `dehydrateState()` + `mutateDehydratedState()` and nothing else; only `getState()` reaches `callBeforeStateDehydrated()`.
Ours shares one assembly with `getState()`, so the snapshot passes `shouldCallHooksBefore: false` to keep that line — and the line is load-bearing rather than cosmetic, because a `beforeStateDehydrated` hook is where a component *commits* its state rather than merely reading it.
The live case is [`BaseFileUpload`](../forms/file-upload.md), whose hook is the store pass: it moves each held upload onto the field's disk and **deletes** the temporary one, so the hook can only be run once per held file.

That makes any snapshot taken during a render destructive, and a repeater takes one per item per request — [`Repeater::getItemLabel()`](../forms/repeater.md) computes its `state` injection eagerly, whether or not an `itemLabel()` was ever declared.
A file field inside a repeater item therefore had its upload consumed by the render that followed it: the store ran against a throwaway pass whose `rawState()` write never reached the wire, the browser posted the same token back, and the save's own pass could no longer resolve it — the token's temporary file was gone.
The entry was dropped, `FileUploadStateCast::get()` answered `null` for the emptied map, and the field saved as null.
Mapped onto a non-nullable column that surfaced as `PropertyAccessor`'s `Expected argument of type "string", "null" given`; mapped onto a nullable one it was silent data loss.
Pinned at both levels: the hook-free snapshot in [`HasStateTest`](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php), and the repeater-item upload surviving its renders in [`LiveFileUploadTest`](../../../packages/forms/tests/Integration/LiveFileUploadTest.php).

`Repeater::getItemState()` already passed `shouldCallHooksBefore: false` for the same reason; `getRawItemState()` and `getItemLabel()` reach the hooks only through `getStateSnapshot()`, so both are covered by fixing it there.

### `getRawState()` seeds an empty array on a blank or top-level path

Filament's container `getRawState()` returns `data_get($livewire, $statePath) ?? []` typed `array|Arrayable`.
Sylament returns `array` only and, when the schema has no state path (top-level binding, no single bag to read), returns `[]` rather than reading the host — the intended seed for the dehydration prune.
`Arrayable` support is dropped (Doctrine collections are not `Arrayable`-shaped here).

### The absolute-path cache is a nullable field plus a boolean flag, not an unset typed property

Filament declares `protected string $cachedAbsoluteStatePath;` and detects a hit with `isset()`, flushing via `unset()` (with a phpstan-ignore for `unset.possiblyHookedProperty`).
Sylament declares `?string $cachedAbsoluteStatePath = null` plus `bool $hasCachedAbsoluteStatePath`, caching through a try/finally that sets value then flag and flushing both — to avoid `unset()` of a typed property and keep PHPStan level max clean without an ignore.
Behaviourally identical; not provisional.

### Schema-level afterStateUpdated dispatch moves to the host's PostHydrate pass

Filament's `Schema::callAfterStateUpdated(string $path)` walks components matching the updated path inside a partial-render `finally` block.
Sylament omits it from the trait and dispatches from `InteractsWithSchemas::callAfterSchemaStateUpdated()` under UX LiveComponent's `#[PostHydrate]`: after re-hydration it iterates `getFlatComponents` and fires `callAfterStateUpdatedHooks()` on every component whose `getRawState` differs from the snapshot `getOldSchemaState`.
This is the Symfony-idiom translation of Livewire's updated lifecycle; the path-matching-plus-bubbling shape and the `partiallyRender` `finally` block are not reproduced.
Reverses only if per-path targeted dispatch is needed.

### The deprecated `name()` and `getName()` aliases are not ported

Filament's schema-level `HasKey` carries `name()`/`getName()` as `@deprecated` forwarders to `key()`/`getKey()`.
Sylament omits them: they were dead on arrival in the source we port from, so shipping them only reintroduces the ambiguity the deprecation was retiring.
The recommended `key()`/`getKey()` are ported.
Reversible only if a migrating consumer proves it still calls the old names.

### `getComponents()` preserves the caller's array keys instead of reindexing

Filament's `filterComponents()` calls `array_values()` unless `withOriginalKeys` is set, so `getComponents()` returns a list reindexed from zero.
Sylament keeps each entry under its source index, and the visibility `array_filter` preserves keys too, so a filtered result can carry the numeric gaps Filament's reindexed output never has.
Reversible by wrapping the return in `array_values()` if a consumer ever needs a dense list.

### The schema renders visible children only, without hidden placeholders

Filament's `renderEmbeddedHtml()` walks `getComponents(withHidden: true)` and keeps every hidden child in the DOM as an empty placeholder (`div.fi-sc-action fi-hidden` for actions, a `toSchemaHtml(isVisible: false)` wrapper for components) so Livewire's DOM diffing stays aligned across re-renders.
Sylament renders visible children only: `Schema::render()` returns `null` when `getComponents(withHidden: false)` is empty — matching Filament's `''`-on-empty contract, which is what lets `Section` suppress the chrome around an empty slot — and `getExtraTemplateData()` hands the template the visible set as `visibleChildren` pairs, each carrying the child and a precomputed `isAction` flag (the Twig-side stand-in for the template's `instanceof` branch).
No placeholder element is ever emitted for a hidden child.
Revisit together with the live-component morphing strategy: if UX LiveComponent morphing needs stable placeholders the way Livewire's does, the loop switches to with-hidden visibility pairs like Filament's `$componentsWithVisibility`.

### `modifyAction()` and `modifyActionGroup()` guard a callback's return type at runtime

Filament's `($this->modifyActionsUsing)($action) ?? $action` relies on PHP's native return-type coercion: a callback that returns neither an `Action` nor `null` trips the method's own `: Action` return type, which throws a `TypeError` implicitly.
PHPStan cannot infer the return type of invoking a bare `?Closure` property, so it flags the expression itself as returning `mixed` — and this codebase writes no `Closure(Action): ?Action` signature spec anywhere, even for a callback invoked plainly with fixed arguments.
Sylament instead narrows explicitly: it holds the coalesced result in a local variable, `instanceof`-checks it, and throws the same `TypeError` itself on a mismatch.
Observable behavior is unchanged — a conforming callback or a `null` return works exactly as Filament's; a non-conforming one still throws `TypeError`, just raised explicitly rather than by implicit return-type coercion.
`modifyActionGroup()` carries the identical guard.
Reverses only if PHPStan gains inference for arbitrary closure invocations.

### The field-wrapper view is opaque data, with no closure arm and no parent walk

`fieldWrapperView(?string)` takes a plain string here where Filament's takes `string|Closure|null`, and `getFieldWrapperView()` returns what was set where Filament's `getCustomFieldWrapperView()` falls back to the parent component's.
Both narrowings follow from what schemas does with the value, which is nothing: it stores an opaque token that only forms' `FieldWrapper` interprets ([fused-group.md](../forms/fused-group.md#only-the-plain-wrapper-view-is-honored)), so there is no render-time moment for a closure to be evaluated in and no resolution order for the walk to feed.
The getter carries Filament's component-tier name rather than its container-tier `getCustomFieldWrapperView()`, because "custom" only distinguishes anything once a default and a walk exist — neither of which ships.
Widen the property and reinstate the walk in the same change as the [Component-tier twin](../forms/fused-group.md#the-component-tier-hasfieldwrapper-twin-is-not-ported), which is where the resolution order belongs.

_See also: the global [rename table](../renames.md) — the Livewire → UX LiveComponent renames (`BelongsToLivewire`→`BelongsToLiveComponent`, `livewire()`→`liveComponent()`, `getLivewire()`→`getLiveComponent()`, `getLivewireKey()`→`getLiveComponentKey()`) and the `viewIdentifier`→`template` idiom translation are recorded there, not as per-component divergences._
