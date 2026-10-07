---
description: A component that renders one of its host's own named schemas in place. (draft)
ports:
  - vendor/filament/schemas/src/Components/EmbeddedSchema.php
owns:
  - packages/schemas/src/Components/EmbeddedSchema.php
  - packages/schemas/templates/partials/EmbeddedSchema/
---
# EmbeddedSchema (schemas)

[`Sylament\Schemas\Components\EmbeddedSchema`](../../../packages/schemas/src/Components/EmbeddedSchema.php) — a component that renders **one of its host's own named schemas** in place.
It is how a host composes a schema out of other schemas it already declares: an action's modal schema can hold `EmbeddedSchema::make('form')` and get the page's form tree itself, rather than a second tree built from the same definition.
It ports [`schemas/src/Components/EmbeddedSchema.php`](../../../vendor/filament/schemas/src/Components/EmbeddedSchema.php), and its template is [`partials/EmbeddedSchema/index.html.twig`](../../../packages/schemas/templates/partials/EmbeddedSchema/index.html.twig) ([component-views.md](component-views.md)).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(string $name)`](../../../packages/schemas/src/Components/EmbeddedSchema.php) | [`make()` / `__construct()`](../../../vendor/filament/schemas/src/Components/EmbeddedSchema.php) | done | — | [diverges](#the-constructor-argument-arrives-through-the-setter) |
| [`name()` / `getName()`](../../../packages/schemas/src/Components/EmbeddedSchema.php) | [`name()` / `getName()`](../../../vendor/filament/schemas/src/Components/EmbeddedSchema.php) | done | — | — |
| [The partial's `renderComponent()` emission](../../../packages/schemas/templates/partials/EmbeddedSchema/index.html.twig) | [`toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/EmbeddedSchema.php) | done | — | [semantics](#the-partial-renders-the-schema-object-not-the-name) |

[`Unit/Components/EmbeddedSchemaTest`](../../../packages/schemas/tests/Unit/Components/EmbeddedSchemaTest.php) covers the literal and closure arms of the name.
[`Integration/Components/EmbeddedSchemaRenderTest`](../../../packages/schemas/tests/Integration/Components/EmbeddedSchemaRenderTest.php) renders an embed pointing at a second named schema on its host and asserts that schema's own output lands, off the host's cached instance.

## Semantics

### The partial renders the schema object, not the name

The one-line partial is `{{ renderComponent(component.getLiveComponent.getSchema(component.getName)) }}` — support's [`renderComponent()`](../support/component.md), which takes a `ViewComponent` instance, and **not** schemas' [`renderSchema()`](schema-extension.md), which takes a string name and reads the host out of the template context's `this`.
A component partial has no `this`: `ViewComponent::render()` passes `component` and nothing else, so `renderSchema()` would throw there.
The host is reached the way every component reaches it — `getLiveComponent()` off the container chain — which is exactly what Filament's `toEmbeddedHtml()` does (`$this->getLivewire()->getSchema(...)`).

### Embedding a schema that is already on the page duplicates its ids

The embed renders the host's schema object itself, and a schema's ids come from its own key path — not from where it is rendered.
So a page that embeds a schema it *also* renders in place emits every id in that tree twice, and a `<label for>` in the second copy resolves to the first copy's input.
The live case is the edit page's own edit modal ([panels](../panels/README.md#the-edit-pages-own-edit-modal-is-the-page-form-itself)), where the modal is a magnified view of the form behind it — the component's only consumer today, and the only shape that hits this at all: embedding a schema the page does *not* otherwise render costs nothing.
Filament has the same shape and no id scope to fix it with.

## Divergences

### The constructor argument arrives through the setter

Filament's `EmbeddedSchema` takes `$name` as a `final` constructor argument and builds through `app(static::class, ['name' => $name])`.
Every Sylament component is built by [`Component::newInstance()`](component.md) through the container's component locator, which cannot pass a constructor argument, so `make()` follows the [`Flex`](flex.md#the-required-schema-arrives-through-components-not-a-constructor) shape: `newInstance()`, `name($name)`, then `configure()`.
The public signature is unchanged — including Filament's own asymmetry, where `make()` accepts only a `string` while `name()` also accepts a `Closure`.
