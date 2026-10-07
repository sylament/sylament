---
description: 'The root container of the component model: the state root a live component holds, its folded container concerns, closure injection and grid template.'
ports:
  - vendor/filament/schemas/src/Schema.php
  - vendor/filament/schemas/src/Concerns/BelongsToParentComponent.php
  - vendor/filament/schemas/src/Concerns/CanBeEmbeddedInParentComponent.php
  - vendor/filament/schemas/src/Concerns/CanBeInline.php
  - vendor/filament/schemas/src/Concerns/CanModifyActions.php
  - vendor/filament/schemas/src/Concerns/Cloneable.php
  - vendor/filament/schemas/src/Concerns/HasComponents.php
  - vendor/filament/schemas/src/Concerns/HasFieldWrapper.php
  - vendor/filament/schemas/src/Concerns/HasHeadings.php
  - vendor/filament/schemas/src/Concerns/HasKey.php
  - vendor/filament/schemas/src/Concerns/HasState.php
owns:
  - packages/schemas/src/Concerns/BelongsToParentComponent.php
  - packages/schemas/src/Concerns/CanBeEmbeddedInParentComponent.php
  - packages/schemas/src/Concerns/CanBeInline.php
  - packages/schemas/src/Concerns/CanModifyActions.php
  - packages/schemas/src/Concerns/Cloneable.php
  - packages/schemas/src/Concerns/HasComponents.php
  - packages/schemas/src/Concerns/HasFieldWrapper.php
  - packages/schemas/src/Concerns/HasHeadings.php
  - packages/schemas/src/Concerns/HasKey.php
  - packages/schemas/src/Concerns/HasState.php
  - packages/schemas/src/Schema.php
  - packages/schemas/templates/partials/Schema/
---
# Schema (schemas)

[`Sylament\Schemas\Schema`](../../../packages/schemas/src/Schema.php) extends `support`'s [`ViewComponent`](../support/component.md) and folds the single-use container concerns Filament splits across `Concerns/`, each a surface row below.
The shared concerns it composes keep their own files: [`HasColumns`](has-columns.md) (Schema aliases `getAllColumns` to `getOwnAllColumns` and wraps it for parent inheritance), [`HasGap`](has-gap.md), [`HasStateBindingModifiers`](has-state-binding-modifiers.md), the bound-host trait [`BelongsToLiveComponent`](belongs-to-live-component.md), the record binding [`BelongsToRecord`](belongs-to-record.md), [`CanBeDisabled`](can-be-disabled.md), [`CanBeValidated`](can-be-validated.md), the operation context [`HasOperation`](has-operation.md), the association accessor [`CanAccessAssociations`](association-reader.md#the-accessor-trait-is-shared-with-the-table-and-action-tiers), and the PropertyAccess state backend [`property-accessor.md`](property-accessor.md), where the `data_get`/`data_set` translation every state read and write here relies on is derived.
From `support` it composes [`HasAlignment`](../support/has-alignment.md), which the four alignment factories build on, and `HasExtraAttributes`; Filament's `Schema` also composes `HasDefaultDataFormattingSettings`, whose composition here is a row in [has-default-data-formatting-settings.md](../support/has-default-data-formatting-settings.md).
The host contract it binds to is [`InteractsWithSchemas`](interacts-with-schemas.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Construction + `make()`](../../../packages/schemas/src/Schema.php) | [`Schema::make()`](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [`resolveDefaultClosureDependencyForEvaluationByName` (`$container`/`$liveComponent`/`$record`)](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-model-closure-parameter-is-not-ported) |
| [`resolveDefaultClosureDependencyForEvaluationByType` (record probe)](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-record-type-probe-is-instanceof-not-a-model-gate) |
| `$model` by name | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | skipped | — | [diverges](#the-model-closure-parameter-is-not-ported) |
| [alignment factories `start`/`end`/`center`/`between`](../../../packages/schemas/src/Schema.php) | [`Schema::start()` …](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [`isLive`/`isLiveOnBlur`/`isLiveDebounced`/`getLiveDebounce` parent-walk overrides](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | in progress | — | [detail](#the-parent-walk-inheritance-overrides-are-untested) |
| [`hasGap`/`isDense` parent-walk overrides](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [`getAllColumns` parent inheritance override](../../../packages/schemas/src/Schema.php) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | in progress | — | [detail](#the-parent-walk-inheritance-overrides-are-untested) |
| [Twig-template rendering (`Schema/index.html.twig`)](../../../packages/schemas/templates/partials/Schema/index.html.twig) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-schema-renders-visible-children-only-without-hidden-placeholders) |
| [`dispatchClientSideStateReset()`](../../../packages/schemas/src/Schema.php) | [`Schema::dispatchClientSideStateReset()`](../../../vendor/filament/schemas/src/Schema.php) | done | — | [diverges](#the-root-schema-drops-livewire-partial-render-and-alpine-wiring) |
| Root Livewire/Alpine render wiring (`wire:partial`, `x-data filamentSchema`) | [`Schema`](../../../vendor/filament/schemas/src/Schema.php) | skipped | — | [diverges](#the-root-schema-drops-livewire-partial-render-and-alpine-wiring) |
| `deferLoading()` / `isLoadingDeferred()` | [`Schema::deferLoading()`](../../../vendor/filament/schemas/src/Schema.php) | blocked | a UX LiveComponent partial-render equivalent | [detail](#partial-rendering-waits-on-a-ux-livecomponent-partial-render-equivalent) |
| [`BelongsToParentComponent`](../../../packages/schemas/src/Concerns/BelongsToParentComponent.php) | [`Concerns/BelongsToParentComponent.php`](../../../vendor/filament/schemas/src/Concerns/BelongsToParentComponent.php) | done | — | [diverges](#parentcomponent-accepts-null-to-detach) |
| [`CanBeEmbeddedInParentComponent`](../../../packages/schemas/src/Concerns/CanBeEmbeddedInParentComponent.php) | [`Concerns/CanBeEmbeddedInParentComponent.php`](../../../vendor/filament/schemas/src/Concerns/CanBeEmbeddedInParentComponent.php) | done | — | — |
| [`CanBeInline`](../../../packages/schemas/src/Concerns/CanBeInline.php) | [`Concerns/CanBeInline.php`](../../../vendor/filament/schemas/src/Concerns/CanBeInline.php) | done | — | — |
| [`Cloneable`](../../../packages/schemas/src/Concerns/Cloneable.php) (`getClone()`, `afterCloned()`, `cloneComponents()`) | [`Concerns/Cloneable.php`](../../../vendor/filament/schemas/src/Concerns/Cloneable.php) | done | — | [diverges](#clonecomponents-never-stamps-the-original-and-always-resets-the-cache) |
| [`components()` / `getComponents(bool $withActions = true, bool $withHidden = true)`](../../../packages/schemas/src/Concerns/HasComponents.php) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | done | — | [semantics](#child-coercion-and-action-children-follow-filaments-unions) · [diverges](#getcomponents-preserves-the-callers-array-keys-instead-of-reindexing) · [diverges](#the-component-collection-keeps-a-single-cache-and-drops-the-flag-keyed-memoization) |
| [`getAction(string $actionName, ?string $nestedContainerKey = null)`](../../../packages/schemas/src/Concerns/HasComponents.php) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | done | — | [semantics](#getaction-searches-visible-components-depth-first-by-nested-key) |
| [`getComponent(string\|Closure $findComponentUsing, bool $withActions = true, bool $withHidden = false, bool $isAbsoluteKey = false)`](../../../packages/schemas/src/Concerns/HasComponents.php) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | done | — | [diverges](#the-component-collection-keeps-a-single-cache-and-drops-the-flag-keyed-memoization) |
| `getComponent()`'s `$skipComponentsChildContainersWhileSearching` arm | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | todo | — | [detail](#getcomponent-lacks-the-skip-child-containers-arm) |
| [`getFlatComponents(bool $withActions = true, bool $withHidden = true)`](../../../packages/schemas/src/Concerns/HasComponents.php) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | done | — | [diverges](#the-component-collection-keeps-a-single-cache-and-drops-the-flag-keyed-memoization) |
| [`getComponentByStatePath(string $statePath, array $skipComponentsChildContainersWhileSearching = [])`](../../../packages/schemas/src/Concerns/HasComponents.php) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | done | — | [diverges](get.md#the-skip-child-containers-search-state-is-per-call-with-no-static-re-entrancy-stack) |
| `getFlatFields()` | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | todo | — | — |
| `clearCachedChildSchemas()` | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | todo | — | [detail](#the-schema-level-clearcachedchildschemas-has-every-prerequisite) |
| `hasCachedComponents()` / `clearCachedComponentsByStatePath()` / `flushCachedHierarchy()` | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | skipped | — | [diverges](#the-component-collection-keeps-a-single-cache-and-drops-the-flag-keyed-memoization) |
| `schema()` alias | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | skipped | — | [diverges](#the-schema-alias-is-not-ported) |
| `clearCachedDefaultChildSchemas()` (deprecated for `clearCachedChildSchemas()`) | [`Concerns/HasComponents.php`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) | skipped | — | [diverges](#the-deprecated-name-getname-and-clearcacheddefaultchildschemas-aliases-are-not-ported) |
| [`fill()` / `hydrateState()` / `callAfterStateHydrated()` / `fillStateWithNull()`](../../../packages/schemas/src/Concerns/HasState.php) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | done | — | [semantics](#fill-fires-the-hydrated-hooks-children-first-and-before-the-null-fill-pass) · [belongs-to-record.md](belongs-to-record.md#fillobject-reads-only-the-paths-the-schema-declares) |
| [`statePath()` / `getStatePath()` / `hasStatePath()` / `getRawState()` / `rawState()`](../../../packages/schemas/src/Concerns/HasState.php) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | done | — | [diverges](#getrawstate-seeds-an-empty-array-on-a-blank-or-top-level-path) · [diverges](#the-absolute-path-cache-is-a-nullable-field-plus-a-boolean-flag-not-an-unset-typed-property) |
| [`getState()` / `getStateSnapshot()` and the dehydration walk](../../../packages/schemas/src/Concerns/HasState.php) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | done | — | [diverges](#getstate-validates-without-a-prepareforvalidation-mutation-layer) · [diverges](#getstatesnapshot-never-runs-the-beforestatedehydrated-hooks) |
| [`mutateStateForValidation(array $state)`](../../../packages/schemas/src/Concerns/HasState.php) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | done | — | [diverges](#getstate-validates-without-a-prepareforvalidation-mutation-layer) |
| `getState()`'s prune to the validated keys (`pruneStateToMatchKeys()`) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | todo | — | [detail](#getstate-keeps-keys-no-component-declares) |
| `callAfterStateUpdated(string $path)` | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | skipped | — | [diverges](#schema-level-afterstateupdated-dispatch-moves-to-the-hosts-posthydrate-pass) |
| Constant state: `state()` / `constantState()` / `getConstantState()` / `getConstantStatePath()` | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | blocked | infolists entries | — |
| Partial rendering: `partiallyRender()` / `shouldPartiallyRender()` / `partialRawState()` / `fillPartially()` / `fillPartiallyWithoutLoadingStateFromRelationships()` / `hydrateStatePartially()` / `shouldLoadStateFromRelationshipsWhenHydratingPartially()` | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | blocked | a UX LiveComponent partial-render equivalent | [detail](#partial-rendering-waits-on-a-ux-livecomponent-partial-render-equivalent) |
| `getStateOnly()` / `getStateExcept()` | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | todo | — | — |
| `flushCachedAbsoluteStatePaths()` | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Concerns/HasState.php) | todo | — | — |
| [`key()` / `getKey()` / `getInheritanceKey()` and the key caches](../../../packages/schemas/src/Concerns/HasKey.php) | [`Concerns/HasKey.php`](../../../vendor/filament/schemas/src/Concerns/HasKey.php) | in progress | — | [detail](#haskey-ports-the-schema-key-chain-minus-the-state-path-fallback) |
| `getLiveComponentKey()` (Filament's `getLivewireKey()`) | [`Concerns/HasKey.php`](../../../vendor/filament/schemas/src/Concerns/HasKey.php) | todo | — | [detail](#haskey-ports-the-schema-key-chain-minus-the-state-path-fallback) |
| `name()` / `getName()` (deprecated for `key()` / `getKey()`) | [`Concerns/HasKey.php`](../../../vendor/filament/schemas/src/Concerns/HasKey.php) | skipped | — | [diverges](#the-deprecated-name-getname-and-clearcacheddefaultchildschemas-aliases-are-not-ported) |
| [`HasHeadings`](../../../packages/schemas/src/Concerns/HasHeadings.php) | [`Concerns/HasHeadings.php`](../../../vendor/filament/schemas/src/Concerns/HasHeadings.php) | done | — | — |
| [`CanModifyActions`](../../../packages/schemas/src/Concerns/CanModifyActions.php) | [`Concerns/CanModifyActions.php`](../../../vendor/filament/schemas/src/Concerns/CanModifyActions.php) | done | — | [diverges](#modifyaction-and-modifyactiongroup-guard-a-callbacks-return-type-at-runtime) |
| [`HasFieldWrapper`](../../../packages/schemas/src/Concerns/HasFieldWrapper.php) | [`Concerns/HasFieldWrapper.php`](../../../vendor/filament/schemas/src/Concerns/HasFieldWrapper.php) | done | — | [diverges](#the-field-wrapper-view-is-opaque-data-with-no-closure-arm-and-no-parent-walk) |

## Details

### The parent-walk inheritance overrides are untested

`isLive()`/`isLiveOnBlur()`/`isLiveDebounced()`/`getLiveDebounce()` and `getAllColumns()` each fall through to `getParentComponent()?->…()` when the schema declares no own value, which is the container's inheritance semantics.
The own-value branches are exercised (state-binding-modifier reads, `SchemaRenderTest` column counts), but no test builds a parent→child schema pair and asserts the child inherits the parent's live mode or column count.

### `getComponent()` lacks the skip-child-containers arm

Filament's `getComponent()` takes a fifth parameter, `$skipComponentsChildContainersWhileSearching`, the nested-traversal branch that lets a component's own `$get`/`$set` avoid recursing into itself.
`getComponentByStatePath()` carries that parameter for the repeater case, and `getComponent()` grows it there if a caller needs it.

### The schema-level `clearCachedChildSchemas()` has every prerequisite

The component-tier `clearCachedChildSchemas()` and the default child-schema cache it clears exist ([component.md](component.md#haschildcomponents-only-the-materialization-core-is-ported)); the schema-level walk that calls it on every component is not ported, so `Set` omits the call Filament makes ([set.md](set.md)).

### `getState()` keeps keys no component declares

Filament's `getState()` keeps only the keys its validation returned (`pruneStateToMatchKeys()`): Laravel's `validated()` answers the rule keys alone, every field registers one, if only `nullable`, so a key no field declares never leaves the raw state.
Here the runner validates per declared path and discards its copy, and `getState()` seeds the whole raw bag before the dehydration walk, which prunes only a non-dehydrated component's own path.
So a key no component owns — one a client wrote into a host's writable `data` prop, every nested path of which ux-live-component makes writable — stays in `getState()` and in the `$data` every mutate hook and `using()` closure receives.
Nothing of it reaches a record through the default seams, since `getData()` writes only the record path map's paths, and those seams refuse a path a hook adds that no component maps ([belongs-to-record.md](belongs-to-record.md#a-hook-added-path-no-component-maps-is-answered-for-the-write-seams-to-refuse)); but a `using()` closure that writes `$data` wholesale writes it too, a mass-assignment footgun.

### Partial rendering waits on a UX LiveComponent partial-render equivalent

Filament's partial rendering re-renders one schema through `wire:partial`, and its deferred loading renders a placeholder that the same partial replaces; UX LiveComponent re-renders the whole component, so neither has a target.
Decide whether targeted re-render is a goal before porting either; [`fillPartially()`'s object arm](belongs-to-record.md#fillpartiallys-object-arm-waits-on-partial-rendering) waits on the same decision.

### `HasKey` ports the schema key chain, minus the state-path fallback

The schema-scope key model — `key()`, the absolute prefix from the parent component's inheritance key, the `getInheritanceKey()` fallback, attribute/JS-character sanitization, and the absolute/inheritance-key caches with their flushes — is ported and covered by [`HasKeyTest`](../../../packages/schemas/tests/Unit/Concerns/HasKeyTest.php).
`getKey()` omits Filament's `?? $this->getStatePath(isAbsolute: false)` fallback, although `getStatePath()` is live on the schema through `HasState` and the component-tier `HasKey` keeps it; no test or recorded decision covers the omission.
Forms' `Repeater` compensates by stamping `->key($itemKey)` beside `->statePath($itemKey)` on every item schema it clones, because an unkeyed one inherits the repeater's key and collapses every item's field keys, and DOM ids, onto one value ([repeater.md](../forms/repeater.md#every-item-schema-is-keyed-as-well-as-re-pathed)); restoring the fallback removes that stamp.
The schema-tier `getLiveComponentKey()` has its prerequisite, the host id [`getSchemasHostId()`](live-component.md#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id), and no consumer.

## Semantics

### Child coercion and action children follow Filament's unions

`getComponents()` resolves a `components()` closure lazily, stamps each child's container, coerces a `string` child to `Text` and any other non-component to `Html`, and filters on visibility.
An `Action` or `ActionGroup` child is passed through the container's `modifyAction()`/`modifyActionGroup()` hook, stamped `schemaContainer($this)` and left uncoerced; `getComponents()` and `getFlatComponents()` drop them when `$withActions` is false, and `getFlatComponents()` appends them but never descends into them.
The state walks in `HasState` and [`HasSchemas`](has-schemas.md) and `getComponentByStatePath()` all pass `withActions: false`, and both accessors declare PHPStan conditional return types, so those call sites keep `array<Component>` inference.

### `getAction()` searches visible components depth-first by nested key

A direct `Action` child matches by name, a direct `ActionGroup` child by its flat-actions map, and a component-registered action only when `$nestedContainerKey` exactly equals the owning component's relative key.
Every component's child schemas are searched recursively: keyed child schemas are entered only when the remaining key matches or prefixes them, unkeyed ones pass it through, and a keyed component's inheritance-key prefix is stripped before descending.
Depth-first, first match wins; hidden components are skipped.

### `fill()` fires the hydrated hooks children-first and before the null-fill pass

Filament hydrates recursively, firing each component's `afterStateHydrated` hooks at the end of its own descent, where this port runs three separate passes over the schema, so two orderings that Filament gets for free are explicit here.
`fill()` fires the hooks **before** `fillStateWithNull()`, because the null-fill pass writes null at every unset leaf path, which would clobber a value a hook is about to read (a simple `Repeater`'s scalar item, `tags.0`, against the leaf `tags.0.value`).
`callAfterStateHydrated()` descends **children-first**, re-reading `getChildSchemas()` at each step, so a component that re-keys its own child state (a nested `Repeater`) never writes at a path its parent has just renamed.
The [Repeater](../forms/repeater.md#the-item-pipeline-runs-children-first-and-before-the-null-fill-pass) makes both observable, and the forms repeater record round-trip pins them.

## Divergences

### The schema() alias is not ported

The root tier follows the component tier: `components()` is the sole spelling, and `schema()` is not ported.
[component.md](component.md#the-schema-alias-is-not-ported) carries the reasoning and the [rename table](../renames.md) the row.
Filament ships the root alias undeprecated.

### Schema is the state root; Filament splits state across the Livewire base and the schema

Filament divides container state between its `Livewire\Component` base (which holds the `$data` array, the updated-lifecycle hooks, and partial rendering) and `Schema`.
Sylament has no Livewire base to inherit: a schema binds to a plain UX LiveComponent — the [`InteractsWithSchemas`](interacts-with-schemas.md) host — reached through [`BelongsToLiveComponent`](belongs-to-live-component.md), and `HasState` reads and writes the host's bound properties through [PropertyAccess](property-accessor.md).
Everything Filament kept on the Livewire base that the schema depends on (the state bag, the updated dispatch) is relocated: state lives on the host's typed properties, and the updated-lifecycle dispatch moves to the host's [`#[PostHydrate]` pass](#schema-level-afterstateupdated-dispatch-moves-to-the-hosts-posthydrate-pass).
The schema becomes the single state root, and the render-time Livewire coupling below drops out with the base class.
Reverses only if a Livewire-shaped base is reintroduced.

### The root schema drops Livewire partial-render and Alpine wiring

Filament's `renderEmbeddedHtml()` emits `wire:partial` (`schema.{key}`), `x-data="filamentSchema({ livewireId, schemaKey })"`, and `x-on:form-validation-error`/`reset-schema-component-state` on the root div.
Sylament's `Schema/index.html.twig` emits no `wire:partial` and no client-side state object: live binding runs field-side through UX LiveComponent `data-model` attributes plus a full component re-render (proven by `LiveStateTest`), so per-schema partial rendering has no port and reverses only if it becomes a goal.

The two window listeners do port, as a Stimulus controller on the root schema element rather than an Alpine `x-on:` pair.
[`validateSchema()`'s throwing branch](can-be-validated.md#the-throwing-branch-announces-form-validation-error-carrying-the-schema-identity) announces `form-validation-error`, and `dispatchClientSideStateReset()` announces `reset-schema-component-state` with `{schemaKey: getKey()}`.
Filament sends `livewireId` beside the key; a browser event dispatched on the host's own root has nothing left for an id to disambiguate, and the host id that does exist, [`getSchemasHostId()`](live-component.md#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id), is a DOM-key prefix, not a listener filter.
`CreateAction`'s create-another arm calls the reset, as Filament's does; the other Filament caller, `CreateRecord`'s create-another, is a todo row in [create-record.md](../panels/create-record.md).

### `parentComponent()` accepts null to detach

Filament types `parentComponent(Component $component, bool $shouldFlushCachedHierarchy = true)` non-nullable.
Sylament widens it to `?Component`, so a schema can be reset to root (`isRoot()` true again), and drops `$shouldFlushCachedHierarchy` with the `flushCachedHierarchy()` it guards, which [the single component cache](#the-component-collection-keeps-a-single-cache-and-drops-the-flag-keyed-memoization) has no use for.
The widening is a harmless superset of Filament's contract, reversible if a caller ever needs the non-null guarantee.

### The component collection keeps a single cache and drops the flag-keyed memoization

Filament memoizes `getComponents` (with/without hidden), `getFlatComponents`, and `getComponentByStatePath` under multi-dimensional caches keyed by `withActions`/`withHidden`/`withOriginalKeys`/`withAbsoluteKeys`/`containerKey`/`spl_object_id`, flushed through `hasCachedComponents()`, `clearCachedComponentsByStatePath()` and `flushCachedHierarchy()`.
Sylament keeps only `$cachedComponents` (the raw resolved list) and recomputes flat/by-state-path each call, so those three members have nothing to manage.
`getComponents(bool $withActions = true, bool $withHidden = true)` matches Filament's `withActions` default but flips the `withHidden` default to `true` (Filament `false`; the render pipeline — `Schema::render()`'s empty guard and `getExtraTemplateData()` — passes `withHidden: false` explicitly), drops `withOriginalKeys` (see [the key-preservation divergence](#getcomponents-preserves-the-callers-array-keys-instead-of-reindexing)), and filters on `isVisible()` rather than `isHidden()`.
`getFlatComponents(bool $withActions = true, bool $withHidden = true)` threads `$withHidden` through with the same flipped default, because its callers here rely on the hidden-inclusive shape.
`getComponent()`/`getSchemaComponent()` keep Filament's `withHidden: false` default and pass it down explicitly, matching Filament's own call sites.
Filament's `withAbsoluteKeys`/`containerKey` parameters and their key-indexed carry are dropped: the flat list stays ordered and unkeyed, and `getComponent()`'s string arm prefixes a relative key with the container's own filled `getKey()` and builds its own key→component map from that list on each call, skipping components with a `null` key and every `Action`/`ActionGroup`, which carry no key at all.
Correct but not memoized; revisit if flat/state-path lookups become hot.

### `cloneComponents()` never stamps the original and always resets the cache

Filament's [`HasComponents::cloneComponents()`](../../../vendor/filament/schemas/src/Concerns/HasComponents.php) stamps `container($this)` onto each **original** component child before copying it, mutating an instance the source schema still holds; ours only copies, because `getComponents()` re-stamps `container`/`schemaContainer` when it rebuilds its cache, so the stamp is redundant on the clone and damaging on the original.
Filament resets its component caches only inside the non-`Closure` arm, so cloning a schema whose `components()` is a closure hands the clone the original's already-built component list, with the *original* as their container; ours resets `$cachedComponents` unconditionally.
Both are pinned by [`CloneableTest`](../../../packages/schemas/tests/Unit/Concerns/CloneableTest.php).
Actions and action groups are shallow `clone`s here and in `cloneChildComponents()`, matching the component tier: `Action` has no `getClone()` ([action.md](../actions/action.md)).

### `getState()` validates without a prepareForValidation mutation layer

Both of Filament's container reads are ported: `getState(bool $shouldCallHooksBefore = true, ?Closure $afterValidate = null)` runs `validate()` on entry (throwing [`ValidationException`](can-be-validated.md) before any dehydration), and `getStateSnapshot()` is the validation-free variant.
Of the machinery Filament wraps around its validate call, one piece is ported and two are not.

The mutation layer is ported as a flat-map walk rather than Livewire's nested rewrite: `Schema::mutateStateForValidation(array $state): array` takes the `absolutePath => value` map the runner has just read, walks the components child-schemas-first (`withActions: false, withHidden: true`, skipping `isNeitherDehydratedNorValidated()`), and hands each path the map holds to the owning component's own [`mutateStateForValidation()`](component.md#the-mutatestateforvalidation-members-mutate-the-runners-copy-of-one-path).
[`validateSchema()`](interacts-with-schemas.md) calls it between reading every path and validating any of them, so a mutated value is what both the presence gate and the validator see.
The signature is **by value** where the dehydration sibling `mutateDehydratedState(array &$state = [])` is by reference, because the runner keeps the return.

`prepareForValidation()`, the host-side merge of `getRules()` / `getValidationAttributes()` into one Livewire call, has no counterpart because validation here is per path, not per component ([interacts-with-schemas.md](interacts-with-schemas.md)).
`pruneStateToMatchKeys()` is not ported either, so [`getState()` keeps keys no component declares](#getstate-keeps-keys-no-component-declares).
The `Component::withVisibilityCache(...)` wrapper is absent, because [no visibility cache exists](can-be-hidden.md#the-static-visibility-cache-is-not-ported).
`$afterValidate` is invoked plainly with the final dehydrated state (Filament's `value($afterValidate, $state)` position).
The child-schema `isHiddenAndNotDehydratedWhenHidden()` guards in `callBeforeStateDehydrated()` are not mirrored, consistent with hidden-guarding living at component level ([can-be-validated.md](can-be-validated.md#the-collection-walk-cannot-skip-directly-hidden-child-schemas)).

### `getStateSnapshot()` never runs the `beforeStateDehydrated` hooks

Filament's `getStateSnapshot()` is `dehydrateState()` + `mutateDehydratedState()` and nothing else; only `getState()` reaches `callBeforeStateDehydrated()`.
Ours shares one assembly with `getState()`, so the snapshot passes `shouldCallHooksBefore: false` to keep that line — and the line is load-bearing, because a `beforeStateDehydrated` hook is where a component *commits* its state rather than merely reading it.
The live case is [`BaseFileUpload`](../forms/file-upload.md), whose hook is the store pass: it moves each held upload onto the field's disk and **deletes** the temporary one, so the hook can only be run once per held file.

Any snapshot that ran the hooks during a render would be destructive, and a repeater takes one per item per request: [`Repeater::getItemLabel()`](../forms/repeater.md) computes its `state` injection eagerly, whether or not an `itemLabel()` was ever declared.
A file field inside a repeater item would then have its upload consumed by the render that followed it: the store runs against a throwaway pass whose `rawState()` write never reaches the wire, the browser posts the same token back, and the save's own pass can no longer resolve it, because the token's temporary file is gone.
The entry drops, `FileUploadStateCast::get()` answers `null` for the emptied map, and the field saves as null: on a non-nullable column that surfaces as `PropertyAccessor`'s `Expected argument of type "string", "null" given`, and on a nullable one it is silent data loss.
Pinned at both levels: the hook-free snapshot in [`HasStateTest`](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php), and the repeater-item upload surviving its renders in [`LiveFileUploadTest`](../../../packages/forms/tests/Integration/LiveFileUploadTest.php).
`Repeater::getItemState()` passes `shouldCallHooksBefore: false` for the same reason; `getRawItemState()` and `getItemLabel()` reach the hooks only through `getStateSnapshot()`.

### `getRawState()` seeds an empty array on a blank or top-level path

Filament's container `getRawState()` returns `data_get($livewire, $statePath) ?? []` typed `array|Arrayable`.
Sylament returns `array` only and, when the schema has no state path (top-level binding, no single bag to read), returns `[]` rather than reading the host, which is the intended seed for the dehydration prune.
`Arrayable` support is dropped (Doctrine collections are not `Arrayable`-shaped here).
`hasStatePath()` is defined here where Filament inlines `filled($this->statePath)`.

### The absolute-path cache is a nullable field plus a boolean flag, not an unset typed property

Filament declares `protected string $cachedAbsoluteStatePath;` and detects a hit with `isset()`, flushing via a public `flushCachedAbsoluteStatePath()` that `unset()`s it (with a phpstan-ignore for `unset.possiblyHookedProperty`).
Sylament declares `?string $cachedAbsoluteStatePath = null` plus `bool $hasCachedAbsoluteStatePath`, caching through a try/finally that sets value then flag and flushing both through a protected `flushCachedAbsoluteStatePath()`, which only `statePath()` and `getClone()` call, to avoid `unset()` of a typed property and keep PHPStan level max clean without an ignore.
Behaviourally identical; not provisional.

### Schema-level afterStateUpdated dispatch moves to the host's PostHydrate pass

Filament's `Schema::callAfterStateUpdated(string $path)` walks components matching the updated path inside a partial-render `finally` block.
Sylament omits it from the trait and dispatches from the host trait's [`HasSchemas::callAfterSchemaStateUpdated()`](has-schemas.md) under UX LiveComponent's `#[PostHydrate]`: after re-hydration it iterates `getFlatComponents` and fires `callAfterStateUpdatedHooks()` on every component whose `getRawState` differs from the snapshot `getOldSchemaState`.
This is the Symfony-idiom translation of Livewire's updated lifecycle; the path-matching-plus-bubbling shape and the `partiallyRender` `finally` block are not reproduced.
Reverses only if per-path targeted dispatch is needed.

### The deprecated `name()`, `getName()` and `clearCachedDefaultChildSchemas()` aliases are not ported

Filament's schema-level `HasKey` carries `name()`/`getName()` as `@deprecated` forwarders to `key()`/`getKey()`, and its `HasComponents` carries `clearCachedDefaultChildSchemas()` as a `@deprecated` forwarder to `clearCachedChildSchemas()`.
Sylament omits them: they are deprecated in the source we port from, so shipping them only reintroduces the ambiguity the deprecation retires.
The recommended `key()`/`getKey()` are ported; `clearCachedChildSchemas()` is a todo row.
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
PHPStan cannot infer the return type of invoking a bare `?Closure` property, so it flags the expression itself as returning `mixed`, and this codebase writes no `Closure(Action): ?Action` signature spec anywhere, even for a callback invoked plainly with fixed arguments.
Sylament instead narrows explicitly: it holds the coalesced result in a local variable, `instanceof`-checks it, and throws the same `TypeError` itself on a mismatch.
A conforming callback or a `null` return works exactly as Filament's; a non-conforming one still throws `TypeError`, raised explicitly rather than by implicit return-type coercion.
`modifyActionGroup()` carries the identical guard.
Reverses only if PHPStan gains inference for arbitrary closure invocations.

### The field-wrapper view is opaque data, with no closure arm and no parent walk

`fieldWrapperView(?string)` takes a plain string here where Filament's takes `string|Closure|null`, and `getFieldWrapperView()` returns what was set where Filament's `getCustomFieldWrapperView()` falls back to the parent component's.
Both narrowings follow from what schemas does with the value, which is nothing: it stores an opaque token that only forms' `FieldWrapper` interprets ([fused-group.md](../forms/fused-group.md#only-the-plain-wrapper-view-is-honored)), so there is no render-time moment for a closure to be evaluated in and no resolution order for the walk to feed.
The getter carries Filament's component-tier name rather than its container-tier `getCustomFieldWrapperView()`, because "custom" only distinguishes anything once a default and a walk exist, and neither ships.
Widen the property and reinstate the walk in the same change as the [Component-tier twin](../forms/fused-group.md#the-component-tier-hasfieldwrapper-twin-is-not-ported), which is where the resolution order belongs.

### The record type probe is `instanceof`, not a `Model` gate

Filament resolves a closure parameter by type through two arms — `Model::class` and `$record::class` — after gating on `is_a($type, Model::class)`, so an arbitrary class-typed parameter never touches the record.
There is no `Model` base class here, so both tiers instead probe `$record instanceof $parameterType`, which covers the exact class, any parent, any interface, and a Doctrine proxy.
The safety the gate provided is restored differently: the component tier's container fallbacks are each `isset($this->container)`-guarded (the [`getLiveComponentOrNull()` idiom](belongs-to-live-component.md#getlivecomponent-throws-instead-of-returning-a-possibly-null-component)), because `$container` is an uninitialized typed property until a schema stamps it — so `evaluate()` on a detached component still resolves its own class rather than fataling on the back-reference.
The component tier resolves its exact `Get::class`/`Set::class` arms before probing the record; the root tier's override is the probe alone, since its parent-component chain is nullable, and the self/static identity Filament's root arm resolves comes from the base `EvaluatesClosures` own-class mechanism.
A type-hinted parameter receives the bound record, never a state snapshot: Filament's `$record` is the persisted record, deliberately stale until save, and live values have `$get` and `$state`, so a snapshot object would change the meaning of every Filament example a migrating user pastes in.

### The `$model` closure parameter is not ported

Filament injects `$model` (the class-string) alongside `$record` by name, on both tiers.
Dropping the name is part of [the record-only rename](belongs-to-record.md#the-record-vocabulary-collapses-onto-record): `$record` covers the instance and `getRecordClass()` covers the class, so keeping `$model` would reintroduce exactly the vocabulary the collapse removed, for a parameter that is one method call away.
Revisit only if a ported surface needs it as a parameter rather than a call.
