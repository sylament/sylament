---
description: The shared concern that sets a component's visual order in its parent grid per breakpoint, through `columnOrder()`.
ports:
  - vendor/filament/support/src/Concerns/CanOrderColumns.php
owns:
  - packages/support/src/Components/Concerns/CanOrderColumns.php
---
# CanOrderColumns (support)

`Sylament\Support\Components\Concerns\CanOrderColumns` ports Filament's trait of the same name; the schemas base [`Component`](../schemas/component.md) composes it, beside [`CanSpanColumns`](can-span-columns.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`columnOrder()`](../../../packages/support/src/Components/Concerns/CanOrderColumns.php) | [`columnOrder()`](../../../vendor/filament/support/src/Concerns/CanOrderColumns.php) | done | — | [semantics](#a-scalar-order-lands-on-lg-and-a-closure-on-default) |
| [`getColumnOrder()`](../../../packages/support/src/Components/Concerns/CanOrderColumns.php) | [`getColumnOrder()`](../../../vendor/filament/support/src/Concerns/CanOrderColumns.php) | done | — | [diverges](can-span-columns.md#empty-breakpoint-keys-are-dropped-by-strict-equality-not-blank) |

## Semantics

### A scalar order lands on `lg`, and a closure on `default`

`columnOrder(2)` stores `['lg' => 2]`, while `columnOrder(fn () => 3)` appends the closure under the next integer key, and `getColumnOrder()` files a scalar it evaluates from an integer key under `default`.
Once any order is set, `getColumnOrder()` returns only the breakpoints that were set, so `columnOrder(2)` reads back as `['lg' => 2]` with no `default` key.
Filament behaves the same, and [`CanOrderColumnsTest`](../../../packages/support/tests/Unit/Components/Concerns/CanOrderColumnsTest.php) pins both cases.
