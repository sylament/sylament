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
state bag. The per-component relationship hooks reroute a path through its own
component when a plain property read or write cannot express the association
([the collapse of Filament's third pass](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk)).
The operation half lives in
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
| [`hasOwnRecord()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) |
| [`getData(?array $state = null)`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#reads-are-lenient-writes-are-strict) · [diverges](#mapping-walks-the-record-graph-by-declared-leaf-paths) · [diverges](#a-missing-singular-intermediate-is-constructed-from-its-declared-type) · [semantics](#a-caller-that-has-already-validated-hands-the-state-in) · [semantics](#a-refused-submit-on-the-query-lane-shows-in-the-row-until-the-next-request) |
| [`getData()`'s record-validation tail](../../../packages/schemas/src/Concerns/BelongsToRecord.php) + [`getRecordViolationPathTranslator()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object) · [diverges](can-be-validated.md#a-refused-submit-leaves-the-record-mutated) · [diverges](can-be-validated.md#a-taken-assigned-key-is-a-field-error-not-a-unique-violation) · [diverges](can-be-validated.md#a-changed-key-is-refused-and-put-back-not-handed-to-doctrine) · [detail](violation-path-translator.md) |
| [`findUnmappedAdditions(array $before, array $after)`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) — the paths a write seam's hook added that no component maps (`@internal`: the default write seams refuse what it answers) | — | done | — | [semantics](#a-hook-added-path-no-component-maps-is-answered-for-the-write-seams-to-refuse) · [diverges](#a-hook-added-key-no-component-maps-is-refused-where-eloquent-writes-or-drops-it) |
| [`getNullableBranches()` + the branch pass in `getData()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) · [diverges](#a-component-can-own-its-whole-record-subtree) · [semantics](#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled) · [semantics](#a-branch-the-record-cannot-null-is-refused-loudly) · [semantics](#a-nullable-branch-with-no-state-path-is-refused-not-ignored) · [semantics](#branches-are-ordered-by-path-depth-because-tree-order-is-not-path-order) |
| [`getNestedRelationshipHooks()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) |
| [`fill(object …)` object arm](../../../packages/schemas/src/Concerns/HasState.php) + [`readStateFromRecord()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | [diverges](#fillobject-reads-only-the-paths-the-schema-declares) · [diverges](#mapping-walks-the-record-graph-by-declared-leaf-paths) · [diverges](#fillobject-does-not-bind-the-record) |
| [`getStateFromRecord()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | — | done | — | the public reading of `readStateFromRecord()` — Eloquent's `attributesToArray()` has no port target, so this is a gap fill rather than a rename. [Declared paths only](#fillobject-reads-only-the-paths-the-schema-declares), so a column the schema does not carry is absent; the fill-side mutators read it instead of filling twice. |
| [`$record` property](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`public $model`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#the-root-record-property-is-protected) |
| `saveRelationships()` | [`saveRelationships()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | skipped | — | [diverges](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |
| [`loadStateFromRelationships()`](../../../packages/schemas/src/Concerns/BelongsToRecord.php) | [`loadStateFromRelationships()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) | done | — | [diverges](#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter) · [diverges](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |
| `fillPartially()` object arm | — | blocked | schemas partial-fill port | [detail](#fillpartiallys-object-arm-waits-on-partial-rendering) |

### Component tier — base `Component`

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`record()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`model()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-component-tier-gains-record) · [semantics](#the-root-tier-memoizes-a-closure-record-the-component-tier-re-evaluates-it) |
| [`getRecordClass()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getModel()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-record-type-probe-is-instanceof-not-a-model-gate) |
| [`getRecord(bool $withContainerRecord = true)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getRecord()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-record-type-probe-is-instanceof-not-a-model-gate) |
| [`getRecordInstance()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getModelInstance()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#getrecordinstance-constructs-with-new-not-the-container) |
| [`getRecordStatePath()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it) |
| [`hasOwnRecord()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) |
| [`getNearestRecordClass()` / `getNearestRecordStatePath()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) |
| [`ownsRecordSubtree()`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) | — | done | — | [diverges](#a-component-can-own-its-whole-record-subtree) · [semantics](#two-components-cannot-both-map-one-record-path-wholesale) |
| [`routesNestedRelationshipHookAt()`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) | — | done | — | [semantics](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) |
| [`nullWhenBlank()` / `isNullableBranch()` / `shouldNullBranch()` / `isBlankStateValue()`](../../../packages/schemas/src/Components/Concerns/HasNullableBranch.php) | — | done | — | [diverges](#a-declared-branch-is-nulled-whole-before-its-leaves-are-written) · [semantics](#the-built-in-blank-test-asks-the-branchs-own-leaves) · [semantics](#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled) · [semantics](#a-branch-the-record-cannot-null-is-refused-loudly) · [diverges](#a-nullwhenblank-closure-sees-two-different-bags) · [diverges](#a-doctrine-embeddable-cannot-be-nulled) |
| [`loadStateFromRelationshipsUsing()` / `hasLoadStateFromRelationships()` / `loadStateFromRelationships(object $record)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`loadStateFromRelationshipsUsing()` / `loadStateFromRelationships()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) · [diverges](#the-exists-guarded-members-are-not-ported) · [semantics](#a-hook-the-record-path-map-does-not-carry-is-refused) |
| [`saveRelationshipsUsing()` / `hasSaveRelationships()` / `saveRelationships(object $record, mixed $state)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`saveRelationshipsUsing()` / `saveRelationships()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) · [diverges](#the-exists-guarded-members-are-not-ported) · [semantics](#a-hook-the-record-path-map-does-not-carry-is-refused) · [semantics](#nullwhenblank-cannot-compose-with-a-save-hook) |
| Nested hook delegation beneath a subtree owner | [`Schema::saveRelationships()`'s tree walk](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) · [`Repeater::saveToRelationship()`'s `$item->model($record)->saveRelationships()`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) |
| [`setAssociationReader()` / `getAssociationReader()`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php) — composed from `CanAccessAssociations` | — | done | — | [semantics](#every-component-reaches-the-association-reader-through-a-required-setter) |
| `saveRelationshipsBeforeChildrenUsing()` / `saveRelationshipsBeforeChildren()` | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | skipped | — | [diverges](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |
| `saveRelationshipsWhenDisabled()` / `saveRelationshipsWhenHidden()` / their `should*` readers | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | skipped | — | [diverges](#the-exists-guarded-members-are-not-ported) |
| `castStateAfterLoadingFromRelationships()` | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | skipped | — | [diverges](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |

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
unbound `LogicException` and the array-bound one, both pinned on the message text
that names which arm was taken, the nested set: a nested graph mapped in place, a
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
throwing, and a path-less declaration throwing; plus the ordering pin — two
sibling branches nesting by path, where the outer null must short-circuit the
inner branch's non-nullable refusal, which is red the moment the `uksort()` goes,
and a branch kept alive — the sibling's record leaf written — through a filled
path-descendant leaf declared by a sibling section and again by a dotted sibling
field; plus the record-subtree owner set, read off the map directly through a
`Schema` subclass fixture — an owner mapped as a leaf with its descendants
dropped, a plain container still contributing its descendants and not itself, a
descendant two containers down still represented by the owner, the whole-property
read on `fill()`, the wholesale write on `getData()` destroying an unmanaged key
the state no longer carries, and the foreign path-descendant refusal pinned on
both class names and the offending path; plus the owner arms of the branch pass —
an owner nulled from the presence of its own path in a bag carrying no leaf path
at all, with the leaf walk then not writing its value back over the null, an
owner holding one all-blank item kept alive, a hidden owner left alone, and an
owner over a non-nullable `array` property meeting the same loud refusal; plus
the delegation set over a routing-owner fixture — a hooked leaf beneath a
vouching owner neither refused nor mapped, and the five shapes that keep
refusing beside it: a non-vouching owner, a path-less hook, a non-owner
container hook, an inner non-vouching owner inside an outer vouching one, and
the earlier of two hooked components sharing one record path); the discovery
walk by
[`Unit/Concerns/NestedRelationshipHooksTest`](../../../packages/schemas/tests/Unit/Concerns/NestedRelationshipHooksTest.php)
(schema-relative keys through a plain container, a hooked nested owner taken as
one entry with nothing beneath it, a hook-less component and a path-less hooked
one both absent, last declaration winning a shared key, and an empty result for
a schema declaring no hook);
the branch declaration itself by
[`Unit/Components/Concerns/HasNullableBranchTest`](../../../packages/schemas/tests/Unit/Components/Concerns/HasNullableBranchTest.php)
(the default, the declaration, blank and filled leaves, a nested sub-branch on
both sides of blank, the closure arm receiving the branch state, and the explicit
`false` — all of those on a detached component, so they also pin the fallback
arm; plus four wired trees for the declared-leaves rule: a branch judged blank
while an unmanaged key beside it is filled, a branch kept alive through a leaf
two containers down, a sibling component's path-descendant leaf counting toward
the verdict, and a wired component without its own path falling back to the
value test; plus the record-subtree owner arm on three shapes — detached, wired
with a declared leaf whose key the slice does not carry, and nested inside a
section branch that must read the owner's whole value rather than the owner's
leaves — each pinned on the one item leaf enumeration would have called blank);
the field-side half of the same rule by forms'
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
pre-existing by-name one; plus the nearest-frame pair — the root container when
nothing between binds, where the path half must equal `getRecordStatePath()`, an
intermediate **component** binding and an intermediate **child schema** binding,
each framing the class and stripping its own prefix while the root-framed twin
still spells the whole path, and a path-less component answering the class with a
null path); the fill walk by
[`Unit/Concerns/HasStateTest`](../../../packages/schemas/tests/Unit/Concerns/HasStateTest.php)
(scoped read, null-fill of what the record cannot supply, uninitialized-property
and object-value skips with both arms of the state-cast exemption beside them,
over an enum leaf and a `DateTimeImmutable` one — a cast-bearing component
reading the flattened value, a cast-less one skipping and
null-filling so no object reaches the bag — array reads,
the unchanged null/array arms, and the
nested reads: a nested object path filled through the graph, null filled past a
null intermediate, a leaf decorated with a path-less child schema staying a
leaf, an array-backed branch read by declared leaves only, and a numeric-string
leaf path surviving the walk's `string` hint); and the
round trip through a real live host by
[`Integration/Concerns/BelongsToRecordTest`](../../../packages/schemas/tests/Integration/Concerns/BelongsToRecordTest.php)
(`fill($object)` → edited bag → `getData()` returns the same mutated instance;
a failing validation leaves the record untouched; and the same pair over a
nested `Post`/`Author` graph — the round trip mutating the associated object in
place, a failing nested submit leaving it untouched); and the relationship hooks
by
[`Integration/Concerns/BelongsToRecordAssociationTest`](../../../packages/schemas/tests/Integration/Concerns/BelongsToRecordAssociationTest.php)
(against a Doctrine `Post`: a load hook supplying a path `fill()` would
otherwise skip as an object-valued leaf, a save hook taking the write off
`setOnRecord()` while the property its path names stays untouched, an *unhooked*
object collection still meeting the refusal — the pin that keeps the bypass
opt-in — the `nullWhenBlank()`-plus-save-hook refusal, the three
map-exclusion refusals, one per exclusion: a container whose children carry the
mapped paths, a component with no state path, and a component beneath an owner
that does not route it — each raised from `fill()`, which is the earlier of the
two edges to reach the map — the delegated arm beside that last one, where a
routing owner carries a hooked child through both edges without a refusal, and a
container-built component receiving the container's `AssociationReader` rather
than the `new`-built fallback).

## Details

### `fillPartially()`'s object arm waits on partial rendering

`fillPartially()`/`hydrateStatePartially()` are not ported at all, blocked on a
UX LiveComponent partial-render equivalent. The object arm — the one this file
would own, sourcing a partial fill from a record rather than an array — is a
one-line addition once they land.

**Next step:** land it with the partial-render decision.

## Semantics

### A hook the record path map does not carry is refused

Both hooks are consulted from inside the map walk, so a component the map
excludes is never asked whether it declares one. Three exclusions produce that
shape, and `getRecordPathMap()` refuses each of them with a `LogicException`
naming the component, which exclusion it met, and the way back into the map:

| the component | why the map skips it | the way out |
| --- | --- | --- |
| carries no state path (or one that resolves to no record path) | the map is keyed by record path | give it the `statePath()` of the association it maps |
| nests path-bearing components without [owning its subtree](#a-component-can-own-its-whole-record-subtree) | the map takes its leaves, never the container | declare `ownsRecordSubtree()` so it maps whole |
| sits beneath a subtree owner that [does not route it](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) | the owner speaks for every path under its own | declare `relationship()` on that owner, or move it out from under it |

Left as a silent skip, a hook on any of the three would be the worst shape this
mapper can produce: no error, no write, no trace — a relationship that simply
never saves. The middle row is the one a user hits, because a container is the
natural place to hang a relationship, and it is exactly the shape the forms
`Repeater` needs; it maps only because `Repeater::ownsRecordSubtree()` returns
true. The refusal is [the same stance](#reads-are-lenient-writes-are-strict) the
mapper takes on a foreign path nesting under an owner, and the same one
[`nullWhenBlank()` without a state path](#a-nullable-branch-with-no-state-path-is-refused-not-ignored)
takes two lines away.

The **third** row is the one a relation-backed field hits, and it is the only one
with an exit the owner can open: an owner that
[routes the hooks beneath it](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused)
takes delegation of that path and the refusal never fires. Where no owner does,
the row's other exits stay dead for that shape — a `Select` mapping a to-one
association from inside a relationship repeater has nowhere to move to, and
dropping its hook only moves the failure to the owner's own write. The two
owners that route are
[the repeater, by the item](../forms/repeater.md#a-nested-relation-backed-field-is-routed-by-the-item-not-by-the-map)
and
[the entangled layout components, onto the related record](entangles-state-with-singular-relationship.md#the-subtree-is-re-rooted-and-its-nested-hooks-are-routed-onto-the-related-record).
The first two rows have no such exit at any depth: a path-less component and a
path-bearing non-owner container are unroutable shapes, so they keep refusing
beneath a routing owner exactly as they do anywhere else.

The guard collects hook-declaring components in the map build's own walk and
tests membership after the owner pass, so it costs no extra traversal and sees
the map exactly as the two edges will consult it. It fires from `fill()` as well
as `getData()` — both go through the map — so a misconfigured hook is reported
on the first render, not first on submit.

### A hook a subtree owner routes is delegated rather than refused

An owner swallows the paths beneath it, so a hook down there can only fire if the
owner runs it. `routesNestedRelationshipHookAt(string $path)` — on
`HasChildComponents` beside `ownsRecordSubtree()`, default `false` — is where the
owner says it will: the mapper asks it, per **owner-relative** path (the owner's
own record path stripped), whether its walks route a hook there, and a `true`
turns the third refusal arm into a delegation. `getNestedRelationshipHooks()` on
the root tier is the walk behind both halves — the owner answers the vouch from
it and routes from it — which is what keeps a vouch from outliving the routing it
promises. Why the vouch is pulled by the mapper rather than handed over when the
map is built is
[stated with the hooks](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk):
at `fill()` a relationship repeater has no item schemas to hand over.

Three rules decide which component a delegation reaches, and each is the reason a
neighbouring shape still refuses.

**The nearest owner is asked, never the outermost.** `$owners` is walked in flat
order, outer before inner, so an outer owner's pass drops an inner owner's
descendants before the inner owner's pass ever sees them —
[`isInsideOwnerTree()`](#a-component-can-own-its-whole-record-subtree) answers
"any ancestor", which cannot tell the two apart. Attribution walks the container
chain to the *first* owner instead, so a hook under a non-routing owner nested
inside a routing one is still refused, and the message names the owner that would
have had to route it.

**Only the map's last claimant is delegated.** The owner pass records the
component it unsets per path, which is by construction the one that survived the
map's last-wins overwrite. Two hooked components sharing one record path under an
owner therefore split: the survivor is delegated, the earlier one was never in
the map, never dropped, never recorded — and keeps refusing. That is also the
component the owner's discovery would have dropped, so mapper and discovery name
the same survivor.

**Only the `default` arm can be skipped.** A hook with no record path and a hook
on a path-bearing non-owner container are unroutable whatever sits above them, so
they refuse beneath a routing owner exactly as they do at the root. So do the two
wholesale-claimant refusals and the foreign-path one, which the owner pass raises
before any of this.

Loudness survives by construction, and that is the invariant to protect when a
new owner is written: because the vouch is answered from the routing walk itself,
every component the mapper stops refusing is one an owner's walk will actually
run. Concretely still refused, each naming the component: a path-less or
non-owner-container hook anywhere; a hook in a child schema the owner's walks
never consult; the earlier of two hooked components sharing a record path; a hook
under an owner whose vouch is off, a plain-array repeater or an undeclared
relationship among them; and a hook under an inner non-routing owner inside an
outer routing one. An owner that vouches for a path it does not route would break
that invariant — and would break it silently, which is the one failure this
mapper exists to prevent.

Discovery membership is a smaller set than the map's, and deliberately so: every
flat component with a load **or** save hook whose state path nests under the
schema's own, minus anything with an intermediate owner between it and the
schema. A nested owner is one entry itself when it declares a hook, and
everything beneath it belongs to that owner — which is how recursion falls out
with no recursive code: the outer owner runs the inner owner's hook, and the
inner owner repeats the whole scheme one level down. Keys are relative to the
schema asked, not to the root, so an item schema and the root schema answer in
their own terms; the derivation is
[the one `getRecordStatePath()` uses](#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it),
against the schema's own state path rather than the root's.

Filament needs none of this machinery for the nested to-one, and not because its
walk is richer: its select is conventionally named for the **foreign-key
column** and stays dehydrated (`Select::relationship()` sets
`dehydrated(fn (Select $component): bool => (! $component->isMultiple()) && $component->isSaved())`),
so `$record->fill($itemData)` writes `author_id` as an ordinary attribute and
the hook is not load-bearing there. Doctrine maps the association and no
foreign-key scalar beside it, so the write has no attribute to land on and the
hook is the only route — which is why the port met a wall Filament does not,
and why closing it was mapper work rather than transcription.

### The nearest record frame is the first node above a component that binds one

`getRecordStatePath()` is root-framed: it strips the **root** container's state
path, so its result indexes the root record. That is right for the map, which is
built once at the root — and wrong for a component asking what its own path
indexes once a subtree is bound to a different record, where the path segment
naming the item (`record-4.author`) belongs to no property of anything.
`getNearestRecordClass()` and `getNearestRecordStatePath()` are the frame-relative
twins: the class of the nearest node above the component that binds a record of
its own — container schemas and the components they hang off alike, **the
component itself excluded** — and the component's path with that node's stripped.
Both come off one private walk, so the class and the path can never name
different frames. With nothing between, the frame is the root container and the
path half equals `getRecordStatePath()`; the two diverge only where something in
between binds.

The probe the walk asks is `hasOwnRecord()`, new on both tiers, and it reads the
**unevaluated** property. Neither existing getter can stand in: `getRecord()`
returns `null` for a class-string binding, which is exactly the binding a subtree
stamp uses, and `getRecordClass()` walks the chain, so it answers the same for a
node that binds and one that inherits. Reading the raw property is also what
keeps the walk free of side effects — the root tier
[memoizes what it evaluates](#the-root-tier-memoizes-a-closure-record-the-component-tier-re-evaluates-it),
and a frame walk that evaluated every node it passed would freeze closure
bindings it merely looked at. The trade runs the other way too: those getters
memoize the *evaluated* value back onto the property, so a closure binding that
resolves `null` answers the probe `true` before any getter runs and `false`
after — two different frames across calls. The shipped stamps floor at the
target class-string, so the flip needs a stamp that resolves nothing under a
bound root: a repeater whose `relationship()` names an association the mapping
cannot describe answers `null` from the soft floor while the root still binds.
The flip is benign there — that tree's fill and save edges refuse the
unresolvable association loudly, so no derivation reading either frame survives
to see it.

The path half returns `null` twice over: for a component carrying no state path
of its own, and for one whose path does not nest under the frame's. That second
arm is where it parts company with `getRecordStatePath()`, which returns the
whole path unstripped in the same situation. A path that does not nest under the
frame's is not a path into the frame's record, and handing it to a type reader
would answer about the wrong object; `null` is the caller's signal to fall back
to whatever it did before a frame was involved.

### Two components cannot both map one record path wholesale

The map is keyed by record path, so two components claiming the same one would
leave the second silently replacing the first — and where one of them
[owns its subtree](#a-component-can-own-its-whole-record-subtree), what is lost
is not a shared leaf but a whole branch that one of the two would stop filling
and saving without a trace. `getRecordPathMap()` refuses the pair, naming both
classes and the path. Two path-bearing components sharing a record path where
**neither** owns a subtree are untouched: that is the ordinary case of two fields
bound to one property, and both still write it.

The shape a user meets this in is
[two layout components entangling one relationship](entangles-state-with-singular-relationship.md#two-components-entangling-one-relationship-throw-rather-than-merge),
which Filament merges instead; that file carries the decision and what would
reverse it.

### `nullWhenBlank()` cannot compose with a save hook

A component declaring both `nullWhenBlank()` and `saveRelationshipsUsing()`
throws a `LogicException` from `getNullableBranches()`, naming the component
class and the two ways out. This is a v1 refusal rather than a rule about what
is possible, and the grounds are that the two declarations answer the same
question differently. The branch pass writes `null` at the branch's own record
path through the property accessor and then skips every path beneath it — but a
save hook exists precisely because the accessor cannot write that path, so the
"null the branch" half would either fail or write past the relationship the hook
owns. Emptiness for a relationship is the hook's own business: a to-many hook
clearing its collection and a to-one hook writing `null` both express it inside
the callback, with the entity manager in reach.

Placed before the state-path check in the same loop, so the conflict is reported
even when the component also carries no state path — the pair of declarations is
wrong independently of where it sits.

### Every component reaches the association reader through a `#[Required]` setter

The two members are
[`CanAccessAssociations`](association-reader.md#the-accessor-trait-is-shared-with-the-table-and-action-tiers),
composed here — the trait is shared with `Table` and `Action`, which need the
reader for the relationship seam and are not components. The note that used to
sit here lives with it.

### The built-in blank test asks the branch's own leaves

`shouldNullBranch()` without a closure does **not** scan the values in the
branch's slice. It walks the root schema's flat components and keeps every
declared leaf whose absolute path sits under the branch's own — path-bearing,
and not itself a container — then reads each leaf's value out of the slice and
asks the leaf whether it is blank, through `Component::isBlankStateValue()`.
Three consequences, all deliberate:

1. **Unmanaged keys in the slice no longer influence the verdict.** An
   array-backed branch keeping a sibling key the schema never declared
   ([leaf granularity](#mapping-walks-the-record-graph-by-declared-leaf-paths))
   is judged on the declared leaves alone — the same "the schema maps what it
   declares" rule the rest of both walks follows.
2. **A leaf absent from the slice is blank, and membership is the path, not the
   tree.** Beneath-ness is the same absolute-path prefix the branch pass skips
   by, so a filled leaf three levels down keeps the branch alive — and so does
   one declared by a *sibling* component whose path nests under the branch,
   [the independence the ordering sort exists for](#branches-are-ordered-by-path-depth-because-tree-order-is-not-path-order).
   Judging tree children instead let a branch null while a filled sibling leaf
   beneath its path was then skipped as nulled — silent data loss, caught in
   review before merge. A leaf whose key the slice does not carry simply
   contributes nothing.
3. **A detached or path-less component falls back to the old value-recursive
   test**, so `shouldNullBranch()` stays total. Neither real walk can reach that
   arm — the mapper
   [refuses a path-less branch outright](#a-nullable-branch-with-no-state-path-is-refused-not-ignored)
   and forms' requirement walk is gated the same way — it exists so a component
   built with `new` in a unit test, with no container to enumerate leaves
   through, still answers. A wired component without a path of its own takes the
   same arm: its absolute path would be its container's, and enumerating leaves
   under that would judge the wrong branch.

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

A branch that [owns its record subtree](#a-component-can-own-its-whole-record-subtree)
asks none of that: it judges its own whole slice through `isBlankStateValue()`
and never enumerates a leaf, and the enumeration skips every leaf sitting under
an owner for the same reason. That is a correctness requirement, not a
shortcut. At mapping time an owner's slice has already been rewritten by its own
dehydration — [the `Repeater` positionalises](../forms/repeater.md#items-are-uuid-keyed-in-the-live-bag-and-positional-on-the-record)
its uuid-keyed items — while the leaves it declares still carry the uuid paths,
so enumeration would find every one of them absent, judge a branch that holds
items vacuously blank, and null it. One all-blank item is the exact case: by
value it is an item, by leaves it is nothing, and the two answers differ by a
whole list. An owner nested inside an ordinary branch is answered the same way —
the outer branch reads the owner's value, not the owner's leaves.

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

An [owner branch](#a-component-can-own-its-whole-record-subtree) is asked at its
**own** path instead, and that is not the one-liner rejected above. It has no
mapped leaf beneath it at all — the owner speaks for them — so the leaf-path
predicate would answer "absent" for every owner and no owner could ever null.
Reading its own path is safe for the reason the leaf predicate is not: an owner's
key is not an emptied parent left behind by pruning, it is the one key the
dehydration walk writes for it, so a hidden owner's key is gone outright and a
present one is evidence of a real submission.

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

That exact sibling-by-path shape is now pinned through the refusal, so deleting
the sort fails a test rather than a code review: the inner branch sits over a
**non-nullable** property, which only survives the pass by being short-circuited
under the nulled outer one, so an unsorted pass reaches it first and throws
[the non-nullable refusal](#a-branch-the-record-cannot-null-is-refused-loudly)
naming `meta.seo`.

One latent type detail rode along with it, now closed: PHP coerces a
numeric-string array key to `int`, so a schema using `statePath('0')` would have
`TypeError`d under `strict_types=1` — in the sort, whose callback hinted
`(string $a, string $b)`, and equally at `setOnRecord()`, which takes a `string`
path. The hints are `int|string`, and `getData()` normalises both walks' keys
with `strval()` once rather than casting at each of the six use sites. The claim
that this covered "both walks" was true only of the two inside `getData()`: the
**fill** walk, `readStateFromRecord()`, iterated the same map's keys raw and
`TypeError`d on `getFromRecord()`'s `string $path`. It normalises the same way
now, so all three walks agree.

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

### A caller that has already validated hands the state in

`getData()` reads `getState()` — which validates first — so a caller that has
*already* validated and then mutated the array it got back cannot write that
array through the state bag: `rawState()` + `getData()` would validate the
mutated values a second time (a hashed password against the `Length(max: 20)`
that passed on the plaintext, a stamped slug against a uniqueness probe) and
re-run every dehydration mutation the components declare, `Repeater`'s item
walk and `FileUpload`'s store move among them. Handing the array in as `$state`
skips exactly one step, `$this->getState()`, and nothing else: the nullable
branch pass, the relationship save hooks and the closing
`validateSchemaRecord()` all run against the supplied array, so record-level
constraints are still answered on the object the mapping produced.

The callers are the write-side seams that sit between validation and mapping —
the modal actions' `CreateAction`/`EditAction` bodies after
`mutateDataUsing()`, the resource page bases after
`mutateFormDataBeforeCreate()`/`mutateFormDataBeforeSave()`, and `Select`'s
option actions after theirs. Every zero-argument call site keeps the validating
read. Each of those seams first refuses a path its hook added that no component maps
([below](#a-hook-added-path-no-component-maps-is-answered-for-the-write-seams-to-refuse)).

### A hook-added path no component maps is answered for the write seams to refuse

`findUnmappedAdditions(array $before, array $after): array` answers the leaf
paths a write seam's hook added to the state it was handed that no component of
the schema maps. It is `@internal`; its readers are the default write seams,
which refuse a non-empty answer by name before mapping: both resource pages after their
`mutateFormData…()` hook, `CreateAction`, `EditAction` and `ReplicateAction`'s
modal arm at the head of their default write closures — so `using()` is never
guarded — and `Select`'s option actions. Before, such a value was dropped in
silence, since `getData()` writes the paths the record path map carries and
nothing else; writing it instead is no option, because a host's writable `data`
prop takes whatever keys a client sends, and writing unmapped state would be
mass assignment.

A leaf path of `$after` — a value that is no array, or an empty array — is
**added** when `$before` does not hold it, read level by level, an unchanged
subtree skipped on `===`; and a path where `$before` held an array and `$after`
holds something else is answered too, since a hook writing `null` over a mapped
`address.street` branch writes nothing `getData()` reads — no component maps
`address` itself. A path is **covered** when a record path equals it or is its
dotted ancestor, so a subtree owner speaks for everything beneath it, `null` over
a relationship repeater's whole `items` included. What follows:

- a key the state already held — a client's own, a `storeFileNamesIn()` sibling —
  is never answered, whatever the hook does to it, so a client can never force
  the refusal; it stays unwritten, as before;
- an overwrite of a mapped key is never answered;
- a key a hidden or `dehydrated(false)` component maps is covered **and
  written**, since the map carries it and the hook put it back in the state;
- a key added inside a relationship repeater's item is covered by the owner and
  still dropped, since the owner writes the item paths its template declares —
  recorded;
- a key the hook spells with a dot (`'a.b' => 1`) is read as one key, where
  `getData()` reads it as a path — at most a missed refusal, recorded;
- a removed key, and a mapped branch emptied to `[]`, are no addition: the record
  keeps what it held, as before — recorded.

Pinned by
[`BelongsToRecordUnmappedAdditionsTest`](../../../packages/schemas/tests/Unit/Concerns/BelongsToRecordUnmappedAdditionsTest.php),
the null-over-a-branch case by name; each seam pins its own refusal.

### A refused submit on the query lane shows in the row until the next request

Mapping happens before the object-level validation, so a refused submit leaves
the record mutated
([the divergence that states it](can-be-validated.md#a-refused-submit-leaves-the-record-mutated)).
On a table's query lane that mutation is visible: the row was rendered from a
managed entity, an `EditAction` beneath it resolves the record through the same
entity manager and the identity map hands back the *same instance*, so the
re-render after the refusal paints the rejected values into the row even though
nothing was flushed. The next request re-queries and the row is correct again.

Filament's `$record->update($data)` has the same shape — it fills before it
saves — so this is faithful rather than a defect, and the escape is the one the
divergence above already names: never flush on the failure path, and refresh or
discard the entity manager if the request continues past it.

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
[has-actions.md](../actions/has-actions.md)), and the Resource pages need this
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
  POPOs; the concept-name leaves with the ecosystem, like rule → constraint —
  where the loud, searchable break was judged better than a kept name
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
`mutateFormDataBeforeFill()`/`beforeFill`/`afterFill` hooks belong to the
Resource pages, as page surface rather than schema surface.

### Reads are lenient, writes are strict

The fill read-walk wraps each accessor read in one `catch (AccessException)` and
skips the path. Symfony's `AccessException` is the common parent of
`NoSuchPropertyException` (no accessor at all) and `UninitializedPropertyException`
(a typed property never assigned), so the single catch covers both without an
`isReadable()` pre-check, which would only be a second copy of the same walk. It
additionally skips object-valued reads — only scalars, arrays, null and an
object **the reading component's own state casts flatten to a non-object** enter
the bag. So a read has exactly two skip reasons:
the object cannot supply the value, or the value is not bag-representable, and
the null-fill pass then supplies the key. A null or scalar *intermediate*
supplies null for the leaf rather than skipping it — the same bag either way,
since the null-fill pass would supply the key.

The state-cast exemption is gated on the cast rather than on the type, because the cast is the whole reason the value is representable at all. A leaf
declaring [`EnumStateCast`](enum-state-cast.md) — or any cast that unwraps a
case, which `OptionStateCast` and `OptionsArrayStateCast` also do — passes,
because the hydration pass immediately behind the read turns the instance into
the scalar backing value the browser will send back. A leaf with **no** cast, or
one whose casts leave the value an object, does not: it skips and null-fills
exactly as it did before the exemption existed.

It shipped enum-shaped and was widened to every object leaf so a
`DateTimeImmutable` property could reach a `DateTimePicker`: the predicate
(`isObjectCarriedByStateCasts()`, formerly `isEnumCarriedByStateCasts()`) lost
its `instanceof BackedEnum` pre-check and kept its body, so what earns the
exemption is unchanged — running the component's own casts forward and finding a
non-object at the end. The narrower gate was never the rule, only the one type
that had a cast when it was written; asking the casts is the rule, and
[`DateTimeStateCast`](date-time-state-cast.md) is the second answer they give.

That gate is not fastidiousness about types. The state bag is a writable `array`
LiveProp, and
[`LiveComponentHydrator::isValueValidDehydratedValue()`](../../../vendor/symfony/ux-live-component/src/LiveComponentHydrator.php)
accepts only bool, null, numeric and string — recursing into arrays — so an
object anywhere inside the bag makes `dehydrateValue()` throw a `LogicException`
naming the **LiveProp**, not the field that put it there. A type-only exemption
would therefore have turned a `TextInput` bound to an enum-typed property from
"renders empty" into a 500 on its first dehydration, which is why the read walk
asks the component instead of asking the value. Running the chain twice — once
here to ask, once behind the read to convert — costs a pure transform, since
`StateCast::set()` is side-effect-free by contract.

Every object-valued leaf no cast answers for still skips, because it has no bag
spelling: an associated entity is the standing case, and an
association that wants one declares
[`loadStateFromRelationshipsUsing()`](#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk),
which is checked before this skip. Without the exemption,
`Select::options(MyEnum::class)`
over an enum-typed property filled to `null` on every mount — the write side
worked, the read side dropped it — which is why the widening ships with the enum
seam rather than after it.

**The relationship fills ask the same question.** The predicate is the
component's own —
[`Component::isObjectCarriedByStateCasts(object $value)`](../../../packages/schemas/src/Components/Concerns/HasState.php),
public, `@internal` and without a Filament counterpart, its body moved unchanged
from this trait's private method — because two more walks ask it: a singular
relationship's fill of its related record
([`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md#the-subtree-is-read-and-written-per-declared-leaf-not-as-an-attribute-bag))
and a relationship
[`Repeater`](../forms/repeater.md#a-relationship-repeater-keys-its-items-by-identity-and-diffs-the-collection)'s
fill of its items. Were both to skip **every** object leaf, a date, an enum or a
`Uuid` inside a relationship repeater item or a `Group::relationship()` would
fill `null`, and the next save — one that never touched the field — would write
NULL over the stored value. The component is the home
because the answer is its casts, and because it is the one place all three
askers reach: the root read runs in `Schema` scope, the other two in a
component's. Each walk asks the field that reads the leaf — the last declared,
where two share a path, as the record path map keeps it. The object enters the
bag only where a hydration pass runs right behind the read and converts it —
`fill()`, the array fill after `getStateFromRecord()`, and the host's
[reload](#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter),
whose child-schema hydration is load-bearing for that reason — and a fill hook
(`mutateFormDataBeforeFill()`, `mutateRecordDataUsing()`) now receives it at its
path inside a relationship, as it always did at the root. An association, a
collection and an object no cast flattens still skip. Pinned by
[`EntanglesStateWithSingularRelationshipObjectLeafTest`](../../../packages/schemas/tests/Integration/Components/Concerns/EntanglesStateWithSingularRelationshipObjectLeafTest.php)
and forms'
[`RepeaterRelationshipObjectLeafTest`](../../../packages/forms/tests/Integration/Components/RepeaterRelationshipObjectLeafTest.php).

One hole is inherited rather than introduced, and is worth naming because the
gate does not cover it: an **array** leaf is not object-valued, so a
`list<MyEnum>` property enters the bag whole and relies on the component's cast
(`EnumArrayStateCast`) to scalarize its elements. A cast-less component bound to
one has always been able to leave instances nested in the bag; the exemption
neither widens nor narrows that.

`getData()` is the opposite. It writes **every** leaf path of the validated
state and lets `NoSuchPropertyException` surface when the target cannot take one:
by calling it you have declared that this state is the object's data, and
silently dropping a key would silently drop a user's edit. Two errors are
softened. A missing intermediate *object*: `setOnRecord()` wraps it in a
`LogicException` naming the path, because Symfony's own message explains nothing
at this boundary. And a property **no setter can change** — a `public readonly` one, or a private one
behind a getter alone: left alone when it already holds the value the state
writes, and refused by name — the path, the class, the two value types — when it
holds another or none yet
([property-accessor.md](property-accessor.md#a-property-no-setter-can-change-is-left-alone-when-it-already-holds-the-value)).
That is what lets a key a record's constructor sets save, where every such write
would otherwise be Symfony's `NoSuchPropertyException`; a property neither writable nor readable
— a misspelled path — still surfaces it. The escape hatch for a page-only field
is Filament's own idiom — `dehydrated(false)` removes it from `getState()` and
therefore from the mapping. Ordering matters and is deliberate:
`getData()` runs the validating `getState()` first, so a failing submit throws
[`ValidationException`](can-be-validated.md) before the record is touched at all.

The **second** throw is the other way round. After the leaf walk, the written
record is validated in its own right —
[record-sourced constraints](can-be-validated.md#record-sourced-constraints-are-validated-on-the-mapped-object)
are answered against the object the mapping produced, which means by then the
record already carries the rejected value
([the cost of that ordering](can-be-validated.md#a-refused-submit-leaves-the-record-mutated)).
So the two failure modes of one call differ: a state failure leaves the record
untouched, and a record failure leaves it mutated and unflushed.

### A hook-added key no component maps is refused, where Eloquent writes or drops it

Filament's write seams hand `$data` to Eloquent — `new $model` and `fill()`,
`$record->update($data)` — and `Model::fill()` decides per key: a fillable key is
written (an unknown column then fails at `save()`), an unfillable one is dropped
in silence, unless the application turned on
`preventSilentlyDiscardingAttributes()` or `shouldBeStrict()` or left the model
wholly guarded — then it is a `MassAssignmentException`, *Add fillable property
[house, year] …*. The record path map plays `$fillable` here, and the toolkit takes the strict
branch, unconditionally, and only for the keys a **hook** added: `getState()` keeps a key a client typed that no component
declares, where Filament's prunes to the keys its validation answered
([schema.md](schema.md#getstate-validates-without-a-prepareforvalidation-mutation-layer)),
so refusing every unmapped key would let a client force the refusal. The message
lists the paths comma-separated, as Eloquent's does.

Permanent.

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
  constructor arguments, an object collection at any segment — recognised from
  the declared type (an adder resolves the property to a `list<Tag>`, not to a
  `Traversable`) and again from the collection instance met on the walk — and
  an array remainder a typed property rejects.
- **Scope: plain nested objects and embeddable-shaped value objects.** The
  walk will mechanically traverse a loaded Doctrine association and mutate the
  associated entity in place — almost never what a form edit means, and
  undetectable here (this walk is handed no `EntityManager`, so it reads declared
  PHP types and no mapping metadata, even though `schemas` now ships
  [`AssociationReader`](association-reader.md) for the component tier).
  Associations are declared instead — `relationship()` on the owning component,
  routed through that reader — and this note is the warning, not a guard.

The container test is `hasPathBearingDescendants()`, and it carries a second arm
for the component that is both leaf and container:
[a component may own its whole record subtree](#a-component-can-own-its-whole-record-subtree),
in which case it maps as a leaf and speaks for every path beneath its own.

### A component can own its whole record subtree

Sylament-only, because the record path map is Sylament-only.
`ownsRecordSubtree()` — on `HasChildComponents`, default `false` — marks a
component whose own dehydrated value **is** the record value for everything
beneath its path — the shape no component had until
[the forms `Repeater`](../forms/repeater.md), which is what the predicate exists
for. The map build answers it in two places. The container skip
is owner-aware, so an owner is collected as a leaf despite its path-bearing
descendants: `fill()` then reads its property whole and `getData()` writes the
dehydrated value back wholesale, which is what carries item keys the schema never
declared and what makes the leaf-granularity rule above stop at the owner. And a
second pass drops every path collected beneath an owner, so nothing re-reads or
re-writes a slice of a value the owner already speaks for.

That pass is not a plain filter, because two different components can produce a
path under an owner's. A **tree** descendant — a field inside the owner's own
child schema — is dropped silently; the owner represents it. A **foreign** one —
a component declared beside the owner whose path merely nests under it,
`StateField::make('meta.extra')` next to an owner at `meta` — throws a
`LogicException` naming both components and the two ways out. Left alone it would
neither fill nor save: the owner's wholesale read never reaches it, and the
owner's wholesale write overwrites whatever it wrote. Silence there is exactly
the shape this mapper [refuses loudly](#reads-are-lenient-writes-are-strict).
Tree membership is the container chain walked to the root, never the path —
a shared path prefix is precisely what cannot tell the two cases apart.

The branch pass answers the predicate too, in
[the three arms recorded with it](#a-declared-branch-is-nulled-whole-before-its-leaves-are-written)
— presence at the owner's own path, the value blank test, and the exact-path skip
after nulling — and forms' derived requirement inherits all three, since it asks
the same `shouldNullBranch()`.

Dehydration needs no owner arm of its own: it prunes by own path, which is
already subtree-safe.

### The relationship hooks are consulted by the two mapping edges, not by a schema-level walk

Filament runs relationships as a **third** pass. `Schema::saveRelationships()`
and `Schema::loadStateFromRelationships()` walk the component tree calling the
per-component callbacks, driven from `Schema::getState()` and again by the
create pages once the model row exists. Sylament has no such walk for the
**save** and does not port one; the load half is a re-read the host runs
after its own flush rather than a pass inside `getState()`
([below](#loadstatefromrelationships-is-the-hosts-reload-after-its-flush-and-takes-no-parameter)). The two mapping edges already walk the record path map —
`fill()`
through `readStateFromRecord()`, `getData()` through its leaf loop — so a path
**the map carries** is routed to its component instead of to the property
accessor:

- **`fill()`** — a component answering `hasLoadStateFromRelationships()` supplies
  the value at its path, bypassing the record read *and* the object-leaf skip.
  That skip is why the hook is needed at all: an association read returns an
  entity or a collection, which the fill walk drops.
- **`getData()`** — a component answering `hasSaveRelationships()` is handed the
  dehydrated state at its path and owns the write, so `setOnRecord()` and its
  [object-collection refusal](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused)
  never run for it. An **unhooked** collection path still meets that refusal
  unchanged, which is the guard that keeps the hook opt-in.

Map membership is the precondition, not a detail: Filament's walk visits every
component in the tree, ours visits only the components
[`getRecordPathMap()`](#mapping-walks-the-record-graph-by-declared-leaf-paths)
carries, which is a strictly smaller set. A hook declared on a component outside
it is refused at map time rather than left to no-op —
[a hook the record path map does not carry is refused](#a-hook-the-record-path-map-does-not-carry-is-refused).

Four consequences follow from collapsing the third pass into the two edges.

**The ordering member loses its reason to exist.**
`saveRelationshipsBeforeChildrenUsing()` is the seam that lets a component write
its own relationship *before* its child schemas write theirs — Eloquent needs
the parent row inserted before a child row can carry the foreign key, so a
nested repeater's items cannot save until the item that owns them has an id.
Doctrine has no such moment: `getData()` mutates an in-memory graph and the
caller's single `flush()` sorts the INSERTs itself
(`UnitOfWork`'s `TopologicalSort` over the association graph). A split hook pair
would let a user declare a write ordering that Doctrine then re-derives anyway,
so the declaration could only ever be ignored or contradicted. Skipped, not
deferred — revisit only if a shape turns up where the *graph mutation* is itself
order-sensitive.

**Hooks fire at map time, once.** Filament runs its walk twice on an insert —
from `getState()`, where the per-component `exists` guard suppresses every hook
because the row is not there yet, and again from the create page after
`handleRecordCreation()`. Sylament's hooks run inside `getData()`, against the
in-memory graph, and the caller flushes once afterwards: the whole submission —
leaves and relationships alike — reaches the database as one transaction, and a
validation failure throws before any of it, since `getData()` validates first.
That single-flush shape is the relation layer's central choice, and it is what makes both
the second walk and the `exists` guard unnecessary.

**`castStateAfterLoadingFromRelationships()` has no seam to hang on.** In
Filament it re-casts a component's state after the relationship walk mutated it
mid-request, because that walk is a separate pass that can land after
hydration. Here the load hook's value enters the ordinary state array
`readStateFromRecord()` returns, so `fill()`'s existing `hydrateState()` /
`callAfterStateHydrated()` / `fillStateWithNull()` sequence already casts it —
the same array arm a hand-written `fill([...])` takes. Skipped as a member with
nothing left to do.

**A hook beneath a subtree owner is pulled by the owner, not pushed by the map.**
Filament's walk reaches a nested hook because it descends the component tree;
ours reaches only what the map carries, and an owner's paths are dropped from it.
The seam that closes the gap is
[a vouch plus a discovery walk the owner runs itself](#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused),
not a sub-map the owner pass hands over at map-build time — and the asymmetry
that rules the handoff out is the repeater's fill side. At `fill($record)` the
raw state is still empty, so no item schema exists yet, so nothing under the
owner is in the flat walk and there is no sub-map to hand anywhere; the owner
discovers from its **template** child schema at that moment instead, and from the
per-item schema at save. A handoff would also park mutable state on the owner
between map builds, and the map is built per edge and again by the
[violation translator](violation-path-translator.md). That translator is
untouched by the delegation, deliberately — and it reaches beneath an owner by
another route, the last paragraph of this section.

The correction that comes with it, because the shape it describes is easy to
misread and this file said it wrong: Filament's own recursion is **not** limited
to newly created items. `Repeater::saveToRelationship()` re-runs
`$item->model($record)->saveRelationships()` only for the items it just created,
which is true of that method read alone — but `Schema::saveRelationships()`, the
tree walk it sits inside, descends children-first over **every** item's schema
before the item's own save runs, so the behaviour recurses for updated items too.
Read the walk, not the method.

**The translator is handed what the map drops beneath an owner.** Over the map
alone, under an entangled owner a nested field's record-level violation would
fold at the owner's path, because the map carries only the owner — and a `Group`
renders no error of its own path, so that would be a 422 with nothing on screen.
So `getRecordPathMap()` is a wrapper over
a private `collectRecordPaths()`, which answers the map beside the paths it
dropped beneath a subtree owner, computed by the one walk that always computed
them, and `getRecordViolationPathTranslator()` builds the translator over both.
So `plaque.title` lands as `data.plaque.title`, under its field, with no contract
member added. A class-level violation on the related record names no path inside
it and still folds at the owner, which renders nothing, so the host files it at
the schema's own path instead, where the page and the modal render it
([why](violation-path-translator.md#a-message-is-filed-only-under-a-component-that-shows-its-own-errors)).
Pinned by
[`BelongsToRecordRelatedRecordTest`](../../../packages/schemas/tests/Integration/Concerns/BelongsToRecordRelatedRecordTest.php)
and, rendered, by panels'
[`EditRecordRelationshipsTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordRelationshipsTest.php).

### `loadStateFromRelationships()` is the host's reload after its flush, and takes no parameter

Filament re-reads relationship-backed state inside `Schema::getState()`: after
`saveRelationships()` it calls `loadStateFromRelationships(shouldHydrate: true)`
whenever the host hands `getState()` an `afterValidate` callback, which its
`EditRecord::save()` does. Here there is no save walk to hang it on — the hooks
run inside `getData()`, against an in-memory graph nothing has flushed yet — so
the reload is the **host's** to call once its flush has run.
[`EditRecord::save()`](../panels/README.md#editrecord) calls
`$schema->loadStateFromRelationships()` after `handleRecordUpdate()` and before
`afterSave`. The name
is Filament's; the `$shouldHydrate` parameter is dropped, because the one caller
that matters passes `true` — it always hydrates.

For every component in the record path map that answers
`hasLoadStateFromRelationships()`, it reads that state off the bound record
through the component's own load hook, hydrates it without defaults, re-hydrates
each child schema and calls its after-hydrated hooks, then the component's, and
null-fills — `fill()`'s sequence, restricted to the relationship-backed paths.
Every other path is left alone, a schema bound to no record reads nothing, and it
runs no statement: it reads what the flush left in memory.

It exists because a relationship [`Repeater`](../forms/repeater.md) keys an
item the add action minted by a per-request uuid until something reads the
collection back, and without the reload nothing does — so the next save would
construct that item again. For an assigned key the second save would be an
`EntityIdentityCollisionException` 500, and under the taken-key rule a false
*"This value is already used."* on the item just saved; for a generated id every
added item would be deleted and re-inserted on **every** save. With the reload the second save writes nothing and a generated id keeps
its value.

Four costs, all enumerated: a relation-backed component's `afterStateHydrated`
hooks run once more per save; a host that flushes itself, outside `EditRecord`,
keeps the old behaviour until it adds the one call after its flush — the demo's
`Articles:ArticleForm` does; UI-only state inside a relationship is reset; and a
relationship repeater item saved on a blank key fails the save itself.
The reload hands a relationship repeater's items and a singular relationship's
group back as the record gives them, so a `dehydrated(false)` field there — state
the save never wrote — reads what the record gives, `null` where the record holds
no such property, after every successful save, rather than keeping what the
user typed. That third cost is Filament's own behaviour, since its reload
runs inside the `getState()` its save makes, and it is accepted rather than
worked around. The fourth is the reload's mint. A relationship repeater item keyed
by a single natural string key that no `prePersist` fills, under a key field the
form does not `required()`, can be saved blank: a blank component is incomplete
to both key rules, so the flush inserts the `''` row. The
reload then keys the item through `RecordKey::of()`, which refuses the blank
component with a `LogicException` after the commit — so the save's own response
is the 500, where without the reload it would answer *Saved* and the next
request's fill would be the 500, the row written either way. `required()` on the
key field is the application's guard. `EditAction`, `CreateAction` and
the option modals unmount on success or refill for create-another, so none needs
the reload. Pinned by
[`BelongsToRecordReloadTest`](../../../packages/schemas/tests/Unit/Concerns/BelongsToRecordReloadTest.php)
and, through the page, by panels'
[`EditRecordRelationshipsTest`](../../../packages/panels/tests/Integration/Resources/Pages/EditRecordRelationshipsTest.php),
a repeater item's and a singular relationship's reset included.

### The `exists`-guarded members are not ported

Every one of Filament's relationship guards reduces to `$this->getRecord()
?->exists` — an Eloquent flag meaning "this model has a database row". The
save-side pair adds `saveRelationshipsWhenDisabled()` / `WhenHidden()` on top of
it, whose whole job is to re-admit a component the `exists`/disabled/hidden
matrix would otherwise skip. None of them is ported, and the reason is that the
predicate has no Doctrine counterpart worth reproducing.

Doctrine's nearest probe is `UnitOfWork::getEntityState()`, which answers
`STATE_NEW` / `MANAGED` / `DETACHED` / `REMOVED` — but the answer does not carry
the meaning `exists` carries. A `MANAGED` entity that was `persist()`ed a moment
ago and not yet flushed has no row; an entity built with `new` and about to be
persisted in the same request has no row and will have one before the
transaction ends. Filament needs the guard because its walk runs from
`getState()` on create and edit alike, and on a create the parent has no primary
key yet: the guard turns that first run into a no-op, and the create page runs
the whole walk again once the row exists. Our hooks run at map time against an
in-memory graph, where a child assigned to an unflushed parent is exactly what
Doctrine's commit-order sort is for. Ported here, the guard would be a probe
whose true answer — "no row yet, but there will be one before the transaction
ends" — it has no way to express, refusing writes that are correct.

The consequence to state plainly: a hook fires whenever its path is present in
the dehydrated state, on an insert as readily as on an update. Disabled and
hidden components are handled where they already are — the dehydration walk
prunes a hidden subtree, so its path never reaches the leaf loop, and that one
mechanism replaces both `*WhenDisabled`/`*WhenHidden` opt-ins. Revisit if a real
shape needs "only on update", which today reads as a condition inside the hook.

### A missing singular intermediate is constructed from its declared type

**This overturns a decision recorded in this file.** It previously read
*"Intermediate objects are never constructed"*, on the grounds that type-directed
construction is right for a value object and silently wrong for an association
that should be looked up. That rejection no longer holds for **singular**
branches, on three grounds — the third is the one that changed:

- **The dangerous case is excluded elsewhere now.** The shape where a wrong
  construction becomes duplicate INSERTs is a *collection*, and a collection is
  [refused outright](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused)
  before it can reach the mapper — the mainstream Doctrine declaration included,
  since the refusal reads both the adder-derived type and the initialized
  instance already on the entity. The original argument rejected construction to
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
  that surface is [`Select::relationship()`](../forms/select.md)'s either way,
  which the walk never sees.

Construction needs no declaration of its own: `record(SomeClass::class)` plus
nested state paths is the entire user-facing flow, and the demo's vivifying
`Article::getAuthor(): AuthorProfile` workaround — the one that produced an empty
`AuthorProfile` on every save — was deleted rather than
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

A branch that [owns its record subtree](#a-component-can-own-its-whole-record-subtree)
takes three narrower arms of the same pass, all following from its having no
mapped leaf beneath it. Presence is read at the branch's **own** path rather than
through a leaf it does not have; the blank test short-circuits to the value arm;
and nulling records the branch path exactly as well as by prefix, because an
owner *is* a mapped path where an ordinary branch never is — without the exact
skip the leaf walk would immediately write the owner's wholesale value back over
the `null` the pass just wrote, and the declaration would silently do nothing.

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

It does not widen for an
[owner branch](#a-component-can-own-its-whole-record-subtree), which is the one
shape where the two bags could differ most: the value arm reads the branch's own
slice, so the raw uuid-keyed bag at validation and the positional one at mapping
answer the same question with the same verdict.

It does widen, by one shape, for a repeater declaring
[value-object items](../forms/repeater.md#the-item-cast-fires-at-the-repeaters-two-pipeline-boundaries-not-in-the-generic-seam):
the built-in test stays verdict-stable there for the same reason (both bags are
arrays, both empty or both not), but a **closure** condition on such a repeater
reads uuid-keyed value *arrays* at validation and a positional list of
*constructed objects* at mapping — so a closure reaching into an item
(`$state[0]['title']`) is right about one bag and fatal on the other.

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
mapping metadata, which this walk is deliberately not handed: `schemas` does own
[`AssociationReader`](association-reader.md), which holds an `EntityManager` and
reads `ClassMetadata`, but only the component tier reaches it — the record walk
in [`Concerns/BelongsToRecord`](../../../packages/schemas/src/Concerns/BelongsToRecord.php)
takes no reader and works entirely off the declared PHP types
[`RecordTypeReader`](record-type-reader.md) supplies. Faking it — writing a fresh
empty embeddable instead of null — would only rename the same outcome. A branch that genuinely has to be
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
produces, and the schema stays in authority over what maps. That was checked, not
assumed.

**Surviving, and decisive on its own.** `deep_object_to_populate` updates
singular children but **not arrays of objects** — it produces a fresh instance
per item. For Doctrine that is not a mapping error but a duplicate INSERT at
flush, plus deletion of the originals under `orphanRemoval`; measured on a
two-tag `Post`, re-saving after renaming one tag and deleting the other yielded
one brand-new `Tag` row and reused neither original. That is precisely the
collection shape the `Repeater` and its relationship mode need, and it is the shape
this mapper [refuses outright](property-accessor.md#a-missing-singular-intermediate-is-constructed-an-object-collection-is-refused)
rather than getting wrong. Since identity matching is a state-shape decision
the repeater has to make anyway, adopting Serializer would buy the singular half
we already have and leave the hard half untouched — at the cost of a second
exception vocabulary. A lesser point still holds: `normalize($record)` is
over-broad against the read-scoping stance and initializes Doctrine proxies
wholesale.

Serializer remains a candidate for leaf-level *casts* (dates, enums, UUIDs),
where its normalizer half fits the existing `StateCast` seam. The whole verdict
reverses if `deep_object_to_populate` ever grows identity-aware collection
population.

### `getRecordInstance()` constructs with `new`, not the container

Filament's `getModelInstance()` ends in `app($this->model)`, Laravel's
container resolving an Eloquent model class. Symfony's container cannot make an
unbound class, and an entity is not a service, so Sylament builds the class
itself, through `PropertyAccessor::constructRecord()`.

For a constructor with no required parameter that is `new`. A
constructor that requires arguments gets a `LogicException` naming the class
and the way out: *Cannot map a new record onto the schema:
App\Entity\Membership requires constructor arguments …*. PHP's own
`ArgumentCountError` used to surface from this file. `CreateAction`'s
relationship arm builds its record the same way. A class-string naming a class
with a non-public constructor, or no class at all, surfaces as
`ReflectionException`, where `new` threw PHP's `Error`.

The resource create page builds its record itself:
`CreateRecord::makeRecord(array $data): object`, called once with the validated
and mutated form data, answers the record the form is then mapped onto, so a
record whose constructor takes arguments is built there; its default builds the
resource's record class through the same `constructRecord()`, with advice naming
`makeRecord()` ([the page](../panels/README.md#createrecord)). The schema tier's
*"through `record()`"* advice therefore no longer reaches the page, whose form is
bound to a class-string and could not take it; nor `CreateAction`, whose plain
arm names `using()` as its relationship arm already did.

This is the same boundary as
[`Component::newInstance()`](../support/component.md)'s fallback: the container
builds what was registered, and a record is built without arguments or refused
by name.

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

A binding here is read in the same frame as the path it is looked up by, which is
what [the nearest-frame pair](#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one)
exists for: forms' derived requirement and the date picker's write-side class
both take class and path from the nearest binding
([the frame rule](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types)),
so a component-tier binding serves those as well as closure injection and
mapping-independent reads. The map itself stays root-framed.

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
