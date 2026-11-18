---
description: The testing helpers that assert which records a live table host renders and how many it counts, and load a deferred table, on the tester a test drives.
ports:
  - vendor/filament/tables/src/Testing/TestsRecords.php
owns:
  - packages/tables/src/Testing/TestsRecords.php
---
# TestsRecords (tables)

[`Sylament\Tables\Testing\TestsRecords`](../../../packages/tables/src/Testing/TestsRecords.php) is a trait on tables' [`Testable`](testable.md) tester, where Filament's is a Livewire `Testable` mixin.
The record assertions read the last response's render, as Filament's do, and take each record's key from the re-hydrated host's `getTableRecordKey()`; `loadTable()` calls the host's `loadTable()` live action.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`assertCanSeeTableRecords()` / `assertCanNotSeeTableRecords()` with record objects, and `$inOrder`](../../../packages/tables/src/Testing/TestsRecords.php) | [`Testing/TestsRecords.php`](../../../vendor/filament/tables/src/Testing/TestsRecords.php) | done | — | [semantics](#a-record-is-seen-when-a-rendered-row-carries-its-whole-key) · [diverges](#the-failure-messages-name-the-row-key) · [diverges](#records-are-any-iterable) |
| [`assertCanSeeTableRecords()` / `assertCanNotSeeTableRecords()` with a key or an array record](../../../packages/tables/src/Testing/TestsRecords.php) | [`Testing/TestsRecords.php`](../../../vendor/filament/tables/src/Testing/TestsRecords.php) | done | — | — |
| [`assertCountTableRecords()` / `loadTable()`](../../../packages/tables/src/Testing/TestsRecords.php) | [`Testing/TestsRecords.php`](../../../vendor/filament/tables/src/Testing/TestsRecords.php) | done | — | [semantics](#the-count-is-the-hosts-total) |

## Semantics

### A record is seen when a rendered row carries its whole key

Every rendered row carries `id="<host id>.table.records.<key>"`, so the helpers read each row's `id` from the render and compare its key whole.
`$inOrder` asks for the records' rows in the given order, other rows allowed between them, as Filament's `assertSeeHtmlInOrder()` does over the HTML.
Filament matches the id as a substring of the HTML, so key `1` is seen in a page that renders only row `12`; a whole-key comparison cannot confuse them, and a key the HTML escapes still matches.

### The count is the host's total

`assertCountTableRecords()` compares the count strictly with the re-hydrated host's `getAllTableRecordsCount()`, as Filament's does with its host's, and fails with Filament's message, `Failed asserting that the [<host>] table has [<count>] records in total.`

## Divergences

### The failure messages name the row key

Filament's assertions fail with PHPUnit's string-containment message, which prints the whole rendered HTML.
Here each names the key and the host, in the phrasing Filament's other table assertions use: `Failed asserting that a table row with key [<key>] is visible on the [<host>] component.`, `… is not visible …`, and, for `$inOrder`, `Failed asserting that table rows with keys [<keys>] are visible in that order on the [<host>] component.`

### Records are any iterable

Filament types `$records` as `array|Collection`, Laravel's `Collection`; here it is `iterable`, so an array, a Doctrine `Collection` or a generator is read the same way.
