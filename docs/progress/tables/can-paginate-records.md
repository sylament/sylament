---
description: The table's pagination settings — page options, the default page size, extreme links, the pagination mode and its enum.
ports:
  - vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php
  - vendor/filament/tables/src/Enums/PaginationMode.php
owns:
  - packages/tables/src/Enums/PaginationMode.php
  - packages/tables/src/Table/Concerns/CanPaginateRecords.php
---
# CanPaginateRecords (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`paginated()` / `isPaginated()` / `paginationPageOptions()` / `getPaginationPageOptions()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | — |
| [`defaultPaginationPageOption()` / `getDefaultPaginationPageOption()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | [diverges](#the-ten-per-page-default-is-matched-strictly) |
| [`extremePaginationLinks()` / `hasExtremePaginationLinks()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | — |
| [`paginationMode()` / `getPaginationMode()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) + [`PaginationMode::Default` / `Simple`](../../../packages/tables/src/Enums/PaginationMode.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php), [`Enums/PaginationMode`](../../../vendor/filament/tables/src/Enums/PaginationMode.php) | done | — | [semantics](#the-pagination-mode-selects-nothing-on-a-records-closure-table) · [host-can-paginate-records.md](host-can-paginate-records.md#a-paginated-query-lane-table-pages-through-doctrines-tools-paginator-and-only-a-collection-join-switches-it-to-identifiers-first) |
| [`PaginationMode::Cursor`](../../../packages/tables/src/Enums/PaginationMode.php) | [`Enums/PaginationMode`](../../../vendor/filament/tables/src/Enums/PaginationMode.php) | blocked | a keyset paginator carrier in support ([pagination.md](../support/pagination.md)) | [detail](#cursor-pagination-waits-on-a-keyset-carrier) |
| [`paginatedWhileReordering()` / `isPaginatedWhileReordering()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | — |
| `persistRecordsPerPageInSession()` / `persistsRecordsPerPageInSession()` | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | todo | — | [detail](#the-per-page-choice-persists-with-no-opt-out) |
| `scrollToTopOnPageChange()` / `shouldScrollToTopOnPageChange()` | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | todo | — | [detail](#scrolltotoponpagechange-has-no-browser-half) |

## Details

### Cursor pagination waits on a keyset carrier

`PaginationMode::Cursor` needs a keyset paginator that support does not ship, and it is refused where the mode is consumed rather than where it is set: a `Closure` mode has no value at set time, so the host's `paginateTableQuery()` throws a `LogicException` naming the missing carrier and the two modes that work.

### The per-page choice persists with no opt-out

Filament's table carries `persistRecordsPerPageInSession()`, defaulting on, and its host reads, writes and forgets the per-page session entry only while `persistsRecordsPerPageInSession()` answers true.
The port has neither member, so [`CanPersistTableState`](can-persist-table-state.md#the-session-read-is-a-post-mount-hook-and-its-priority-is-the-precedence-rule) reads and writes the per-page entry for every paginated host with a session, which matches Filament's default and leaves no way to turn it off.

### `scrollToTopOnPageChange()` has no browser half

Filament's host `setPage()` dispatches a `scrollToTopOfTable` event when the flag is on, and its Alpine `table.js` scrolls the table root into view on it.
The port has neither the setter nor the event; the host's `gotoTablePage()` action and the table controller are where the two halves would land.

## Semantics

### The pagination mode selects nothing on a `records()` closure table

`paginationMode()` is read only by the query lane's [`paginateTableQuery()`](host-can-paginate-records.md#a-paginated-query-lane-table-pages-through-doctrines-tools-paginator-and-only-a-collection-join-switches-it-to-identifiers-first); a `records()` closure returns whatever it built, so the enum selects nothing there.
**Default** pages against a true total, counted by Doctrine's Tools paginator.
**Simple** runs no count: the lane probes one record past the page and support's [`Paginator`](../support/pagination.md) takes the null total, the footer [branching on it](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both).
The enum is Filament's verbatim, three pure cases in declaration order; the getter is typed `PaginationMode` where Filament declares `?PaginationMode` over a body that cannot return null.

## Divergences

### The ten-per-page default is matched strictly

`getDefaultPaginationPageOption()` prefers `10` when the page options contain it, and Filament tests that with a loose `in_array(10, $options)` where this port passes `strict: true`.
The two disagree on one input: `paginationPageOptions(['10', 25])` yields the **string** `'10'` here (the loose match having failed, the first option wins) and the **int** `10` in Filament — a value that is not in the option list at all, which the host's [validating getter](host-can-paginate-records.md#per-page-is-validated-in-the-getter-on-every-read) would then reject in favour of… this same method.

Strict is the arm to keep: the returned value is compared against the option list again on every read, so a default that is not literally one of the options is a loop.
It is also the house rule — `in_array()` without `strict: true` is a PHPStan-flagged smell at level max — and the input that distinguishes them (numeric strings mixed with ints in one option list) has no reason to exist.
The `'all'` option is a string on purpose and is unaffected: it is matched by value, not by this branch.
