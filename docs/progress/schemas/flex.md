---
description: The schemas layout row whose children sit side by side outside the schema grid, each growing to fill the space unless it opts out.
ports:
  - vendor/filament/schemas/src/Components/Flex.php
  - vendor/filament/support/src/Concerns/HasFromBreakpoint.php
owns:
  - packages/schemas/src/Components/Flex.php
  - packages/support/src/Components/Concerns/HasFromBreakpoint.php
---
# Flex (schemas)

[`Sylament\Schemas\Components\Flex`](../../../packages/schemas/src/Components/Flex.php) — the one-dimensional layout row: children sit side by side rather than in the schema grid, each growing to fill the space unless it opts out with `grow(false)`.
Its template is [`partials/Flex/index.html.twig`](../../../packages/schemas/templates/partials/Flex/index.html.twig).

Like [`Group`](group.md) and [`Section`](section.md) it can entangle its subtree with a singular association ([`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md)), so a flex row over an author's fields writes back through `author`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(array\|Closure $schema)`](../../../packages/schemas/src/Components/Flex.php) — the schema is required | [`make()` / `__construct()`](../../../vendor/filament/schemas/src/Components/Flex.php) | done | — | [diverges](#the-required-schema-arrives-through-components-not-a-constructor) |
| [`HasFromBreakpoint`](../../../packages/support/src/Components/Concerns/HasFromBreakpoint.php) — `from()` / `getFromBreakpoint()` | [`support/Concerns/HasFromBreakpoint.php`](../../../vendor/filament/support/src/Concerns/HasFromBreakpoint.php) | done | — | — |
| [`HasVerticalAlignment`](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) — `verticalAlignment()` / `verticallyAlign*()` | [`support/Concerns/HasVerticalAlignment.php`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | [has-vertical-alignment.md](../support/has-vertical-alignment.md) |
| [`HasAlignment`](../../../packages/support/src/Components/Concerns/HasAlignment.php) — `align*()` through `getAlignmentClass()` | [`support/Concerns/HasAlignment.php`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | [has-alignment.md](../support/has-alignment.md) |
| [`isDense()`](../../../packages/schemas/src/Components/Concerns/HasGap.php) — `dense()` drives `sy-dense` | [`'fi-dense' => $this->isDense()`](../../../vendor/filament/schemas/src/Components/Flex.php) | done | — | [has-gap.md](has-gap.md) |
| [`EntanglesStateWithSingularRelationship`](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | [`Concerns/EntanglesStateWithSingularRelationship.php`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | [entangles-state-with-singular-relationship.md](entangles-state-with-singular-relationship.md) · [diverges](#flex-implements-canentanglewithsingularrelationships-which-filaments-flex-omits) |
| [`getExtraTemplateData()` + the template](../../../packages/schemas/src/Components/Flex.php) — root classes, per-child `sy-growable`, the action branch | [`toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Flex.php) | done | — | [semantics](#cangrow-defaults-to-true-so-an-unconfigured-child-fills-the-row) |
| Per-child `filamentSchemaComponent` `x-data`, `x-init` watchers and `x-bind:class` visibility | [`toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Flex.php) | skipped | — | [diverges](#the-per-child-alpine-visibility-and-state-wiring-is-not-ported) |
| `publishedViewOverrideCheckPath` (Blade view-override detection) | [`Flex.php`](../../../vendor/filament/schemas/src/Components/Flex.php) | skipped | — | [diverges](../support/view-component.md#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |

## Semantics

### `canGrow()` defaults to true, so an unconfigured child fills the row

The template asks each non-action child `canGrow()` with the trait's default `true` ([`CanGrow`](../../../packages/support/src/Components/Concerns/CanGrow.php)), so a plain `Flex::make([...])` gives every child `sy-growable` and they share the row evenly.
`grow(false)` is what takes a child out of the growth pool.
Action and action-group children are never growable — they render inside a bare `div`, exactly as Filament does.

### The root carries no `id` attribute

Unlike [`Group`](group.md), whose Filament original merges `['id' => $this->getId()]` into its attribute bag, Filament's `Flex` merges only the extra attributes and the class list — so `id()` on a `Flex` reaches the DOM only through `extraAttributes()`.
The port matches, because a divergence here would change the id a consumer's CSS or test selects on.

## Divergences

### The required schema arrives through `components()`, not a constructor

Filament's `Flex` takes its schema as a `final` constructor argument and builds through `app(static::class, ['schema' => $schema])`.
Every Sylament component is built by [`Component::newInstance()`](component.md) through the container's component locator, which cannot pass a constructor argument, so `make()` follows the [`Group`](group.md) shape: `newInstance()`, then `components($schema)`, then `configure()`.
The parameter stays **required**, so the API reads the same at the call site; `schema()` is not an alias here any more than anywhere else ([component.md](component.md#the-schema-alias-is-not-ported)).

### The per-child Alpine visibility and state wiring is not ported

Filament wraps each non-action child in a `div` carrying `x-data="filamentSchemaComponent({path, containerPath, $wire})"`, an `x-init` that installs `$wire.watch` callbacks for `afterStateUpdatedJs()`, and an `x-bind:class` toggling `fi-hidden` from `hiddenJs`/`visibleJs`.
None of that arm exists in this port — there is no `hiddenJs`/`visibleJs` ([`CanBeHidden`](can-be-hidden.md)), no `afterStateUpdatedJs`, and no per-component client state object — so the wrapper is a bare `div` that carries `sy-growable` or nothing.
A child hidden on the server is left out on both sides, and only Filament's client-side visibility condition adds `fi-hidden`, so the ported `> .fi-hidden` rule never matches ([styles.md](styles.md#flex-css-keeps-the-hidden-child-rule-the-port-never-emits)).
The [`Schema` partial](schema.md#the-schema-renders-visible-children-only-without-hidden-placeholders) makes the same call for the same reasons, and both reverse together if the client-side state arm is ported.

### `Flex` implements `CanEntangleWithSingularRelationships`, which Filament's `Flex` omits

Filament's `Flex` uses the `EntanglesStateWithSingularRelationship` trait without the contract, and the trait's hook closures accept it because they type `Component | CanEntangleWithSingularRelationships`.
The port's hook closures type the contract alone ([`EntanglesStateWithSingularRelationship.php`](../../../packages/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php)), so `Flex` implements it, as `Group`, `Grid`, `Fieldset` and `Section` do on both sides.
