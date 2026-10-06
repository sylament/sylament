# Group (schemas)

[`Sylament\Schemas\Components\Group`](../../../packages/schemas/src/Components/Group.php)
is [`Grid`](grid.md)'s schema-in-the-factory sibling: it takes its children
rather than a column count, sets no columns of its own, and so its child
[`Schema`](schema.md) falls back to the default map (`default => 1`). The result
is that a `Group` claims one cell of the outer grid and stacks everything inside
it — which is the whole point of the component, and what makes it the way to keep
a run of fields together inside a wider layout.

It composes the shared
[`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md)
concern and implements its `CanEntangleWithSingularRelationships` contract, so
`Group::make()->relationship('profile')` binds its children to a to-one Doctrine
association; that concern's file carries the status of what is and is not ported.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Group.php`](../../../vendor/filament/schemas/src/Components/Group.php)
  — the constructor's schema forwarding and the `toEmbeddedHtml()` render path

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries a **diverges** link to the permanent note in
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / schema forwarding](../../../packages/schemas/src/Components/Group.php) | [`Group::make()` / `__construct()`](../../../vendor/filament/schemas/src/Components/Group.php) | done | — | [diverges](#make-takes-the-full-components-union) |
| [Twig render (host element + child schema)](../../../packages/schemas/templates/partials/Group/index.html.twig) | [`Group::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Group.php) | done | — | [diverges](#the-root-element-carries-the-sy-marker-and-a-component-hook-class) |
| [`id` + extra attributes on the host element](../../../packages/schemas/templates/partials/Group/index.html.twig) | [`Group::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Group.php) | done | — | — |

Covered by
[`GroupTest`](../../../packages/schemas/tests/Unit/Components/GroupTest.php) (the
array / single-component / closure / empty factory arms and `configureUsing()`)
and
[`GroupRenderTest`](../../../packages/schemas/tests/Integration/Components/GroupRenderTest.php),
whose third case is the load-bearing one: it asserts the child schema emits
`--cols-default: repeat(1, …)` and **no** `--cols-lg`, which is what proves a
Group's children do not join the outer grid. The last case pins the same
attribute precedence [`grid.md`](grid.md) records — the id source sits after the
extra attributes, so `getId()` wins over a caller's
`extraAttributes(['id' => …])`.

## Divergences

### The root element carries the `sy` marker and a component hook class

Filament's `Group::toEmbeddedHtml()` emits a `<div>` with the component's `id`
and extra attributes and no class at all. Sylament's root carries
`class="sy sy-sc-group"`: the bare `sy` marker is
mandatory on every component root ([why](../../internals/styling.md#why-does-every-component-root-carry-the-bare-marker-class)) — it is how the scoped
Tailwind preflight identifies Sylament markup — and `sy-sc-group` follows the
hook-class convention the rest of the port uses. Both are inert today; no CSS
rule matches either. (Filament renders `Group` through the shared grid view,
which is why [`grid.md`](grid.md) records the one-template-each split.)

### `make()` takes the full `components()` union

Filament types `Group::__construct()` as `array | Closure`; ours takes
`components()`' union verbatim, so a single component, a `Schema`, a string or a
`Markup` all pass. That is the existing
[components()/childComponents() accept a single component](component.md#componentschildcomponents-accept-a-single-component-not-just-an-array)
divergence reaching the factory, not a new one — the factory cannot be narrower
than the setter it forwards to.

_See also: the global [rename table](../renames.md) — `make()` arguments moving
from constructors to setters, and the `toEmbeddedHtml()` → Twig-template render
idiom, are systematic translations recorded there, not per-component
divergences._
