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

Enums, contracts, the icon-alias registry, the `renderTable()` Twig helper,
templates, test helpers, and the bundle wiring —
[`src/Enums`](../../../vendor/filament/tables/src/Enums),
[`src/Contracts`](../../../vendor/filament/tables/src/Contracts),
[`src/Testing`](../../../vendor/filament/tables/src/Testing),
[`src/View`](../../../vendor/filament/tables/src/View),
[`TablesServiceProvider.php`](../../../vendor/filament/tables/src/TablesServiceProvider.php).
A row whose Ports cell names no Filament source is Sylament-only — nothing to
diff against. The host contract [`InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php)
is not listed here: it is inseparable from the trait it pairs with, so it shares
that row in [Features](#features).

The bundle owns the two Twig-side rows as well as its own, so all three share
[tables-bundle.md](tables-bundle.md): what `loadExtension()` wires and why
Filament's provider has no counterpart for it, the `renderTable()` helper, and
the alias set's growth order.

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`TableExtension`](../../../packages/tables/src/Twig/Extension/TableExtension.php) (`renderTable()`) | — (Sylament-only; mirrors schemas' [`SchemaExtension`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php)) | done | [tables-bundle.md](tables-bundle.md#the-bundle-registers-a-rendertable-twig-extension-which-filament-has-no-counterpart-for) |
| [`TablesIconAlias`](../../../packages/tables/src/Twig/TablesIconAlias.php) | [`View/TablesIconAlias.php`](../../../vendor/filament/tables/src/View/TablesIconAlias.php) | in progress | [tables-bundle.md](tables-bundle.md#the-alias-set-is-one-constant-of-filaments-23) |
| [`SylamentTablesBundle`](../../../packages/tables/src/SylamentTablesBundle.php) | [`TablesServiceProvider.php`](../../../vendor/filament/tables/src/TablesServiceProvider.php) | in progress | [tables-bundle.md](tables-bundle.md) |
| _rest not audited yet_ | | | |
