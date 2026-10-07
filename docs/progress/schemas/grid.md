---
description: '`Sylament\Schemas\Components\Grid` is the basic layout component: it holds a column count and a host element, and nothing else. (draft)'
ports:
  - vendor/filament/schemas/src/Components/Grid.php
owns:
  - packages/schemas/src/Components/Grid.php
  - packages/schemas/templates/partials/Grid/
---
# Grid (schemas)

[`Sylament\Schemas\Components\Grid`](../../../packages/schemas/src/Components/Grid.php) is the basic layout component: it holds a column count and a host element, and nothing else.
The grid itself is resolved one level down — the child [`Schema`](schema.md) reads the columns through `Schema::getAllColumns()`'s parent fallback, and support's `GridExtension` turns that map into the `sy-grid-cols` classes plus the inline `--cols-*` custom properties.
So `Grid::make(3)` contributes a column map and a `<div>` carrying its id and extra attributes; the layout is the child schema's doing.

It composes the shared [`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md) concern and implements its `CanEntangleWithSingularRelationships` contract, so `Grid::make()->relationship('profile')` binds its children to a to-one Doctrine association; that concern's file carries the status of what is and is not ported.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / column forwarding](../../../packages/schemas/src/Components/Grid.php) | [`Grid::make()` / `__construct()`](../../../vendor/filament/schemas/src/Components/Grid.php) | done | — | [diverges](#make-accepts-a-closure-where-filaments-constructor-does-not) |
| [Twig render (host element + child schema)](../../../packages/schemas/templates/partials/Grid/index.html.twig) | [`Grid::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Grid.php) | done | — | [diverges](#the-root-element-carries-the-sy-marker-and-a-component-hook-class) |
| [`id` + extra attributes on the host element](../../../packages/schemas/templates/partials/Grid/index.html.twig) | [`Grid::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Grid.php) | done | — | — |

Covered by [`GridTest`](../../../packages/schemas/tests/Unit/Components/GridTest.php) (the column unions, the closure resolution, `$component` injection and `configureUsing()`) and [`GridRenderTest`](../../../packages/schemas/tests/Integration/Components/GridRenderTest.php), which asserts on the emitted `--cols-lg` custom property rather than a class — that variable is the proof that the column map reached the child schema.
Its last case pins the attribute precedence: `attrs()` lets the later source win, so the template lists the id **after** the extra attributes and `getId()` beats a caller's `extraAttributes(['id' => …])`, matching Filament's `->merge(['id' => $this->getId()])->merge($this->getExtraAttributes())` and [`Section`](section.md)'s placement.
[`fieldset.md`](fieldset.md#the-chrome-lives-in-a-support-twig-component) carries the mechanism in full, since its two-bag bridge is where getting it backwards is easiest.

## Divergences

### The root element carries the `sy` marker and a component hook class

Filament's `Grid::toEmbeddedHtml()` emits a `<div>` with the component's `id` and extra attributes and no class at all.
Sylament's root carries `class="sy sy-sc-grid"`: the bare `sy` marker is mandatory on every component root ([why](../../internals/styling.md#why-does-every-component-root-carry-the-bare-marker-class)) — it is how the scoped Tailwind preflight identifies Sylament markup — and `sy-sc-grid` follows the hook-class convention the rest of the port uses.
Both are inert today; no CSS rule matches either.

### Grid and Group get one template each, where Filament shares one view

Filament points both components' `publishedViewOverrideCheckPath` at `filament-schemas::components.grid`, so `Group` renders through the grid view.
The port's per-component template layout gives each its own `partials/<Name>/index.html.twig`, which is what lets each name its own hook class ([`group.md`](group.md) records Group's side).

### `make()` accepts a `Closure`, where Filament's constructor does not

Filament types `Grid::__construct()` as `array | int | null` even though `columns()` itself accepts a `Closure`.
Ours takes `columns()`' union verbatim, so `Grid::make(fn () => 3)` works and the factory cannot be narrower than the setter it forwards to.

_See also: the global [rename table](../renames.md) — `make()` arguments moving from constructors to setters, and the `toEmbeddedHtml()` → Twig-template render idiom, are systematic translations recorded there, not per-component divergences._
