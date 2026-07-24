# Schemas — port progress

Ports [`filament/schemas`](../../../vendor/filament/schemas) into
[`packages/schemas`](../../../packages/schemas). The component model: `Schema`, the
base `Component`, layout containers, and the state loop.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

The tables below are structure only; nothing has been audited yet.
[`support/component.md`](../support/component.md) is the shape each detail file
follows.

## Components

`Schema`, the base `Component`, and the layout containers —
[`src/Components`](../../../vendor/filament/schemas/src/Components).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Features

Cross-cutting concerns and traits —
[`src/Concerns`](../../../vendor/filament/schemas/src/Concerns) (schema-level) and
[`src/Components/Concerns`](../../../vendor/filament/schemas/src/Components/Concerns)
(component-level).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Supporting surface

Contracts, state casts, `$get`/`$set` utilities, templates, test helpers —
[`src/Contracts`](../../../vendor/filament/schemas/src/Contracts),
[`src/Testing`](../../../vendor/filament/schemas/src/Testing),
[`src/View`](../../../vendor/filament/schemas/src/View).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |
