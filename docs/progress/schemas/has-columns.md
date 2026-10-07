---
description: The column-count concern that `Schema` and the schemas `Component` share, folding `columns()` calls into a breakpoint map for the grid.
ports:
  - vendor/filament/schemas/src/Concerns/HasColumns.php
owns:
  - packages/schemas/src/Components/Concerns/HasColumns.php
---
# HasColumns (schemas)

[`Sylament\Schemas\Components\Concerns\HasColumns`](../../../packages/schemas/src/Components/Concerns/HasColumns.php) — the shared grid concern used by **both** [`Schema`](schema.md) and the schemas [`Component`](component.md).
It gives a component a `columns` property that accepts a literal int, a responsive `breakpoint => int` map, or a `Closure`, and folds successive `columns()` calls plus per-entry closure evaluation into a `default`/`sm`/`md`/`lg`/`xl`/`2xl` map for the grid template.
It ports Filament's identically-named schemas concern method for method.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`columns()` setter — closure append, int→`lg` wrap, spread-merge](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::columns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | done | — | — |
| [`getAllColumns()` — null→default map, per-entry `evaluate()`, breakpoint/int-key resolution](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::getAllColumns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | done | — | [diverges](#the-breakpoint-loop-rebuilds-a-fresh-map-instead-of-filaments-seed-and-unset-and-gates-empty-keys-with---rather-than-blank) · [diverges](#the-schema-parent-fallback-lives-in-a-schema-override-not-an-instanceof-schema-branch-inside-the-trait) |
| [`getColumns()` — no-arg full map (`Schema/index.html.twig` `grid()`)](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::getColumns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | done | — | [diverges](../support/component-attribute-bag.md#the-sy-grid-cols-marker-class-is-emitted-unconditionally) |
| [`getColumns(breakpoint)` — single-breakpoint lookup](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::getColumns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | done | — | — |
| [`hasCustomColumns()` — has-user-set-columns predicate](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::hasCustomColumns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | in progress | — | [detail](#hascustomcolumns-has-no-caller-and-no-test) |

## Details

### `hasCustomColumns()` has no caller and no test

The predicate (`$this->columns !== null`) is ported, and nothing in `packages/` calls or tests it.
Filament's callers are its resource pages, which give a schema without custom columns a `columns(2)` default.
The port's resource pages do not guard that way, for the reason [list-records.md](../panels/list-records.md#a-modals-schema-keeps-the-resource-forms-own-column-count) records, so the row waits on a test of its own.

## Divergences

### The Schema-parent fallback lives in a `Schema` override, not an `instanceof Schema` branch inside the trait

Filament's `getAllColumns()` opens with `if ($this instanceof Schema && $this->getParentComponent()) return $this->getParentComponent()->getAllColumns();`.
Sylament drops that branch from the shared trait: the trait carries only the component flavor, and [`Schema`](schema.md) aliases the method (`use HasColumns { getAllColumns as private getOwnAllColumns; }`) then overrides `getAllColumns()` as `getParentComponent()?->getAllColumns() ?? $this->getOwnAllColumns()`.
It keeps a concern shared by two class hierarchies free of a runtime type check and lets both contexts stay statically typed, the rationale [`has-gap.md`](has-gap.md) also follows.

### The breakpoint loop rebuilds a fresh map instead of Filament's seed-and-unset, and gates empty keys with `=== ''` rather than `blank()`

Filament seeds `$columns = $this->columns ?? [defaults]` and mutates it in place, `unset()`-ing each numeric/blank key it re-homes and testing empties with Laravel's `blank($columnBreakpoint)`.
Sylament starts from `$columns = []`, appends only resolved entries (so no unset dance is needed), and drops an empty key with a strict `$columnBreakpoint === ''`.
The result is identical for every realistic input: int keys map to `'lg'` and `''` skips on both sides.
The one edge where `blank()` and `=== ''` disagree is a whitespace-only string key, which the real breakpoint set never produces.
Switching the guard to the ported [`blank()`](../../../packages/support/src/helpers.php) helper reverses it.
