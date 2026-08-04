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

One further Sylament-only trait is tracked here rather than in a file of its own,
because `getData()` is its only consumer:
[`Components\Concerns\HasNullableBranch`](../../../packages/schemas/src/Components/Concerns/HasNullableBranch.php)
— `nullWhenBlank()`, composed onto the base `Component` alongside `HasColumns` so
any container can declare itself an optional branch of the record graph. The
write-side type lookup all of this now rests on has its own file,
[`record-type-reader.md`](record-type-reader.md).

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
| [`getData()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#reads-are-lenient-writes-are-strict) · [diverges](#mapping-walks-the-record-graph-by-declared-leaf-paths) · [diverges](#a-missing-singular-intermediate-is-constructed-from-its-declared-type) |
| [`getNullableBranches()` + the branch pass in `getData()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) · [semantics](#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled) · [semantics](#a-branch-the-record-cannot-null-is-refused-loudly) · [semantics](#a-nullable-branch-with-no-state-path-is-refused-not-ignored) · [semantics](#branches-are-ordered-by-path-depth-because-tree-order-is-not-path-order) |
| [`fill(object …)` object arm](../../../packages/schemas/src/Concerns/HasState.php) + [`readStateFromRecord()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#fillobject-reads-only-the-paths-the-schema-declares) · [diverges](#mapping-walks-the-record-graph-by-declared-leaf-paths) · [diverges](#fillobject-does-not-bind-the-record) |
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
| [`getRecordStatePath()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it) |
| [`nullWhenBlank()` / `isNullableBranch()` / `shouldNullBranch()` / `isBlankStateValue()`](../../../packages/schemas/src/Components/Concerns/HasNullableBranch.php) | — | done | — | [diverges](#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) · [semantics](#the-built-in-blank-test-asks-the-branchs-own-leaves) · [semantics](#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled) · [semantics](#a-branch-the-record-cannot-null-is-refused-loudly) · [diverges](#a-nullwhenblank-closure-sees-two-different-bags) · [diverges](#a-doctrine-embeddable-cannot-be-nulled) |
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
unbound `LogicException`, the nested set: a nested graph mapped in place, a
fresh instance with an initialized intermediate, a non-dehydrated nested leaf
excluded, a present-but-null nested leaf written, an array-backed branch keeping
its unmanaged keys, a missing nested object constructed, and a missing branch
constructed with its leaves written; and the branch pass: a declared branch nulled
when every leaf is blank, kept when one is filled, an undeclared branch still
constructed while blank, a nulled branch skipping only the paths beneath it and
not a sibling sharing its character prefix, a nested branch short-circuited
beneath a nulled outer one, and the refusals — a hidden branch and a branch whose
only leaf is hidden both left alone rather than nulled, a `nullWhenBlank()` leaf
writing nothing because a leaf declares no branch, a non-nullable branch target
throwing, and a path-less declaration throwing); the branch declaration itself by
[`Unit/Components/Concerns/HasNullableBranchTest`](../../../packages/schemas/tests/Unit/Components/Concerns/HasNullableBranchTest.php)
(the default, the declaration, blank and filled leaves, a nested sub-branch on
both sides of blank, the closure arm receiving the branch state, and the explicit
`false` — all of those on a detached component, so they also pin the fallback
arm; plus two wired trees for the declared-leaves rule: a branch judged blank
while an unmanaged key beside it is filled, and a branch kept alive through a
leaf two containers down); the field-side half of the same rule by forms'
[`CanBeValidatedTest`](../../../packages/forms/tests/Integration/Components/Concerns/CanBeValidatedTest.php)
(requirement suppressed while a branch's only non-blank value is an untouched
checkbox, resumed once it is ticked, and `getData()` nulling that branch) and by
[`CheckboxTest`](../../../packages/forms/tests/Integration/Components/CheckboxTest.php)
/ [`ToggleTest`](../../../packages/forms/tests/Integration/Components/ToggleTest.php)
(`isBlankStateValue()` over `false` / `true` / `null`);
`getRecordStatePath()` by the component-tier suite (no path, a path-less
component under a root state path, the prefix stripped, no root prefix to strip,
and a memoized absolute path that no longer sits under the prefix); the component
tier by
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

### The built-in blank test asks the branch's own leaves

`shouldNullBranch()` without a closure does **not** scan the values in the
branch's slice. It walks the branch's child schemas, and for every declared leaf
beneath it — path-bearing, and not itself a container — reads that leaf's value
out of the slice and asks the leaf whether it is blank, through
`Component::isBlankStateValue()`. Three consequences, all deliberate:

1. **Unmanaged keys in the slice no longer influence the verdict.** An
   array-backed branch keeping a sibling key the schema never declared
   ([leaf granularity](#mapping-walks-the-record-graph-by-declared-leaf-paths))
   is judged on the declared leaves alone — the same "the schema maps what it
   declares" rule the rest of both walks follows.
2. **A leaf absent from the slice is blank, and depth still counts.** The walk is
   flat over `getFlatComponents()`, so it reaches leaves through any number of
   intermediate containers and a filled leaf three levels down still keeps the
   branch alive; a leaf whose key the slice does not carry simply contributes
   nothing.
3. **A detached or path-less component falls back to the old value-recursive
   test**, so `shouldNullBranch()` stays total. Neither real walk can reach that
   arm — the mapper
   [refuses a path-less branch outright](#a-nullable-branch-with-no-state-path-is-refused-not-ignored)
   and forms' requirement walk is gated the same way — it exists so a component
   built with `new` in a unit test, with no container to enumerate leaves
   through, still answers.

The trap that forced the change is a boolean. `filled(false)` is `true`, and
`Checkbox`/`Toggle` cast with `BooleanStateCast(isNullable: false)`, so an
untouched box dehydrates `false` rather than null. Under the old value scan that
made **any** boolean field inside a `nullWhenBlank()` branch permanently
non-blank: derived requirement never suppressed, so the branch's non-nullable
leaves stayed required and a blank branch could not be submitted, and had it been
submitted the branch could never have nulled. Asking the leaf instead puts the
answer where the state casts are —
[`Checkbox`](../forms/checkbox.md) and [`Toggle`](../forms/toggle.md) override
`isBlankStateValue()` to read their resting `false` as blank, the same field-side
placement that already carries
[their derivation opt-out](../forms/can-be-validated.md#a-boolean-field-opts-out-of-derivation-but-selectboolean-does-not).

The leaf-enumerating predicate the walk needs,
`hasPathBearingDescendants()`, moved from a private helper on the root tier onto
`Component` (via `HasChildComponents`) so both walks share one definition, the
same convergence
[`getRecordStatePath()`](#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it)
argues for.

### A branch the bag no longer carries is skipped, not nulled

The branch pass judges a branch only when the dehydrated bag still holds **at
least one of the leaf paths the schema declares beneath it**. If it holds none,
the pass writes nothing at all.

This is the guard that stops branch-nulling from being a data-loss bug, and the
reason is that "hidden" and "blank" arrive at the pass looking identical.
[The dehydration walk](component.md) *forgets* a non-dehydrated component's whole
subtree — `isDehydrated()` ends in `! isHiddenAndNotDehydratedWhenHidden()` — so a
hidden branch leaves no trace in the bag. Read that absence with
`getFromArray()`, coerce the non-array to `[]`, and `shouldNullBranch([])`
answers `true`, correctly, because an empty bag holds nothing filled. The pass
had asked the wrong question. Measured, with a `Post` holding a real
`Author('Ada')` and a *filled* author in state:

| schema | before | now |
| --- | --- | --- |
| `->statePath('author')->hidden()`, undeclared | survives | survives |
| `->statePath('author')->nullWhenBlank()->hidden()` | **NULL — data loss** | survives |
| `->statePath('author')->nullWhenBlank()` with a `->hidden()` leaf | **NULL — data loss** | survives |

The realistic trigger is not exotic:
`Section::make('Author')->statePath('author')->nullWhenBlank()->hiddenOn(Operation::Edit)`
deleted an article's author every time someone edited its title, with no
exception and no validation error.

**A `hasInArray()` check on the branch path itself is not enough**, and this file
proposed exactly that before it was tested.
[`forgetFromArray()`](property-accessor.md#the-detached-array-trio-ports-laravels-arr-helpers-because-propertyaccess-is-host-only-and-has-no-key-removal)
unsets only the leaf key and deliberately leaves emptied parents behind, so
pruning a branch's only leaf leaves `author => []` in the bag and
`hasInArray($state, 'author')` still answers `true`. Swapping the leaf-path
predicate for that one-liner fails the hidden-leaf case and nothing else — run as
an experiment, not assumed.

The same predicate settles two cases that were filed separately. A component with
no declared leaf beneath it is a **leaf, not a branch** — `nullWhenBlank()` on a
plain field now writes nothing, where before the pass nulled it every time and
was saved only by the leaf walk overwriting the null a moment later. That rescue
did not apply to a `dehydrated(false)` leaf, where the null reached the record and
contradicted `getData()`'s own docblock. Both are gone.

The residual, stated so it is not mistaken for an oversight: a branch with *some*
leaves pruned and some present is still judged on the leaves that remain. That is
[the two-bag hazard](#a-nullwhenblank-closure-sees-two-different-bags), not this
guard's business.

### A branch the record cannot null is refused loudly

`nullWhenBlank()` on a property the record declares non-nullable — `private Author
$author` rather than `?Author` — is a declaration that can never be honoured. It
used to reach `setOnRecord($record, 'author', null)` unguarded and surface as
Symfony's `InvalidTypeException`: a 500 whose message names neither
`nullWhenBlank()` nor the fix, from a mapper whose
[documented contract](#reads-are-lenient-writes-are-strict) is that its failures
spell the fix out. The pass now asks
[`acceptsNullOnRecord()`](property-accessor.md#the-nullability-probe-lives-here-because-the-type-reader-does)
and throws a `LogicException` naming the path, the record and the declaration.

Two placement decisions inside that, both deliberate:

- **Before the branch condition, not after.** A non-nullable branch throws
  whether or not the user left it blank, so the failure is deterministic and
  surfaces on the first save in development rather than on the first *empty* save
  in production.
- **After the presence guard, not before.** A branch the bag does not carry is
  not part of this submission at all, so a `hiddenOn(Operation::Edit)` branch over
  a non-nullable property does not throw on every edit.

The alternative considered and rejected was to keep the mapper quiet and instead
refuse the [derived-requirement suppression](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types)
for a non-nullable branch, so the user meets a field-level 422 instead. It does
not actually close the hole: suppression only produces a 422 when some leaf
beneath the branch derives a requirement, and a branch whose leaves are all
themselves nullable stays blank, stays valid, and reaches the same crash. It also
aims a message at the *end user* for a mistake only the *developer* can fix,
leaving `nullWhenBlank()` a silent no-op on that property — the same failure mode
as the path-less case below.

### A nullable branch with no state path is refused, not ignored

`getNullableBranches()` keys branches by
[`getRecordStatePath()`](#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it),
which is null for a component carrying no `statePath()` of its own. Such a
component names no property, so it cannot be a branch — but dropping it silently
meant that

```php
Section::make('Author')->nullWhenBlank()->components([
    TextInput::make('author.name'),   // the path is on the field, not the section
])
```

— a natural way to write "this author block is optional" — did nothing at all,
with no feedback. It now throws, naming the component class and the two ways out
(give it the `statePath()` of the branch it wraps, or drop the declaration).

Forms' validation walk had no such filter, which is how one field's requirement
came to depend on unrelated fields elsewhere: `getRawState()` on a path-less
component [falls through to the container's path](component.md), so
`shouldNullBranch()` was handed the **whole form's** raw bag and suppressed
requirement whenever *any* of it was blank. That walk is now gated the same way,
so both agree on what a branch is —
[the forms side](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types).

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

### Branches are ordered by path depth because tree order is not path order

`getNullableBranches()` ends in a `uksort()` by dot count, so the branch pass runs
outermost-first and an outer branch that nulls can short-circuit every inner one
beneath it. **This sort is load-bearing. Do not delete it as redundant.**

The tempting argument for deleting it is that `getFlatComponents()` already walks
the tree depth-first, so a parent is always visited before its children and
ancestors already come first. That argument is false, and it was made once — the
sort's own author offered it, and it did not survive review. Tree nesting and path
nesting are independent: a component's record path comes from `statePath()`, not
from where it sits in the component tree, so two **siblings** can nest by path.

```php
Section::make()->statePath('meta.seo')->nullWhenBlank(),   // declared first
Section::make()->statePath('meta')->nullWhenBlank(),       // its path-ancestor
```

Pre-order traversal reaches `meta.seo` first. Without the sort the pass evaluates
the descendant before the ancestor, so nulling `meta` afterwards writes over a
decision already taken about a path underneath it, and the `$nulledPrefixes`
short-circuit — which only skips branches it has *already* seen a prefix for —
cannot save it. Sorting by depth restores the invariant the short-circuit assumes.

One latent type detail rode along with it, now closed: PHP coerces a
numeric-string array key to `int`, so a schema using `statePath('0')` would have
`TypeError`d under `strict_types=1` — in the sort, whose callback hinted
`(string $a, string $b)`, and equally at `setOnRecord()`, which takes a `string`
path. The hints are `int|string`, and `getData()` normalises both walks' keys
with `strval()` once rather than casting at each of the six use sites.

### `getRecordStatePath()` is the one definition of record-relative, and both walks must share it

`getRecordPathMap()` and `getNullableBranches()` both key by a component's
record-relative path, and `getData()` then compares them with `str_starts_with()`
to decide which leaves sit beneath a nulled branch — and, since the branch pass
gained its [presence guard](#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled),
to decide whether a branch is in the bag at all. That comparison is only
meaningful if both sides computed the prefix the same way — two definitions of
"record-relative" would not error, they would silently skip the wrong leaves. So
`getRecordPathMap()` was converged onto `getRecordStatePath()` rather than keeping
its own inline prefix strip; that convergence is mandatory, not a tidy-up.

A **third** walk shares the definition and is easy to forget: forms' derived
requirement climbs the ancestor chain asking the same question at validation
time. It is gated on `getRecordStatePath() !== null` for exactly the reason this
section exists —
[a walk that answered differently made one field's requirement depend on another](#a-nullable-branch-with-no-state-path-is-refused-not-ignored).

Two guards inside it look redundant and are not. `hasStatePath()` is checked
first because `getStatePath()` on a path-less component under a root
`statePath('data')` returns `'data'` — the root's own path, verbatim — which is
non-blank and would make that component look like a branch declared at the record
root, so `getData()` would write `setOnRecord($record, 'data', null)`. The
`filled($path)` check below it then still earns its place, because `hasStatePath()`
reads the **raw** path while `getStatePath()` also strips control characters, so a
path of nothing but a control character passes the first guard and comes out empty
from the second.

The final "path does not sit under the prefix" fallback is structurally
unreachable by ordinary construction — absolute paths are built from the container
chain, so a component's path is always under its root's — and is reachable only
through a memoized absolute path that predates a later `statePath()` change. Its
test constructs that premise explicitly rather than implying the branch is
ordinarily reachable; the sibling `getRecordPathMap()` carried the identical
fallback before the convergence.

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
- **A missing singular intermediate is constructed; an object collection is
  refused.** A null or uninitialized intermediate whose declared type names a
  constructible class is built and assigned, so a nested object path needs no
  hand-written vivifying getter. This
  [overturns a recorded decision](#a-missing-singular-intermediate-is-constructed-from-its-declared-type),
  which is where the grounds are. What still throws: a declared class needing
  constructor arguments, an object collection at any segment, and an array
  remainder a typed property rejects.
- **Scope: plain nested objects and embeddable-shaped value objects.** The
  walk will mechanically traverse a loaded Doctrine association and mutate the
  associated entity in place — almost never what a form edit means, and
  undetectable here (`schemas` has no EntityManager). Associations remain the
  relationship epic's surface; this note is the warning, not a guard.

One known edge: the container test is `hasPathBearingDescendants()`, correct
for every current component and wrong the day a component is both leaf and
container (repeaters) — revisit the predicate with that epic.

### A missing singular intermediate is constructed from its declared type

**This overturns a decision recorded in this file.** It previously read
*"Intermediate objects are never constructed"*, on the grounds that type-directed
construction is right for a value object and silently wrong for an association
that should be looked up. That rejection no longer holds for **singular**
branches, on three grounds — the third is the one that changed:

- **The dangerous case is excluded elsewhere now.** The shape where a wrong
  construction becomes duplicate INSERTs is a *collection*, and a collection is
  [refused outright](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused)
  before it can reach the mapper. The original argument rejected construction to
  avoid a case the collection refusal already removes.
- **Doctrine itself vivifies here.** An `#[ORM\Embedded]` write goes through
  `ReflectionEmbeddedProperty::setValue()`, which instantiates the embeddable
  through `Instantiator` when the property is null. Requiring a user to
  hand-write the vivification Doctrine performs for itself is the divergence;
  performing it is the parity.
- **The type is declared, not guessed.** The old reasoning was written when the
  mapper had no type information and would have had to infer a class from the
  path. With [`RecordTypeReader`](record-type-reader.md) the class comes from the
  property's own write-side declaration, gated on instantiability, so
  "construct what the record says goes here" is a reading of the record rather
  than an inference about it. A singular association that should be *looked up*
  is a field concern — a `Select` over related records — not a nested branch, and
  that surface belongs to the deferred relationship epic either way.

Construction needs no declaration of its own: `record(SomeClass::class)` plus
nested state paths is the entire user-facing flow, and the demo's vivifying
`Article::getAuthor(): AuthorProfile` workaround — the one that produced an empty
`AuthorProfile` on every save and started this epic — was deleted rather than
replaced. What remains loud: a declared class whose constructor takes required
arguments throws rather than being half-built, and Doctrine's constructor-bypass
trick is deliberately not copied (`doctrine/instantiator` is not in the graph, and
a bypassed constructor leaves typed properties uninitialized — one crash traded
for a later, stranger one).

### A declared branch is nulled whole before its leaves are written

`nullWhenBlank()` marks a container as an optional branch of the record graph.
`getData()` collects every declared branch before the leaf walk, evaluates each
against its own slice of the dehydrated state, and for one that should not exist
writes `null` at the **branch** path and then skips every leaf beneath it — so a
discarded branch is never rebuilt by one of its own leaves. `true` uses the
built-in test — every leaf the schema declares beneath the branch reports its own
value blank, at any depth, so a filled leaf three levels down keeps the whole
branch alive
([the leaves answer, not the values](#the-built-in-blank-test-asks-the-branchs-own-leaves));
a closure receives that slice as `$state` and decides for itself.

There is no Filament counterpart and the near neighbour has opposite polarity —
`relationship(condition:)` says *save when true* and ships no blank test, so a
Filament user writes the emptiness check by hand every time. The name is recorded
as a Sylament invention rather than a rename in the
[rename table](../renames.md#names-with-no-filament-source).

Two consequences worth stating. **An undeclared branch is not nulled**, it is
constructed: leave `nullWhenBlank()` off and a blank nested object still gets
built, then fails validation on its first non-nullable leaf — which is the
behaviour that makes the declaration opt-in rather than a default. And **nulling
does not relax validation**: an explicitly `required()` leaf inside a blank
nullable branch still fails, because branch-nulling runs after `getState()`. That
is not an oversight — the seam that lets a blank optional branch submit is
[derived requirement](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types),
which suppresses itself under a blank nullable branch; an explicit declaration
staying strict is the correct half of that pair.

### A `nullWhenBlank()` closure sees two different bags

The blank test runs at two different moments against two differently-shaped bags,
and a closure written for one can be wrong about the other.

- **At mapping**, `getData()` passes the branch's slice of the **dehydrated**
  state — state casts applied, `dehydrated(false)` leaves pruned out.
- **At validation**, forms' derived requirement asks the same question through
  `shouldNullBranch()` to decide whether to suppress a leaf's requirement, and
  reads the **raw** live state off the host instead, because requirement is
  decided before any dehydrated bag is assembled.

So `nullWhenBlank(fn (array $state) => ...)` is invoked twice per submit with two
shapes, and the observable consequence is sharper than the shape difference: a
branch holding a filled leaf the dehydration walk prunes reads **non-blank** at
validation — so the branch's other leaves are required and the user is made to
fill them — and then reads **blank** at mapping, so the whole branch, including
the value the user was forced to supply, is discarded.

The [presence guard](#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled)
narrowed that to a **mixed** branch. A branch whose leaves are *all* pruned is now
skipped outright rather than nulled, so the discard needs at least one surviving
leaf beside the pruned one. It did not remove the hazard, and the guard is not
the fix — the two walks still read two bags.

Not fixed, and the reason is cost rather than doubt: making validation read
dehydrated state would mean dehydrating a branch per field per request, on the
hot path, to serve a combination (a pruned leaf inside a `nullWhenBlank()` branch)
that no shipped schema uses. The practical rule until then — **do not put a
pruned leaf inside a nullable branch**, where pruned covers a hidden leaf
(`hidden()`, `hiddenOn(...)`, `visible(false)`) as much as a `dehydrated(false)`
one, and the hidden shape is the likelier of the two to be written by accident —
and write closure conditions against key *presence* and blankness rather than
against an exact bag shape. Reverses if the cost model changes or a real schema
needs the combination.

### A Doctrine embeddable cannot be nulled

Branch-nulling round-trips for a plain property, a DTO, and a real `OneToOne`
association. It does **not** round-trip for an `#[ORM\Embedded]`, and this is
Doctrine's constraint rather than the mapper's: an embeddable write goes through
`ReflectionEmbeddedProperty::setValue()`, which vivifies, and a read returns null
only when the whole parent property is null. Set the branch to null, flush, reload
— and Doctrine hydrates the columns straight back into an empty object. The
mapper's `null` is written and accepted; it simply does not survive the
round trip.

Documented rather than worked around. Detecting it would mean reading Doctrine
mapping metadata, which `schemas` deliberately cannot do (it has no
`EntityManager`, the same boundary that leaves association traversal to the
relationship epic), and faking it — writing a fresh empty embeddable instead of
null — would only rename the same outcome. A branch that genuinely has to be
absent wants a nullable `OneToOne` or a plain nullable property, not an
embeddable.

### A Serializer-based mapper stays rejected, on amended grounds

Amended twice now, and each round has *removed* an objection rather than added
one — so read this as one surviving reason, not a pile.

**Answered, and no longer carrying the rejection.** The original reason — a
denormalizer constructs where an edit must mutate the managed entity — is
answered by Serializer's own `AbstractNormalizer::OBJECT_TO_POPULATE`. The second
— that the type graph rather than the schema would decide what maps, inverting
authority at exactly the unbuilt surface (`dehydrated(false)`, multi-value
fields, associations) where we want "not supported" to fail loudly — is answered
by Serializer's `attributes` context option, which takes a **nested** tree: a
schema can hand it the same declared leaf paths `getRecordPathMap()` already
produces, and the schema stays in authority over what maps. That was checked when
this epic reopened the question, not assumed.

**Surviving, and decisive on its own.** `deep_object_to_populate` updates
singular children but **not arrays of objects** — it produces a fresh instance
per item. For Doctrine that is not a mapping error but a duplicate INSERT at
flush, plus deletion of the originals under `orphanRemoval`; measured on a
two-tag `Post`, re-saving after renaming one tag and deleting the other yielded
one brand-new `Tag` row and reused neither original. That is precisely the
collection shape the `Repeater` and relationship epics need, and it is the shape
this mapper [refuses outright](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused)
rather than getting wrong. Since identity matching is a state-shape decision
those epics have to make anyway, adopting Serializer would buy the singular half
we already have and leave the hard half untouched — at the cost of a second
exception vocabulary. A lesser point still holds: `normalize($record)` is
over-broad against the read-scoping stance and initializes Doctrine proxies
wholesale.

Serializer remains a candidate for leaf-level *casts* (dates, enums, UUIDs),
where its normalizer half fits the existing `StateCast` seam. The whole verdict
reverses if `deep_object_to_populate` ever grows identity-aware collection
population.

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
