---
description: The base class every schema component extends — container membership, child schemas, identity, headings, closure injection and state.
ports:
  - vendor/filament/schemas/src/Components/Component.php
  - vendor/filament/schemas/src/Components/Concerns/BelongsToContainer.php
  - vendor/filament/schemas/src/Components/Concerns/CanBeRepeated.php
  - vendor/filament/schemas/src/Components/Concerns/Cloneable.php
  - vendor/filament/schemas/src/Components/Concerns/HasChildComponents.php
  - vendor/filament/schemas/src/Components/Concerns/HasHeadings.php
  - vendor/filament/schemas/src/Components/Concerns/HasId.php
  - vendor/filament/schemas/src/Components/Concerns/HasKey.php
  - vendor/filament/schemas/src/Components/Concerns/HasState.php
owns:
  - packages/schemas/src/Components/Component.php
  - packages/schemas/src/Components/Concerns/BelongsToContainer.php
  - packages/schemas/src/Components/Concerns/CanBeRepeated.php
  - packages/schemas/src/Components/Concerns/Cloneable.php
  - packages/schemas/src/Components/Concerns/HasChildComponents.php
  - packages/schemas/src/Components/Concerns/HasHeadings.php
  - packages/schemas/src/Components/Concerns/HasId.php
  - packages/schemas/src/Components/Concerns/HasKey.php
  - packages/schemas/src/Components/Concerns/HasState.php
---
# Component (schemas)

[`Sylament\Schemas\Components\Component`](../../../packages/schemas/src/Components/Component.php) is the base class every schema component extends.
It subclasses the support [`ViewComponent`](../support/view-component.md) and layers on the schema component model: container membership, child schemas, identity (`id`/`key`), visibility, headings, and the `$get`/`$set`/state machinery.

Only what this class adds over `ViewComponent` is tracked here.
The base `evaluate()` / `Configurable` machinery is audited in [`support/component.md`](../support/component.md), and the support-level traits it also composes are tracked in [`can-order-columns.md`](../support/can-order-columns.md), [`can-span-columns.md`](../support/can-span-columns.md), [`has-extra-attributes.md`](../support/has-extra-attributes.md) and, for `CanGrow`, [`tables/column.md`](../tables/column.md).
The cross-component schema concerns it composes have their own files: the record binding [`BelongsToRecord`](component-belongs-to-record.md), [`CanAccessProperties`](property-accessor.md), [`CanBeConcealed`](can-be-concealed.md), [`CanBeDisabled`](can-be-disabled.md), [`CanBeHidden`](can-be-hidden.md), [`HasActions`](has-actions.md), [`HasColumns`](has-columns.md), [`HasGap`](has-gap.md), [`HasNullableBranch`](has-nullable-branch.md) and [`HasStateBindingModifiers`](has-state-binding-modifiers.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Trait composition](../../../packages/schemas/src/Components/Component.php) | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | in progress | — | [detail](#trait-composition-ports-a-subset-of-filaments) |
| [`$evaluationIdentifier = 'component'`](../../../packages/schemas/src/Components/Component.php) | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | done | — | — |
| [`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/schemas/src/Components/Component.php) (get/set/state/rawState/liveComponent/record/operation/context) | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | in progress | — | [detail](#the-by-name-closure-dependency-map-is-missing-one-key) |
| [`resolveDefaultClosureDependencyForEvaluationByType()`](../../../packages/schemas/src/Components/Component.php) (Get/Set/record) | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | done | — | [diverges](#type-based-closure-resolution-probes-the-record-instead-of-gating-on-model) |
| `getExtraTemplateData()` override | [`getExtraViewData()`](../../../vendor/filament/schemas/src/Components/Component.php) | todo | — | [detail](#getextratemplatedata-is-not-overridden) |
| `toSchemaHtml()` render wrapper | [`toSchemaHtml()`](../../../vendor/filament/schemas/src/Components/Component.php) | blocked | live-component-state | [detail](#the-render-wrappers-client-half-waits-on-the-live-component-state-runtime) |
| `renderWrapperBladeComponent()` | [`renderWrapperBladeComponent()`](../../../vendor/filament/schemas/src/Components/Component.php) | skipped | — | [diverges](#renderwrapperbladecomponent-is-a-blade-shim-with-no-twig-counterpart) |
| `$viewIdentifier = 'schemaComponent'` | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | skipped | — | [diverges](../support/view-component.md#the-instance-is-always-exposed-as-component) |
| [`BelongsToContainer`](../../../packages/schemas/src/Components/Concerns/BelongsToContainer.php) | [`Concerns/BelongsToContainer.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToContainer.php) | in progress | — | [detail](#belongstocontainer-lacks-getmodelrootcontainer-and-the-re-parenting-flush) · [diverges](#belongstocontainer-gains-a-sylament-only-getlivecomponentornull-peek) |
| [`HasChildComponents`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) | [`Concerns/HasChildComponents.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasChildComponents.php) | in progress | — | [detail](#haschildcomponents-only-the-materialization-core-is-ported) · [semantics](#childcomponents-replaces-a-keys-contents-instead-of-appending) · [diverges](#only-the-default-child-schema-cache-is-ported) · [diverges](#componentschildcomponents-accept-a-single-component-not-just-an-array) · [diverges](#getchildschemas-drops-the-withhidden-parameter) |
| `schema()` alias of `components()` | [`Concerns/HasChildComponents.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasChildComponents.php) | skipped | — | [diverges](#the-schema-alias-is-not-ported) |
| `getChildComponentContainer()` / `getChildComponentContainers()` / `clearCachedDefaultChildSchemas()`, deprecated for `getChildSchema()` / `getChildSchemas()` / `clearCachedChildSchemas()` | [`Concerns/HasChildComponents.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasChildComponents.php) | skipped | — | [renames.md](../renames.md#a-deprecated-filament-member-is-not-ported) |
| [`HasId`](../../../packages/schemas/src/Components/Concerns/HasId.php) | [`Concerns/HasId.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasId.php) | done | — | — |
| [`HasState`](../../../packages/schemas/src/Components/Concerns/HasState.php) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php) | in progress | — | [detail](#hasstate-lacks-a-direct-formatstateusing-test-and-hydratestatepartially) · [semantics](#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default) · [semantics](#dehydration-prunes-around-shared-state-paths-and-pathless-components) · [semantics](#the-mutatestateforvalidation-members-mutate-the-runners-copy-of-one-path) · [diverges](#relationship-derived-and-constant-state-are-dropped-from-the-component-trait) |
| `getGetCallback()` / `getSetCallback()`, deprecated for `makeGetUtility()` / `makeSetUtility()` | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php) | skipped | — | [renames.md](../renames.md#a-deprecated-filament-member-is-not-ported) |
| [`HasKey`](../../../packages/schemas/src/Components/Concerns/HasKey.php) | [`Concerns/HasKey.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasKey.php) | done | — | [live-component.md](live-component.md#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id) |
| [`HasHeadings`](../../../packages/schemas/src/Components/Concerns/HasHeadings.php) | [`Concerns/HasHeadings.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasHeadings.php) | done | — | — |
| [`CanBeRepeated`](../../../packages/schemas/src/Components/Concerns/CanBeRepeated.php) | [`Concerns/CanBeRepeated.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeRepeated.php) | done | — | [diverges](#canberepeated-discriminates-on-haskeyeditems-not-on-a-forms-type) |
| [`Cloneable`](../../../packages/schemas/src/Components/Concerns/Cloneable.php) (`afterCloned()` / `getClone()`) | [`Concerns/Cloneable.php`](../../../vendor/filament/schemas/src/Components/Concerns/Cloneable.php) | done | — | [diverges](#canberepeated-discriminates-on-haskeyeditems-not-on-a-forms-type) |

## Details

### Trait composition ports a subset of Filament's

The base composes container membership, child schemas, identity, visibility, headings and state, plus the record binding, actions, concealment, the shared column and gap concerns, `CanBeRepeated` and `Cloneable` — enough to drive Section and the forms `Field` slots.
Filament's traits not on it are `CanBeGridContainer`, `CanBeLiberatedFromContainerGrid`, `CanPartiallyRender`, `CanPoll`, `HasEntryWrapper`, `HasFieldWrapper`, `HasInlineLabel`, `HasMaxWidth` and `HasMeta`; each is an Unported row in the [schemas README](README.md#unported), and nothing in the composed set waits on them.

### The by-name closure-dependency map is missing one key

`resolveDefaultClosureDependencyForEvaluationByName()` resolves `$get`, `$set`, `$state`, `$rawState`, `$liveComponent` (Filament's `livewire` key), `$record` ([`BelongsToRecord`](component-belongs-to-record.md)), and `$operation`/`$context` ([`HasOperation`](has-operation.md), resolved through the container and [guarded](has-operation.md#the-injection-arm-is-guarded-so-a-detached-component-still-evaluates)).
Missing is Filament's `parentRepeaterItemIndex` key: [`CanBeRepeated`](#canberepeated-discriminates-on-haskeyeditems-not-on-a-forms-type) answers `getParentRepeaterItemIndex()`, and the map has no arm injecting that number into a closure under that name.
Filament's `model` key is deliberately absent, because [the model vocabulary collapses onto record](../renames.md#the-model-vocabulary-collapses-onto-record).

### `getExtraTemplateData()` is not overridden

Filament's `getExtraViewData()` seeds every component template with `get`, `operation` and `record`.
The base does not override `getExtraTemplateData()`, because the hook [stays minimal](../support/view-component.md): only values a template cannot get from a single getter call belong in it, and `component.getRecord` / `component.getContainer.getOperation` are single getter calls.
`get`, which no getter on the component answers, is the entry a shipped template would need first.

### The render wrapper's client half waits on the live-component-state runtime

Filament's `toSchemaHtml()` wraps each component in an outer element carrying grid-column attributes, `wire:key`, `wire:poll`, `wire:partial`, an `x-data="filamentSchemaComponent"` binding with `x-bind:class` visibility toggling, a `<fieldset disabled>` swap for a client-side visibility condition and max-width and growable classes, around an inner `fi-sc-component` div.
The port renders the server half from the parent schema's template instead: [`Schema/index.html.twig`](../../../packages/schemas/templates/partials/Schema/index.html.twig) wraps every visible child in a `div` carrying `gridColumn()` and the `sy-growable` class, around an inner `sy-sc-component` div, and [renders no hidden placeholders](schema.md#the-schema-renders-visible-children-only-without-hidden-placeholders).
Missing is everything the client reads: the polling and partial-render attributes (`CanPoll` and `CanPartiallyRender` are unported), the visibility expressions ([`visibleJs()` / `hiddenJs()`](can-be-hidden.md#the-js-visibility-variants-wait-on-the-live-component-state-runtime), blocked on the same runtime), the fieldset swap that keeps a client-hidden control out of native validation, the `fi-width-*` class (`HasMaxWidth`) and the grid-container class (`CanBeGridContainer`).

### BelongsToContainer lacks getModelRootContainer() and the re-parenting flush

`container()`, `getContainer()`, `getRootContainer()` and `getLiveComponent()` (Filament's `getLivewire()`) are ported.
Filament's `container()` flushes the component's cached hierarchy when it is re-parented to a different schema: `flushCachedHierarchy()` clears the cached absolute key, inheritance key and state path, the parent-repeater memo and `$rootContainer`, and flushes the hierarchy of the cached child schemas.
The port's `container()` is a plain assignment and it has neither `flushCachedHierarchy()` nor `hasContainer()`, so a component moved to a second schema after a cached read keeps the answers it computed under the first.
Missing too is `getModelRootContainer()`, `@internal` upstream, which walks the parent chain to the outermost container bound to the same record.
Filament's only callers are the relationship-entangling components, where it roots the walk that collects every component naming one relationship so the first of them fills and saves for all; the port [refuses that merge outright](entangles-state-with-singular-relationship.md#two-components-entangling-one-relationship-throw-rather-than-merge), and the record-relative machinery reads `getRecordClass()`'s container fallback through `getRootContainer()`.
It is wanted when that refusal is reversed — teaching the record path map to fold several subtree owners at one path — renamed off the `model` vocabulary with the rest.

### HasChildComponents: only the materialization core is ported

`components()`, `childComponents()`, `getChildSchema()`, `getChildSchemas()`, `getDefaultChildSchemas()`, `makeChildSchema()`, `configureChildSchema()`, `clearCachedChildSchemas()` and `cloneChildComponents()` are ported and drive real consumers (Section, the forms `Field` schema slots).
`getChildSchemas()` never materializes the implicit `default` schema during a state walk — only an explicit `getChildSchema()` call does — so a component with no child components yields no phantom schema.
The default-child-schema cache is ported too — `getCachedDefaultChildSchemas()`, the `areCachedDefaultChildSchemasFresh()` seam, the unknown-key fallback in `getChildSchema()`, the cached-map spread in `getChildSchemas()`, and invalidation from `childComponents()` and `cloneChildComponents()` — the seam the Repeater's per-item schemas ride ([repeater.md](../forms/repeater.md)); Filament's per-key cache is not ([diverges](#only-the-default-child-schema-cache-is-ported)).
Left: the `getChildComponents()` and `getDefaultChildComponents()` helpers, and Filament's cache bookkeeping around them — `getCachedChildSchemas()`, `isChildSchemaCached()`, `clearCachedComponentsByStatePath()`, `flushCachedChildSchemaHierarchies()`, the per-key `isCachedDefaultChildSchemaFresh()` hooks and the recursion guards `$isEvaluatingDefaultChildSchemas` and `$evaluatingChildSchemaKeys`.
The Sylament-only `hasPathBearingDescendants()`, `ownsRecordSubtree()` and `routesNestedRelationshipHookAt()` sit on this trait and are documented with the record walks that call them ([component-belongs-to-record.md](component-belongs-to-record.md), [has-nullable-branch.md](has-nullable-branch.md)).

### HasState lacks a direct formatStateUsing() test and hydrateStatePartially()

The state engine is ported: state-path composition, raw and cast read-write through the [PropertyAccessor seam](property-accessor.md), state casts, defaults, hydration, the dehydration prune and mutate walk, the `afterStateUpdated` registry, the validation-mutation trio and the validation flag trio (`validatedWhenNotDehydrated()`, `isValidatedWhenNotDehydrated()`, `isNeitherDehydratedNorValidated()`, the guard [`Schema::getValidationConstraints()`](can-be-validated.md) skips on).
`formatStateUsing()` registers an `afterStateHydrated()` closure that writes the evaluated callback back through `state()`, and no test in the schemas or forms suites calls it.
`hydrateStatePartially()`, Filament's re-hydration of a subset of state paths for partial rendering, waits on the unported `CanPartiallyRender` concern.
The public `shouldUpdateValidatedStateAfterBeforeStateDehydratedRuns()` getter is not ported; `callBeforeStateDehydrated()` reads the flag it reports directly.
Every other absent member is a divergence below: relationship-derived and constant state, the `afterStateUpdated` orchestrator and its JS hooks, and `resolveRelativeKey()` with it.

## Semantics

### A defaults fill nulls a defaultless component, and the shared record is what protects a container's default

`Schema::fill()` with no argument runs `hydrateState(applyDefaults: true)`, and that pass is a **replacement**, not a gap-filler: a component declaring a default gets it written over whatever the bag held, and a component declaring **none** gets `rawState(null)`.
Both arms are Filament's ([`hydrateDefaultState`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php)), and the second is the one that reads like a bug — a defaults fill wiping state where no default was ever declared — so it is the one that gets "fixed" back into a skip.

A skip breaks a reset: a `SelectFilter` builds a `Select` that takes a `default()` only when the *filter* declares one, so under a skip `resetTableFiltersForm()` leaves an applied `status` value standing in both bags while the sibling `Filter` — a `Checkbox`, which declares `default(false)` in its own `setUp()` — clears.
The asymmetry is only reachable when the bag already holds state at fill time, which is why every mount-time fill looks right either way ([the table side](../tables/host-has-filters.md#filter-mutations-reset-the-page-and-flush-the-record-cache)).

The null-write cannot stand alone, and that is what `$hydratedDefaultState` is for.
It is a by-ref record, threaded through the whole walk by the [`Schema`](schema.md)-level `hydrateState()`, of every default already written, keyed by absolute state path; a component whose path the record already covers returns untouched.
Without it, a container's default is destroyed by its own children: `Group::make([...])->statePath('author')->default(['name' => 'Ada'])` writes the whole map, and then the defaultless `author.name` inside it — visited next, because `getFlatComponents()` is parent-first — nulls the very key the parent just filled.
With the record, `author.name` is already covered and skips; its sibling `author.surname`, which the parent default never mentioned, is not covered and still nulls.
Both halves are pinned in [Unit/Concerns/HasStateTest](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php).

What the record cannot protect is a container whose child paths are *derived from the state the default replaces* — a repeater, whose item schemas the flat walk materialised before the default rewrote their keys: those children sit at paths the new default never mentions, so the record waves them through and their null-writes rebuild the items the default just dropped ([the repeater divergence](../forms/repeater.md#a-defaults-fill-over-a-populated-repeater-resurrects-its-pre-fill-items)).

### Dehydration prunes around shared state paths and pathless components

The dehydration walk looks like "a non-dehydrated component removes its key", and that reading is wrong twice over.

Two components can be bound to the **same** state path, so removing the key outright would destroy the sibling's data.
A non-dehydrated component therefore leaves the key untouched when `getRootContainer()->hasDehydratedComponent()` reports a dehydrated owner for the path; otherwise, when a sibling shares the path, it forgets only the paths its own descendants own (`getDescendantStatePathsToForget()`), and drops the shared parent only once nothing meaningful is left under it.

A **pathless** (layout) component has no key of its own to forget.
It prunes by recursing into its child schemas with `isDehydrated: false` — that recursion is the whole mechanism carrying the prune down to its descendants, so a layout component reads as a no-op here while doing all the work.

### `childComponents()` replaces a key's contents instead of appending

[`childComponents($components, $key)`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) is a plain `$this->childComponents[$key] = $components` — a second call for the same key discards whatever the first put there, with no merge and no error.
Every named-slot setter on [`Section`](../../../packages/schemas/src/Components/Section.php) and [`Field`](../../../packages/forms/src/Components/Field.php) funnels through it, so "add this to the slot as well" is never what calling one twice does.

The cost lands on concerns that seed a slot from `setUp()`, because each is written as a slot call and a component can compose several.
Forms' [`HasHint`](../../../packages/forms/src/Components/Concerns/HasHint.php) registers exactly one `afterLabel()` closure and returns the hint text, the hint icon **and** the hint actions from inside it; splitting the actions into a second `afterLabel()` call would leave the slot holding the actions alone.
Filament folds the same three into one closure for the same reason.
A concern that has to add to a slot another concern owns cannot do it through the setter at all.

### The mutateStateForValidation members mutate the runner's copy of one path

`mutateStateForValidationUsing()`, `mutateStateForValidation()` and `mutatesStateForValidation()` are ported verbatim, while Filament's surrounding plumbing is not.
Upstream, the host's `prepareForValidation()` rewrites a whole nested attributes array before Laravel's validator sees it, and `getState()` then has to prune that array back down to the keys the schema owns.
A per-path runner needs none of that: [`validateSchema()`](interacts-with-schemas.md) reads each constrained path's value, the [schema-tier walk](schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer) hands it to the owning component's `mutateStateForValidation()`, and the runner validates the result and discards the copy, so `getState()` never consumes a validated array and `prepareForValidation()` and `pruneStateToMatchKeys()` have no job.

Two consequences follow, both deliberate.
The mutation reaches **the validated value only** — the live bag the browser wrote is untouched, so a field the user typed whitespace into keeps it on screen.
And the mutated value is what [`PresenceGate`](presence-gate.md) sees, so trimming an input to nothing gates it as blank and strips its non-implicit constraints.

The walk skips a component whose `isNeitherDehydratedNorValidated()` is true, the same guard the [validation collection walk](can-be-validated.md) skips on.
Why `Field` does not force `mutatesStateForValidation()` to `true` is recorded [with the forms trait](../forms/can-be-validated.md#field-does-not-force-mutatesstateforvalidation-true).

### Each template ports a PHP `toEmbeddedHtml()`, not a Blade view

Filament's schemas components emit their HTML from a PHP `toEmbeddedHtml()` method, and each Twig template under [`packages/schemas/templates/partials/`](../../../packages/schemas/templates/partials) translates that method, rendered through support's [`ViewComponent`](../support/view-component.md).
Filament's `resources/views/components/` holds only `fieldset.blade.php` and `grid.blade.php`, which back the `publishedViewOverrideCheckPath` of `Fieldset`, `Grid` and `Group` and `MorphToSelect`'s view; those three render from `toEmbeddedHtml()` too, and their templates port that method, not the Blade beside it.

## Divergences

### The schema() alias is not ported

Filament ships `schema()` beside `components()` on this trait, undeprecated — pure back-compat surface for its own pre-v4 call sites.
Sylament ships `components()` alone: a second name for the same call adds grep surface and a second spelling every example must choose between, for call sites this port has none of.
A migrating user's `->schema([...])` breaks loudly — missing method, and the fix is mechanical — which the [rename criterion](../renames.md) prefers to a silently kept synonym.
The same decision covers the root tier ([schema.md](schema.md#the-schema-alias-is-not-ported)) and is recorded in the [rename table](../renames.md#renames).
It reverses only if migration friction proves it wrong in practice.

### BelongsToContainer gains a Sylament-only getLiveComponentOrNull() peek

`getLiveComponent()` (like its schema-level counterpart) throws when the chain is unbound, where Filament's `getLivewire()` is nullable — so the actions package's live-component fallback chain, which probes a bound schema component for its host, needs a non-throwing read.
`getLiveComponentOrNull()` is that peek: `null` while the `$container` back-reference is uninitialized, otherwise the container's own [`getLiveComponentOrNull()`](belongs-to-live-component.md) (the raw nullable property).
Filament has no counterpart method because its primary getters already return `null`.
The actions-side [`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php) exercises it through its schema-component hop.

### renderWrapperBladeComponent() is a Blade shim with no Twig counterpart

Filament's protected `renderWrapperBladeComponent()` renders a field or entry wrapper named as any Blade component through `<x-dynamic-component>`, kept for wrappers written before wrapper views existed.
The port has no Blade and no such wrapper names to honour — its field wrapper is a Twig template ([schema.md](schema.md#the-field-wrapper-view-is-opaque-data-with-no-closure-arm-and-no-parent-walk)) — so the method has nothing to render.

### Type-based closure resolution probes the record instead of gating on `Model`

`resolveDefaultClosureDependencyForEvaluationByType()` is wired, alongside [`Schema`](schema.md)'s, so `fn (Get $reader) => …`, `fn (Set $writer) => …` and `fn (Article $record) => …` all resolve by type-hint, not only by the `$get`/`$set`/`$record` *names*.
It diverges in the record arm: Filament resolves `Model::class` and `$record::class` behind an `is_a($type, Model::class)` gate, and there is no `Model` base class here, so the exact `Get::class`/`Set::class` arms are matched first and the record is then probed with `$record instanceof $parameterType` — which additionally covers parent classes, interfaces, and Doctrine proxies.

### Only the default child-schema cache is ported

Filament caches materialized schemas twice: `cachedDefaultChildSchemas` (the default map, behind the `areCachedDefaultChildSchemasFresh()` seam) and `cachedChildSchemas` (one entry per non-default key, filled by `getChildSchema()` when the slot holds a non-empty, non-`Closure` value).
Sylament ports the first and not the second, so a named slot re-materializes on every `getChildSchema()` call — the Repeater needs only the default map, and the per-key cache would freeze slots whose components a consumer mutates between reads.
Filament's matching second arm in `getChildSchema()` (a *known* key that also appears in the cached default map wins from the cache) goes with it: our fallback arm answers unknown keys only, so `childComponents` always wins for a key it holds.
`cloneChildComponents()` resets the ported cache where Filament resets its per-key one; its action arm is a shallow `clone` ([schema.md](schema.md#clonecomponents-never-stamps-the-original-and-always-resets-the-cache)).
Restore the per-key cache if slot materialization ever shows in a profile.

### components()/childComponents() accept a single component, not just an array

Filament types `components()` as `array | Closure` and `childComponents()` as a wide `array | Schema | Component | Action | ActionGroup | string | Htmlable | Closure | null` union.
Sylament widens `components()` to the same full `Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null` union as `childComponents()` (single-value convenience, matching how Section/`Field` pass one component).
Reversible if the single-value form proves ambiguous.

### getChildSchemas() drops the withHidden parameter

Filament's `getChildSchemas(bool $withHidden = false)` returns `[]` for a hidden component unless `withHidden` is true.
Sylament's `getChildSchemas()` takes no argument and never filters on visibility: it always answers what Filament's `withHidden: true` answers, which is what [`CanBeHidden::hiddenWhenAllChildComponentsHidden()`](can-be-hidden.md) asks for upstream as well.
A caller that wants Filament's default checks `isHidden()` itself; restore the parameter when a visibility-aware traversal needs it.

### Relationship-derived and constant state are dropped from the component trait

Filament's `HasState` composes `CanGetStateFromRelationships` and carries `loadStateFromRelationships`, the whole constant-state surface (`getConstantStateUsing`, `constantState`, `getConstantState`, `getConstantStatePath`, `getRecordConstantStatePath`, `getConstantStateFromRecord`, `getStatePathForRelationship`, `hasConstantState`) and the list-splitting helpers (`separator`/`getSeparator`, `distinctList`/`isDistinctList`).
None is ported — they all assume Eloquent models and relationships.
The Doctrine relation layer does **not** reverse this: it answers the relationship read with the [per-component hook pair](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) consulted by the two mapping edges, so `loadStateFromRelationships` on the trait has nothing left to do — its post-save re-read is what [`Schema::loadStateFromRelationships()`](belongs-to-record.md#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter) runs from the root, over the record path map — and constant state, a *display* value read across a relation and never dehydrated, has no consumer in the port.
Revisit when one arrives (infolists is the likely first).

### Child-schema recursion moves from the component trait up to the schema trait

Filament's component-level `hydrateState` and `fillStateWithNull` recurse into `getChildSchemas()` to walk the tree.
Sylament's leaf variants handle only their own component (`hydrateState`: the defaults pass plus one cast-set pass keyed by `appliedStateCastPaths`; `fillStateWithNull`: `state(null)` when the raw slot is null); the whole-tree walk, and the `callAfterStateHydrated()` pass Filament runs inside each `hydrateState()`, are driven by the [`Schema`](schema.md)-level state trait via `getFlatComponents()`.
This is why the leaf `hydrateState` signature reads `(bool $applyDefaults = true, array &$appliedStateCastPaths = [], array &$hydratedDefaultState = [])` — no `$shouldCallHydrationHooks`, no `$shouldApplyStateCasts` and no `Arrayable`→array normalization, and the defaults flag is an explicit `bool` where Filament encodes it in the nullability of `$hydratedDefaultState`.
The shared record itself is ported, along with the `hydrateDefaultState` it feeds ([why it has to be](#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default)), one visibility down: Filament's is public, ours is `protected`, because only the component's own `hydrateState()` calls it.

### afterStateUpdated firing, de-dup, and bubbling move out of the trait to the LiveComponent host

Filament's `callAfterStateUpdated` orchestrates `partiallyRender`/`skipRender`/parent bubbling and dedups each hook run via `Livewire\store` run-ids; it also supports `afterStateUpdatedJs` client-side expressions.
Sylament ports the hook registry (`afterStateUpdated`, `clearAfterStateUpdatedHooks`), a public `callAfterStateUpdatedHooks()` (protected upstream) and the protected `callAfterStateUpdatedHook()`, which injects `old`/`oldRaw`.
Firing and value-change de-dup are the [`HasSchemas` host](has-schemas.md)'s `#[PostHydrate]` responsibility (proven by [`LiveStateTest`](../../../packages/schemas/tests/Integration/LiveStateTest.php)), and `$set(..., shouldCallUpdatedHooks: true)` fires them for the component it writes; no orchestrator and no JS-hook surface are ported.
`resolveRelativeKey()`, the key-path twin of `resolveRelativeStatePath()`, goes with the orchestrator: its one caller upstream is the parent bubbling.

### getRawState returns null (not []) for a pathless component and skips blank-to-null coercion

Filament's `getRawState` returns `[]` when the state path is blank and coerces a non-array blank value to null.
Sylament returns `null` for a blank path and returns the [PropertyAccessor](property-accessor.md) value verbatim (no blank coercion).
Downstream callers rely on `blank()`/`filled()` rather than the `[]` sentinel, so the change is contained; revisit if a caller depends on the array default.

### cachedAbsoluteStatePath uses an explicit has-flag instead of an unset typed property

Filament relies on `isset()` over an uninitialized typed string property plus `unset()` to flush the cache.
Sylament stores a nullable `?string cachedAbsoluteStatePath` guarded by a `bool hasCachedAbsoluteStatePath` flag (set in `cacheAbsoluteStatePath()` via try/finally), which PHPStan at level max reads without an `unset()` of a typed property.
`flushCachedAbsoluteStatePath()` is `protected` where Filament's is public; its callers are `statePath()` and `Cloneable::getClone()`.

### CanBeRepeated discriminates on HasKeyedItems, not on a forms type

Filament's [`CanBeRepeated`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeRepeated.php) hard-codes `Repeater | Builder` in its return type, its `instanceof` test and its `assert()` — three references from `schemas` up into `forms`, which the package split forbids.
The port discriminates on [`HasKeyedItems`](../../../packages/schemas/src/Components/Contracts/HasKeyedItems.php) instead, the contract a repeater-shaped component already implements, and returns the intersection type `(Component&HasKeyedItems)|null`.
Filament's `assert()` becomes a thrown `LogicException`, because shipped source carries no assertions that vanish under `zend.assertions=-1`.

`getParentRepeaterItemIndex()` reads the index off `$repeater->getItemKeys()` rather than `array_keys($item->getParentComponent()->getState())`: the contract already publishes the item keying, so the index needs no state read at all, and it stays correct for a component whose state has not been hydrated.
The memo keeps Filament's three states, typed `Schema|false|null` where Filament types `Schema|bool|null`, and is tested with `!== null` / `instanceof` rather than Filament's `filled()` / `?:`.
Both spellings agree — `filled(false)` is `true` in Laravel's helper and in [ours](../support/helpers.md), so the `false` sentinel does short-circuit the walk — but only by an accident of the helper a reader has to reconstruct, and [`filled()`](../support/helpers.md)'s sole static assertion is a null check, so the `instanceof` is needed anyway to reach the `?Schema` return at level max.
Saying it directly costs nothing and pins the sentinel's meaning.

Filament flushes the memo in two places the port does not: its `getClone()` calls `flushCachedParentRepeaterItem()`, and its `container()` does through `flushCachedHierarchy()` when a component is re-parented.
The port's `CanBeRepeated` has no flush, its [`Cloneable::getClone()`](../../../packages/schemas/src/Components/Concerns/Cloneable.php) resets the three cached absolute paths and nothing else, and its `container()` flushes nothing ([BelongsToContainer](#belongstocontainer-lacks-getmodelrootcontainer-and-the-re-parenting-flush)), so a clone or a re-parented component keeps the original's answer.
No shipped path reaches the difference: the live clone path copies components before anything has queried them — [`Repeater`](../forms/repeater.md#every-item-schema-is-keyed-as-well-as-re-pathed) cloning its *template* child schema per item, and a template is never rendered, so nothing in it has walked for a parent repeater item.
A clone taken after a walk would keep the original's answer; port the flush with the re-parenting flush when such a path appears.
