---
description: 'The table''s pagination settings: page options, the default page size, extreme links and the pagination mode (draft)'
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
| [`paginated()` / `isPaginated()` / `paginationPageOptions()` / `getPaginationPageOptions()` / `defaultPaginationPageOption()` / `getDefaultPaginationPageOption()` / `extremePaginationLinks()` / `hasExtremePaginationLinks()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | [diverges](#the-ten-per-page-default-is-matched-strictly) |
| [`paginationMode()` / `getPaginationMode()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) + [`PaginationMode`](../../../packages/tables/src/Enums/PaginationMode.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php), [`Enums/PaginationMode`](../../../vendor/filament/tables/src/Enums/PaginationMode.php) | in progress | its `Cursor` arm — a keyset carrier ([pagination.md](../support/pagination.md)) | [detail](#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) · [host-can-paginate-records.md](host-can-paginate-records.md#a-paginated-query-lane-table-pages-through-doctrines-tools-paginator-and-only-a-collection-join-switches-it-to-identifiers-first) |
| [`paginatedWhileReordering()` / `isPaginatedWhileReordering()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | — |
| `scrollToTopOnPageChange()` | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | todo | — | [detail](#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) |

## Details

### The pagination mode ships two cases, and two setters wait on their features

`paginationMode()` selects between Laravel's length-aware, simple and cursor paginators, all three built by the query lane's [`paginateTableQuery()`](host-can-paginate-records.md#a-paginated-query-lane-table-pages-through-doctrines-tools-paginator-and-only-a-collection-join-switches-it-to-identifiers-first) — on the closure lane the userland closure returns whatever it built, so the enum selects nothing there, which is why it arrived with the lane rather than with the rest of the pagination configuration.
Two of its three cases ship.
**Default** pages against a true total, counted by Doctrine's Tools paginator.
**Simple** runs no count: the lane probes one record past the page and support's [`Paginator`](../support/pagination.md) takes the null total, the footer [branching on it](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both) — which a `records()` closure could already reach, so what the enum added was the query lane's fetching.
**Cursor** still needs a keyset carrier the toolkit does not ship, and is refused where the mode is consumed rather than where it is set: a `Closure` mode has no value at set time, so `paginateTableQuery()` throws a `LogicException` naming the missing carrier and the two modes that work.
The enum is Filament's verbatim, three pure cases in declaration order; the getter is typed `PaginationMode` where Filament declares `?PaginationMode` over a body that cannot return null.

`paginatedWhileReordering()` has a **done** row of its own, since the table it reads on exists.
`scrollToTopOnPageChange()` is the one still here: an Alpine `$el.scrollIntoView()` in Filament's view, where the port has no scroll affordance at all, so it belongs with the [in-flight affordance](can-defer-loading.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) that is still out rather than with pagination configuration, and returns with it.

## Divergences

### The ten-per-page default is matched strictly

`getDefaultPaginationPageOption()` prefers `10` when the page options contain it, and Filament tests that with a loose `in_array(10, $options)` where this port passes `strict: true`.
The two disagree on one input: `paginationPageOptions(['10', 25])` yields the **string** `'10'` here (the loose match having failed, the first option wins) and the **int** `10` in Filament — a value that is not in the option list at all, which the host's [validating getter](host-can-paginate-records.md#per-page-is-validated-in-the-getter-on-every-read) would then reject in favour of… this same method.

Strict is the arm to keep: the returned value is compared against the option list again on every read, so a default that is not literally one of the options is a loop.
It is also the house rule — `in_array()` without `strict: true` is a PHPStan-flagged smell at level max — and the input that distinguishes them (numeric strings mixed with ints in one option list) has no reason to exist.
The `'all'` option is a string on purpose and is unaffected: it is matched by value, not by this branch.
