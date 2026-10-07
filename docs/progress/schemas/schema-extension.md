---
description: The Twig extension behind renderSchema(), which renders a host LiveComponent's named schema from a template.
ports: []
owns:
  - packages/schemas/src/Twig/Extension/
---
# SchemaExtension (schemas)

[`Sylament\Schemas\Twig\Extension\SchemaExtension`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php) registers the `renderSchema(name = 'schema')` Twig function.
Given a render context, it resolves the schema host (`context['this']`), calls `host.getSchema(name)`, and delegates to support's [`ComponentExtension`](../support/component.md)`.renderComponent()` for the render, the same layering one package up.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`getFunctions()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php) registers `renderSchema` | — (no Filament equivalent) | done | — | [diverges](#renderschema-replaces-filaments-magic-schema-property-in-a-template) |
| [`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php), name defaulting to `'schema'` | — (no Filament equivalent) | done | — | [diverges](#renderschema-replaces-filaments-magic-schema-property-in-a-template) |
| [`renderSchema()`](../../../packages/schemas/src/Twig/Extension/SchemaExtension.php) host guard | — (no Filament equivalent) | done | — | — |

## Divergences

### `renderSchema()` replaces Filament's magic schema property in a template

A Filament page view renders a named schema by echoing a property, as in `{{ $this->content }}` (`vendor/filament/filament/resources/views/pages/page.blade.php`): `ResolvesDynamicLivewireProperties::__get()` resolves the name to the schema, and Blade's echo calls its `toHtml()`.
The port has no magic property, so a template names the schema in a function call instead: `renderSchema('content')` reads the host from the render context (`context['this']`, the UX LiveComponent that carries the schema), checks it against [`InteractsWithSchemas`](interacts-with-schemas.md), and throws a `RuntimeException` when the host is missing or does not implement it.
Why the dynamic properties stay out is on [has-schemas.md](has-schemas.md#dynamic-livewire-properties-are-not-ported-because-they-hide-from-phpstan).
