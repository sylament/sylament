---
description: The table's sort settings — the sortable-column resolver, the default sort and its tiebreaker, and the sort's session flag.
ports:
  - vendor/filament/tables/src/Table/Concerns/CanSortRecords.php
owns:
  - packages/tables/src/Table/Concerns/CanSortRecords.php
---
# CanSortRecords (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getSortableVisibleColumn()` / `getSortColumn()` / `getSortDirection()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [semantics](#sorting-and-global-search-both-skip-a-hidden-column) |
| [`defaultSort()` / `getDefaultSort()` / `getDefaultSortDirection()` / `defaultKeySort()` / `hasDefaultKeySort()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [semantics](#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker) · [host-can-sort-records.md](host-can-sort-records.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) · [host-can-sort-records.md](host-can-sort-records.md#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded) · [semantics](has-query.md#the-mappings-order-is-the-relation-scoped-tables-default-sort) |
| `getDefaultSortColumn()` / `getDefaultSortQuery()` | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | skipped | — | [deprecated for `getDefaultSort()`](../README.md#statuses) |
| `defaultSortOptionLabel()` / `getDefaultSortOptionLabel()` | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | blocked | the sorting dropdown of the content and stacked-on-mobile layouts | [detail](#defaultsortoptionlabel-waits-on-the-layouts-sorting-dropdown) |
| [`persistSortInSession()` / `persistsSortInSession()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |

## Details

### `defaultSortOptionLabel()` waits on the layouts' sorting dropdown

Filament reads the label in one place: the empty option of the column select in its sorting settings dropdown, which `index.blade.php` renders only for a table with a content layout or one stacked on mobile.
The port renders neither layout, so nothing would read the label, and it lands with the dropdown.

## Semantics

### The default sort is a secondary order, and its direction rules the tiebreaker

`defaultSort()` does not mean *the order when nothing else applies*.
It is appended **after** whatever the user clicked, so a table with both sorts by the user's column first and breaks its ties with the default — and only when the default names the very column the user is sorting by is it skipped, which is what keeps `defaultSort('title')` from ordering by `title` twice.
Filament composes it exactly this way; the port keeps the composition, and [`applySortingToTableQuery()`](host-can-sort-records.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) is where it is assembled.

The direction is the second surprise.
`defaultSort()` takes one and defaults it to `'asc'`, and that declared direction — not the direction the user clicked — is what the default sort **and** the `defaultKeySort()` tiebreaker are applied in: `getDefaultSortDirection() ?? getTableSortDirection()`, with anything but `'desc'` read as `'asc'`.
So under a `title:desc` user sort, a table declaring `defaultSort('plays')` orders `title desc, plays asc, id asc`.
The user's own column keeps the direction they clicked; nothing after it does.
A table that never calls `defaultSort()` has a null default direction, so the tiebreaker follows the user instead.

The `'desc'` comparison is the host's and it is case-sensitive, which is why `getDefaultSortDirection()` lowercases what it evaluates (Filament's `Str::lower()`) before handing it over: `defaultSort('plays', 'DESC')` means descending, and dropping that one call would silently turn every mixed-case declaration ascending.
It is the only transformation on this side of the family.

`getDefaultSort()` is declared to return `string|QueryBuilder|null` — wider than the `string|Closure|null` property behind it, because the closure arm may hand back a builder to sort **and replace** the query with, which is the one way a default sort can also filter.
The declaration is the contract the host branches on, and it needs no evaluation seam: PHPStan reads the declared return, not the narrower type `evaluate()` infers from the property.
Filament widens the same return and then cannot use it — every caller of `applySortingToTableQuery()` discards the builder the arm produces — so *replacing* the query is the one behaviour on this row that is [ours rather than the original's](host-can-sort-records.md#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded).

### Sorting and global search both skip a hidden column

Filament rejects a hidden column in two places, and both arms call the column's `isHidden()`, which [`CanBeHidden`](column.md#surface) gives it.

`getSortableVisibleColumn()` rejects three things — an unknown column, a **hidden** one, and an unsortable one.
A column the developer hid cannot be sorted by; one the user toggled off still can, in both codebases, because `isHidden()` reads only the developer's `hidden()` and `visible()`, and the user's choice is the separate `isToggledHidden()`.
The name keeps the word *visible* deliberately: it is Filament's, and renaming it would break the API a migrating user greps for.

The host's [global search loop](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) applies the same check: it skips a hidden column before asking whether the column is globally searchable, Filament's own order.
Without it, a term matching nothing a reader could see would narrow the page anyway.
Neither arm reads a client-supplied column set — both ask the column itself.
