---
description: The testing helper that selects a live table host's records for a bulk action, on the tester a test drives.
ports:
  - vendor/filament/tables/src/Testing/TestsBulkActions.php
owns:
  - packages/tables/src/Testing/TestsBulkActions.php
---
# TestsBulkActions (tables)

[`Sylament\Tables\Testing\TestsBulkActions`](../../../packages/tables/src/Testing/TestsBulkActions.php) is a trait on tables' [`Testable`](testable.md) tester, where Filament's is a Livewire `Testable` mixin.
A bulk action is called through actions' [`callAction()`](../actions/tests-actions.md) with `TestAction::make('<name>')->table()->bulk()`, after `selectTableRecords()` picks its records.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`selectTableRecords()` with record objects and keys](../../../packages/tables/src/Testing/TestsBulkActions.php) | [`Testing/TestsBulkActions.php`](../../../vendor/filament/tables/src/Testing/TestsBulkActions.php) | done | — | [semantics](#a-record-object-is-selected-by-the-key-the-host-mints) · [diverges](#records-are-an-array) |
| `mountTableBulkAction()` / `callTableBulkAction()` / `setTableBulkActionData()` / `assertTableBulkActionDataSet()` and the other `*TableBulkAction*` adapters | [`Testing/TestsBulkActions.php`](../../../vendor/filament/tables/src/Testing/TestsBulkActions.php) | todo | — | — |
| `assertTableBulkActionHeld()` | [`Testing/TestsBulkActions.php`](../../../vendor/filament/tables/src/Testing/TestsBulkActions.php) | skipped | — | [deprecated for `assertTableBulkActionHalted()`](../README.md#statuses) |
| _rest not audited yet_ | — | — | — | — |

## Semantics

### A record object is selected by the key the host mints

`selectTableRecords()` writes the host's writable `selectedTableRecords` prop in one live request, as Filament's `set()` does.
A record object is replaced by the key the re-hydrated host mints for it with `getTableRecordKey()`, where Filament maps an Eloquent model, and anything else is sent as the key it is.

## Divergences

### Records are an array

Filament takes `array|Collection`, Laravel's `Collection`; here `$records` is an `array`, since Sylament has no Laravel collection and Filament reads the collection only through `all()`.
