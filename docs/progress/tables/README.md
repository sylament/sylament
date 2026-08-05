# Tables — port progress

Ports [`filament/tables`](../../../vendor/filament/tables) into
[`packages/tables`](../../../packages/tables). The table builder: query, columns,
filters, actions, grouping, summaries.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

The tables below list what the minimal tables vertical (M1 · 5) has landed so
far; the audit against [`vendor/filament/tables`](../../../vendor/filament/tables)
is still pending, which is what each section's remaining
[unaudited marker](../README.md#an-unaudited-table-says-so) says.
[`support/component.md`](../support/component.md) is the shape each detail file
follows.

## Components

`Table` plus the column, filter, action, and grouping catalogs —
[`src/Table.php`](../../../vendor/filament/tables/src/Table.php),
[`src/Columns`](../../../vendor/filament/tables/src/Columns),
[`src/Filters`](../../../vendor/filament/tables/src/Filters),
[`src/Actions`](../../../vendor/filament/tables/src/Actions),
[`src/Grouping`](../../../vendor/filament/tables/src/Grouping).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Table`](../../../packages/tables/src/Table.php) | [`src/Table.php`](../../../vendor/filament/tables/src/Table.php) | in progress | [table.md](table.md) |
| [`Column`](../../../packages/tables/src/Columns/Column.php) | [`src/Columns/Column.php`](../../../vendor/filament/tables/src/Columns/Column.php) | in progress | [column.md](column.md) |
| [`TextColumn`](../../../packages/tables/src/Columns/TextColumn.php) | [`src/Columns/TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php) | in progress | [text-column.md](text-column.md) |
| _rest not audited yet_ | | | |

## Features

Cross-cutting concerns and traits —
[`src/Concerns`](../../../vendor/filament/tables/src/Concerns) (host-level) and
[`src/Table`](../../../vendor/filament/tables/src/Table) (table-level).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`HasTable`](../../../packages/tables/src/Concerns/HasTable.php) / [`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php) | [`src/Concerns/InteractsWithTable.php`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php), [`src/Contracts/HasTable.php`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | in progress | [has-table.md](has-table.md) |
| _rest not audited yet_ | | | |

## Supporting surface

Enums, contracts, templates, test helpers —
[`src/Enums`](../../../vendor/filament/tables/src/Enums),
[`src/Contracts`](../../../vendor/filament/tables/src/Contracts),
[`src/Testing`](../../../vendor/filament/tables/src/Testing),
[`src/View`](../../../vendor/filament/tables/src/View).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |
