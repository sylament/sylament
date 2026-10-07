---
description: 'The table''s sort settings: the sortable-column resolver, the default sort and session persistence of the sort (draft)'
ports:
  - vendor/filament/tables/src/Table/Concerns/CanSortRecords.php
owns:
  - packages/tables/src/Table/Concerns/CanSortRecords.php
---
# CanSortRecords (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getSortableVisibleColumn()` / `getSortColumn()` / `getSortDirection()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [diverges](#sorting-and-global-search-both-skip-a-hidden-column) |
| [`defaultSort()` / `getDefaultSort()` / `getDefaultSortDirection()` / `defaultKeySort()` / `hasDefaultKeySort()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [semantics](#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker) · [host-can-sort-records.md](host-can-sort-records.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) · [host-can-sort-records.md](host-can-sort-records.md#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded) · [semantics](has-query.md#the-mappings-order-is-the-relation-scoped-tables-default-sort) |
| `defaultSortOptionLabel()` / `getDefaultSortOptionLabel()` | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | skipped | — | It labels the "unsorted" entry of the mobile sort **dropdown**, which the port does not render and nothing else reads; it belongs to that layout rather than to sorting, and lands only if the layout does |
| [`persistSortInSession()` / `persistsSortInSession()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |

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
It is the only transformation on this side of the family, and the [sorting suite](host-can-sort-records.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) pins it.

`getDefaultSort()` is declared to return `string|QueryBuilder|null` — wider than the `string|Closure|null` property behind it, because the closure arm may hand back a builder to sort **and replace** the query with, which is the one way a default sort can also filter.
The declaration is the contract the host branches on, and it needs no evaluation seam: PHPStan reads the declared return, not the narrower type `evaluate()` infers from the property.
Filament widens the same return and then cannot use it — every caller of `applySortingToTableQuery()` discards the builder the arm produces — so *replacing* the query is the one behaviour on this row that is [ours rather than the original's](host-can-sort-records.md#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded).

## Divergences

### Sorting and global search both skip a hidden column

Filament rejects a hidden column in two places, and both arms need an `isHidden()` to call, which [`CanBeHidden` and `CanBeToggled`](column.md#surface) give the column.

`getSortableVisibleColumn()` rejects three things — an unknown column, a **hidden** one, and an unsortable one.
A column the developer hid, or the user toggled off, cannot be sorted by.
The name keeps the word *visible* deliberately: it is Filament's, and renaming it would break the API a migrating user greps for.

The host's [global search loop](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) applies the same check: it skips a hidden column before asking whether the column is globally searchable, Filament's own order.
Without it, a term matching nothing a reader could see would narrow the page anyway.
Neither arm reads a client-supplied column set — both ask the column itself, after the visibility sync.
