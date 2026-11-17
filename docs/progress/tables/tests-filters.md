---
description: The testing helpers that set, reset and remove a live table host's filters, and assert that a filter exists, on the tester a test drives.
ports:
  - vendor/filament/tables/src/Testing/TestsFilters.php
owns:
  - packages/tables/src/Testing/TestsFilters.php
---
# TestsFilters (tables)

[`Sylament\Tables\Testing\TestsFilters`](../../../packages/tables/src/Testing/TestsFilters.php) is a trait on tables' [`Testable`](testable.md) tester, where Filament's is a Livewire `Testable` mixin.
`resetTableFilters()`, `removeTableFilter()` and `removeTableFilters()` call the host's `resetTableFiltersForm()`, `removeTableFilter()` and `removeTableFilters()` live actions, as Filament's do; `filterTable()` and `assertTableFilterExists()` read the re-hydrated host's table and resolve a filter name through the host's `parseTableFilterName()`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`filterTable()` for a ternary, a select, a multiple select and any other filter, with a record object or an enum](../../../packages/tables/src/Testing/TestsFilters.php) | [`Testing/TestsFilters.php`](../../../vendor/filament/tables/src/Testing/TestsFilters.php) | done | — | [semantics](#the-data-is-shaped-by-the-filters-class) · [semantics](#a-record-object-is-selected-by-the-key-the-host-mints) · [diverges](#filtertable-writes-the-whole-applied-filter-state) |
| [`resetTableFilters()` / `removeTableFilters()`](../../../packages/tables/src/Testing/TestsFilters.php) | [`Testing/TestsFilters.php`](../../../vendor/filament/tables/src/Testing/TestsFilters.php) | done | — | — |
| [`removeTableFilter()` with a filter name](../../../packages/tables/src/Testing/TestsFilters.php) | [`Testing/TestsFilters.php`](../../../vendor/filament/tables/src/Testing/TestsFilters.php) | done | — | — |
| [`removeTableFilter()` with a `$field`](../../../packages/tables/src/Testing/TestsFilters.php) | [`Testing/TestsFilters.php`](../../../vendor/filament/tables/src/Testing/TestsFilters.php) | in progress | — | [detail](#a-field-and-a-filter-class-wait-for-a-test-that-needs-them) |
| [`assertTableFilterExists()` with `$checkFilterUsing`](../../../packages/tables/src/Testing/TestsFilters.php) | [`Testing/TestsFilters.php`](../../../vendor/filament/tables/src/Testing/TestsFilters.php) | done | — | — |
| [`filterTable()` / `removeTableFilter()` / `assertTableFilterExists()` given a filter class](../../../packages/tables/src/Testing/TestsFilters.php) | [`Testing/TestsFilters.php`](../../../vendor/filament/tables/src/Testing/TestsFilters.php) | in progress | — | [detail](#a-field-and-a-filter-class-wait-for-a-test-that-needs-them) |
| `assertTableFilterVisible()` / `assertTableFilterHidden()` | [`Testing/TestsFilters.php`](../../../vendor/filament/tables/src/Testing/TestsFilters.php) | todo | — | — |

## Details

### A field and a filter class wait for a test that needs them

`removeTableFilter()` sends its `$field` to the host's `removeTableFilter()` under the `field` argument, which resets only that field of the filter's schema, and every helper here turns a filter class into its default name through the host's `parseTableFilterName()`; both are ported, and no test passes either yet.
Each is covered by the first test that does, since every ported helper is exercised by the suite.

## Semantics

### The data is shaped by the filter's class

`filterTable()` checks for a `TernaryFilter` before a `SelectFilter`, which it extends, as Filament's does.
A ternary filter is given `['value' => $data]`, and `['value' => true]` when `$data` is `true` or only the name is passed, which `func_num_args()` tells apart from an explicit `null`.
A select filter is given `['value' => $data]`, or `['values' => […]]` over `wrap($data)` when it is multiple.
Any other filter is given `$data` when it is an array, and otherwise `['isActive' => …]`, true for `true` or `null`.

### A record object is selected by the key the host mints

A select filter's value that is a `BackedEnum` is sent as its `value`, and any other object as the key the re-hydrated host mints for it with `getTableRecordKey()`, where Filament sends an Eloquent model's `getKey()`.
A record of a class other than the table's, such as a jar's supplier on a jar table, is keyed by its readable `id`, which is what an options list keyed by id carries; one with no `id` is refused by the host's `LogicException`, and so is a pure `UnitEnum`.

## Divergences

### filterTable() writes the whole applied filter state

Filament writes the filter's slice at `tableFilters.<name>`, and Livewire runs the host's `updatedTableFilters()` for a nested write, which copies the applied state into the deferred draft and resets the page.
UX runs a prop's `onUpdated` hook only when the whole prop is written, so here the helper writes `tableFilters` whole in one request: the re-hydrated host's applied state with this filter's slice replaced, which runs `updatedTableFilters()` as Filament's write does and keeps every other filter applied when one is later removed.
A browser never meets the difference: a deferred table's form writes the draft and applies it, and a live table's fields write their own paths, whose `afterStateUpdated()` hooks run the same updates.
