# BelongsToRecord (schemas)

Two identically-named traits, one per tier — Filament ships both under the same
class name in different namespaces, so Sylament does too:
[`Sylament\Schemas\Concerns\BelongsToRecord`](../../../packages/schemas/src/Concerns/BelongsToRecord.php)
on [`Schema`](schema.md) and
[`Sylament\Schemas\Components\Concerns\BelongsToRecord`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php)
on the base [`Component`](component.md). It ports Filament's `BelongsToModel`
under the record-only vocabulary (see
[the collapse](#the-record-vocabulary-collapses-onto-record) and the
[rename table](../renames.md)): `record()` binds a Doctrine entity, a plain
object, raw record data, or a class-string, and the two Sylament-only mapping
edges — the [`fill()`](#fillobject-reads-only-the-paths-the-schema-declares)
object arm and `getData()` — carry state between that object and the schema's
state bag. The operation half of the same epic lives in
[`has-operation.md`](has-operation.md).

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php)
  — the root-tier binding, the getter chain, and the relationship walks
- [`schemas/src/Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php)
  — the component-tier binding plus the per-component relationship save/load
  members
- [`filament/src/Resources/Pages/EditRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/EditRecord.php)
  — where Filament does the mapping our two edges replace
  (`attributesToArray()` in, `$record->update($data)` out)

## Surface

Every point of both tiers, plus the mapping edges and the closure-injection arms
they feed. _Blocked by_ names what has to land first. _Detail_ carries a
**detail** link (the write-up in [Details](#details), deleted once the row is
**done**), a **semantics** link (a permanent note in [Semantics](#semantics)),
and/or a **diverges** link (the permanent note in
[Divergences](#divergences)), or `—`. A row with no _Ports_ link is
Sylament-only.

### Root tier — `Schema`

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`record()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`model()` + `record()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#the-record-vocabulary-collapses-onto-record) · [semantics](#the-root-tier-memoizes-a-closure-record-the-component-tier-re-evaluates-it) |
| [`getRecordClass()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`getModel()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#the-record-vocabulary-collapses-onto-record) |
| [`getRecord(bool $withParentComponentRecord = true)`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`getRecord()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | — |
| [`getRecordInstance()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`getModelInstance()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#getrecordinstance-constructs-with-new-not-the-container) |
| [`getData()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#reads-are-lenient-writes-are-strict) · [diverges](#mapping-walks-the-record-graph-by-declared-leaf-paths) |
| [`fill(object …)` object arm + `readStateFromRecord()`](../../../packages/schemas/src/Concerns/HasState.php) | — | done | — | [diverges](#fillobject-reads-only-the-paths-the-schema-declares) · [diverges](#mapping-walks-the-record-graph-by-declared-leaf-paths) · [diverges](#fillobject-does-not-bind-the-record) |
| [`$record` property](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`public $model`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#the-root-record-property-is-protected) |
| `saveRelationships()` | [`saveRelationships()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | blocked | Doctrine relationship-state epic | [detail](#the-relationship-layer-is-not-ported) |
| `loadStateFromRelationships()` | [`loadStateFromRelationships()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | blocked | Doctrine relationship-state epic | [detail](#the-relationship-layer-is-not-ported) |
| `fillPartially()` object arm | — | blocked | schemas partial-fill port | [detail](#the-relationship-layer-is-not-ported) |

### Component tier — base `Component`

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`record()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`model()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-component-tier-gains-record) · [semantics](#the-root-tier-memoizes-a-closure-record-the-component-tier-re-evaluates-it) |
| [`getRecordClass()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getModel()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-record-type-probe-is-instanceof-not-a-model-gate) |
| [`getRecord(bool $withContainerRecord = true)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getRecord()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-record-type-probe-is-instanceof-not-a-model-gate) |
| [`getRecordInstance()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getModelInstance()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#getrecordinstance-constructs-with-new-not-the-container) |
| Per-component relationship members (`saveRelationships*`, `loadStateFromRelationships`, `castStateAfterLoadingFromRelationships`, the `*Using` setters, `saveRelationshipsWhenDisabled`/`WhenHidden`) | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | blocked | Doctrine relationship-state epic | [detail](#the-relationship-layer-is-not-ported) |

### Closure injection

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$record` by name, both tiers](../../../packages/schemas/src/Schema.php) | [`Schema` / `Components\Component`](../../../vendor/filament/schemas/src/Schema.php) | done | — | — |
| [Record probe by type, both tiers (`resolveDefaultClosureDependencyForEvaluationByType`)](../../../packages/schemas/src/Components/Component.php) | [`Model::class` / `$record::class` arms](../../../vendor/filament/schemas/src/Components/Component.php) | done | — | [diverges](#the-record-type-probe-is-instanceof-not-a-model-gate) |
| `$model` by name | [`Components\Component`](../../../vendor/filament/schemas/src/Components/Component.php) | skipped | — | [diverges](#the-model-closure-parameter-is-not-ported) |
| Lazy `$state`-snapshot injection | — | skipped | — | [diverges](#closures-receive-the-record-never-a-state-snapshot) |

Coverage: the root tier by
[`Unit/Concerns/BelongsToRecordTest`](../../../packages/schemas/tests/Unit/Concerns/BelongsToRecordTest.php)
(instance / array / class-string / closure payloads, class-string-declares-
without-binding, rebind-replaces, the all-null unbound case, closure
memoization, by-name and by-type injection, and the `getData()` paths — onto a
bound instance, onto a fresh one built from the declared class,
`dehydrated(false)` exclusion, the strict-write `NoSuchPropertyException`, the
unbound `LogicException`, and the nested set: a nested graph mapped in place, a
fresh instance with an initialized intermediate, a non-dehydrated nested leaf
excluded, a present-but-null nested leaf written, an array-backed branch keeping
its unmanaged keys, and the missing-intermediate `LogicException` naming the
path); the component tier by
[`Unit/Components/Concerns/BelongsToRecordTest`](../../../packages/schemas/tests/Unit/Components/Concerns/BelongsToRecordTest.php)
(container fallback, own-record precedence, `withContainerRecord: false`,
per-call re-evaluation, the detached-component all-null case and its still-working
typed-closure evaluation, record injection, and `Get`/`Set` by type under
non-mapped parameter names, which is what proves the by-type arm rather than the
pre-existing by-name one); the fill walk by
[`Unit/Concerns/HasStateTest`](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php)
(scoped read, null-fill of what the record cannot supply, uninitialized-property
and object-value skips, array reads, the unchanged null/array arms, and the
nested reads: a nested object path filled through the graph, null filled past a
null intermediate, a leaf decorated with a path-less child schema staying a
leaf, and an array-backed branch read by declared leaves only); and the
round trip through a real live host by
[`Integration/Concerns/BelongsToRecordTest`](../../../packages/schemas/tests/Integration/Concerns/BelongsToRecordTest.php)
(`fill($object)` → edited bag → `getData()` returns the same mutated instance;
a failing validation leaves the record untouched; and the same pair over a
nested `Post`/`Author` graph — the round trip mutating the associated object in
place, a failing nested submit leaving it untouched).

## Details

### The relationship layer is not ported

Everything in Filament's trait beyond binding and lookup is relationship
machinery: the root walks (`saveRelationships()`,
`loadStateFromRelationships()`) and the per-component members they drive
(`saveRelationshipsUsing`/`saveRelationshipsBeforeChildrenUsing`/
`loadStateFromRelationshipsUsing`, `saveRelationshipsWhenDisabled`/`WhenHidden`,
`castStateAfterLoadingFromRelationships`). Every one of them is Eloquent-shaped
— the guards read `$this->getRecord()?->exists`, and the callbacks save
`HasMany`/`BelongsToMany` sets. **Blocked** on the Doctrine relationship-state
epic, which owns both the `exists` equivalent (an identity-map / `UnitOfWork`
probe) and the association write side; it is the same prerequisite that blocks
`CanGetStateFromRelationships`, `EntanglesStateWithSingularRelationship`, and
constant state ([schema.md](schema.md#hasstate-still-lacks-constant-state-relationships-and-partial-rendering)).

`fillPartially()`'s object arm is listed separately because it waits on a
different prerequisite: `fillPartially()`/`hydrateStatePartially()` are not
ported at all, blocked on a UX LiveComponent partial-render equivalent. The
object arm is a one-line addition to whichever epic lands them.

**Next step:** land the relationship members with the Doctrine
relationship-state epic (Filament's T6), and the partial-fill arm with the
partial-render decision.

## Semantics

### The root tier memoizes a closure record; the component tier re-evaluates it

Both tiers accept a closure and both run `evaluate()` on entry to every getter,
but they store the result differently, and this is Filament-exact rather than an
oversight: the root tier assigns back
(`$this->record = $this->evaluate($this->record)`), so the closure runs **once**
and the schema is bound to whatever it returned; the component tier reads into a
local, so the closure runs on **every** call and a component-level record can
change between two reads in the same request. Both are pinned by a
call-counting test per tier. The consequence worth knowing: a root-tier
`record(fn () => $repository->find($id))` is a lazy binding, not a live one —
after the first getter it can never see a different record.

## Divergences

### The `dataClass()` concept was rejected in favour of porting the record binding

The typed boundary between a schema and the object behind it was carried as an
open question, leaning toward a Sylament-only `dataClass()` deliberately
distinct from Filament's record semantics. It resolves the other way. Because
Doctrine entities are POPOs, the ported binding *subsumes* the idea for free —
an "opt-in declared data class" is exactly `record(SomeDto::class)` or
`record($someDto)`, and any object binds — so the distinct concept would have
carried no distinct behaviour. It would also have left every
`BelongsToModel`-triggered divergence in this tree permanently dangling
(schema.md closure injection; component.md's by-name subset, by-type resolution
and `getModelRootContainer()`; the `*On()` rows in
[can-be-hidden.md](can-be-hidden.md) / [can-be-disabled.md](can-be-disabled.md);
the record/operation notes in [action.md](../actions/action.md) and
[has-actions.md](../actions/has-actions.md)), and M2's Resource pages need this
surface anyway — they bind a record, set an operation, and set a state path
exactly as Filament's do. The "record semantics" Filament attaches beyond
binding — relationships, `exists` probes, `unique`/`exists` rules — is the
unported remainder tracked above, not a reason for a parallel vocabulary.

### The record vocabulary collapses onto `record()`

Filament exposes one property under two names: root-tier `model()` with a
`record()` alias that accepts a narrower payload (no class-string), and a
component tier that ships `model()` and no `record()` at all. Sylament ships
`record()` only, on both tiers, widened to take the full payload set — instance,
raw array, class-string (declares the class without binding an instance),
closure — and renames the getters to match (`getModel()` → `getRecordClass()`,
`getModelInstance()` → `getRecordInstance()`; `getRecord()` keeps its name).
Three reasons, judged against the [rename criterion](../renames.md):

- **Filament is internally inconsistent here.** The rest of it — panels
  (`EditRecord`, `getRecord()`), tables, actions — speaks "record"; only these
  two traits speak "model". We collapse onto Filament's own more prevalent name.
- **"Model" names an Eloquent concept that does not exist here.** Entities are
  POPOs; the concept-name leaves with the ecosystem, like rule → constraint in
  M1 · 1 — where the loud, searchable break was judged better than a kept name
  over changed semantics.
- **It removes a real API trap.** With two setters over one last-write-wins
  property, `->record($article)->model(Article::class)` reads as additive but
  silently unbinds the instance — and an unbound instance on an edit form means
  every save inserts a new row. One name, one call:
  `record($article ?? Article::class)`.

It also converges with the codebase: actions' `InteractsWithRecord` already
ships record-only, and schemas' `BelongsTo*` family (`BelongsToContainer`,
`BelongsToLiveComponent`, `BelongsToParentComponent`) absorbs `BelongsToRecord`
naturally. **Migration cost, stated:** Filament code calling `->model(...)` or
type-hinting `fn ($model)` breaks loudly — a missing method or an unresolvable
closure parameter, never a silent behaviour change. The rename set is recorded
in the [rename table](../renames.md).

### `fill($object)` reads only the paths the schema declares

Filament's `EditRecord` fills from `$record->attributesToArray()` — every
non-`$hidden` attribute of the row, pushed into the browser's state bag — and
offers `mutateFormDataBeforeFill()` to strip what should not have gone.
`readStateFromRecord()` inverts that: it walks the schema's own
`getFlatComponents(withActions: false)` and reads only the paths those
components declare, so an attribute no field names is never read and never
reaches the client. That is a privacy improvement, not just a scoping choice —
the mutate hooks exist in Filament because the default is over-broad. It also
means the object arm is *precisely* "`attributesToArray()`, scoped to the
schema's fields": having built the array it runs the existing array arm
unchanged, so state casts, `afterStateHydrated`, and the null-fill pass behave
identically to a hand-written `fill([...])`. The page-level
`mutateFormDataBeforeFill()`/`beforeFill`/`afterFill` hooks arrive with the M2
Resource pages, as page surface rather than schema surface.

### Reads are lenient, writes are strict

The fill read-walk wraps each accessor read in one `catch (AccessException)` and
skips the path. Symfony's `AccessException` is the common parent of
`NoSuchPropertyException` (no accessor at all) and `UninitializedPropertyException`
(a typed property never assigned), so the single catch covers both without an
`isReadable()` pre-check, which would only be a second copy of the same walk. It
additionally skips object-valued reads — only scalars, arrays, and null enter the
bag until a state-cast epic widens it. So a read has exactly two skip reasons:
the object cannot supply the value, or the value is not bag-representable, and
the null-fill pass then supplies the key. A null or scalar *intermediate*
supplies null for the leaf rather than skipping it — the same bag either way,
since the null-fill pass would supply the key.

`getData()` is the opposite. It writes **every** leaf path of the validated
state and lets `NoSuchPropertyException` surface when the target cannot take one:
by calling it you have declared that this state is the object's data, and
silently dropping a key would silently drop a user's edit. A missing
intermediate *object* is the one softened error: `setOnRecord()` wraps it in a
`LogicException` naming the path, because Symfony's own message explains
nothing at this boundary. The escape hatch for a page-only field is Filament's
own idiom — `dehydrated(false)` removes it from `getState()` and therefore from
the mapping. Ordering matters and is deliberate:
`getData()` runs the validating `getState()` first, so a failing submit throws
[`ValidationException`](can-be-validated.md) before the record is touched at all.

### Mapping walks the record graph by declared leaf paths

Both mapping edges iterate one walk, `getRecordPathMap()`: every path-bearing
leaf component's record-relative path (the schema's root `statePath` prefix
stripped), with containers — components nesting a path-bearing component, at
any depth and through any child-schema key — contributing their leaves and
never themselves, so a leaf decorated with a path-less child schema
(`helperText()`, `belowContent()`) stays a leaf. `fill($record)` reads each leaf
through `PropertyAccessor::getFromRecord()` and `getData()` writes each through
`setOnRecord()`
([property-accessor.md](property-accessor.md#the-record-pair-completes-the-data_getdata_set-port-for-object-graphs)):
object segments hop by property, array segments by key, so
`Section::make('author')->statePath('author')` over a nested plain object and
a section over an array-valued property (the JSON-column shape) both
round-trip. Three consequences are deliberate:

- **Leaf granularity is the contract in both directions.** An array-backed
  branch is read leaf-by-leaf — an unmanaged sibling key never enters the bag
  or reaches the client (previously the container row's whole-array read
  leaked it, contradicting the read-scoping stance above) — and written
  read-modify-write, so unmanaged keys survive `getData()` where the old
  wholesale top-level write destroyed them. Filament wholesale-replaces here;
  we diverge toward our own scoping rule: the schema maps what it declares,
  nothing else.
- **Intermediate objects are never constructed.** A null or uninitialized
  intermediate under a nested object path throws `LogicException` from
  `getData()`, naming the path and the fix (initialize the object —
  constructor or lazy getter). Vivification was rejected: type-directed
  construction is right for a value object and silently wrong for an
  association that should be looked up, and that asymmetry is the relationship
  epic's problem, not the accessor's.
- **Scope: plain nested objects and embeddable-shaped value objects.** The
  walk will mechanically traverse a loaded Doctrine association and mutate the
  associated entity in place — almost never what a form edit means, and
  undetectable here (`schemas` has no EntityManager). Associations remain the
  relationship epic's surface; this note is the warning, not a guard.

One known edge: the container test is `hasPathBearingDescendants()`, correct
for every current component and wrong the day a component is both leaf and
container (repeaters) — revisit the predicate with that epic.

### A Serializer-based mapper stays rejected, on amended grounds

The originally recorded reason — a denormalizer constructs where edit must
mutate the managed entity — is answered by Serializer's own
`AbstractNormalizer::OBJECT_TO_POPULATE`, so it no longer carries the
rejection. What does: `deep_object_to_populate` updates singular children but
**not arrays of objects**, exactly the collection shape the relationship epic
needs — fresh instances handed to Doctrine surface as duplicate INSERTs at
flush, not as mapping errors; the type graph rather than the schema would
decide what maps, inverting authority at precisely the unbuilt surface
(`dehydrated(false)`, multi-value fields, associations) where we want "not
supported" to fail loudly; and `normalize($record)` is over-broad against the
read-scoping stance and initializes Doctrine proxies wholesale. Serializer
remains a candidate for leaf-level *casts* (dates, enums, UUIDs), where its
normalizer half fits the existing `StateCast` seam.

### `getRecordInstance()` constructs with `new`, not the container

Filament's `getModelInstance()` ends in `app($this->model)` — Laravel's
container resolving an Eloquent model class. Sylament writes `new $class()`.
Symfony's container cannot make an unbound class, and an entity is not a
service; a constructor that requires arguments therefore fails fast with PHP's
own missing-argument error rather than being autowired. This is the same
boundary as
[`Component::newInstance()`](../support/component.md)'s fallback: the container
builds what was registered, `new` covers the rest.

### The record type probe is `instanceof`, not a `Model` gate

Filament resolves a closure parameter by type through two arms — `Model::class`
and `$record::class` — after gating on `is_a($type, Model::class)`, so an
arbitrary class-typed parameter never touches the record. There is no `Model`
base class here, so both tiers instead probe
`$record instanceof $parameterType`, which covers the exact class, any parent,
any interface, and a Doctrine proxy. The safety the gate provided is restored
differently: the component tier's container fallbacks are each
`isset($this->container)`-guarded (the
[`getLiveComponentOrNull()` idiom](belongs-to-live-component.md#getlivecomponent-throws-instead-of-returning-a-possibly-null-component)),
because `$container` is an uninitialized typed property until a schema stamps
it — so `evaluate()` on a detached component still resolves its own class rather
than fataling on the back-reference. The component tier resolves its exact
`Get::class`/`Set::class` arms before probing the record; the root tier's
override is the probe alone, since its parent-component chain is already
nullable.

### The root `$record` property is protected

Filament declares the root-tier property `public Model | array | string | Closure
| null $model` while the component tier's is `protected` — the asymmetry is a
Livewire artifact (a public property is what Livewire's serializer can see), not
an API decision. Sylament declares both `protected`, translating the artifact
away like the other Livewire idioms. Nothing reads the property from outside;
`getRecord()`/`getRecordClass()`/`getRecordInstance()` are the surface.

### The component tier gains `record()`

Filament's component tier has `model()` and no `record()`, so a component-level
binding can only be written in the vocabulary the rest of Filament abandoned.
With the [collapse](#the-record-vocabulary-collapses-onto-record) both tiers
carry the same setter name and the same payload set, which is what makes
`Component::record(...)` overriding a container binding a one-line, symmetric
thing to read. `record()` on a component is otherwise Filament's behaviour
exactly, container fallback included.

### The `$model` closure parameter is not ported

Filament injects `$model` (the class-string) alongside `$record` by name.
Dropping the name is part of the rename set: `$record` covers the instance and
`getRecordClass()` covers the class, so keeping `$model` would reintroduce
exactly the vocabulary the collapse removed — for a parameter that is one method
call away. Revisit only if a ported surface needs it as a parameter rather than
a call.

### Closures receive the record, never a state snapshot

An alternative was carried in the design discussion: have a type-hinted closure
parameter receive a lazily built, one-way snapshot object of the current state
instead of the bound record. It is rejected. Filament's `$record` is the
persisted record — deliberately stale until save — and live values already have
`$get` and `$state`; a snapshot would silently change the meaning of every
Filament example a migrating user pastes in. It stays additive if it is ever
wanted.

### `fill($object)` does not bind the record

Passing an object to `fill()` sources initial state from it and nothing more —
it does not call `record()`. Binding is a schema-definition concern (`record()`
alongside `operation()` and `statePath()`, rebuilt on every request), while
`fill()` is a one-shot mount-time action; conflating them would mean a host that
fills on mount silently loses its binding on the next request, when `fill()` is
not called again. So an edit page writes both: `record($entity)` in the schema
definition and `fill($entity)` in `mount()`.

_See also: the global [rename table](../renames.md) — the `BelongsToModel` →
`BelongsToRecord`, `model()` → `record()`, `getModel()` → `getRecordClass()`,
and `getModelInstance()` → `getRecordInstance()` entries are recorded there,
with the criterion they were judged against._
