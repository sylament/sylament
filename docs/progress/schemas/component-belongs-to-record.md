---
description: 'The component tier of `BelongsToRecord` on the base `Component`: its record binding, record-relative paths and per-component relationship hooks (draft)'
ports:
  - vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php
  - vendor/filament/schemas/src/Concerns/BelongsToModel.php
  - vendor/filament/forms/src/Components/Repeater.php
owns:
  - packages/schemas/src/Components/Concerns/BelongsToRecord.php
---
# Component-tier BelongsToRecord (schemas)

## Surface — Component

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`record()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`model()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](#the-component-tier-gains-record) · [semantics](belongs-to-record.md#the-root-tier-memoizes-a-closure-record-the-component-tier-re-evaluates-it) |
| [`getRecordClass()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getModel()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](belongs-to-record.md#the-record-type-probe-is-instanceof-not-a-model-gate) |
| [`getRecord(bool $withContainerRecord = true)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getRecord()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](belongs-to-record.md#the-record-type-probe-is-instanceof-not-a-model-gate) |
| [`getRecordInstance()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`getModelInstance()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](belongs-to-record.md#getrecordinstance-constructs-with-new-not-the-container) |
| [`getRecordStatePath()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](#getrecordstatepath-is-the-one-definition-of-record-relative-and-both-walks-must-share-it) |
| [`hasOwnRecord()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) |
| [`getNearestRecordClass()` / `getNearestRecordStatePath()`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | — | done | — | [semantics](belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) |
| [`ownsRecordSubtree()`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) | — | done | — | [diverges](belongs-to-record.md#a-component-can-own-its-whole-record-subtree) · [semantics](belongs-to-record.md#two-components-cannot-both-map-one-record-path-wholesale) |
| [`routesNestedRelationshipHookAt()`](../../../packages/schemas/src/Components/Concerns/HasChildComponents.php) | — | done | — | [semantics](belongs-to-record.md#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) |
| [`loadStateFromRelationshipsUsing()` / `hasLoadStateFromRelationships()` / `loadStateFromRelationships(object $record)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`loadStateFromRelationshipsUsing()` / `loadStateFromRelationships()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) · [diverges](#the-exists-guarded-members-are-not-ported) · [semantics](belongs-to-record.md#a-hook-the-record-path-map-does-not-carry-is-refused) |
| [`saveRelationshipsUsing()` / `hasSaveRelationships()` / `saveRelationships(object $record, mixed $state)`](../../../packages/schemas/src/Components/Concerns/BelongsToRecord.php) | [`saveRelationshipsUsing()` / `saveRelationships()`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | done | — | [diverges](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) · [diverges](#the-exists-guarded-members-are-not-ported) · [semantics](belongs-to-record.md#a-hook-the-record-path-map-does-not-carry-is-refused) · [semantics](has-nullable-branch.md#nullwhenblank-cannot-compose-with-a-save-hook) |
| Nested hook delegation beneath a subtree owner | [`Schema::saveRelationships()`'s tree walk](../../../vendor/filament/schemas/src/Concerns/BelongsToModel.php) · [`Repeater::saveToRelationship()`'s `$item->model($record)->saveRelationships()`](../../../vendor/filament/forms/src/Components/Repeater.php) | done | — | [semantics](belongs-to-record.md#a-hook-a-subtree-owner-routes-is-delegated-rather-than-refused) |
| [`setAssociationReader()` / `getAssociationReader()`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php) — composed from `CanAccessAssociations` | — | done | — | [semantics](#every-component-reaches-the-association-reader-through-a-required-setter) |
| `saveRelationshipsBeforeChildrenUsing()` / `saveRelationshipsBeforeChildren()` | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | skipped | — | [diverges](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |
| `saveRelationshipsWhenDisabled()` / `saveRelationshipsWhenHidden()` / their `should*` readers | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | skipped | — | [diverges](#the-exists-guarded-members-are-not-ported) |
| `castStateAfterLoadingFromRelationships()` | [`Components/Concerns/BelongsToModel.php`](../../../vendor/filament/schemas/src/Components/Concerns/BelongsToModel.php) | skipped | — | [diverges](belongs-to-record.md#the-relationship-hooks-are-consulted-by-the-two-mapping-edges-not-by-a-schema-level-walk) |

## Semantics

### Every component reaches the association reader through a `#[Required]` setter

The two members are [`CanAccessAssociations`](association-reader.md#the-accessor-trait-is-shared-with-the-table-and-action-tiers), composed here — the trait is shared with `Table` and `Action`, which need the reader for the relationship seam and are not components.
The note that used to sit here lives with it.

### `getRecordStatePath()` is the one definition of record-relative, and both walks must share it

`getRecordPathMap()` and `getNullableBranches()` both key by a component's record-relative path, and `getData()` then compares them with `str_starts_with()` to decide which leaves sit beneath a nulled branch — and, since the branch pass gained its [presence guard](has-nullable-branch.md#a-branch-the-bag-no-longer-carries-is-skipped-not-nulled), to decide whether a branch is in the bag at all.
That comparison is only meaningful if both sides computed the prefix the same way — two definitions of "record-relative" would not error, they would silently skip the wrong leaves.
So `getRecordPathMap()` was converged onto `getRecordStatePath()` rather than keeping its own inline prefix strip; that convergence is mandatory, not a tidy-up.

A **third** walk shares the definition and is easy to forget: forms' derived requirement climbs the ancestor chain asking the same question at validation time.
It is gated on `getRecordStatePath() !== null` for exactly the reason this section exists — [a walk that answered differently made one field's requirement depend on another](has-nullable-branch.md#a-nullable-branch-with-no-state-path-is-refused-not-ignored).

Two guards inside it look redundant and are not.
`hasStatePath()` is checked first because `getStatePath()` on a path-less component under a root `statePath('data')` returns `'data'` — the root's own path, verbatim — which is non-blank and would make that component look like a branch declared at the record root, so `getData()` would write `setOnRecord($record, 'data', null)`.
The `filled($path)` check below it then still earns its place, because `hasStatePath()` reads the **raw** path while `getStatePath()` also strips control characters, so a path of nothing but a control character passes the first guard and comes out empty from the second.

The final "path does not sit under the prefix" fallback is structurally unreachable by ordinary construction — absolute paths are built from the container chain, so a component's path is always under its root's — and is reachable only through a memoized absolute path that predates a later `statePath()` change.
Its test constructs that premise explicitly rather than implying the branch is ordinarily reachable; the sibling `getRecordPathMap()` carried the identical fallback before the convergence.

## Divergences

### The `exists`-guarded members are not ported

Every one of Filament's relationship guards reduces to `$this->getRecord() ?->exists` — an Eloquent flag meaning "this model has a database row".
The save-side pair adds `saveRelationshipsWhenDisabled()` / `WhenHidden()` on top of it, whose whole job is to re-admit a component the `exists`/disabled/hidden matrix would otherwise skip.
None of them is ported, and the reason is that the predicate has no Doctrine counterpart worth reproducing.

Doctrine's nearest probe is `UnitOfWork::getEntityState()`, which answers `STATE_NEW` / `MANAGED` / `DETACHED` / `REMOVED` — but the answer does not carry the meaning `exists` carries.
A `MANAGED` entity that was `persist()`ed a moment ago and not yet flushed has no row; an entity built with `new` and about to be persisted in the same request has no row and will have one before the transaction ends.
Filament needs the guard because its walk runs from `getState()` on create and edit alike, and on a create the parent has no primary key yet: the guard turns that first run into a no-op, and the create page runs the whole walk again once the row exists.
Our hooks run at map time against an in-memory graph, where a child assigned to an unflushed parent is exactly what Doctrine's commit-order sort is for.
Ported here, the guard would be a probe whose true answer — "no row yet, but there will be one before the transaction ends" — it has no way to express, refusing writes that are correct.

The consequence to state plainly: a hook fires whenever its path is present in the dehydrated state, on an insert as readily as on an update.
Disabled and hidden components are handled where they already are — the dehydration walk prunes a hidden subtree, so its path never reaches the leaf loop, and that one mechanism replaces both `*WhenDisabled`/`*WhenHidden` opt-ins.
Revisit if a real shape needs "only on update", which today reads as a condition inside the hook.

### The component tier gains `record()`

Filament's component tier has `model()` and no `record()`, so a component-level binding can only be written in the vocabulary the rest of Filament abandoned.
With the [collapse](belongs-to-record.md#the-record-vocabulary-collapses-onto-record) both tiers carry the same setter name and the same payload set, which is what makes `Component::record(...)` overriding a container binding a one-line, symmetric thing to read.
`record()` on a component is otherwise Filament's behaviour exactly, container fallback included.

A binding here is read in the same frame as the path it is looked up by, which is what [the nearest-frame pair](belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one) exists for: forms' derived requirement and the date picker's write-side class both take class and path from the nearest binding ([the frame rule](../forms/can-be-validated.md#requirement-is-derived-from-the-records-write-side-types)), so a component-tier binding serves those as well as closure injection and mapping-independent reads.
The map itself stays root-framed.
