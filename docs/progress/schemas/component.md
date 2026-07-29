# Component (schemas)

[`Sylament\Schemas\Components\Component`](../../../packages/schemas/src/Components/Component.php)
— the abstract base every schema component (Section, and the form fields to come)
extends. It subclasses the support
[`ViewComponent`](../support/component.md) and layers on the schema component
model: container membership, child schemas, identity (`id`/`key`), visibility,
headings, and the `$get`/`$set`/state machinery. It ports
[`schemas/src/Components/Component.php`](../../../vendor/filament/schemas/src/Components/Component.php).

Only what this class **adds over `ViewComponent`** is tracked here. The base
`evaluate()` / `Configurable` machinery and the support-level traits it also
composes — `CanGrow`, `CanOrderColumns`, `CanSpanColumns`, `HasExtraAttributes`
— are audited in [`support/component.md`](../support/component.md). The
cross-component schema concerns it composes have their own files:
[`HasColumns`](has-columns.md), [`HasGap`](has-gap.md),
[`HasStateBindingModifiers`](has-state-binding-modifiers.md), and the visibility
concern [`CanBeHidden`](can-be-hidden.md). The single-use concerns below
(`BelongsToContainer`, `HasChildComponents`, `HasId`, plus the component-level
`HasState`, `HasKey`, `HasHeadings`) are folded into this file as surface rows.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Component.php`](../../../vendor/filament/schemas/src/Components/Component.php)
  — the base class, its trait set, and the closure-dependency overrides
- [`schemas/src/Components/Concerns/`](../../../vendor/filament/schemas/src/Components/Concerns)
  — the folded concern implementations behind the rows below

## Surface

Every point this class adds, plus one row per folded concern. _Blocked by_ names
what has to land first. _Detail_ carries a **detail** link (the write-up in
[Details](#details), deleted once the row is **done**), a **semantics** link (a
permanent note in [Semantics](#semantics)), and/or a **diverges** link (the
permanent note in [Divergences](#divergences)), or `—`. Rows never disappear;
only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Trait composition](../../../packages/schemas/src/Components/Component.php) | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | in progress | — | [detail](#trait-composition-ports-a-subset-of-filaments) |
| [`evaluationIdentifier = 'component'`](../../../packages/schemas/src/Components/Component.php) | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | done | — | — |
| [`resolveDefaultClosureDependencyForEvaluationByName`](../../../packages/schemas/src/Components/Component.php) (get/set/state/rawState/liveComponent/record/operation/context) | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | in progress | repeaters | [detail](#the-by-name-closure-dependency-map-is-missing-one-key) |
| [`resolveDefaultClosureDependencyForEvaluationByType`](../../../packages/schemas/src/Components/Component.php) (Get/Set/record) | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | done | — | [diverges](#type-based-closure-resolution-probes-the-record-instead-of-gating-on-model) |
| [`getExtraTemplateData()` override](../../../packages/schemas/src/Components/Component.php) | [`getExtraViewData()`](../../../vendor/filament/schemas/src/Components/Component.php) | todo | — | [detail](#getextratemplatedata-is-not-overridden) |
| [`toSchemaHtml()` render wrapper](../../../packages/schemas/src/Components/Component.php) | [`toSchemaHtml()`](../../../vendor/filament/schemas/src/Components/Component.php) | blocked | live-component-state | [diverges](#the-livewirealpine-render-wrapper-toschemahtml-is-not-ported) |
| [`renderWrapperBladeComponent()`](../../../packages/schemas/src/Components/Component.php) | [`renderWrapperBladeComponent()`](../../../vendor/filament/schemas/src/Components/Component.php) | skipped | — | [diverges](#the-livewirealpine-render-wrapper-toschemahtml-is-not-ported) |
| [`viewIdentifier = 'schemaComponent'`](../../../packages/schemas/src/Components/Component.php) | [`Component.php`](../../../vendor/filament/schemas/src/Components/Component.php) | todo | — | [detail](#viewidentifier-is-not-set) |
| [`BelongsToContainer`](../../../packages/schemas/src/Components/Concerns/BelongsToContainer.php) | [`Concerns/BelongsToContainer.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToContainer.php) | in progress | Doctrine relationship-state epic | [detail](#belongstocontainer-getmodelrootcontainer-awaits-its-consumer), [diverges](#belongstocontainer-gains-a-sylament-only-getlivecomponentornull-peek) |
| [`HasChildComponents`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) | [`Concerns/HasChildComponents.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasChildComponents.php) | in progress | — | [detail](#haschildcomponents-only-the-materialization-core-is-ported) |
| [`HasId`](../../../packages/schemas/src/Components/Concerns/HasId.php) | [`Concerns/HasId.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasId.php) | done | — | — |
| [`HasState`](../../../packages/schemas/src/Components/Concerns/HasState.php) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php) | in progress | — | [detail](#hasstate-the-getset-core-and-validation-flags-are-in-relationship-and-partial-render-state-are-out) · [semantics](#dehydration-prunes-around-shared-state-paths-and-pathless-components) |
| [`HasKey`](../../../packages/schemas/src/Components/Concerns/HasKey.php) | [`Concerns/HasKey.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasKey.php) | in progress | — | [detail](#getlivewirekey-is-deferred-to-the-live-component-layer) |
| [`HasHeadings`](../../../packages/schemas/src/Components/Concerns/HasHeadings.php) | [`Concerns/HasHeadings.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasHeadings.php) | done | — | — |

## Details

Specifics for rows that are not finished — what is in, what is left, the next
step. Delete a subsection when its row flips to **done**; the row and any
divergence it earned stay behind.

### Trait composition ports a subset of Filament's

`evaluationIdentifier = 'component'` is set, and the composed set covers
container/child/identity/visibility/heading/state plus the
[record binding](belongs-to-record.md), actions, and the shared column and gap
concerns — enough to drive Section and the forms `Field` slots. Left: the
Filament traits not yet on the base — `CanBeConcealed`,
`CanBeGridContainer`, `CanBeLiberatedFromContainerGrid`,
`CanBeRepeated`, `CanPartiallyRender`, `CanPoll`, `Cloneable`,
`HasEntryWrapper`, `HasFieldWrapper`, `HasInlineLabel`, `HasMaxWidth`,
`HasMeta` — each of which has its own row in the
[schemas README](README.md) and lands with its own epic. Next step: compose them
as their concerns are audited; nothing here blocks the already-composed set.

### The by-name closure-dependency map is missing one key

`resolveDefaultClosureDependencyForEvaluationByName` resolves `$get`, `$set`,
`$state`, `$rawState`, `$liveComponent` (Filament's `livewire` key), `$record`
([`BelongsToRecord`](belongs-to-record.md)), and `$operation`/`$context`
([`HasOperation`](has-operation.md), resolved through the container and
[guarded](has-operation.md#the-injection-arm-is-guarded-so-a-detached-component-still-evaluates)),
tested end to end by
[`ComponentTest`](../../../packages/schemas/tests/Unit/Components/ComponentTest.php)
and the two concerns' own suites. Left: `parentRepeaterItemIndex` only, gated on
repeaters. Filament's `model` key is deliberately absent, not pending — see
[the dropped parameter](belongs-to-record.md#the-model-closure-parameter-is-not-ported).
Next step: add the repeater key with `CanBeRepeated`.

### `getExtraTemplateData()` is not overridden

Filament's `getExtraViewData()` seeds the template with `get`, `operation`, and
`record`. All three values are now reachable (`makeGetUtility()`,
`getContainer()->getOperation()`, `getRecord()`), so this is no longer a
prerequisite question but a policy one: the hook
[stays minimal](../support/view-component.md) — only values a template cannot
get from a single getter call belong in it, and `component.getRecord` /
`component.getContainer.getOperation` are single getter calls. Next step:
override only when a shipped template actually needs one of them and reading it
inline would be worse; `get`, which is not a getter on the component, is the
likeliest first entry.

### `viewIdentifier` is not set

Filament sets `protected string $viewIdentifier = 'schemaComponent'`. Sylament
does not declare it, because the property only matters to the unported
`toSchemaHtml` wrapper and the wrapper-view identity it drives. Next step: set it
in the same change that lands the render wrapper.

### BelongsToContainer: getModelRootContainer() awaits its consumer

`container()` / `getContainer()` / `getRootContainer()` (byte-identical to
Filament) and `getLiveComponent()` (Filament's `getLivewire()`) are ported and
tested by
[`BelongsToContainerTest`](../../../packages/schemas/tests/Unit/Components/Concerns/BelongsToContainerTest.php)
and `ComponentTest`'s `$liveComponent` case. Left: `getModelRootContainer()`,
which walks the parent chain looking for the outermost container bound to the
same record — `@internal` in Filament but wanted for parity. The getters it
compares now exist ([`BelongsToRecord`](belongs-to-record.md) supplies
`getRecordClass()`/`getRecord()`), so what it waits on is its consumer:
Filament's only callers are the relationship-entangling components, so it lands
with the Doctrine relationship-state epic and would otherwise be untested dead
code. Next step: port it there, renamed off the `model` vocabulary with the rest.

### HasChildComponents: only the materialization core is ported

`components()` / `childComponents()` / `getChildSchema()` / `getChildSchemas()` /
`makeChildSchema()` / `configureChildSchema()` are ported and drive real
consumers (Section, the forms `Field` schema slots); `getChildSchema` is
documented to *not* materialize the implicit `default` schema during a state
walk, a deliberate behavioural note to preserve. Direct coverage is thin
([`HasChildComponentsTest`](../../../packages/schemas/tests/Unit/Components/Concerns/HasChildComponentsTest.php)
pins the null-slot clear and the empty-default survival), the rest exercised
transitively via `SectionTest`/`HasIdTest`/forms `Field`. Left: the `schema()`
alias, the `getChildComponents()` / `getDefaultChildComponents()` /
`getDefaultChildSchemas()` helpers, the deprecated aliases, the caching/clone
layer, and the `withHidden` parameter — the last three carry divergences below.
Next step: add the cheap helpers and `schema()` alias now; defer caching to the
render pipeline and `cloneChildComponents()` to `Cloneable`.

### HasState: the get/set core and validation flags are in; relationship and partial-render state are out

The state engine is fully ported and covered across three test files
([Unit/Components/Concerns/HasStateTest](../../../packages/schemas/tests/Unit/Components/Concerns/HasStateTest.php),
[Unit/Concerns/HasStateTest](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php),
[Integration/LiveStateTest](../../../packages/schemas/tests/Integration/LiveStateTest.php)):
state-path composition, raw/cast read-write via the
[PropertyAccessor seam](property-accessor.md), defaults, hydrate, the
dehydration prune/mutate walk, and the `afterStateUpdated` round-trip. The
validation flag trio is in too, Filament-exact —
`validatedWhenNotDehydrated(bool|Closure $condition = true)`,
`isValidatedWhenNotDehydrated()` (default `true`), and
`isNeitherDehydratedNorValidated()`, the guard
[`Schema::getValidationConstraints()`](can-be-validated.md) skips on — covered
by the component-tier `HasStateTest`.

It stays in progress because whole method families are deliberately absent —
relationship-derived and constant state, the `mutateStateForValidation`
members, and partial-render/JS hooks (each a divergence below) — and because
one ported method, `formatStateUsing()`, has no direct test. Next step: add a
`formatStateUsing` test; port the absent families under their respective epics
(Doctrine relationship-state, `CanPartiallyRender`).

### getLivewireKey() is deferred to the live-component layer

The whole key model — `key()` setter with inheritability, relative/absolute
`getKey()` with the state-path fallback, `getInheritanceKey()`, sanitization, and
absolute caching — is ported byte-for-byte and fully covered by
[`HasKeyTest`](../../../packages/schemas/tests/Unit/Components/Concerns/HasKeyTest.php).
The one method left is `getLivewireKey()`, which composes
`{getLiveComponent()->getId()}.{key}` — it depends on the live-component layer's
id contract, so it is **blocked**, not skipped. Next step: port it as
`getLiveComponentKey()` (recorded in the [rename table](../renames.md)) in the
change that wires `getLiveComponent()->getId()`, with a render/id test.

## Semantics

Faithful ports whose behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### Dehydration prunes around shared state paths and pathless components

The dehydration walk looks like "a non-dehydrated component removes its key", and
that reading is wrong twice over.

Two components can be bound to the **same** state path, so removing the key
outright would destroy the sibling's data. A non-dehydrated component therefore
leaves the key untouched when
`getRootContainer()->hasDehydratedComponent()` reports a dehydrated owner for the
path; otherwise, when a sibling shares the path, it forgets only the paths its own
descendants own (`getDescendantStatePathsToForget()`), and drops the shared parent
only once nothing meaningful is left under it.

A **pathless** (layout) component has no key of its own to forget. It prunes by
recursing into its child schemas with `isDehydrated: false` — that recursion is
the whole mechanism carrying the prune down to its descendants, so a layout
component reads as a no-op here while doing all the work.

## Divergences

Deliberate deviations from Filament, and the reason each holds. These outlive the
detail sections above. Provisional ones say what would reverse them.

### BelongsToContainer gains a Sylament-only getLiveComponentOrNull() peek

`getLiveComponent()` (like its schema-level counterpart) throws when the chain
is unbound, where Filament's `getLivewire()` is nullable — so the actions
package's live-component fallback chain, which probes a bound schema component
for its host, needs a non-throwing read. `getLiveComponentOrNull()` is that
peek: `null` while the `$container` back-reference is uninitialized, otherwise
the container's own
[`getLiveComponentOrNull()`](belongs-to-live-component.md) (the raw nullable
property). Filament has no counterpart method because its primary getters
already return `null`. Exercised by the actions-side
[`BelongsToLiveComponentTest`](../../../packages/actions/tests/Unit/Concerns/BelongsToLiveComponentTest.php)
schema-component hop.

### The Livewire/Alpine render wrapper (toSchemaHtml) is not ported

Filament's `toSchemaHtml()` emits the outer `<div>` with `wire:key`, `wire:poll`,
`x-data="filamentSchemaComponent"`, `x-bind:class` visibility toggling,
grid-column attributes, and the `fi-sc-component` inner wrapper — plus
`renderWrapperBladeComponent()`. None is ported: it is entirely Livewire + Alpine
+ Blade and depends on the live-component-state runtime (state paths, `$wire`,
polling, JS visibility). It returns in the epic that lands
[`getLiveComponent()`](has-schemas.md)-backed rendering.
`renderWrapperBladeComponent()` (a Blade back-compat shim for
`x-dynamic-component` wrappers) is **skipped** — a non-goal even then.

### Type-based closure resolution probes the record instead of gating on `Model`

`resolveDefaultClosureDependencyForEvaluationByType` is wired — the codebase's
first `…ByType()` override, alongside [`Schema`](schema.md)'s — so
`fn (Get $reader) => …`, `fn (Set $writer) => …` and `fn (Article $record) => …`
all resolve by type-hint, not only by the `$get`/`$set`/`$record` *names*. It
diverges in the record arm: Filament resolves `Model::class` and `$record::class`
behind an `is_a($type, Model::class)` gate, and there is no `Model` base class
here, so the exact `Get::class`/`Set::class` arms are matched first and the
record is then probed with
[`$record instanceof $parameterType`](belongs-to-record.md#the-record-type-probe-is-instanceof-not-a-model-gate)
— which additionally covers parent classes, interfaces, and Doctrine proxies.
Filament's `self`/`static` arm has no counterpart: the base `EvaluatesClosures`
own-class mechanism already injects the evaluating instance.

### The child-schema caching and clone layer is not ported

Filament caches materialized schemas (`cachedDefaultChildSchemas`,
`cachedChildSchemas`, cleared on `childComponents()` mutation and via
`clearCachedChildSchemas`) and deep-clones child components in
`cloneChildComponents()`. Sylament re-materializes on every `getChildSchema` call
and ships no clone support (`Cloneable` is not on the base). Provisional: the
cache returns with the render pipeline where repeated materialization would show;
`cloneChildComponents()` returns with the `Cloneable` concern.

### components()/childComponents() accept a single component, not just an array

Filament types `components()` as `array | Closure` and `childComponents()` as a
wide `array | Schema | Component | Action | ActionGroup | string | Htmlable |
Closure | null` union. Sylament widens `components()` to the same full
`Schema|array|Component|Action|ActionGroup|string|Markup|Closure|null` union as
`childComponents()` (single-value convenience, matching how Section/`Field` pass
one component). Reversible if the single-value form proves ambiguous.

### getChildSchemas() drops the withHidden parameter

Filament's `getChildSchemas(bool $withHidden = false)` returns `[]` for a hidden
component unless `withHidden` is true, and threads the flag through. Sylament's
`getChildSchemas()` takes no argument and does not filter on visibility, so
callers (e.g. `CanBeHidden::hiddenWhenAllChildComponentsHidden`) cannot request
the hidden set. Behaviour matches under the current unfiltered traversal; restore
the parameter when visibility-aware traversal is needed.

### Relationship-derived and constant state are dropped from the component trait

Filament's `HasState` composes `CanGetStateFromRelationships` and carries
`loadStateFromRelationships`, the whole constant-state surface
(`getConstantStateUsing`, `constantState`, `getConstantState`,
`getConstantStatePath`, `getRecordConstantStatePath`, `getConstantStateFromRecord`,
`getStatePathForRelationship`, `hasConstantState`) and the list-splitting helpers
(`separator`/`getSeparator`, `distinctList`/`isDistinctList`). None is ported —
they all assume Eloquent models/relationships. Reversed by the Doctrine
relationship-state epic (Filament's T6).

### The mutateStateForValidation members are not ported

Filament's `mutateStateForValidation()` / `mutateStateForValidationUsing()` /
`mutatesStateForValidation()` rewrite a copy of a component's state for the
host's `prepareForValidation()` pass before Laravel's validator sees it. The
Symfony port validates the raw live state — violations never mutate state and
no rule-object enum→scalar conversion exists
([schema.md](schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer))
— so the members have no job. Permanent. The sibling flag trio
(`validatedWhenNotDehydrated` / `isValidatedWhenNotDehydrated` /
`isNeitherDehydratedNorValidated`) is ported Filament-exact — it is the guard
the [validation collection walk](can-be-validated.md) skips on.

### Child-schema recursion moves from the component trait up to the schema trait

Filament's component-level `hydrateState` and `fillStateWithNull` recurse into
`getChildSchemas()` to walk the tree. Sylament's leaf variants handle only their
own component (`hydrateState`: default + one cast-set pass keyed by
`appliedStateCastPaths`; `fillStateWithNull`: `state(null)` when the raw slot is
null); the whole-tree walk is driven by the [`Schema`](schema.md)-level state
trait via `getFlatComponents()`. This is why the leaf `hydrateState` signature
shrank to `(bool $applyDefaults, array &$appliedStateCastPaths)` — no
`$hydratedDefaultState` tracking, no `$shouldCallHydrationHooks`, no
`Arrayable`→array normalization, no separate `hydrateDefaultState`.

### afterStateUpdated firing, de-dup, and bubbling move out of the trait to the LiveComponent host

Filament's `callAfterStateUpdated` orchestrates `partiallyRender`/`skipRender`/
parent bubbling and dedups each hook run via `Livewire\store` run-ids; it also
supports `afterStateUpdatedJs` client-side expressions. Sylament ports only the
hook registry (`afterStateUpdated`, `clearAfterStateUpdatedHooks`) and a public
`callAfterStateUpdatedHooks` / `callAfterStateUpdatedHook` that inject
old/oldRaw. Firing and value-change de-dup are the [`HasSchemas`
host](has-schemas.md)'s `#[PostHydrate]` responsibility (proven by
`LiveStateTest`); no orchestrator and no JS-hook surface are ported.

### getRawState returns null (not []) for a pathless component and skips blank-to-null coercion

Filament's `getRawState` returns `[]` when the state path is blank and coerces a
non-array blank value to null. Sylament returns `null` for a blank path and
returns the [PropertyAccessor](property-accessor.md) value verbatim (no blank
coercion). Downstream callers rely on `blank()`/`filled()` rather than the `[]`
sentinel, so the change is contained; revisit if a caller depends on the array
default.

### cachedAbsoluteStatePath uses an explicit has-flag instead of an unset typed property

Filament relies on `isset()` over an uninitialized typed string property plus
`unset()` to flush the cache. Sylament stores a nullable `?string
cachedAbsoluteStatePath` guarded by a `bool hasCachedAbsoluteStatePath` flag (set
atomically in `cacheAbsoluteStatePath` via try/finally). This is PHPStan
level-max friendly and lets `null` be a legitimately cached absolute path.

### hydrateStatePartially and resolveRelativeKey are not ported here

Filament's `HasState` also carries `hydrateStatePartially` (partial re-hydration
of a subset of state paths, used by partial rendering) and `resolveRelativeKey`
(the key-path twin of `resolveRelativeStatePath`). `hydrateStatePartially` is
blocked on the `CanPartiallyRender` concern (todo); `resolveRelativeKey` belongs
to the key concern — Sylament keeps key resolution in `HasKey`, not `HasState`.
The deprecated `getGetCallback`/`getSetCallback` aliases and the
`shouldUpdateValidatedStateAfterBeforeStateDehydratedRuns()` getter are also
intentionally not carried.

_See also: the global [rename table](../renames.md) — the Livewire → UX
LiveComponent, Blade view → Twig template, `Htmlable` → `Twig\Markup`, and
`data_get`/`Arr::` → PropertyAccessor translations these rows lean on are
systematic idiom translations recorded there, not per-component divergences._
