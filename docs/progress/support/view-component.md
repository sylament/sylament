---
description: The abstract `Component` subclass that adds Twig-backed rendering, and the `renderComponent()` Twig function that drives it.
ports:
  - vendor/filament/support/src/Components/ViewComponent.php
  - vendor/filament/support/src/Components/Contracts/HasEmbeddedView.php
owns:
  - packages/support/src/Components/ViewComponent.php
  - packages/support/src/Twig/Extension/ComponentExtension.php
---
# ViewComponent (support)

`Sylament\Support\Components\ViewComponent` is the abstract `Component` subclass that adds Twig-backed rendering.
It ports Filament's `ViewComponent`: a closure-evaluable template name plus one or more layers of closure-evaluable template data, merged and handed to the view at render time.
Every Sylament component that renders a template extends it, and the `renderComponent()` Twig function drives it.
The port keeps the data-plumbing surface Filament-exact while dropping the Blade and Livewire embedded-view machinery that the Twig and UX render path replaces.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`template()` / `getTemplate()`](../../../packages/support/src/Components/ViewComponent.php) | [`view()` / `getView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [diverges](#two-level-template-resolution-with-null-vs-throw-tail-difference) |
| [`defaultTemplate()`](../../../packages/support/src/Components/ViewComponent.php) | [`defaultView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [diverges](#two-level-template-resolution-with-null-vs-throw-tail-difference) |
| `getDefaultTemplate()` | [`getDefaultView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | todo | — | — |
| `hasView()` | [`hasView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |
| [`templateData()` / `getTemplateData()`](../../../packages/support/src/Components/ViewComponent.php) | [`viewData()` / `getViewData()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | — |
| [`getExtraTemplateData()`](../../../packages/support/src/Components/ViewComponent.php) | [`getExtraViewData()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | — |
| [`render(Environment)`](../../../packages/support/src/Components/ViewComponent.php) | [`render()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [diverges](#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |
| [`renderComponent()` Twig function](../../../packages/support/src/Twig/Extension/ComponentExtension.php) | `Htmlable` on [`ViewComponent`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [diverges](#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |
| Embedded view, `Htmlable` and published-view overrides | [`toHtml()` / `toHtmlString()` / `toEmbeddedHtml()` / `hasPublishedEmbeddedViewOverride()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |
| `extractPublicMethods()` | [`extractPublicMethods()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#extractpublicmethods-is-not-ported) |
| `attributes` seeding | [`renderView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#the-template-context-does-not-seed-an-attributes-bag) |
| `viewIdentifier` | [`viewIdentifier`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#the-instance-is-always-exposed-as-component) |

## Divergences

### `render()` returns a string; the `Htmlable` / embedded-view machinery is dropped

Filament's `ViewComponent implements Htmlable`, and its render surface is a web of Laravel and Livewire pieces: `render()` returns a Blade `View`; `toHtml()` / `toHtmlString()` string it out; `HasEmbeddedView` and `toEmbeddedHtml()` are a fast path that bypasses Blade with PHP-generated HTML, chosen when `hasView()` is false; and `getPublishedViewOverrideCheckPath()` / `hasPublishedEmbeddedViewOverride()` / `registerPublishedEmbeddedViewOverrideNamespace()` re-implement Laravel's `resource_path('views/vendor/…')` publishing so an embedded view can still be overridden on disk.

The Twig and UX port keeps none of it.
`render(Environment $twig)` takes the Twig environment and returns a plain `?string` (null when no template is set), and the `Twig\Markup` wrapping, the `Htmlable` half, lives one layer out in [`ComponentExtension::renderComponent()`](../../../packages/support/src/Twig/Extension/ComponentExtension.php), which the `renderComponent()` Twig function calls.
There is a single render path (a Twig template), so the embedded-view fast path has nothing to optimize and `hasView()` has no choice to make, and template overriding is Symfony and Twig's own bundle-path concern, not something this class re-implements.
Reversing this means re-introducing a second, PHP-generated render path, which is not planned.
`Htmlable` → `Twig\Markup` and Blade `view()` → Twig `template()` are the systematic [translations](../translations.md#idiom) underneath this divergence.

### Two-level template resolution with null vs. throw tail difference

Filament carries two properties: `$view` (the resolved template, set by a subclass or a `view()` call) and `$defaultView` (a closure-evaluable fallback), with `getView()` throwing `LogicException` when neither is set.
Sylament mirrors this with two properties, `$template` (explicit) and `$defaultTemplate` (fallback), both closure-evaluable and nullable.
`getTemplate()` performs the same two-level lookup, explicit first, then default.
Subclasses set their default by overriding a property directly, as [`Schema`](../../../packages/schemas/src/Schema.php) overrides `$template` and [`Action`](../../../packages/actions/src/Action.php) `$defaultTemplate`, just as Filament subclasses override `$view`.
Two tails differ.
`render()` returns null on a null template rather than throwing: the throw was Filament's guard against a component reaching Blade with no view, and here a null template is a valid "render nothing".
And `template(null)` clears an explicit template so the default applies again, where Filament's `view(null)` returns without touching `$view`.

### `extractPublicMethods()` is not ported

Filament reflects every public method of the component and injects them as view variables so Blade can call them bare.
Twig calls methods on the instance directly (`component.getTemplate()`), and the instance is always in scope as `component`, so the reflection pass has no purpose here.
Filament's `ViewComponent::extractPublicMethods()` delegates to `ComponentManager::extractPublicMethods()`, which caches the method names in a `methodCache` property; the port drops both, so the manager's skipped row in [component-manager.md](component-manager.md) links here.

### The template context does not seed an `attributes` bag

Filament's `renderView()` seeds `'attributes' => new ComponentAttributeBag` into every view.
Sylament's `render()` seeds only `component`, then `getExtraTemplateData()`, then user `templateData()`, the last winning.
Attribute assembly is a template-side concern handled by the `attrs()` Twig function, backed by [`ComponentAttributeBag`](component-attribute-bag.md), so no ambient `attributes` variable is threaded through the context.

### The instance is always exposed as `component`

Filament exposes the instance under a per-subclass `$viewIdentifier`: a schemas component's view sees `$schemaComponent`, a schema's `$schema`.
Sylament fixes the key to `component` for every subclass.
The schemas base sets the closure-evaluation identifier to `component` too, so the template variable and the `fn ($component) => …` injection name agree.
