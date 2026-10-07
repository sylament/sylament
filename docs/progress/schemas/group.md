---
description: The schemas layout component that takes its children in the factory and claims one cell of the outer grid, stacking them inside it.
ports:
  - vendor/filament/schemas/src/Components/Group.php
owns:
  - packages/schemas/src/Components/Group.php
  - packages/schemas/templates/partials/Group/
---
# Group (schemas)

[`Sylament\Schemas\Components\Group`](../../../packages/schemas/src/Components/Group.php) is [`Grid`](grid.md)'s schema-in-the-factory sibling: it takes its children rather than a column count, sets no columns of its own, and so its child [`Schema`](schema.md) falls back to the default map (`default => 1`).
The result is that a `Group` claims one cell of the outer grid and stacks everything inside it — which is the whole point of the component, and what makes it the way to keep a run of fields together inside a wider layout.

It composes the shared [`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md) concern and implements its `CanEntangleWithSingularRelationships` contract, so `Group::make()->relationship('profile')` binds its children to a to-one Doctrine association.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / schema forwarding](../../../packages/schemas/src/Components/Group.php) | [`Group::make()` / `__construct()`](../../../vendor/filament/schemas/src/Components/Group.php) | done | — | [diverges](#make-takes-the-full-components-union) |
| [Twig render (host element + child schema)](../../../packages/schemas/templates/partials/Group/index.html.twig) | [`Group::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Group.php) | done | — | [diverges](#the-root-element-carries-the-sy-marker-and-a-component-hook-class) |
| [`id` + extra attributes on the host element](../../../packages/schemas/templates/partials/Group/index.html.twig) | [`Group::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Group.php) | done | — | [fieldset.md](fieldset.md#the-chrome-lives-in-a-support-twig-component) |

## Divergences

### The root element carries the `sy` marker and a component hook class

Filament's `Group::toEmbeddedHtml()` emits a `<div>` with the component's `id` and extra attributes and no class at all.
Sylament's root carries `class="sy sy-sc-group"`: the bare `sy` marker is mandatory on every component root ([why](../../internals/styling.md#why-does-every-component-root-carry-the-bare-marker-class)) — it is how the scoped Tailwind preflight identifies Sylament markup — and `sy-sc-group` follows the hook-class convention the rest of the port uses.
No stylesheet rule matches either.

### `make()` takes the full `components()` union

Filament types `Group::__construct()` as `array | Closure`; ours takes `components()`' union verbatim, so a single component, a `Schema`, a string or a `Markup` all pass.
That is the [components()/childComponents() accept a single component](component.md#componentschildcomponents-accept-a-single-component-not-just-an-array) divergence reaching the factory, because the factory cannot be narrower than the setter it forwards to.
