# ViewComponent (support)

`Sylament\Support\Components\ViewComponent` — the abstract `Component` subclass
that adds Twig-backed rendering. It ports Filament's `ViewComponent`: a
closure-evaluable template name plus one or more layers of closure-evaluable
template data, merged and handed to the view at render time. Every rendered
Sylament component (schemas' `Schema` and `Component`, `actions`' `Action`)
extends it, and the `renderComponent()` Twig function drives it. The port keeps
the data-plumbing surface Filament-exact while dropping the Blade/Livewire
embedded-view machinery that the Twig/UX render path replaces.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php)
  — the base class this ports
- [`support/src/Components/Contracts/HasEmbeddedView.php`](../../../vendor/filament/support/src/Components/Contracts/HasEmbeddedView.php)
  — the embedded-view contract the render path branches on

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries only two links, either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences). Rows never disappear — only their
detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`template()` / `getTemplate()`](../../../packages/support/src/Components/ViewComponent.php) | [`view()` / `getView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [diverges](#one-nullable-template-replaces-the-view--defaultview-pair) |
| [`defaultTemplate()`](../../../packages/support/src/Components/ViewComponent.php) | [`defaultView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | — |
| [`templateData()` / `getTemplateData()`](../../../packages/support/src/Components/ViewComponent.php) | [`viewData()` / `getViewData()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | — |
| [`getExtraTemplateData()`](../../../packages/support/src/Components/ViewComponent.php) | [`getExtraViewData()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | — |
| [`render(Environment)`](../../../packages/support/src/Components/ViewComponent.php) | [`render()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | done | — | [diverges](#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |
| Embedded view / `Htmlable` | [`toHtml()` / `toEmbeddedHtml()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |
| `extractPublicMethods()` | [`extractPublicMethods()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#extractpublicmethods-is-not-ported) |
| `attributes` seeding | [`renderView()`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#the-template-context-does-not-seed-an-attributes-bag) |
| `viewIdentifier` | [`viewIdentifier`](../../../vendor/filament/support/src/Components/ViewComponent.php) | skipped | — | [diverges](#the-instance-is-always-exposed-as-component) |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

Every row above is settled — **done** or **skipped** — so none has a write-up
here now. A row moving to **in progress** or **blocked** grows one.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. These
outlive the detail sections above: a row stays **done** or **skipped** and keeps
pointing here. Say what would reverse the decision when it is provisional.

### `render()` returns a string; the `Htmlable` / embedded-view machinery is dropped

Filament's `ViewComponent implements Htmlable`, and its render surface is a web
of Laravel/Livewire pieces: `render()` returns a Blade `View`; `toHtml()` /
`toHtmlString()` string it out; `HasEmbeddedView` + `toEmbeddedHtml()` are a v5
fast path that bypasses Blade with PHP-generated HTML; and
`getPublishedViewOverrideCheckPath()` /
`hasPublishedEmbeddedViewOverride()` /
`registerPublishedEmbeddedViewOverrideNamespace()` re-implement Laravel's
`resource_path('views/vendor/…')` publishing so an embedded view can still be
overridden on disk.

The Twig/UX port keeps none of it. `render(Environment $twig)` takes the Twig
environment and returns a plain `?string` (null when no template is set), and
the `Twig\Markup` wrapping — the `Htmlable` half — lives one layer out in
[`ComponentExtension::renderComponent()`](../../../packages/support/src/Twig/Extension/ComponentExtension.php),
which the `renderComponent()` Twig function calls. There is a single render path
(a Twig template), so the embedded-view fast path has nothing to optimize, and
template overriding is Symfony/Twig's own bundle-path concern, not something this
class re-implements. Reversing this means re-introducing a second, PHP-generated
render path — not planned.

_See also:_ the [rename table](../renames.md) — `Htmlable` → `Twig\Markup` and
Blade `view()` → Twig `template()` are the systematic translations underneath
this divergence.

### One nullable template replaces the `$view` + `$defaultView` pair

Filament carries two properties: `$view` (the resolved template, set by a
subclass or a `view()` call) and `$defaultView` (a closure-evaluable fallback),
with `getView()` throwing `LogicException` when neither is set. Sylament collapses
both into a single closure-evaluable, nullable `$template`. Subclasses set their
default by overriding the property directly — e.g.
[`Schema`](../../../packages/schemas/src/Schema.php) and
[`Action`](../../../packages/actions/src/Action.php) — exactly as Filament
subclasses override `$view`. With no embedded-view fallback to arbitrate,
`getDefaultView()` and the two-level lookup have nothing to do, so `getTemplate()`
simply returns the (possibly null) value and `render()` returns null on a null
template rather than throwing. The throw was Filament's guard against a component
reaching Blade with no view; here a null template is a valid "render nothing".

### `extractPublicMethods()` is not ported

Filament reflects every public method of the component and injects them as view
variables so Blade can call them bare. Twig calls methods on the instance
directly (`component.getTemplate()`), and the instance is always in scope as
`component`, so the reflection pass has no purpose here.

### The template context does not seed an `attributes` bag

Filament's `renderView()` seeds `'attributes' => new ComponentAttributeBag` into
every view. Sylament's `render()` seeds only `component`, then
`getExtraTemplateData()`, then user `templateData()` (last wins). Attribute
assembly is a template-side concern handled by the `attrs()` Twig function
(backed by [`ComponentAttributeBag`](../../../packages/support/src/Twig/ComponentAttributeBag.php)),
so no ambient `attributes` variable is threaded through the context.

### The instance is always exposed as `component`

Filament exposes the instance under a per-subclass `$viewIdentifier` (a `Section`
view sees `$section`). Sylament fixes the key to `component` for every subclass.
The schemas base sets the closure-evaluation identifier to `component` too, so
the template variable and the `fn ($component) => …` injection name agree.

_See also:_ the [rename table](../renames.md) — `view()` → `template()` is a
systematic translation recorded there, not a per-component divergence.
