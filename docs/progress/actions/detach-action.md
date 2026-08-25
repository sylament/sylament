# DetachAction (actions)

[`Sylament\Actions\DetachAction`](../../../packages/actions/src/DetachAction.php)
— the prebuilt many-to-many detach: a danger-coloured cross trigger that opens a
confirmation modal and, on confirm, undoes the bound record's membership of the
table's relationship and flushes. It is the undo of
[`AttachAction`](attach-action.md), and like it, it only makes sense on a
[relation-scoped table](../tables/table.md): `Table::relationship()` names the
owner and the association, and without one the action refuses.

Unlike its attach sibling it is **complete**: detaching needs no form, so there
is no schema to build and nothing waiting on forms.

Its bulk sibling
[`DetachBulkAction`](../../../packages/actions/src/DetachBulkAction.php) is
audited [at the bottom of this file](#detachbulkaction), the way
[`DeleteBulkAction`](delete-action.md#deletebulkaction) rides along with
`DeleteAction`: it is the same detach over a table's selection instead of over
one row.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php)
  — the class this ports, whole
- [`actions/src/DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php)
  — the bulk sibling below
- [`actions/src/Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php)
  — the trait both compose, ported whole (row in [README](README.md#features))
- [`actions/resources/lang/en/detach.php`](../../../vendor/filament/actions/resources/lang/en/detach.php)
  — the strings they set

Covered by
[`DetachActionTest`](../../../packages/actions/tests/Unit/DetachActionTest.php)
and
[`DetachBulkActionTest`](../../../packages/actions/tests/Unit/DetachBulkActionTest.php)
for the chrome, the presets and the counting titles, and end to end by
[`AttachDetachActionsTest`](../../../packages/tables/tests/Integration/AttachDetachActionsTest.php)
in the **tables** suite — a row detach with its record-titled heading and its
toast, and the bulk detach on both arms with the no-fetch arm proved never to
hydrate the selection. It sits there for the reason
[`PrebuiltDeleteActionTest`](delete-action.md) does: only a table host supplies a
relationship, a bound row record and a selection, and the actions test kernel has
none of the three.

## Surface

Every member Filament's class carries. _Detail_ carries **diverges** → the note
in [Divergences](#divergences), and/or **semantics** → the permanent note in
[Semantics](#semantics).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DetachAction.php) (`'detach'`) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | done | — | — |
| [`setUp()` chrome — label, modal heading (`"Detach {record title}"`), submit label, success title, `defaultColor('danger')`, `requiresConfirmation()`](../../../packages/actions/src/DetachAction.php) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | done | — | [semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb) · [diverges](#the-strings-are-literals-not-translation-keys) |
| [`icon('heroicons:x-mark-20-solid')` + `modalIcon('heroicons:x-mark')`](../../../packages/actions/src/DetachAction.php) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) (`FilamentIcon::resolve(…) ?? Heroicon::XMark`) | done | — | [diverges](#the-icons-are-literal-names-not-resolved-aliases) |
| [The `action()` body — `detach()` then one `flush()`, then `success()`](../../../packages/actions/src/DetachAction.php) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | done | — | [semantics](#the-write-goes-through-the-association-reader-on-the-owning-side) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | The default body is [`detachRecord(object $record)`](../../../packages/actions/src/DetachAction.php); the resolver injects the bound record by name, the `DeleteAction` shape. |
| The `allowsDuplicates()` branch (`$record->getRelationValue($pivotAccessor)->delete()`) | [`DetachAction.php`](../../../vendor/filament/actions/src/DetachAction.php) | skipped | — | [diverges](#the-duplicates-arm-is-not-ported) |

## Semantics

Faithful ports whose behaviour is easy to misread. Permanent.

### The headings interpolate, and fall back to the bare verb

`"Detach {$this->getRecordTitle()}"` reads the action's record chain — custom
title → **table** → host hook → record label — so a table declaring
`recordTitleAttribute('name')` is what makes a row's modal read *Detach Rock*
rather than *Detach genre*. With nothing bound and nothing naming a class the
chain answers null, and the heading collapses to the bare `Detach`. Same shape
one file over on [`DeleteAction`](delete-action.md#the-headings-interpolate-and-fall-back-to-the-bare-verb).

Because it confirms, the modal's default width is `md`; a non-confirming action's
default is `4xl`. Neither action sets a width of its own.

### The write goes through the association reader, on the owning side

[`AssociationReader::detach()`](../schemas/association-reader.md) does the
removal, and it always writes the side Doctrine persists: a many-to-many removes
from the owner's collection, or from the *target's* when the owner holds the
inverse side, since an inverse-only write emits no SQL at all. The removal runs
through the domain's own remover (Symfony's adder/remover diff over the whole
desired list), not through `Collection::removeElement()` — which is why the
contract is the one forms already state: a collection-valued association needs an
adder and a remover.

A one-to-many detach is a *dissociation*, and the reader refuses it where the
mapping forbids one (orphan removal, a `nullable: false` join column, a setter
that will not take null). `DetachAction` does not screen for that itself — the
reader's `LogicException` is the answer, and it names the fix.

## Divergences

Where the port deliberately differs from Filament.

### The duplicates arm is not ported

Filament branches on `$table->allowsDuplicates()`: with duplicates allowed it
deletes the pivot row the record was hydrated with, else it calls
`$relationship->detach($record)`. Doctrine has no pivot model and a join table's
primary key is the pair, so a duplicate join row is unrepresentable and the
branch has nothing to select between — the port carries only the second arm.
Same skip as [`AttachAction`](attach-action.md#pivot-data-duplicates-morphs-and-relationshipjoiner-are-not-ported).

### The icons are literal names, not resolved aliases

Filament reads both icons through `FilamentIcon::resolve(ActionsIconAlias::…)`
with a `Heroicon` enum fallback. The port has no per-action icon-alias registry
(support's `IconManager` registers aliases toolkit-wide instead), so the two
names are literal: `heroicons:x-mark-20-solid` on the trigger and the outlined
`heroicons:x-mark` on the modal. The same call
[`DeleteAction`](delete-action.md#one-plain-icon-replaces-filaments-table-and-grouped-pair)
made.

### The strings are literals, not translation keys

Filament reads every string through `__('filament-actions::detach…')`. i18n is
deferred for the whole toolkit, so the port hardcodes Filament's `en` values —
`Detach`, `Detach {label}`, `Detached`, and the bulk trio below.

---

## DetachBulkAction

[`Sylament\Actions\DetachBulkAction`](../../../packages/actions/src/DetachBulkAction.php)
— the same detach over a table's selection: a danger toolbar action that confirms,
detaches every selected record and deselects afterwards. It extends
[`BulkAction`](bulk-action.md), so it inherits the selection plumbing,
`fetchSelectedRecords()` and the counting failure report.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).
Everything Filament's class does is ported; the row stays short of complete for
the reason `DeleteBulkAction`'s does — the swallowed exception has nowhere to go.

### Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DetachBulkAction.php) (`'detach'`) | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | done | — | — |
| [`setUp()` chrome — label, modal heading (`"Detach selected {title-case plural record label}"`), submit label, success title, `defaultColor('danger')`, both icons, `requiresConfirmation()`, `deselectRecordsAfterCompletion()`](../../../packages/actions/src/DetachBulkAction.php) | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | done | — | [semantics](#the-headings-interpolate-and-fall-back-to-the-bare-verb) · [diverges](#the-strings-are-literals-not-translation-keys) |
| [`failureNotificationTitle()` — the counting closure](../../../packages/actions/src/DetachBulkAction.php) | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | done | — | Filament's two `trans_choice` branches become two `sprintf`s over the same `successCount` / `totalCount` injections — [bulk-action.md](bulk-action.md#the-failure-notification-carries-four-injections-of-filaments-nine) |
| [The fetching arm — `detach()` per hydrated record, one `flush()`](../../../packages/actions/src/DetachBulkAction.php) | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | done | — | [diverges](#the-bulk-detach-is-atomic-where-filaments-fetching-arm-is-per-record) |
| [The `fetchSelectedRecords(false)` arm — `getReference()` per key, then the same](../../../packages/actions/src/DetachBulkAction.php) | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) (`$relationship->detach($records)`) | done | — | [diverges](#the-no-fetch-arm-detaches-through-references-not-a-join-table-delete) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | The default body is [`detachRecords()`](../../../packages/actions/src/DetachBulkAction.php), taking the action and the selection. |
| Reporting the swallowed exception | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) (`report($exception)`) | todo | a logger on `Action` | [detail](#the-swallowed-exception-has-nowhere-to-go-yet) |
| The pivot-accessor delete in the fetching arm | [`DetachBulkAction.php`](../../../vendor/filament/actions/src/DetachBulkAction.php) | skipped | — | [diverges](#the-duplicates-arm-is-not-ported) |

### The no-fetch arm detaches through references, not a join-table DELETE

Filament's no-fetch arm hands the plucked keys straight to
`$relationship->detach($records)`, which is one `DELETE FROM pivot WHERE … IN (…)`
and returns the affected row count. The port cannot answer that way: **DQL cannot
target a join table** — it addresses entities, and a many-to-many join table is
mapped by no entity — and the toolkit writes no raw SQL. Going through DBAL was
considered and rejected: quoting the join table and its two columns correctly
means re-implementing Doctrine's `QuoteStrategy` against the same mapping the ORM
already owns, and getting it subtly wrong is a data bug rather than a failure.

So the arm builds a **reference** per selected key
([`AssociationReader::getReference()`](../schemas/association-reader.md)) and runs
the same `detach()` the fetching arm does. What it buys is what the flag promises:
the selected rows are never `SELECT`ed — the end-to-end test asserts the contrast,
the fetching arm running a hydrating selection over the keys and this one running
none. The owning collection still loads once, which is the price of writing
through the domain's remover rather than around it, and the same price a single
`DetachAction` pays.

The reported count is the number of records the loop detached, not an affected-row
count — there is no statement to read one off. A key naming a row that was already
detached therefore still counts, where Filament's `DELETE` would not have counted
it.

### The bulk detach is atomic where Filament's fetching arm is per-record

Filament's fetching arm deletes inside `$records->each(...)`, catching per record,
so one failure reports a single failure and the rest still go. The port's body
ends in **one** `flush()`, so the batch detaches whole or not at all: any
`Throwable` becomes `reportCompleteBulkProcessingFailure()` and the danger toast
says the whole selection failed. Same shape and same reasoning as
[`DeleteBulkAction`](delete-action.md#the-bulk-delete-is-atomic-where-filaments-is-per-record):
a Doctrine `flush()` is the unit of work, so a per-record report would be a lie
about what landed.

The identity-map caveat `DeleteBulkAction` records applies here too: after the
flush the detached references are still managed objects, and a test re-reading
through the same manager must `clear()` or `refresh()` first.

### The swallowed exception has nowhere to go yet

Filament calls Laravel's `report($exception)` in its catch blocks, so the counting
notification is the *user-facing* answer while the log still carries the cause.
`Action` has no logger — a `psr/log` optional setter is a non-`symfony/*`
dependency and needs sign-off — so the port's catch swallows silently and the
danger toast is the only trace. Recorded rather than papered over, exactly as on
[`DeleteBulkAction`](delete-action.md#the-swallowed-exception-has-nowhere-to-go-yet),
and it lifts for both at once.
