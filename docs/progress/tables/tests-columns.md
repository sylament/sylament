---
description: The testing helpers that sort, search, toggle and click a live table host's columns, and assert that a column exists, on the tester a test drives.
ports:
  - vendor/filament/tables/src/Testing/TestsColumns.php
owns:
  - packages/tables/src/Testing/TestsColumns.php
---
# TestsColumns (tables)

[`Sylament\Tables\Testing\TestsColumns`](../../../packages/tables/src/Testing/TestsColumns.php) is a trait on tables' [`Testable`](testable.md) tester, where Filament's is a Livewire `Testable` mixin.
Its driving helpers send the host's own live action or prop write, under the host's `#[LiveArg]` names, in the requests Filament's send; `assertTableColumnExists()` reads the re-hydrated host's table.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`sortTable()`](../../../packages/tables/src/Testing/TestsColumns.php) | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | done | — | — |
| [`searchTable()` / `searchTableColumns()`](../../../packages/tables/src/Testing/TestsColumns.php) | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | done | — | — |
| [`toggleAllTableColumns()`](../../../packages/tables/src/Testing/TestsColumns.php) | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | done | — | [semantics](#toggling-every-column-applies-the-column-managers-draft) |
| [`callTableColumnAction()` with a record object or a key](../../../packages/tables/src/Testing/TestsColumns.php) | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | done | — | [semantics](#a-record-object-is-sent-as-the-key-the-host-mints) |
| [`assertTableColumnExists()` with `$checkColumnUsing` and a record object](../../../packages/tables/src/Testing/TestsColumns.php) | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | done | — | [semantics](#a-record-object-is-sent-as-the-key-the-host-mints) · [semantics](#a-column-is-checked-against-a-record-the-table-has) · [diverges](#a-record-the-table-does-not-have-is-refused-by-name) |
| [`assertTableColumnExists()` with a key or an array record](../../../packages/tables/src/Testing/TestsColumns.php) | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | done | — | — |
| `assertTableColumnDoesNotExist()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| `assertTableColumnVisible()` / `assertTableColumnHidden()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| `assertCanRenderTableColumn()` / `assertCanNotRenderTableColumn()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| `assertTableRecordKeyExists()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| `assertTableColumnStateSet()` / `assertTableColumnStateNotSet()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| `assertTableColumnEnabled()` / `assertTableColumnDisabled()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| `assertTableColumnFormattedStateSet()` / `assertTableColumnFormattedStateNotSet()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| `assertTableColumnHasExtraAttributes()` / `assertTableColumnDoesNotHaveExtraAttributes()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| `assertTableColumnHasDescription()` / `assertTableColumnDoesNotHaveDescription()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| `assertTableSelectColumnHasOptions()` / `assertTableSelectColumnDoesNotHaveOptions()` | [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |

## Semantics

### Toggling every column applies the column manager's draft

Filament's `toggleAllTableColumns()` writes the flipped state into the host's `tableColumns` and then calls `applyTableColumnManager()`.
Here the applied `tableColumns` is not client-writable, so the helper reads it from the re-hydrated host, flips `isToggled` on every column whose `isToggledHiddenByDefault` is not null (the state's mark of a toggleable column, where Filament's items carry `isToggleable`), writes the result into the manager's writable draft `tableDeferredColumns` and calls `applyTableColumnManager()` with no state, which applies the draft: the same two requests, and the state a user reaches by ticking every box and applying.

### A record object is sent as the key the host mints

`callTableColumnAction()` and `assertTableColumnExists()` replace a record object with the key the re-hydrated host mints for it with `getTableRecordKey()`, where Filament maps an Eloquent model, so a composite or association key reaches the host as the row carries it.
`callTableColumnAction()` sends anything else as the key it is, under the host's `name` and `recordKey` arguments.

### A column is checked against a record the table has

`assertTableColumnExists()` with a record hands the column the record the host resolves from its key before `$checkColumnUsing` runs, as Filament's does.

## Divergences

### A record the table does not have is refused by name

`assertTableColumnExists()` given a record whose key the table resolves to no record throws a `LogicException` naming the host and the key, where Filament's `record()` refuses the missing record with a `TypeError`.
