# DissociateAction (actions)

[`Sylament\Actions\DissociateAction`](../../../packages/actions/src/DissociateAction.php)
— the prebuilt per-row undo of [`AssociateAction`](associate-action.md): a
danger-coloured cross that confirms, then nulls the record's owner and flushes.
Its bulk sibling
[`DissociateBulkAction`](../../../packages/actions/src/DissociateBulkAction.php)
is audited [at the bottom of this file](#dissociatebulkaction), the way
[`DeleteBulkAction`](delete-action.md#deletebulkaction) rides along with
`DeleteAction`.

Both read the relationship off the **table**, and both refuse — as developer
errors, not toasts — where the mapping does not permit a dissociation at all; see
[the four refusals](#dissociating-is-refused-where-the-mapping-forbids-it).

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php)
  — the class this ports, whole
- [`actions/src/DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php)
  — the bulk class, whole
- [`actions/src/Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php)
  — the trait both compose (row in [README](README.md#features))
- [`actions/resources/lang/en/dissociate.php`](../../../vendor/filament/actions/resources/lang/en/dissociate.php)
  — the strings both set

Covered by
[`DissociateActionTest`](../../../packages/actions/tests/Unit/DissociateActionTest.php)
for the confirmation chrome, the record-titled heading and the hostless refusal,
and end to end by
[`AssociateDissociateActionsTest`](../../../packages/tables/tests/Integration/AssociateDissociateActionsTest.php)
in the **tables** suite — the row dissociate with its `Dissociate Airbag`
heading, the surviving siblings, the success toast, the orphan-removal
refusal on both arms, and `DissociateBulkAction`'s own hostless refusal — that
last one driven against a container-built host rather than a mounted request,
because a table whose `relationship()` answers null cannot render at all. It sits there for the reason
[`PrebuiltDeleteActionTest`](delete-action.md) does: only a table host supplies a
relationship, and the actions test kernel registers neither tables nor forms.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DissociateAction.php) (`'dissociate'`) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | done | — | — |
| [`setUp()` chrome — label, submit label, success title, `defaultColor('danger')`, `requiresConfirmation()`](../../../packages/actions/src/DissociateAction.php) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`modalHeading()` — `"Dissociate {record title}"`](../../../packages/actions/src/DissociateAction.php) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | done | — | [semantics](#the-heading-resolves-the-record-title-through-the-table) |
| [`icon('heroicons:x-mark-20-solid')` / `modalIcon('heroicons:x-mark')`](../../../packages/actions/src/DissociateAction.php) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) (`FilamentIcon::resolve()` over two aliases) | done | — | [diverges](#the-icons-are-literal-names-not-resolved-aliases) |
| [The `action()` body — `process()` then `success()`](../../../packages/actions/src/DissociateAction.php) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | done | — | [diverges](#the-write-goes-through-the-association-reader-not-a-belongsto) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| `getInverseRelationshipFor()` in the write | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | skipped | — | [diverges](#the-write-goes-through-the-association-reader-not-a-belongsto) |

## Semantics

### The heading resolves the record title through the table

`getRecordTitle()` walks the action's own chain — a custom title, then the
table's `recordTitle()` / `recordTitleAttribute()`, then the host's
`getDefaultActionRecordTitle()`, then the record label. So a relation-scoped
table naming `recordTitleAttribute('title')` is what turns the confirmation into
`Dissociate Airbag`; with nothing to title by, the heading is the bare verb.

## Divergences

### The write goes through the association reader, not a `BelongsTo`

Filament reaches the target's inverse `BelongsTo` object
(`$table->getInverseRelationshipFor($record)`) and calls `dissociate()` on it.
Doctrine has no such object: the port asks
[`AssociationReader::detach()`](../schemas/association-reader.md) instead, which
reads `mappedBy` off the mapping and writes `null` through the record's own
setter, then flushes through the action's entity manager. The whole inverse
family is therefore not ported.

### Dissociating is refused where the mapping forbids it

A one-to-many is only dissociable when the mapping actually permits an ownerless
target. The reader checks **four** things, in this order, and only the first
reachable one on any given mapping can fire:

1. the owner's association declares **`orphanRemoval`** — a record removed from
   such a collection is *deleted*, so dissociating is not something the mapping
   permits at all and `DeleteAction` is the intended action;
2. the target's mapped-by field is part of the target's own **identifier** —
   nulling it would erase half the record's identity, so there is nothing to
   dissociate *to* and `DeleteAction` is again the intended action. This one
   refused before it had a name: an `#[Id]` to-one's join column is
   `nullable: false` in `ClassMetadata`, so such a mapping tripped (3) and was
   told to make the column nullable, which for an identifier column is
   impossible. The refusal did not move; the advice did;
3. the target's join column is **`nullable: false`** — the column cannot hold the
   null a dissociation would write;
4. the target's setter or property **does not accept null** — a non-nullable PHP
   type, which would `TypeError` on the write.

All four are mapping facts rather than runtime failures, so all four throw a
`LogicException` naming the fix and propagate: they are a developer
misconfiguration, and swallowing them into a danger toast would hide a mapping
bug behind a user-facing message. The demo's `Article::$sections` trips (1) —
which is why the demo's dissociate card is elsewhere. A many-to-many always
passes; detaching from one is `DetachAction`'s job.

### The icons are literal names, not resolved aliases

Filament resolves `ActionsIconAlias::DISSOCIATE_ACTION` /
`DISSOCIATE_ACTION_MODAL` through the icon facade, falling back to two Heroicons.
The port sets the two names outright — `heroicons:x-mark-20-solid` on the trigger
and `heroicons:x-mark` on the modal — and an alias layer over them is a later,
toolkit-wide change.

### The strings are literals, not translation keys

i18n is deferred toolkit-wide, so both classes hardcode Filament's `en` strings:
`Dissociate` / `Dissociate :label` / `Dissociated`, and `Dissociate selected` /
`Dissociate selected :label` / `Dissociated`. The counting failure title beside
them has no `en` source — it is
[an addition the atomic shape forces](detach-action.md#the-counting-failure-title-is-an-addition-filament-has-neither-of).

## DissociateBulkAction

[`Sylament\Actions\DissociateBulkAction`](../../../packages/actions/src/DissociateBulkAction.php)
— the same dissociation over a table's **selected records**. It extends
[`BulkAction`](bulk-action.md) (so `bulk()` and `accessSelectedRecords()` arrive
on) and composes the same
[`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php),
so a caller replaces the whole body with `using()`.

**Status:** in progress — the chrome, both arms and the counting failure
notification ship; the `report($exception)` call has nowhere to go
([the same gap `DeleteBulkAction` records](delete-action.md#the-swallowed-exception-has-nowhere-to-go-yet)).

Covered by
[`DissociateBulkActionTest`](../../../packages/actions/tests/Unit/DissociateBulkActionTest.php)
for the chrome, the inherited presets and the failure title's arithmetic, and end
to end by
[`AssociateDissociateActionsTest`](../../../packages/tables/tests/Integration/AssociateDissociateActionsTest.php)
in the **tables** suite — the fetching arm's three `UPDATE`s under one flush, the
by-key arm's single `UPDATE … IN (?, ?, ?)`, and the pre-flight refusal that runs
before any statement does.

### Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DissociateBulkAction.php) (`'dissociate'`) | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) | done | — | — |
| [`setUp()` chrome — label, modal heading (`"Dissociate selected {title-case plural record label}"`), submit label, success title, `defaultColor('danger')`, both icons, `requiresConfirmation()`, `deselectRecordsAfterCompletion()`](../../../packages/actions/src/DissociateBulkAction.php) | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) · [diverges](#the-icons-are-literal-names-not-resolved-aliases) |
| [`failureNotificationTitle()` — the counting closure](../../../packages/actions/src/DissociateBulkAction.php) | — | done | — | [diverges](detach-action.md#the-counting-failure-title-is-an-addition-filament-has-neither-of) |
| [The fetching arm — `detach()` per record, one `flush()`](../../../packages/actions/src/DissociateBulkAction.php) | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) (`$records->each(…)`) | done | — | [diverges](#the-fetching-arm-is-atomic-where-filaments-is-per-record) |
| [`dissociateSelectedRecordsByKey()` — the `fetchSelectedRecords(false)` arm](../../../packages/actions/src/DissociateBulkAction.php) | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) (`getSelectedRecordsQuery()->update([…=> null])`) | done | — | [diverges](#the-by-key-arm-is-a-dql-update-over-the-plucked-keys) |
| [The mapping refusals as a pre-flight](../../../packages/actions/src/DissociateBulkAction.php) | — | done | — | [diverges](#dissociating-is-refused-where-the-mapping-forbids-it) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| Reporting the swallowed exception | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) (`report($exception)`) | todo | a logger on `Action` | [delete-action.md](delete-action.md#the-swallowed-exception-has-nowhere-to-go-yet) |

### The fetching arm is atomic where Filament's is per-record

Filament dissociates and `save()`s record by record inside the loop, catching per
record and counting partial success. The port writes every record in memory and
flushes **once**, so the batch lands whole or not at all and any `Throwable`
becomes `reportCompleteBulkProcessingFailure()` — the shape
[`DeleteBulkAction` already carries](delete-action.md#the-bulk-delete-is-atomic-where-filaments-is-per-record),
for the same reason: a Doctrine `flush()` is one transaction, and a failure part
way through leaves the unit of work unusable rather than partially applied.

### The by-key arm is a DQL update over the plucked keys

Under `fetchSelectedRecords(false)` the selection is never hydrated: one
`UPDATE <target> sylament_dissociated_record SET …<mappedBy> = NULL` narrowed by
[`IdentifierConstraint::in()`](../schemas/record-key.md#the-operations-and-their-dql)
runs per chunk, and the affected-row counts are summed and reported as the
successful count, exactly as
[`deleteSelectedRecordsByKey()`](delete-action.md#the-by-key-arm-is-a-dql-delete-over-the-plucked-keys)
does for the delete — the fourth and last site to take
[the chunk rule](../schemas/record-key.md#the-chunk-rule), with one
`Connection::transactional()` around the chunks from the second on, so a
mid-batch failure means the whole batch failed. It carries that arm's caveat
too: a DQL `UPDATE` bypasses the `UnitOfWork`, so an entity already in the
identity map keeps its stale owner until it is reloaded or the manager is
cleared.

The conversion closed a **data defect** rather than only widening the arm. Since
[M7 · 1](../roadmap.md#m7--composite-identifiers) this action is handed the
table selection's `RecordKey` strings, and it bound them raw against one
identifier column — so a target whose column form is not its printed form (a
`uuid` stored `BINARY(16)`, a value-object identifier) matched **zero rows**: a
select-all of records that exist left every one of them owned and answered the
danger toast `Failed to dissociate N records`, with nothing naming why. No
fixture carried a target whose column form differs from its printed form until
this epic's converting one, which is why nothing in the suite saw it. The typed
decode is what makes the rows move.

Five refusals run before either arm picks up, all of them `LogicException`s
raised outside the two `try` blocks so none is swallowed into the danger toast.
[The four dissociate refusals](#dissociating-is-refused-where-the-mapping-forbids-it)
are the first, so an orphan-removal mapping throws before any statement is issued
rather than nulling a column the mapping treats as ownership; the fifth is that
the relationship must be a **one-to-many**, and the refusal says to use
`DetachBulkAction` instead. That fifth one reads `AssociationTarget::$isManyToMany`
rather than testing `$mappedBy` for null — Doctrine sets `mappedBy` on *every*
inverse side, many-to-many included, so a null test would let an inverse-side
many-to-many straight through to a DQL `UPDATE` of a collection-valued field and
report it as a failed batch. It gates the fetching arm too, even though `detach()`
would quietly do a many-to-many removal there: the class is the one-to-many action
on both arms, and a batch that succeeds or refuses depending on
`fetchSelectedRecords()` is worse than one refusal naming the right action.
`$mappedBy` keeps its own `?? throw` behind that branch as a one-to-many sanity
check, matching the reader's own. A sixth guards the by-key arm alone: every
selection entry must be a string record key (`DissociateBulkAction dissociates by
key from record keys, got %s.`), walked before the `try` beside the chunking, so
a host feeding something else is the developer error it is rather than a danger
toast.
