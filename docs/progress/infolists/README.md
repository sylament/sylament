# Infolists — port progress

Ports [`filament/infolists`](../../../vendor/filament/infolists) into
[`packages/infolists`](../../../packages/infolists). Read-only entries and the
display machinery shared with table columns.

**Status:** todo — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

The tables below are structure only; nothing has been audited yet.
[`support/component.md`](../support/component.md) is the shape each detail file
follows.

Two decisions are taken ahead of the audit. `Entry` extends the schemas
`Component`, as Filament v5's does: an entry is a schema component reading
schema state, not a second tree beside the schema. `TextEntry` shares the
formatting catalogue [`TextColumn`](../tables/text-column.md) folds from
`Columns\Concerns\CanFormatState` (badge, colours, icons, size/weight/font,
copyable, date/money, lists, `html()`/`markdown()`) rather than porting the
parallel copy Filament keeps in `Infolists\Components\Concerns\CanFormatState`.
`CodeEntry` stays a gap: Filament highlights through Phiki, a vendor this port
has no sign-off for.

## Components

The `Entry` base and every entry —
[`src/Components`](../../../vendor/filament/infolists/src/Components).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Features

Cross-cutting concerns and traits —
[`src/Components/Concerns`](../../../vendor/filament/infolists/src/Components/Concerns)
(entry-level) and [`src/Concerns`](../../../vendor/filament/infolists/src/Concerns)
(host-level).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Supporting surface

Contracts, templates, test helpers —
[`src/Contracts`](../../../vendor/filament/infolists/src/Contracts),
[`src/Testing`](../../../vendor/filament/infolists/src/Testing),
[`src/View`](../../../vendor/filament/infolists/src/View).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |
