---
description: The prebuilt header action that points picked records at a one-to-many relation-scoped table's owner through a record select.
ports:
  - vendor/filament/actions/src/AssociateAction.php
  - vendor/filament/actions/src/Concerns/CanCustomizeProcess.php
  - vendor/filament/actions/resources/lang/en/associate.php
owns:
  - packages/actions/src/AssociateAction.php
---
# AssociateAction (actions)

[`Sylament\Actions\AssociateAction`](../../../packages/actions/src/AssociateAction.php) is the prebuilt header action of a **one-to-many** relation-scoped table: a modal carrying one record select over every target record that is *not* this owner's, which on submit points each picked record at the owner and flushes.
It is the one-to-many half of the pair Filament ships; [`AttachAction`](attach-action.md) is the many-to-many half, and the two are separate classes here as they are there.
The record select, its options and the submit's re-resolution are the same code in both classes, written up once in attach-action.md and linked from the rows below.

The relationship it writes is the **table's**: [`Table::relationship()`](../tables/table.md) is the only thing that supplies one, and the action refuses with a `LogicException` naming that when a host mounts it without one.

[`AssociateActionTest`](../../../packages/actions/tests/Unit/AssociateActionTest.php) covers the class alone; the end-to-end cases live in the tables suite's [`AssociateDissociateActionsTest`](../../../packages/tables/tests/Integration/AssociateDissociateActionsTest.php), because only a table host supplies a relationship and the actions test kernel registers neither tables nor forms.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/AssociateAction.php) (`'associate'`) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | — |
| [`setUp()` chrome](../../../packages/actions/src/AssociateAction.php) (label, submit label, `modalWidth('lg')`, success title, `defaultColor('gray')`) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`modalHeading()`](../../../packages/actions/src/AssociateAction.php) (`"Associate {title-case record label}"`) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`extraModalFooterActions()`](../../../packages/actions/src/AssociateAction.php) (the `associateAnother` submit) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [attach-action.md](attach-action.md#the-another-arm-refills-the-schema-and-halts) |
| [`schema()`](../../../packages/actions/src/AssociateAction.php) (the record select alone) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [attach-action.md](attach-action.md#the-record-select-is-a-forms-select-which-is-why-actions-requires-forms) |
| [The `action()` body](../../../packages/actions/src/AssociateAction.php) (resolve, bind, `process()` per record, the "another" arm) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [attach-action.md](attach-action.md#the-submitted-key-is-re-resolved-through-the-candidate-query) · [diverges](#the-vendors-raw-ismultiple-read-is-not-copied) |
| [`recordSelect()` / `recordSelectOptionsQuery()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [attach-action.md](attach-action.md#recordselectoptionsquery-is-evaluated-with-query-and-search) |
| [`associateAnother()` / `canAssociateAnother()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | — |
| [`preloadRecordSelect()` / `isRecordSelectPreloaded()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [attach-action.md](attach-action.md#options-is-installed-only-on-the-preloaded-arm) |
| [`multiple()` / `isMultiple()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [diverges](#the-vendors-raw-ismultiple-read-is-not-copied) |
| [`recordSelectSearchColumns()` / `getRecordSelectSearchColumns()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [attach-action.md](attach-action.md#the-search-columns-are-validated-against-the-mapping) |
| [`forceSearchCaseInsensitive()` / `isSearchForcedCaseInsensitive()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [attach-action.md](attach-action.md#the-case-insensitive-flag-rides-searchconstraintlike) |
| [`getRecordSelect()`](../../../packages/actions/src/AssociateAction.php) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | done | — | [attach-action.md](attach-action.md#the-title-fast-path-projects-the-attribute) · [attach-action.md](attach-action.md#the-title-ordering-applies-to-both-arms-not-only-the-projected-one) · [attach-action.md](attach-action.md#searchable-takes-a-re-keyed-list-not-the-raw-column-array) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| `getRelationCountHash()` self-join, `whereDoesntHave()` | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | skipped | — | [diverges](#the-candidate-query-needs-no-inverse-relationship) |
| `getInverseRelationshipFor()` in the write | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | skipped | — | [diverges](#the-candidate-query-needs-no-inverse-relationship) |
| `whereNotMorphedTo()`, `HasOneOrManyThrough` | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | skipped | — | [diverges](#morphs-hasoneormanythrough-and-the-pivot-arm-are-not-ported) |
| `disableAssociateAnother()` (`@deprecated` for `associateAnother(false)`) | [`AssociateAction.php`](../../../vendor/filament/actions/src/AssociateAction.php) | skipped | — | [renames.md](../renames.md#renames) |

## Divergences

### The vendor's raw `$isMultiple` read is not copied

Filament's `action()` body loops with `$this->isMultiple ? $record : [$record]`, reading the **property**, not `isMultiple()`.
A `multiple(fn (): bool => false)` therefore reads as truthy there (a `Closure` is an object) while `getRecordSelect()` evaluates the same property properly, so the modal renders a single select whose one key is then iterated as if it were a list.
The port's body never reads the flag: it iterates the records `findRecordsToAssociate()` resolved from the submission, which it wraps into a list whatever its shape, and only `getRecordSelect()` reads `isMultiple()`.
For the literal `true`/`false` arguments consumers write, the two readings agree.

### The candidate query needs no inverse relationship

Filament excludes already-related records with `whereDoesntHave($table->getInverseRelationship(), …)`: it must name the target's inverse `BelongsTo` to do it, and it works around a self-referencing relationship with `getRelationCountHash()`.
The port asks [`AssociationReader::createRelationshipCandidateQuery()`](../schemas/association-reader.md) instead, which for an owner keyed by one field reads the mapping's `mappedBy` off Doctrine metadata and writes `WHERE record.<mappedBy> IS NULL OR record.<mappedBy> <> :sylament_relationship_owner`; the `IS NULL` arm matters, because SQL's `<>` alone drops the unowned rows.
An owner keyed by several fields gets the negated subselect [`AttachAction`'s query](attach-action.md#the-candidate-query-needs-no-inverse-relationship) uses.
So the whole inverse family (`inverseRelationship()`, `getInverseRelationship()`, `getInverseRelationshipFor()`) has nothing to do here and is [not ported](../renames.md#renames), and the write goes through the reader's `attach()` rather than through a `BelongsTo` object.

The rule this inherits from Filament: a record that belongs to **another** owner is a candidate and gets re-pointed.
Associating is a move, not only an adoption of orphans.

### Morphs, `HasOneOrManyThrough` and the pivot arm are not ported

`MorphMany`'s `whereNotMorphedTo()` branch, `HasOneOrManyThrough` and the relation-count-hash self-join are Eloquent mechanics with no Doctrine referent: Doctrine's ORM maps no polymorphic association, and a "through" relation is a join the caller writes.
Doctrine has no pivot model either, so the pivot columns and accessors Filament's attach family carries have no counterpart; a join table with attributes is an entity of its own here.

### The strings are literals, not translation keys

Filament resolves every label through `__('filament-actions::associate.…')`.
The port hardcodes the six `en` strings: `Associate`, `Associate :label`, `Record`, `Associate`, `Associate & associate another` and `Associated`, the same call every other prebuilt action's strings got.
When translation lands they become the same keys.
