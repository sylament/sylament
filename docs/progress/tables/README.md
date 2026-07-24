# Tables — port progress

Ports [`filament/tables`](../../../vendor/filament/tables) into
[`packages/tables`](../../../packages/tables). The table builder: query, columns,
filters, actions, grouping, summaries.

**Status:** todo — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

The tables below are structure only; nothing has been audited yet.
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
| _not audited yet_ | | | |

## Features

Cross-cutting concerns and traits —
[`src/Concerns`](../../../vendor/filament/tables/src/Concerns) (host-level) and
[`src/Table`](../../../vendor/filament/tables/src/Table) (table-level).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Supporting surface

Enums, contracts, templates, test helpers —
[`src/Enums`](../../../vendor/filament/tables/src/Enums),
[`src/Contracts`](../../../vendor/filament/tables/src/Contracts),
[`src/Testing`](../../../vendor/filament/tables/src/Testing),
[`src/View`](../../../vendor/filament/tables/src/View).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |
