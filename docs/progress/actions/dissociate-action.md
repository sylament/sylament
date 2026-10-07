---
description: The prebuilt one-to-many dissociate and its bulk sibling DissociateBulkAction, nulling records' owner on a relation-scoped table.
ports:
  - vendor/filament/actions/src/DissociateAction.php
  - vendor/filament/actions/src/DissociateBulkAction.php
  - vendor/filament/actions/src/Concerns/CanCustomizeProcess.php
  - vendor/filament/actions/resources/lang/en/dissociate.php
owns:
  - packages/actions/src/DissociateAction.php
  - packages/actions/src/DissociateBulkAction.php
---
# DissociateAction (actions)

[`Sylament\Actions\DissociateAction`](../../../packages/actions/src/DissociateAction.php) is the prebuilt per-row undo of [`AssociateAction`](associate-action.md): a danger-coloured cross that confirms, then nulls the record's owner and flushes.
[`DissociateBulkAction`](../../../packages/actions/src/DissociateBulkAction.php) is the same dissociation over a table's selected records; it extends [`BulkAction`](bulk-action.md) and composes the same `CanCustomizeProcess`, so a caller replaces either body with `using()`.
Both read the relationship off the **table**, and both let the reader's refusals propagate as developer errors, not toasts, where [the mapping does not permit a dissociation](../schemas/association-reader.md#dissociating-is-refused-where-the-mapping-forbids-it).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DissociateAction.php) (`'dissociate'`) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | done | — | — |
| [`setUp()` chrome — label, submit label, success title, `defaultColor('danger')`, `requiresConfirmation()`](../../../packages/actions/src/DissociateAction.php) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) |
| [`modalHeading()` — `"Dissociate {record title}"`](../../../packages/actions/src/DissociateAction.php) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | done | — | [semantics](#the-heading-resolves-the-record-title-through-the-table) |
| [`icon('heroicons:x-mark-20-solid')` / `modalIcon('heroicons:x-mark')`](../../../packages/actions/src/DissociateAction.php) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) (`FilamentIcon::resolve()` over two aliases) | done | — | [diverges](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection) |
| [The `action()` body — `process()` then `success()`](../../../packages/actions/src/DissociateAction.php) | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | done | — | [diverges](#the-write-goes-through-the-association-reader-not-a-belongsto) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| `getInverseRelationshipFor()` in the write | [`DissociateAction.php`](../../../vendor/filament/actions/src/DissociateAction.php) | skipped | — | [diverges](#the-write-goes-through-the-association-reader-not-a-belongsto) |

## Surface — DissociateBulkAction

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getDefaultName()`](../../../packages/actions/src/DissociateBulkAction.php) (`'dissociate'`) | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) | done | — | — |
| [`setUp()` chrome](../../../packages/actions/src/DissociateBulkAction.php) — label, modal heading, submit label, success title, colour, both icons, confirmation, deselect | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) | done | — | [diverges](#the-strings-are-literals-not-translation-keys) · [diverges](create-action.md#icons-are-literal-heroicon-names-with-no-alias-indirection) |
| [`failureNotificationTitle()` — the counting closure](../../../packages/actions/src/DissociateBulkAction.php) | — | done | — | [diverges](detach-action.md#the-counting-failure-title-is-an-addition-filament-has-neither-of) |
| [The fetching arm — `detach()` per record, one `flush()`](../../../packages/actions/src/DissociateBulkAction.php) | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) (`$records->each(…)`) | done | — | [diverges](#the-fetching-arm-is-atomic-where-filaments-is-per-record) |
| [`dissociateSelectedRecordsByKey()`](../../../packages/actions/src/DissociateBulkAction.php) — the `fetchSelectedRecords(false)` arm | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) (`getSelectedRecordsQuery()->update([…=> null])`) | done | — | [diverges](#the-by-key-arm-is-a-dql-update-and-a-dbal-one-where-the-foreign-key-spans-several-columns) |
| [The refusals as a pre-flight](../../../packages/actions/src/DissociateBulkAction.php) | — | done | — | [semantics](#dissociatebulkaction-refuses-before-either-arm-writes) · [diverges](../schemas/association-reader.md#dissociating-is-refused-where-the-mapping-forbids-it) |
| [`CanCustomizeProcess`](../../../packages/actions/src/Concerns/CanCustomizeProcess.php) (`using()` / `process()`) | [`Concerns/CanCustomizeProcess.php`](../../../vendor/filament/actions/src/Concerns/CanCustomizeProcess.php) | done | — | — |
| Reporting the swallowed exception | [`DissociateBulkAction.php`](../../../vendor/filament/actions/src/DissociateBulkAction.php) (`report($exception)`) | blocked | a logger on `Action` | [delete-action.md](delete-action.md#the-swallowed-exception-has-nowhere-to-go-yet) |

## Semantics

### The heading resolves the record title through the table

`getRecordTitle()` walks the action's own chain: a custom title, then the table's `recordTitle()` / `recordTitleAttribute()`, then the host's `getDefaultActionRecordTitle()`, then the record label.
So a relation-scoped table naming `recordTitleAttribute('title')` is what turns the confirmation into `Dissociate Airbag`; with nothing to title by, the heading is the bare verb.

### DissociateBulkAction refuses before either arm writes

Every refusal of a mapping or a selection shape is a `LogicException` raised outside the two `try` blocks, so none is swallowed into the danger toast.
[The reader's four dissociate refusals](../schemas/association-reader.md#dissociating-is-refused-where-the-mapping-forbids-it) run first, through `assertRelationshipAcceptsDetach()`, so an orphan-removal mapping throws before any statement is issued rather than nulling a column the mapping treats as ownership.
Next the relationship must be a **one-to-many**, and the refusal says to use `DetachBulkAction` instead.
That check reads `AssociationTarget::$isManyToMany` rather than testing `$mappedBy` for null: Doctrine sets `mappedBy` on *every* inverse side, many-to-many included, so a null test would let an inverse-side many-to-many through to the by-key arm, whose to-one guard would throw inside its `try` and report it as a failed batch.
It gates the fetching arm too, even though `detach()` would quietly do a many-to-many removal there: the class is the one-to-many action on both arms, and a batch that succeeds or refuses depending on `fetchSelectedRecords()` is worse than one refusal naming the right action.
`$mappedBy` keeps its own `?? throw` behind that branch as a one-to-many sanity check, matching the reader's own.

The by-key arm alone then walks the selection before its `try`: every entry must be a string record key (`DissociateBulkAction dissociates by key from record keys, got %s.`), and the keys are chunked there too, so a host feeding something else is the developer error it is.
The fetching arm's shape check is not hoisted: a non-object entry raises `DissociateBulkAction can only dissociate entities, got %s.` inside its `try`, where it becomes the danger toast.
A narrowing inside the statement builder, that the mapped-by is the owning to-one side Doctrine requires of every one-to-many's mapped-by, guards the type system rather than the caller, because no mapping that reaches it fails it.

## Divergences

### The write goes through the association reader, not a `BelongsTo`

Filament reaches the target's inverse `BelongsTo` object (`$table->getInverseRelationshipFor($record)`) and calls `dissociate()` on it, then `save()`s the record.
Doctrine has no such object: the port asks [`AssociationReader::detach()`](../schemas/association-reader.md) instead, which reads `mappedBy` off the mapping and writes `null` through the record's own setter, then flushes through the action's entity manager.
The whole inverse family is therefore not ported.

### The strings are literals, not translation keys

Both classes hardcode Filament's `en` strings, for the reason [create-action.md](create-action.md#the-strings-are-literals-not-translation-keys) records: `Dissociate` / `Dissociate :label` / `Dissociated`, and `Dissociate selected` / `Dissociate selected :label` / `Dissociated`.
The counting failure title beside them has no `en` source; it is [an addition the atomic shape forces](detach-action.md#the-counting-failure-title-is-an-addition-filament-has-neither-of).

### The fetching arm is atomic where Filament's is per-record

Filament dissociates and `save()`s record by record inside the loop, catching per record and counting partial success.
The port writes every record in memory and flushes **once**, so the batch lands whole or not at all and any `Throwable` becomes `reportCompleteBulkProcessingFailure()`, the shape [`DeleteBulkAction`](delete-action.md#the-bulk-delete-is-atomic-where-filaments-is-per-record) carries, for the same reason: a Doctrine `flush()` is one transaction, and a failure part way through leaves the unit of work unusable rather than partially applied.

### The by-key arm is a DQL update, and a DBAL one where the foreign key spans several columns

Under `fetchSelectedRecords(false)` Filament runs `getSelectedRecordsQuery()->update([<foreign key> => null])`; the port nulls the mapped-by foreign key of the plucked keys, one statement per chunk of [the chunk rule](../schemas/record-key-contract.md#the-chunk-rule), with one `Connection::transactional()` around the chunks from the second on, so a mid-batch failure means the whole batch failed.
The keys are decoded through `IdentifierConstraint`, never bound raw, for the reason [bulk-action.md](bulk-action.md#under-fetchselectedrecordsfalse-a-body-receives-record-keys-not-column-values) gives: bound raw against a target whose column form is not its printed form, they would match zero rows, and a select-all would answer `Failed to dissociate N records` with nothing naming why.
Either statement's affected-row counts are summed and reported as the successful count, as [`deleteSelectedRecordsByKey()`](delete-action.md#the-by-key-arm-is-a-dql-delete-over-the-plucked-keys) does for the delete.
Both statements bypass the ORM's unit of work, so an entity already in the identity map keeps its stale owner until it is reloaded or the manager is cleared.

**One join column.**
For a mapped-by with one join column, the statement is a DQL `UPDATE … SET … = NULL WHERE IdentifierConstraint::in(…)`.
Doctrine's SQL walker adds to it what it adds to every DQL update: a single-table target's discriminator, every enabled SQL filter, and, for a joined target, the multi-table executor, which narrows by the leaf table.

**Several join columns.**
Only a composite owner has a mapped-by spanning several join columns, and DQL cannot null such a to-one at all: a DQL `UPDATE <Target> t SET t.<mappedBy> = NULL` over it is a `QueryException` raised in the SQL walker, and **no** DQL form of that statement exists.
A DQL-only arm would therefore refuse every one-to-many under a composite owner, while the fetching arm dissociates it at any width.
So the statement is a DBAL `UPDATE` of the table declaring the association, nulling each join column over [`IdentifierConstraint::inColumns()`](../schemas/record-key-contract.md#incolumns-is-in-in-sql-for-a-statement-that-names-one-table).
The port takes that second public SQL-side operation in `schemas` over reaching the codec's `@internal` field descriptor from `actions`, so the key contract stays the one place a key is decoded, and `actions` composes only the table and the `SET` half, from the mapping.
The DBAL statement carries no discriminator and no filter, which is safe while the keys are plucked through the relationship query.
The chunk sizes are the DQL arm's: they bound the statement's parameter count, and the DQL parse cost they are also sized against is paid on the one-column arm only.

**Why the split.**
The single-column arm stays on DQL rather than sharing the DBAL statement, because under `query()` beside `relationship()` over a single-table or joined hierarchy, the DBAL statement writes rows of other classes the DQL one excludes.
The delete is DQL at every width, because a `DELETE` names no association and so has nothing DQL cannot write.

Every name in the SQL statement is the **mapping's**, read through Doctrine's own `QuoteStrategy`.
The table is `getTableName()` of the entity the mapped-by to-one was inherited from (`$mapping->inherited`, falling back to the target), which is the ORM's own rule for a JOINED hierarchy, so an inherited foreign key is written on the root's table, and a mapped superclass, which has no table, is never named.
There is one `SET` per join column, each through `getJoinColumnName()`, so a foreign key onto a composite owner is nulled whole in one statement.
A reserved word is quoted where the mapping quotes it and nowhere else.
A schema-qualified table goes through the same `getTableName()`; no test pins it, because SQLite hosts one only behind an `ATTACH` that every test factory would have to carry.
Executed, through the real action:

| Target | The statement |
| --- | --- |
| a mapped-by onto a scalar-pair owner (SQL) | `UPDATE ShortcutNote SET shortcut_host = NULL, shortcut_path = NULL WHERE id IN (?)` |
| a mapped-by onto a derived-identity owner (SQL) | `UPDATE ResidencyRider SET residency_venue_id = NULL, residency_season = NULL WHERE id IN (?)` |
| reserved words, quoted by the mapping (SQL) | `UPDATE "order" SET "select" = NULL, "from" = NULL WHERE "group" IN (?)` |
| a single-table leaf (DQL) | `UPDATE Gauge SET depot_id = NULL WHERE (id IN (?)) AND kind IN ('dial')` |
| a JOINED leaf whose root declares the mapped-by (DQL) | `UPDATE Appliance SET depot_id = NULL WHERE (appliance_id) IN (SELECT appliance_id FROM Appliance_id_tmp)`, after an `INSERT` into that temporary table selecting from `Toaster` joined to `Appliance` |

The keys either arm narrows by are **plucked** through the table's base query, which is DQL and so already discriminated and filtered, and a selected key naming a row that query excludes is written by nothing.
That base query is the relationship's only while the table declares no `query()` of its own; declare both and the query wins, which is [what makes it a query table](../tables/has-query.md#the-mappings-order-is-the-relation-scoped-tables-default-sort), while the arm still reads the relationship for the mapping to null.
Over a host declaring `query(Gauge)` beside `relationship()` on a depot's `dials`, with a dial of that depot, a plain `Gauge` of it and a dial of another depot selected, the DQL arm's `AND kind IN ('dial')` writes **two** rows and leaves the plain gauge owned, where the DBAL statement would write three.
The other depot's dial is nulled either way, because the arm scopes by key and never by owner.
Guarding the arm on `isRelationshipBaseQuery()` would refuse a declaration the table supports, so that exposure, and the SQL arm's missing discriminator and filter under the same declaration, is a known gap, left open rather than closed.

Both by-key statements evict the second-level cache: the DQL `UPDATE` through Doctrine's `HINT_CACHE_EVICT`, and the DBAL `UPDATE`, which Doctrine never sees, through `Cache::evictEntityRegion()` on the target class afterwards.
Once every chunk has run, either arm also evicts the owner association's collection region through `Cache::evictCollectionRegion()`: a dissociated record stays in the database, so reading it again puts it back into the target's region, and from then on an owner's cached collection that still listed it would hit and serve it.
A concurrent request reading a target between an eviction and its commit can put its pre-write row back into a `NONSTRICT_READ_WRITE` region, which serves it until the entry expires: the DQL arm's hint evicts before its statement, and under the chunks' transaction the DBAL arm's eviction after its own statement lands before the commit too.
That is the window Doctrine's own hint has, which only a unit-of-work write closes, its cached persister writing the entry afresh once the transaction has committed.
