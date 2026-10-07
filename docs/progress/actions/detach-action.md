---
description: The prebuilt many-to-many detach and its bulk sibling DetachBulkAction, undoing records' membership of a relation-scoped table's relationship.
ports:
  - vendor/filament/actions/src/DetachAction.php
  - vendor/filament/actions/src/DetachBulkAction.php
  - vendor/filament/actions/src/Concerns/CanCustomizeProcess.php
  - vendor/filament/actions/resources/lang/en/detach.php
owns:
  - packages/actions/src/DetachAction.php
  - packages/actions/src/DetachBulkAction.php
---
# DetachAction (actions)

[`Sylament\Actions\DetachAction`](../../../packages/actions/src/DetachAction.php) is the prebuilt many-to-many detach: a danger-coloured cross trigger that opens a confirmation modal and, on confirm, undoes the bound record's membership of the table's relationship and flushes.
It is the undo of [`AttachAction`](attach-action.md), and like it, it only makes sense on a [relation-scoped table](../tables/table.md): `Table::relationship()` names the owner and the association, and without one the action refuses.
[`DetachBulkAction`](../../../packages/actions/src/DetachBulkAction.php) is the same detach over a table's selection: it extends [`BulkAction`](bulk-action.md), confirms, detaches every selected record and deselects afterwards.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DetachAction.php) (`'detach'`) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | done | — | — |
| [`setUp()` chrome — label, modal heading (`"Detach {record title}"`), submit label, success title, `defaultColor('danger')`, `requiresConfirmation()`](../../../packages/actions/src/DetachAction.php) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | done | — | [semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb) · [diverges](#the-strings-are-literals-not-translation-keys) |
| [`icon('heroicons:x-mark-20-solid')` + `modalIcon('heroicons:x-mark')`](../../../packages/actions/src/DetachAction.php) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) (`FilamentIcon::resolve(…) ?? Heroicon::XMark`) | done | — | [diverges](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection) |
| [The `action()` body — `detach()` then one `flush()`, then `success()`](../../../packages/actions/src/DetachAction.php) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | done | — | [semantics](#the-write-goes-through-the-association-reader-on-the-owning-side) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| The `allowsDuplicates()` branch (`$record->getRelationValue($pivotAccessor)->delete()`) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | skipped | — | [diverges](#the-duplicates-arm-is-not-ported) |

## Surface — DetachBulkAction

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DetachBulkAction.php) (`'detach'`) | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | done | — | — |
| [`setUp()` chrome](../../../packages/actions/src/DetachBulkAction.php) — label, modal heading, submit label, success title, colour, both icons, confirmation, deselect | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | done | — | [semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb) · [diverges](#the-strings-are-literals-not-translation-keys) · [diverges](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection) |
| [`failureNotificationTitle()` — the counting closure](../../../packages/actions/src/DetachBulkAction.php) | — | done | — | [diverges](#the-counting-failure-title-is-an-addition-filament-has-neither-of) |
| [The fetching arm — `detach()` per hydrated record, one `flush()`](../../../packages/actions/src/DetachBulkAction.php) | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | done | — | [diverges](#the-bulk-detach-is-atomic-where-filaments-fetching-arm-is-per-record) |
| [The `fetchSelectedRecords(false)` arm — `getReference()` per key, then the same](../../../packages/actions/src/DetachBulkAction.php) | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) (`$relationship->detach($records)`) | done | — | [diverges](#the-no-fetch-arm-detaches-through-references-not-a-join-table-delete) · [semantics](#a-stale-key-is-a-danger-toast-except-on-an-owning-side-many-to-many-over-a-scalar-identifier) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| [The one-to-many refusal](../../../packages/actions/src/DetachBulkAction.php) raised before the `try` | — | in progress | — | [detail](#a-bulk-detach-over-a-one-to-many-the-reader-refuses-fails-as-a-danger-toast) |
| Reporting the swallowed exception | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) (`report($exception)`) | blocked | a logger on `Action` | [delete-action.md](delete-action.md#the-swallowed-exception-has-nowhere-to-go-yet) |
| The pivot-accessor delete in the fetching arm | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | skipped | — | [diverges](#the-duplicates-arm-is-not-ported) |

## Details

### A bulk detach over a one-to-many the reader refuses fails as a danger toast

`DetachBulkAction` raises no relationship refusal up front: `detach()` runs `assertRelationshipAcceptsDetach()` as the first statement of its one-to-many arm, so a bulk detach over a mapping the reader refuses fails on its **first** record, and it fails **inside** the action's `try`, which turns a developer error into the danger toast a stale key produces.
`DissociateBulkAction` raises the same refusal up front, outside both `try` blocks, where it propagates.
Calling `assertRelationshipAcceptsDetach()` before the `try`, as the twin does, closes it.

## Semantics

### The headings interpolate, and fall back to the bare verb

`"Detach {$this->getRecordTitle()}"` reads the action's record chain (custom title, then the **table**, then the host hook, then the record label), so a table declaring `recordTitleAttribute('name')` is what makes a row's modal read *Detach Rock* rather than *Detach genre*.
With nothing bound and nothing naming a class the chain answers null, and the heading collapses to the bare `Detach`; `DetachBulkAction`'s heading does the same over `getTitleCasePluralRecordLabel()`, collapsing to `Detach selected`.
Same shape as [`DeleteAction`](delete-action.md#the-headings-interpolate-and-fall-back-to-the-bare-verb).

### The write goes through the association reader, on the owning side

[`AssociationReader::detach()`](../schemas/association-reader.md) does the removal, and it always writes the side Doctrine persists: a many-to-many removes from the owner's collection, or from the *target's* when the owner holds the inverse side, because an inverse-only write emits no SQL at all.
The removal runs through the domain's own remover (Symfony's adder/remover diff over the whole desired list), not through `Collection::removeElement()`, which is why the contract is the one forms already state: a collection-valued association needs an adder and a remover.

A one-to-many detach is a *dissociation*, and the reader refuses it [where the mapping forbids one](../schemas/association-reader.md#dissociating-is-refused-where-the-mapping-forbids-it).
`DetachAction` does not screen for that itself: the reader's `LogicException` is the answer, and it names the fix.

### A stale key is a danger toast, except on an owning-side many-to-many over a scalar identifier

`DetachBulkAction` walks the selection before its `try`: a selection entry that is neither an entity nor a key is a host feeding the action something it never promised, so it throws `DetachBulkAction can only detach entities or their keys, got %s.` and propagates as a developer error, while each key is cast to `string`.
That is the placement [`DeleteBulkAction`](delete-action.md#both-arms-swallow-and-report-the-same-failure) follows too.
A *malformed* key never arrives from a table at all, because the selection query narrowed it through the codec before it was plucked, so what stays inside the `try` is the **stale** key: a row detached or deleted between the render and the submit.

What a stale key produces depends on the route the reference took.
On [the loading route](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route) `find()` answers null and the reader's own `LogicException` becomes the failure toast; on a one-to-many the reference route reaches the same place by another door, because nulling the mapped-by initialises the proxy and Doctrine raises `EntityNotFoundException`.
An **inverse-side** many-to-many lands there too: `detach()` reads the related record's mapped-by collection off the phantom, which initialises it.
But on an **owning-side many-to-many over a scalar identifier**, the commonest shape there is, the phantom is never initialised, because `detach()` filters the **owner's** collection by identity and never reads the phantom: the `!==` filter removes nothing, the record is counted, and the arm reports success for a row that does not exist ([the reader's `getReference()` contract](../schemas/association-reader.md#getreference-throws-where-doctrine-returns-null)).
None of these is a 500.

## Divergences

### The duplicates arm is not ported

Filament's `DetachAction` branches on `$table->allowsDuplicates()`: with duplicates allowed it deletes the pivot row the record was hydrated with, else it calls `$relationship->detach($record)`.
Filament's `DetachBulkAction` fetching arm always deletes through the pivot accessor.
Doctrine has no pivot model, and a join table's primary key is the pair, so a duplicate join row is unrepresentable and the branch has nothing to select between: the port carries only `detach()`, on both classes and both arms.
Same skip as [`AttachAction`](attach-action.md#pivot-data-duplicates-morphs-and-relationshipjoiner-are-not-ported).

### The strings are literals, not translation keys

Filament reads every string through `__('filament-actions::detach…')`.
The port hardcodes Filament's `en` values instead, for the reason [create-action.md](create-action.md#the-strings-are-literals-not-translation-keys) records: `Detach`, `Detach {label}` and `Detached`, and `Detach selected`, `Detach selected {label}` and `Detached` on the bulk class.

### The no-fetch arm detaches through references, not a join-table DELETE

Filament's no-fetch arm hands the plucked keys straight to `$relationship->detach($records)`, which is one `DELETE FROM pivot WHERE … IN (…)` and returns the affected row count.
The port cannot answer that way: **DQL cannot target a join table**, because it addresses entities and a many-to-many join table is mapped by no entity.
Quoting is not the obstacle, since `DissociateBulkAction` writes a DBAL `UPDATE` through Doctrine's own `QuoteStrategy` ([dissociate-action.md](dissociate-action.md#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns)).
What keeps the detach on references is the state beside the table: a `detach()` per reference keeps the unit of work's loaded collections and the second-level cache in step with the join rows it removes, and a join-table `DELETE` would bypass both.

So the arm builds a **reference** per selected key ([`AssociationReader::getReference()`](../schemas/association-reader.md#getreference-decodes-first-and-then-picks-its-route)) and runs the same `detach()` the fetching arm does.
That call decodes the key against the target's own mapping first and then takes one of two routes: a no-load reference for a scalar identifier of any width, and a `find()` where an identifier field is itself an association, because `getReference()` is a `TypeError` for those against a **cold** identity map.
A bulk detach is exactly where the map is cold, so without that route a derived-identity selection would fail on the first request after a page load.
The arm is reached only where a host asks for it: `fetchSelectedRecords()` defaults to **true**, so the shipped default hydrates and this key route is opt-in.
What it buys is what the flag promises: on the owning side, the selected rows are never `SELECT`ed on their own.
The owning collection still loads once, which is the price of writing through the domain's remover rather than around it, and the same price a single `DetachAction` pays.
On the **inverse** side the reader writes through the owning side, which is each selected record's own collection, so each reference is initialised: one `SELECT` of the row per key, where the fetching arm reads them all in one statement, and one collection load per key on either arm, so the flag costs statements there rather than saving them.

The reported count is the number of records the loop detached, not an affected-row count, because there is no statement to read one off.
A key naming a row that is already detached therefore still counts, where Filament's `DELETE` would not count it.

### The bulk detach is atomic where Filament's fetching arm is per-record

Filament's fetching arm deletes inside `$records->each(...)`, catching per record, so one failure reports a single failure and the rest still go.
The port's body ends in **one** `flush()`, so the batch detaches whole or not at all: any `Throwable` becomes `reportCompleteBulkProcessingFailure()` and the danger toast says the whole selection failed.
Same shape and same reasoning as [`DeleteBulkAction`](delete-action.md#the-bulk-delete-is-atomic-where-filaments-is-per-record): a Doctrine `flush()` is the unit of work, so a per-record report would misstate what landed.

### The counting failure title is an addition Filament has neither of

`DetachBulkAction` sets a `failureNotificationTitle()` closure answering `Detached %d of %d` and `Failed to detach %d records`.
Filament's does not, and there is nothing to port: `vendor/filament/actions/src/DetachBulkAction.php` declares no `failureNotificationTitle()`, and `resources/lang/en/detach.php` carries no `*_partial` or `*_none` key; only `delete.php`, `force-delete.php` and `restore.php` do.

Without a title neither side sends a failure toast: Filament's `sendFailureNotification()` sends only when `filled($notification?->getTitle())`, and the port's returns early when `blank($notification->getTitle())`.
In Filament a failed detach is therefore silent, its partial failures counted but never shown; in the port, where the atomic shape above makes every failure a whole-batch failure, a silent toast would hide a batch that changed nothing, so the title is added.
The wording follows `DeleteBulkAction`'s, whose title *is* a port: the two `sprintf`s and their `successCount` and `totalCount` [injections](can-notify.md#the-failure-notification-carries-four-injections-of-filaments-nine) come from there, not from vendor's detach.
