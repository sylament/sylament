---
description: The component tier of BelongsToRecord on the base Component, with its record binding, record-relative paths and per-component relationship hooks.
ports:
  - vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php
owns:
  - packages/schemas/src/Components/Concerns/BelongsToRecord.php
---
# Component-tier BelongsToRecord (schemas)

[`Sylament\Schemas\Components\Concerns\BelongsToRecord`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) is composed by the base [`Component`](component.md) and ports Filament's component-tier `BelongsToModel`.
The root tier, `Schema`'s trait of the same name, and the mapping edges both tiers feed are in [belongs-to-record.md](belongs-to-record.md).

## Surface — Component

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`record()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`model()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-component-tier-gains-record) · [semantics](belongs-to-record.md#the-root-tier-memoizes-a-closure-record-the-component-tier-re-evaluates-it) |
| [`getRecordClass()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getModel()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](schema.md#the-record-type-probe-is-instanceof-not-a-model-gate) |
| [`getRecord(bool $withContainerRecord = true)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getRecord()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](schema.md#the-record-type-probe-is-instanceof-not-a-model-gate) |
| [`getRecordInstance()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getModelInstance()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](belongs-to-record.md#getrecordinstance-constructs-with-new-not-the-container) |
| [`getRecordStatePath()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it) |
| [`hasOwnRecord()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) |
| [`getNearestRecordClass()` / `getNearestRecordStatePath()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) |
| [`ownsRecordSubtree()`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) | — | done | — | [diverges](belongs-to-record.md#a-component-can-own-its-whole-record-subtree) · [semantics](belongs-to-record.md#two-components-cannot-both-map-one-record-path-wholesale) |
| [`routesNestedRelationshipHookAt()`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) | — | done | — | [semantics](belongs-to-record.md#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) |
| [`loadStateFromRelationshipsUsing()` / `hasLoadStateFromRelationships()` / `loadStateFromRelationships(object $record)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`loadStateFromRelationshipsUsing()` / `loadStateFromRelationships()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) · [diverges](#the-exists-guarded-members-are-not-ported) · [semantics](belongs-to-record.md#a-hook-the-record-path-map-does-not-carry-is-refused) |
| [`saveRelationshipsUsing()` / `hasSaveRelationships()` / `saveRelationships(object $record, mixed $state)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`saveRelationshipsUsing()` / `saveRelationships()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) · [diverges](#the-exists-guarded-members-are-not-ported) · [semantics](belongs-to-record.md#a-hook-the-record-path-map-does-not-carry-is-refused) · [semantics](has-nullable-branch.md#nullwhenblank-cannot-compose-with-a-save-hook) |
| Nested hook delegation beneath a subtree owner | [`Schema::saveRelationships()`](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) tree walk | done | — | [semantics](belongs-to-record.md#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) |
| [`setAssociationReader()` / `getAssociationReader()`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php), composed from `CanAccessAssociations` | — | done | — | [semantics](association-reader.md#the-accessor-trait-is-shared-with-the-table-and-action-tiers) |
| `saveRelationshipsBeforeChildrenUsing()` / `saveRelationshipsBeforeChildren()` | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | skipped | — | [diverges](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |
| `saveRelationshipsWhenDisabled()` / `saveRelationshipsWhenHidden()` / their `should*` readers | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | skipped | — | [diverges](#the-exists-guarded-members-are-not-ported) |
| `castStateAfterLoadingFromRelationships()` | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | skipped | — | [diverges](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |

## Semantics

### `getRecordStatePath()` is the one definition of record-relative, and both walks must share it

`getRecordPathMap()` and `getNullableBranches()` both key by a component's record-relative path, and `getData()` compares them with `str_starts_with()` to decide which leaves sit beneath a nulled branch and, through the branch pass's [presence guard](has-nullable-branch.md#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled), whether a branch is in the bag at all.
That comparison is only meaningful if both sides compute the prefix the same way: two definitions of "record-relative" would not error, they would silently skip the wrong leaves.
So `getRecordPathMap()` takes its keys from `getRecordStatePath()` rather than stripping the prefix inline, and that is mandatory, not a tidy-up.

A third walk shares the definition and is easy to forget: forms' derived requirement climbs the ancestor chain asking the same question at validation time.
It is gated on `getRecordStatePath() !== null` for exactly this reason: [a walk that answered differently made one field's requirement depend on another](has-nullable-branch.md#a-nullable-branch-with-no-state-path-is-refused-not-ignored).

Two guards inside it look redundant and are not.
`hasStatePath()` is checked first because `getStatePath()` on a path-less component under a root `statePath('data')` returns `'data'`, the root's own path verbatim, which is non-blank and would make that component look like a branch declared at the record root, so `getData()` would write `setOnRecord($record, 'data', null)`.
The `filled($path)` check below it still earns its place, because `hasStatePath()` reads the raw path while `getStatePath()` also strips control characters, so a path of nothing but a control character passes the first guard and, when no container carries a state path, comes out empty from the second.
Under a root `statePath('data')` the same path comes out of `getStatePath()` as `'data'`, which neither guard catches, so `getRecordStatePath()` returns `'data'`.

The final "path does not sit under the prefix" fallback is unreachable by ordinary construction, because absolute paths are built from the container chain, so a component's path is always under its root's.
It is reachable only through a memoized absolute path that predates a later `statePath()` change, and its test constructs that premise explicitly.

### The nearest record frame is the first node above a component that binds one

`getRecordStatePath()` is root-framed: it strips the **root** container's state path, so its result indexes the root record.
That is right for the map, which is built once at the root, and wrong for a component asking what its own path indexes once a subtree is bound to a different record, where the path segment naming the item (`record-4.author`) belongs to no property of anything.
`getNearestRecordClass()` and `getNearestRecordStatePath()` are the frame-relative twins: the class of the nearest node above the component that binds a record of its own (container schemas and the components they hang off alike, **the component itself excluded**), and the component's path with that node's stripped.
Both come off one private walk, so the class and the path can never name different frames.
With nothing between, the frame is the root container and the path half equals `getRecordStatePath()`; the two diverge only where something in between binds.

The probe the walk asks is `hasOwnRecord()`, on both tiers, and it reads the **unevaluated** property.
Neither existing getter can stand in: `getRecord()` returns `null` for a class-string binding, which is exactly the binding a subtree stamp uses, and `getRecordClass()` walks the chain, so it answers the same for a node that binds and one that inherits.
Reading the raw property is also what keeps the walk free of side effects: the root tier [memoizes what it evaluates](belongs-to-record.md#the-root-tier-memoizes-a-closure-record-the-component-tier-re-evaluates-it), and a frame walk that evaluated every node it passed would freeze closure bindings it merely looked at.
The trade runs the other way too: those getters memoize the *evaluated* value back onto the property, so a closure binding that resolves `null` answers the probe `true` before any getter runs and `false` after, two different frames across calls.
The shipped stamps floor at the target class-string, so the flip needs a stamp that resolves nothing under a bound root: a repeater whose `relationship()` names an association the mapping cannot describe answers `null` from the soft floor while the root still binds.
The flip is benign there: that tree's fill and save edges refuse the unresolvable association loudly, so no derivation reading either frame survives to see it.

The path half returns `null` twice over: for a component carrying no state path of its own, and for one whose path does not nest under the frame's.
That second arm is where it parts company with `getRecordStatePath()`, which returns the whole path unstripped in the same situation.
A path that does not nest under the frame's is not a path into the frame's record, and handing it to a type reader would answer about the wrong object; `null` is the caller's signal to fall back to whatever it did before a frame was involved.

## Divergences

### The `exists`-guarded members are not ported

Every one of Filament's relationship hooks is guarded by `$this->getRecord()?->exists`, an Eloquent flag meaning "this model has a database row".
The save-side pair adds `saveRelationshipsWhenDisabled()` / `saveRelationshipsWhenHidden()` on top of it, whose whole job is to re-admit a component the `exists`/disabled/hidden matrix would otherwise skip.
None of them is ported, because the predicate has no Doctrine counterpart worth reproducing.

Doctrine's nearest probe is `UnitOfWork::getEntityState()`, which answers `STATE_NEW` / `MANAGED` / `DETACHED` / `REMOVED`, but the answer does not carry the meaning `exists` carries.
A `MANAGED` entity that was `persist()`ed a moment ago and not yet flushed has no row; an entity built with `new` and about to be persisted in the same request has no row and will have one before the transaction ends.
Filament needs the guard because its walk runs from `getState()` on create and edit alike, and on a create the parent has no primary key yet: the guard turns that first run into a no-op, and the create page runs the whole walk again once the row exists.
Sylament's hooks run at map time against an in-memory graph, where a child assigned to an unflushed parent is exactly what Doctrine's commit-order sort is for.
Ported here, the guard would be a probe whose true answer, "no row yet, but there will be one before the transaction ends", it has no way to express, refusing writes that are correct.

The consequence: a hook fires whenever its path is present in the dehydrated state, on an insert as readily as on an update.
Hidden components are handled by the dehydration walk, which prunes a hidden subtree that is not dehydrated when hidden, so its path never reaches the leaf loop; `dehydrated()` and `dehydratedWhenHidden()` take the place of both `*WhenDisabled`/`*WhenHidden` opt-ins.
A real shape that needs "only on update" would reopen this; until then it is a condition inside the hook.

### The component tier gains `record()`

Filament's component tier has `model()` and no `record()`, so a component-level binding can only be written in the vocabulary the rest of Filament abandoned.
With the [collapse](belongs-to-record.md#the-record-vocabulary-collapses-onto-record) both tiers carry the same setter name and the same payload set, so `Component::record(...)` overriding a container binding reads the same as the root binding.
`record()` on a component is otherwise Filament's behaviour exactly, container fallback included.

A binding here is read in the same frame as the path it is looked up by, which is what [the nearest-frame pair](#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) exists for: forms' derived requirement, the date picker's write-side class and the repeater all take class and path from the nearest binding ([the frame rule](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types)), so a component-tier binding serves those as well as closure injection and mapping-independent reads.
The map itself stays root-framed.
