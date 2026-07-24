# Support — port progress

Ports [`filament/support`](../../../vendor/filament/support) into
[`packages/support`](../../../packages/support). Foundation for every other bundle:
the base component model, shared UI components, colors, icons, assets, helpers.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

The tables below are structure only; only [`Component`](component.md) has a
detail file so far, and it is the shape the rest follow.

## Components

The shared UI kit —
[`src/Components`](../../../vendor/filament/support/src/Components).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Component`](../../../packages/support/src/Component/Component.php) | [`Components/Component.php`](../../../vendor/filament/support/src/Components/Component.php) | in progress | [component.md](component.md) |
| _rest not audited yet_ | | | |

## Features

Cross-cutting concerns and traits —
[`src/Concerns`](../../../vendor/filament/support/src/Concerns).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Supporting surface

Enums, contracts, colors, icons, assets, templates, helpers —
[`src/Enums`](../../../vendor/filament/support/src/Enums),
[`src/Contracts`](../../../vendor/filament/support/src/Contracts),
[`src/Colors`](../../../vendor/filament/support/src/Colors),
[`src/Icons`](../../../vendor/filament/support/src/Icons),
[`src/Assets`](../../../vendor/filament/support/src/Assets),
[`src/View`](../../../vendor/filament/support/src/View).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |
