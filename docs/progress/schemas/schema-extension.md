# SchemaExtension (schemas)

[`Sylament\Schemas\Twig\Extension\SchemaExtension`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php)
— the Twig extension that exposes the `renderSchema(name)` function. Given a
render context, it resolves the schema-host component (`context['this']`), calls
`host.getSchema(name)`, and delegates to support's
[`ComponentExtension`](../support/component.md)`.renderComponent()` for the
actual Twig render. It is the schemas-layer parallel of support's
`renderComponent` — same layering, one level up — and has no single Filament
file behind it (Filament renders schemas through Blade/Livewire partials), so it
is a Sylament-invented supporting surface.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- none — Sylament-invented; see the [divergence](#schemaextension-has-no-11-filament-source--it-is-the-twig-side-parallel-of-supports-componentextension) below

## Surface

Every point of this extension. _Blocked by_ names what has to land first.
_Detail_ carries a **diverges** link to the permanent note in
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getFunctions()` exposes `renderSchema` (`needs_environment` + `needs_context`)](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php) | — (no Filament equivalent) | done | — | [diverges](#schemaextension-has-no-11-filament-source--it-is-the-twig-side-parallel-of-supports-componentextension) |
| [`renderSchema()` resolves the host from context and renders the named schema to `Markup` via `ComponentExtension`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php) | — (no Filament equivalent) | done | — | — |
| [`renderSchema()` type-guard: throws `RuntimeException` when the host is missing or not `InteractsWithSchemas`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php) | — (no Filament equivalent) | done | — | — |

## Divergences

### SchemaExtension has no 1:1 Filament source — it is the Twig-side parallel of support's ComponentExtension

Filament renders schemas through Blade directives and Livewire view partials, so
there is no single PHP file to port. Sylament factors schema rendering into a
Twig extension instead: `renderSchema(name)` reads the host from the render
context (`context['this']`, the UX LiveComponent that carries the schema),
type-checks it against the Sylament interface
[`InteractsWithSchemas`](interacts-with-schemas.md), calls `host.getSchema(name)`,
and delegates to support's
[`ComponentExtension`](../support/component.md)`.renderComponent()` — the same
layering `renderComponent` itself uses. This is why it earns a row in the schemas
README *Supporting surface* table rather than mapping to any file under
`vendor/filament/schemas/src`.

_See also: the global [rename table](../renames.md) — the host-role name swap
(Sylament's interface `InteractsWithSchemas` ports Filament's `Contracts\HasSchemas`)
and the Livewire → UX LiveComponent `this` translation are systematic, recorded
there, not per-component divergences._
