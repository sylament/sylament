---
description: The testing helpers that assert which records a live table host renders, on the tester a test drives.
ports:
  - vendor/filament/tables/src/Testing/TestsRecords.php
owns:
  - packages/tables/src/Testing/TestsRecords.php
---
# TestsRecords (tables)

[`Sylament\Tables\Testing\TestsRecords`](../../../packages/tables/src/Testing/TestsRecords.php) is a trait on tables' [`Testable`](testable.md) tester, where Filament's is a Livewire `Testable` mixin.
Its assertions read the last response's render, as Filament's do, and take each record's key from the re-hydrated host's `getTableRecordKey()`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`assertCanSeeTableRecords()` / `assertCanNotSeeTableRecords()` with record objects, and `$inOrder`](../../../packages/tables/src/Testing/TestsRecords.php) | [`Testing/TestsRecords.php`](../../../vendor/filament/tables/src/Testing/TestsRecords.php) | done | — | [semantics](#a-record-is-seen-when-a-rendered-row-carries-its-whole-key) · [diverges](#the-failure-messages-name-the-row-key) |
| [`assertCanSeeTableRecords()` / `assertCanNotSeeTableRecords()` with a key or an array record](../../../packages/tables/src/Testing/TestsRecords.php) | [`Testing/TestsRecords.php`](../../../vendor/filament/tables/src/Testing/TestsRecords.php) | in progress | — | [detail](#a-key-and-an-array-record-wait-for-a-test-that-needs-them) |
| `assertCountTableRecords()` / `loadTable()` | [`Testing/TestsRecords.php`](../../../vendor/filament/tables/src/Testing/TestsRecords.php) | todo | — | — |

## Details

### A key and an array record wait for a test that needs them

A record given as a key is compared as that string, and an array record is keyed by the host as an object is, through its `ArrayRecord` key; both are ported, and no test passes either yet.
Each is covered by the first test that does, since every ported helper is exercised by the suite.

## Semantics

### A record is seen when a rendered row carries its whole key

Every rendered row carries `id="<host id>.table.records.<key>"`, so the helpers read each row's `id` from the render and compare its key whole.
`$inOrder` asks for the records' rows in the given order, other rows allowed between them, as Filament's `assertSeeHtmlInOrder()` does over the HTML.
Filament matches the id as a substring of the HTML, so key `1` is seen in a page that renders only row `12`; a whole-key comparison cannot confuse them, and a key the HTML escapes still matches.

## Divergences

### The failure messages name the row key

Filament's assertions fail with PHPUnit's string-containment message, which prints the whole rendered HTML.
Here each names the key and the host, in the phrasing Filament's other table assertions use: `Failed asserting that a table row with key [<key>] is visible on the [<host>] component.`, `… is not visible …`, and, for `$inOrder`, `Failed asserting that table rows with keys [<keys>] are visible in that order on the [<host>] component.`
