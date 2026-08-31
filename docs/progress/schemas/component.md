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
[`HasStateBindingModifiers`](has-state-binding-modifiers.md), the visibility
concern [`CanBeHidden`](can-be-hidden.md), and the concealment walk
[`CanBeConcealed`](can-be-concealed.md). The single-use concerns below
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
| [`BelongsToContainer`](../../../packages/schemas/src/Components/Concerns/BelongsToContainer.php) | [`Concerns/BelongsToContainer.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToContainer.php) | in progress | a consumer — the subtree-owner fold ([entangles](entangles-state-with-singular-relationship.md#two-components-entangling-one-relationship-throw-rather-than-merge)) | [detail](#belongstocontainer-getmodelrootcontainer-awaits-its-consumer), [diverges](#belongstocontainer-gains-a-sylament-only-getlivecomponentornull-peek) |
| [`HasChildComponents`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) | [`Concerns/HasChildComponents.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasChildComponents.php) | in progress | — | [detail](#haschildcomponents-only-the-materialization-core-is-ported) · [semantics](#childcomponents-replaces-a-keys-contents-instead-of-appending) |
| [`HasId`](../../../packages/schemas/src/Components/Concerns/HasId.php) | [`Concerns/HasId.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasId.php) | done | — | — |
| [`HasState`](../../../packages/schemas/src/Components/Concerns/HasState.php) | [`Concerns/HasState.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php) | in progress | — | [detail](#hasstate-the-getset-core-and-validation-flags-are-in-relationship-and-partial-render-state-are-out) · [semantics](#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default) · [semantics](#dehydration-prunes-around-shared-state-paths-and-pathless-components) |
| [`HasKey`](../../../packages/schemas/src/Components/Concerns/HasKey.php) | [`Concerns/HasKey.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasKey.php) | done | — | [live-component.md](live-component.md#the-host-id-is-minted-by-the-host-because-ux-has-no-stable-component-id) |
| [`HasHeadings`](../../../packages/schemas/src/Components/Concerns/HasHeadings.php) | [`Concerns/HasHeadings.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasHeadings.php) | done | — | — |

## Details

Specifics for rows that are not finished — what is in, what is left, the next
step. Delete a subsection when its row flips to **done**; the row and any
divergence it earned stay behind.

### Trait composition ports a subset of Filament's

`evaluationIdentifier = 'component'` is set, and the composed set covers
container/child/identity/visibility/heading/state plus the
[record binding](belongs-to-record.md), actions, [concealment](can-be-concealed.md),
and the shared column and gap concerns, plus [`CanBeRepeated`](#canberepeated-discriminates-on-haskeyeditems-not-on-a-forms-type) — enough to drive Section and the forms `Field` slots. Left: the
Filament traits not yet on the base —
`CanBeGridContainer`, `CanBeLiberatedFromContainerGrid`,
`CanPartiallyRender`, `CanPoll`,
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
and the two concerns' own suites. Left: `parentRepeaterItemIndex` only, and it is
no longer gated on a trait —
[`CanBeRepeated`](#canberepeated-discriminates-on-haskeyeditems-not-on-a-forms-type)
landed with the repeater chrome and answers `getParentRepeaterItemIndex()`
already; what is missing is the arm that injects that number into a closure under
that name. Filament's `model` key is deliberately absent, not pending — see
[the dropped parameter](belongs-to-record.md#the-model-closure-parameter-is-not-ported).
Next step: add the `$parentRepeaterItemIndex` key to the by-name map — deferred
past the repeater-chrome epic and owned by its sweep.

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
`getRecordClass()`/`getRecord()`), so what it waits on is its consumer — and the
[Doctrine relation layer](../roadmap.md#pulled-forward) shipped **without**
becoming one. Filament's only callers are the relationship-entangling
components, where the method roots the walk that collects every component naming
one relationship so the first of them fills and saves for all; the port
[refuses that merge outright](entangles-state-with-singular-relationship.md#two-components-entangling-one-relationship-throw-rather-than-merge)
(two subtree owners at one record path is a `LogicException`), and the
record-relative machinery it did need reads
[`getRecordClass()`'s container fallback](belongs-to-record.md) through the
ordinary `getRootContainer()`. The remaining trigger is that refusal being
reversed — teaching the record path map to fold several subtree owners at one
path — which is the only shape here that needs *the outermost container bound to
the same record* rather than the root. Next step: port it with that fold,
renamed off the `model` vocabulary with the rest.

### HasChildComponents: only the materialization core is ported

`components()` / `childComponents()` / `getChildSchema()` / `getChildSchemas()` /
`makeChildSchema()` / `configureChildSchema()` are ported and drive real
consumers (Section, the forms `Field` schema slots); `getChildSchema` is
documented to *not* materialize the implicit `default` schema during a state
walk, a deliberate behavioural note to preserve. The default-child-schema cache
is ported too — `getDefaultChildSchemas()` / `getCachedDefaultChildSchemas()` /
`areCachedDefaultChildSchemasFresh()` / `clearCachedChildSchemas()`, the
unknown-key fallback in `getChildSchema()`, the cached-map spread in
`getChildSchemas()`, and invalidation from `childComponents()` and
`cloneChildComponents()` — the seam the Repeater's per-item schemas ride
([repeater.md](../forms/repeater.md)); Filament's second, per-key
`cachedChildSchemas` is not
([diverges](#only-the-default-child-schema-cache-is-ported)).
[`HasChildComponentsTest`](../../../packages/schemas/tests/Unit/Components/Concerns/HasChildComponentsTest.php)
pins the null-slot clear, the empty-default survival, the keyed fallback, the
memoisation and its three invalidations; the rest is exercised transitively via
`SectionTest`/`HasIdTest`/forms `Field`. `cloneChildComponents()` is ported and
pinned by
[`CloneableTest`](../../../packages/schemas/tests/Unit/Components/Concerns/CloneableTest.php).
Left: the `getChildComponents()` / `getDefaultChildComponents()` helpers, the
deprecated aliases, and the `withHidden` parameter — the last carries a
divergence below; the `schema()` alias is deliberately not ported
([diverges](#the-schema-alias-is-not-ported)).
Next step: add the cheap helpers.

### HasState: the get/set core and validation flags are in; relationship and partial-render state are out

The state engine is fully ported and covered across three test files
([Unit/Components/Concerns/HasStateTest](../../../packages/schemas/tests/Unit/Components/Concerns/HasStateTest.php),
[Unit/Concerns/HasStateTest](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php),
[Integration/LiveStateTest](../../../packages/schemas/tests/Integration/LiveStateTest.php)):
state-path composition, raw/cast read-write via the
[PropertyAccessor seam](property-accessor.md), defaults, hydrate, the
dehydration prune/mutate walk, and the `afterStateUpdated` round-trip. The
validation-mutation trio joins them —
`mutateStateForValidationUsing(?Closure $callback)`,
`mutateStateForValidation(mixed $state): mixed` (which evaluates the closure with
a named `state` injection) and `mutatesStateForValidation(): bool` — each ported
verbatim and covered in the component-tier `HasStateTest`, with the
[root-tier walk](schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer)
that drives them covered in the schema-tier one. `Field` deliberately does **not**
override `mutatesStateForValidation()` to a constant `true` the way Filament's
forms trait does: its only first-party job upstream is unwrapping a `BackedEnum`
for Laravel's `in` rule, and the raw bag here never holds an enum —
[`EnumStateCast::set()`](enum-state-cast.md) stringifies on hydration. The
validation flag trio is in too, Filament-exact —
`validatedWhenNotDehydrated(bool|Closure $condition = true)`,
`isValidatedWhenNotDehydrated()` (default `true`), and
`isNeitherDehydratedNorValidated()`, the guard
[`Schema::getValidationConstraints()`](can-be-validated.md) skips on — covered
by the component-tier `HasStateTest`.

It stays in progress because whole method families are deliberately absent —
relationship-derived and constant state, and partial-render/JS hooks (each a
divergence below) — and because one ported method, `formatStateUsing()`, has no
direct test. The `mutateStateForValidation` members are no longer among them:
they shipped with M3 · 8 and carry a
[semantics note](#the-mutatestateforvalidation-members-mutate-the-runners-copy-of-one-path)
of their own. Next step: add a `formatStateUsing` test; port the absent families
under their respective epics (the
[relationship-derived state](#relationship-derived-and-constant-state-are-dropped-from-the-component-trait)
families below, `CanPartiallyRender`).

## Semantics

Faithful ports whose behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### A defaults fill nulls a defaultless component, and the shared record is what protects a container's default

`Schema::fill()` with no argument runs `hydrateState(applyDefaults: true)`, and
that pass is a **replacement**, not a gap-filler: a component declaring a default
gets it written over whatever the bag held, and a component declaring **none**
gets `rawState(null)`. Both arms are Filament's
([`hydrateDefaultState`](../../../vendor/filament/schemas/src/Components/Concerns/HasState.php)),
and the second is the one that reads like a bug — a defaults fill wiping state
where no default was ever declared — so it is the one that gets "fixed" back into
a skip.

It was, here, until a browser pass on the table-filters epic caught it: a
`SelectFilter` builds a `Select` that takes a `default()` only when the *filter*
declares one, so `resetTableFiltersForm()` left an applied `status` value
standing in both bags while the sibling `Filter` — a `Checkbox`, which declares
`default(false)` in its own `setUp()` — cleared correctly. The asymmetry is only
reachable when the bag already holds state at fill time, which is why every
mount-time fill looked right ([the epic side](../tables/has-table.md#filter-mutations-reset-the-page-and-flush-the-record-cache)).

The null-write cannot stand alone, and that is what `$hydratedDefaultState` is
for. It is a by-ref record, threaded through the whole walk by the
[`Schema`](schema.md)-level `hydrateState()`, of every default already written,
keyed by absolute state path; a component whose path the record already covers
returns untouched. Without it, a container's default is destroyed by its own
children: `Group::make([...])->statePath('author')->default(['name' => 'Ada'])`
writes the whole map, and then the defaultless `author.name` inside it — visited
next, because `getFlatComponents()` is parent-first — nulls the very key the
parent just filled. With the record, `author.name` is already covered and skips;
its sibling `author.surname`, which the parent default never mentioned, is not
covered and still nulls. Both halves are pinned in
[Unit/Concerns/HasStateTest](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php).

What the record cannot protect is a container whose child paths are *derived from
the state the default replaces* — a repeater, whose item schemas the flat walk
materialised before the default rewrote their keys: those children sit at paths
the new default never mentions, so the record waves them through and their
null-writes rebuild the items the default just dropped
([the repeater divergence](../forms/repeater.md#a-defaults-fill-over-a-populated-repeater-resurrects-its-pre-fill-items)).

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

### `childComponents()` replaces a key's contents instead of appending

[`childComponents($components, $key)`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php)
is a plain `$this->childComponents[$key] = $components` — a second call for the
same key discards whatever the first put there, with no merge and no error. Every
named-slot setter funnels through it
([`Section`](../../../packages/schemas/src/Components/Section.php)'s six,
[`Field`](../../../packages/forms/src/Components/Field.php)'s ten), so "add this
to the slot as well" is never what calling one twice does.

The cost lands on concerns that seed a slot from `setUp()`, because each is
written as a slot call and a component can compose several. Forms'
[`HasHint`](../../../packages/forms/src/Components/Concerns/HasHint.php) registers
exactly one `afterLabel()` closure and returns the hint text, the hint icon **and**
the hint actions from inside it; splitting the actions into a second
`afterLabel()` call would have left the slot holding the actions alone. Filament
folds the same three into one closure for the same reason. A concern that has to
add to a slot another concern owns cannot do it through the setter at all.

## Divergences

Deliberate deviations from Filament, and the reason each holds. These outlive the
detail sections above. Provisional ones say what would reverse them.

### The schema() alias is not ported

Filament ships `schema()` beside `components()` on this trait, undeprecated —
pure back-compat surface for its own pre-v4 call sites. Sylament ships
`components()` alone (decided 2026-08-04, with the Repeater epic's planning):
a second name for the same call adds grep surface and a second spelling every
example must choose between, for call sites this port has none of. A migrating
user's `->schema([...])` breaks loudly — missing method, and the fix is
mechanical — which the [rename criterion](../renames.md) prefers to a silently
kept synonym. The same decision covers the root tier
([schema.md](schema.md#the-schema-alias-is-not-ported)); recorded in the
[rename table](../renames.md). Reverses only if migration friction proves it
wrong in practice.

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

### Only the default child-schema cache is ported

Filament caches materialized schemas twice: `cachedDefaultChildSchemas` (the
default map, behind the `areCachedDefaultChildSchemasFresh()` seam) and
`cachedChildSchemas` (one entry per non-default key, filled by `getChildSchema()`
when the slot holds a non-empty, non-`Closure` value). Sylament ports the first
and not the second, so a named slot re-materializes on every `getChildSchema()`
call as it always did — the Repeater needs only the default map, and the per-key
cache would freeze slots whose components a consumer mutates between reads.
Filament's matching second arm in `getChildSchema()` (a *known* key that also
appears in the cached default map wins from the cache) goes with it: our fallback
arm answers unknown keys only, so `childComponents` always wins for a key it
holds. `cloneChildComponents()` resets the ported cache where Filament resets its
per-key one; its action arm is a shallow `clone`
([schema.md](schema.md#clonecomponents-never-stamps-the-original-and-always-resets-the-cache)).
Restore the per-key cache if slot materialization ever shows in a profile.

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
they all assume Eloquent models/relationships. The
[Doctrine relation layer](../roadmap.md#pulled-forward) did **not** reverse this,
as an earlier note here predicted it would: it answers the relationship read
with the [per-component hook pair](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk)
consulted by the two mapping edges, so `loadStateFromRelationships` on the trait
has nothing left to do, and constant state — a *display* value read across a
relation, never dehydrated — has no consumer in the port yet. Revisit when one
arrives (infolists is the likely first).

### The mutateStateForValidation members mutate the runner's copy of one path

`mutateStateForValidationUsing()` / `mutateStateForValidation()` /
`mutatesStateForValidation()` are ported verbatim, and this tree said until
M3 · 8 that they never would be. The reason given was Livewire's shape: upstream,
the host's `prepareForValidation()` rewrites a whole nested attributes array
before Laravel's validator sees it, and `getState()` then has to prune that array
back down to the keys the schema owns. A per-path runner needs none of that
plumbing — [`validateSchema()`](interacts-with-schemas.md) reads one path's value,
hands it to the owning component's `mutateStateForValidation()`, validates the
result and discards the copy, and `getState()` never consumes a validated array
at all. So the hooks port cleanly while `prepareForValidation()` and
`pruneStateToMatchKeys()` stay unported, and what reverses is only the claim that
the hooks had no job.

Two consequences, both deliberate. The mutation reaches **the validated value
only** — the live bag the browser wrote is untouched, so a field the user typed
whitespace into keeps it on screen. And the mutated value is what
[`PresenceGate`](presence-gate.md) sees, so trimming an input to nothing gates it
as blank and strips its non-implicit constraints.

The sibling flag trio (`validatedWhenNotDehydrated` /
`isValidatedWhenNotDehydrated` / `isNeitherDehydratedNorValidated`) is ported
Filament-exact — it is the guard both the
[validation collection walk](can-be-validated.md) and the mutation walk skip on.

### Child-schema recursion moves from the component trait up to the schema trait

Filament's component-level `hydrateState` and `fillStateWithNull` recurse into
`getChildSchemas()` to walk the tree. Sylament's leaf variants handle only their
own component (`hydrateState`: the defaults pass plus one cast-set pass keyed by
`appliedStateCastPaths`; `fillStateWithNull`: `state(null)` when the raw slot is
null); the whole-tree walk is driven by the [`Schema`](schema.md)-level state
trait via `getFlatComponents()`. This is why the leaf `hydrateState` signature
reads `(bool $applyDefaults, array &$appliedStateCastPaths, array
&$hydratedDefaultState)` — no `$shouldCallHydrationHooks` and no
`Arrayable`→array normalization, and the defaults flag is an explicit `bool`
where Filament encodes it in the nullability of `$hydratedDefaultState`. The
shared record itself is ported, along with the `hydrateDefaultState` it feeds
([why it has to be](#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default)),
one visibility down: Filament's is public, ours is `protected`, since the walk
that calls it now lives on the component itself.

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

### CanBeRepeated discriminates on HasKeyedItems, not on a forms type

Filament's
[`CanBeRepeated`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeRepeated.php)
hard-codes `Repeater | Builder` in its return type, its `instanceof` test and its
`assert()` — three references from `schemas` up into `forms`, which the package
split forbids. The port discriminates on
[`HasKeyedItems`](../../../packages/schemas/src/Components/Contracts/HasKeyedItems.php)
instead, the contract a repeater-shaped component already implements, and returns
the intersection type `(Component&HasKeyedItems)|null`. Filament's `assert()`
becomes a thrown `LogicException`, since shipped source carries no assertions that
vanish under `zend.assertions=-1`.

`getParentRepeaterItemIndex()` reads the index off `$repeater->getItemKeys()`
rather than `array_keys($item->getParentComponent()->getState())`: the contract
already publishes the item keying, so the index needs no state read at all, and
it stays correct for a component whose state has not been hydrated. The memo
field keeps Filament's three-state `Schema|false|null` shape but is tested with
`!== null` / `instanceof` rather than Filament's `filled()` / `?:`. Both spellings
agree — `filled(false)` is `true` in Laravel's helper and in
[ours](../support/helpers.md), so the `false` sentinel does short-circuit the walk
— but only by an accident of the helper a reader has to reconstruct, and
[`filled()`](../support/helpers.md)'s sole static assertion is a null check, so
the `instanceof` is needed anyway to reach the `?Schema` return at level max.
Saying it directly costs nothing and pins the sentinel's meaning.

The memo is **not** flushed by
[`Cloneable::getClone()`](../../../packages/schemas/src/Components/Concerns/Cloneable.php),
which resets the three cached absolute paths and nothing else — Filament's
`getClone()` flushes it no more than ours does, so a clone inheriting the
original's answer is parity, not an oversight. It also stays out of reach on the
shipped clone path, which copies components before anything has queried them —
[`Repeater`](../forms/repeater.md#every-item-schema-is-keyed-as-well-as-re-pathed)
cloning its *template* child schema per item is the live case, and a template is
never rendered, so nothing in it has walked for a parent repeater item and there
is no memoised answer to carry across. A clone taken after a walk would keep the
original's.

_See also: the global [rename table](../renames.md) — the Livewire → UX
LiveComponent, Blade view → Twig template, `Htmlable` → `Twig\Markup`, and
`data_get`/`Arr::` → PropertyAccessor translations these rows lean on are
systematic idiom translations recorded there, not per-component divergences._
