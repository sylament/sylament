# HasColumns (schemas)

[`Sylament\Schemas\Components\Concerns\HasColumns`](../../../packages/schemas/src/Components/Concerns/HasColumns.php)
— the shared grid concern used by **both** [`Schema`](schema.md) and the schemas
[`Component`](component.md). It gives a component a `columns` property that
accepts a literal int, a responsive `breakpoint => int` map, or a `Closure`, and
folds successive `columns()` calls plus per-entry closure evaluation into a
`default`/`sm`/`md`/`lg`/`xl`/`2xl` map for the grid template. It ports Filament's
identically-named schemas concern method for method.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Concerns/HasColumns.php`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php)
  — the trait, setter, and getters ported here

## Surface

Every point of this trait. _Blocked by_ names what has to land first. _Detail_
carries either a **detail** write-up in [Details](#details) (deleted once the row
is **done**), a **diverges** link to the permanent note in
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`columns()` setter — closure append, int→`lg` wrap, spread-merge](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::columns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | done | — | — |
| [`getAllColumns()` — null→default map, per-entry `evaluate()`, breakpoint/int-key resolution](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::getAllColumns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | done | — | [diverges](#the-breakpoint-loop-rebuilds-a-fresh-map-instead-of-filaments-seed-and-unset-and-gates-empty-keys-with---rather-than-blank) |
| [`getColumns()` — no-arg full map (`Schema/index.html.twig` `grid()`)](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::getColumns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | done | — | [diverges](#the-sy-grid-cols-marker-class-is-emitted-unconditionally) |
| [`getColumns(breakpoint)` — single-breakpoint lookup](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::getColumns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | in progress | — | [detail](#getcolumnsbreakpoint--single-breakpoint-lookup) |
| [`hasCustomColumns()` — has-user-set-columns predicate](../../../packages/schemas/src/Components/Concerns/HasColumns.php) | [`HasColumns::hasCustomColumns()`](../../../vendor/filament/schemas/src/Concerns/HasColumns.php) | in progress | — | [detail](#hascustomcolumns--has-user-set-columns-predicate) |

The Schema-parent column fallback is not a row of this trait — it lives in a
[`Schema::getAllColumns()`](schema.md) override, documented as a
[divergence](#the-schema-parent-fallback-lives-in-a-schema-override-not-an-instanceof-schema-branch-inside-the-trait)
below.

## Details

### `getColumns(breakpoint)` — single-breakpoint lookup

The `?string $breakpoint` argument form (`return $columns[$breakpoint] ?? null`)
is ported verbatim, but no template or test passes a breakpoint —
`Schema/index.html.twig` only ever calls the no-arg form. Next step: exercise it the
first time a component needs a per-breakpoint column count (or add a direct case
to a future `HasColumnsTest`); nothing behavioral is missing.

### `hasCustomColumns()` — has-user-set-columns predicate

Ported (`$this->columns !== null`) ahead of any consumer — no reference in
schemas `src/`, templates, or tests. Panels' resource pages were briefly the one
caller, mirroring Filament's `columns(2)` default for a modal schema, and
[dropped the guard with the default](../panels/README.md#a-modals-schema-keeps-the-resource-forms-own-column-count):
the schema it guards is the blank one the host seeds per mount, so it can never
see what the resource's own `form()` set one call later. Next step: wire it in
when a caller needs to branch on whether columns were user-set (Filament uses it
in layout heuristics), then cover it.

## Divergences

### The Schema-parent fallback lives in a `Schema` override, not an `instanceof Schema` branch inside the trait

Filament's `getAllColumns()` opens with
`if ($this instanceof Schema && $this->getParentComponent()) return $this->getParentComponent()->getAllColumns();`.
Sylament drops that branch from the shared trait: the trait carries only the
component flavor, and [`Schema`](schema.md) aliases the method
(`use HasColumns { getAllColumns as private getOwnAllColumns; }`) then overrides
`getAllColumns()` as `getParentComponent()?->getAllColumns() ?? $this->getOwnAllColumns()`.
Deliberate and permanent — it keeps a concern shared by two class hierarchies
free of a runtime type check and lets both contexts stay statically typed
(mirrors the rationale in [`has-gap.md`](has-gap.md), which splits the same way).

### The breakpoint loop rebuilds a fresh map instead of Filament's seed-and-unset, and gates empty keys with `=== ''` rather than `blank()`

Filament seeds `$columns = $this->columns ?? [defaults]` and mutates it in place,
`unset()`-ing each numeric/blank key it re-homes and testing empties with Laravel's
`blank($columnBreakpoint)`. Sylament starts from `$columns = []`, appends only
resolved entries (so no unset dance is needed), and drops an empty key with a
strict `$columnBreakpoint === ''`. Behaviorally identical for every realistic
input — int keys still map to `'lg'`, `''` still skips — and the resolution cases
in [`SchemaRenderTest`](../../../packages/schemas/tests/Integration/Components/SchemaRenderTest.php)
pin it. The one edge where `blank()` and `=== ''` disagree is a whitespace-only
string key, which the real breakpoint set never produces; reversible by switching
the guard to the ported [`blank()`](../../../packages/support/src/helpers.php)
helper if that parity ever matters.

### The `sy-grid-cols` marker class is emitted unconditionally

Filament's `grid()` bag helper emits the base grid-cols class only when the
`default` breakpoint resolves to more than one column
(`($columns['default'] > 1) ? 'fi-grid-cols' : ''`). Sylament's
[`grid()`](../../../packages/support/src/Twig/Extension/GridExtension.php) Twig
function always appends `sy-grid-cols` for the `default` breakpoint. Inert either
way — the marker carries no CSS of its own on either side — but the emission is
unguarded here. The emission actually lives in the support `GridExtension`; if a
support grid-extension doc is ever created, that is the more precise home.

_See also: the global [rename table](../renames.md) — the `Concerns\` namespace
root swap and the `blank()` → `=== ''` idiom translation are systematic
translations recorded there, not per-component divergences._
