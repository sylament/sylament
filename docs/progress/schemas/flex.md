# Flex (schemas)

[`Sylament\Schemas\Components\Flex`](../../../packages/schemas/src/Components/Flex.php) — the one-dimensional layout row: children sit side by side rather than in the schema grid, each growing to fill the space unless it opts out with `grow(false)`.
It ports [`schemas/src/Components/Flex.php`](../../../vendor/filament/schemas/src/Components/Flex.php) and its template is [`partials/Flex/index.html.twig`](../../../packages/schemas/templates/partials/Flex/index.html.twig) ([component-views.md](component-views.md)).

Like [`Group`](group.md) and [`Section`](section.md) it can entangle its subtree with a singular association ([`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md)), so a flex row over an author's fields writes back through `author`.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Flex.php`](../../../vendor/filament/schemas/src/Components/Flex.php) — the class, its trait set, and `toEmbeddedHtml()`
- [`schemas/resources/css/components/flex.css`](../../../vendor/filament/schemas/resources/css/components/flex.css) — the stylesheet, ported as [`components/flex.css`](../../../packages/schemas/assets/src/styles/components/flex.css) ([styles.md](styles.md))
- [`support/src/Concerns/HasFromBreakpoint.php`](../../../vendor/filament/support/src/Concerns/HasFromBreakpoint.php), [`HasVerticalAlignment.php`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) — the two support concerns this component is the first consumer of

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(array\|Closure $schema)`](../../../packages/schemas/src/Components/Flex.php) — the schema is required | [`make()` / `__construct()`](../../../vendor/filament/schemas/src/Components/Flex.php) | done | — | [diverges](#the-required-schema-arrives-through-components-not-a-constructor) |
| [`HasFromBreakpoint`](../../../packages/support/src/Components/Concerns/HasFromBreakpoint.php) — `from()` / `getFromBreakpoint()` | [`support/Concerns/HasFromBreakpoint.php`](../../../vendor/filament/support/src/Concerns/HasFromBreakpoint.php) | done | — | — |
| [`HasVerticalAlignment`](../../../packages/support/src/Components/Concerns/HasVerticalAlignment.php) — `verticalAlignment()` / `verticallyAlign*()` | [`support/Concerns/HasVerticalAlignment.php`](../../../vendor/filament/support/src/Concerns/HasVerticalAlignment.php) | done | — | — |
| [`HasAlignment`](../../../packages/support/src/Components/Concerns/HasAlignment.php) — `align*()` through `getAlignmentClass()` | [`support/Concerns/HasAlignment.php`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | — |
| [`HasGap`](has-gap.md) — `dense()` drives `sy-dense` | [`isDense()`](../../../vendor/filament/schemas/src/Components/Flex.php) | done | — | — |
| [`EntanglesStateWithSingularRelationship`](entangles-state-with-singular-relationship.md) | [`Concerns/EntanglesStateWithSingularRelationship.php`](../../../vendor/filament/schemas/src/Components/Concerns/EntanglesStateWithSingularRelationship.php) | done | — | — |
| [`getExtraTemplateData()` + the template](../../../packages/schemas/src/Components/Flex.php) — root classes, per-child `sy-growable`, the action branch | [`toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Flex.php) | done | — | [semantics](#hidden-children-are-filtered-server-side-not-class-toggled) |
| Per-child `filamentSchemaComponent` `x-data`, `x-init` watchers and `x-bind:class` visibility | [`toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Flex.php) | skipped | — | [diverges](#the-per-child-alpine-visibility-and-state-wiring-is-not-ported) |
| `publishedViewOverrideCheckPath` (Blade view-override detection) | [`Flex.php`](../../../vendor/filament/schemas/src/Components/Flex.php) | skipped | — | — |

[`Unit/Components/FlexTest`](../../../packages/schemas/tests/Unit/Components/FlexTest.php) covers the array and closure schema arms, the breakpoint, vertical alignment, alignment class, the container-inherited density, the relationship entanglement and `configureUsing()`.
[`Integration/Components/FlexRenderTest`](../../../packages/schemas/tests/Integration/Components/FlexRenderTest.php) covers the rendered DOM: the default `sy-from-default` root with no alignment classes, `from('md')`, `verticallyAlignCenter()`, a raw vertical-alignment string, `alignBetween()`, `dense()`, a growable child in `div.sy-growable`, a `grow(false)` child in a bare `div`, an `Action` child in a bare `div`, a hidden child omitted, and extra attributes merged onto the root.

## Semantics

### Hidden children are filtered server-side, not class-toggled

`getExtraTemplateData()` walks `getChildSchema()?->getComponents(withHidden: false)`, so a hidden child never reaches the template — the same choice the [`Schema` partial](schema.md) makes.
Filament renders every child and lets the Alpine binding add `fi-hidden`, which is why its stylesheet carries a `> .fi-hidden` rule; ours keeps the ported rule but never emits the class ([styles.md](styles.md#flex-css-keeps-the-hidden-child-rule-the-port-never-emits)).

### `canGrow()` defaults to true, so an unconfigured child fills the row

The template asks each non-action child `canGrow()` with the trait's default `true` ([`CanGrow`](../../../packages/support/src/Components/Concerns/CanGrow.php)), so a plain `Flex::make([...])` gives every child `sy-growable` and they share the row evenly.
`grow(false)` is what takes a child out of the growth pool.
Action and action-group children are never growable — they render inside a bare `div`, exactly as Filament does.

## Divergences

### The required schema arrives through `components()`, not a constructor

Filament's `Flex` takes its schema as a `final` constructor argument and builds through `app(static::class, ['schema' => $schema])`.
Every Sylament component is built by [`Component::newInstance()`](component.md) through the container's component locator, which cannot pass a constructor argument, so `make()` follows the [`Group`](group.md) shape: `newInstance()`, then `components($schema)`, then `configure()`.
The parameter stays **required**, so the API reads the same at the call site; `schema()` is not an alias here any more than anywhere else ([component.md](component.md#the-schema-alias-is-not-ported)).

### The per-child Alpine visibility and state wiring is not ported

Filament wraps each non-action child in a `div` carrying `x-data="filamentSchemaComponent({path, containerPath, $wire})"`, an `x-init` that installs `$wire.watch` callbacks for `afterStateUpdatedJs()`, and an `x-bind:class` toggling `fi-hidden` from `hiddenJs`/`visibleJs`.
None of that arm exists in this port — there is no `hiddenJs`/`visibleJs` ([`CanBeHidden`](can-be-hidden.md)), no `afterStateUpdatedJs`, and no per-component client state object — so the wrapper is a bare `div` that carries `sy-growable` or nothing.
The [`Schema` partial](component-views.md#the-schema-template-omits-the-livecomponent-wiring-hidden-placeholders-and-grid-liberation) made the same call for the same reasons; both return together if the client-side state arm is ever ported.

### The root carries no `id` attribute

Unlike [`Group`](group.md), whose Filament original merges `['id' => $this->getId()]` into its attribute bag, Filament's `Flex` merges only the extra attributes and the class list — so `id()` on a `Flex` reaches the DOM only through `extraAttributes()`.
This port matches, deliberately: a divergence here would change the id a consumer's CSS or test selects on.
