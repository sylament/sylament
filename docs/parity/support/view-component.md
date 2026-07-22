# ViewComponent (support component base)

The Twig-backed rendering base `Sylament\Support\Component\ViewComponent` and
the plain `Sylament\Support\Component\Component` it extends — the roots every
Sylament component hangs off. Ported so far: `Configurable`,
`EvaluatesClosures`, `template()` / `templateData()` (closure-evaluable),
`getTemplate()` / `getTemplateData()`, `getExtraTemplateData()`, `render()`.
Blade view → Twig template naming (`view()` → `template()`, `viewData()` →
`templateData()`) follows the translation conventions in
[renames.md](../renames.md), not renames.

Reference:

- [`vendor/filament/support/src/Components/Component.php`](../../../vendor/filament/support/src/Components/Component.php)
- [`vendor/filament/support/src/Components/ViewComponent.php`](../../../vendor/filament/support/src/Components/ViewComponent.php)

## Missing

- [ ] `defaultView()` / `getDefaultView()` / `hasView()` — the caller-supplied
  default-template fallback consulted when no template was set. Port with the
  first component that needs a swappable default, or promote to a divergence.
- [ ] `Conditionable` (`when()` / `unless()`) and `Tappable` (`tap()`) — the
  Laravel fluent-helper traits Filament's base component exposes for userland
  conditional/side-effecting chains. Ported once, then removed while nothing in
  the codebase consumes them; re-add when a component or consumer needs the
  fluent forms.

## Divergences

- **`render()` returns `null` without a template.** `template(null)` clears
  the template and `render()`/`renderComponent()` then return `null` (the
  component vanishes from output); Filament's `view(null)` silently no-ops and
  `getView()` throws when nothing was ever set. Callers that require output
  must set a template — this is the render contract, not an accident.
- **Blade-isms dropped**: `$viewIdentifier` (Blade's `$this` alias in views —
  Twig templates receive `component` via `render()`) and `toHtmlString()`
  (Twig-side wrapping happens in `renderComponent()`, which returns `Markup`).
- **`Macroable` is not ported.** No runtime method injection; the extension
  points are subclassing and `configureUsing()`.
