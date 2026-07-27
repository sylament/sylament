# Icon button (support)

`packages/support/templates/components/IconButton/index.html.twig` — the
anonymous Twig component (`<twig:SylamentSupport:IconButton>`) that ports
Filament's `<x-filament::icon-button>`. It renders a `<button>` or `<a>` whose
sole content is an icon, deriving the icon size from the button size and exposing
its `label` only as the accessible name. The Blade component's behaviour is split
across two Filament files: the `icon-button.blade.php` view (markup, props, size
and attribute logic) and the `IconButtonComponent.php` PHP class (the WCAG-AA
contrast color map). Sylament ports the structural view faithfully but leaves the
whole interactive/decorative layer — Livewire loading indicator, badge, key
bindings, tooltip, SPA-mode href — and the color-map machinery unbuilt, pending
the infrastructure each depends on.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/resources/views/components/icon-button.blade.php`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php)
  — the Blade view: props, size resolution, attribute merge, markup
- [`support/src/View/Components/IconButtonComponent.php`](../../../vendor/filament/support/src/View/Components/IconButtonComponent.php)
  — the color-map provider the view's `->color(IconButtonComponent::class, …)` call resolves against

## Surface

Every point of this component. _Blocked by_ names what has to land first. _Detail_
carries either or both: **detail** → the write-up in [Details](#details), deleted
once the row is **done**; **diverges** → the permanent note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Tag + size classes](../../../packages/support/templates/components/IconButton/index.html.twig) | [`icon-button.blade.php` `->class([…])`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | — |
| [Icon rendering + derived size](../../../packages/support/templates/components/IconButton/index.html.twig) | [`generate_icon_html(…)`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | — |
| [Label → `aria-label` + `title`](../../../packages/support/templates/components/IconButton/index.html.twig) | [`aria-label`/`title` merge](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | [diverges](#the-aria-label-is-double-encoded-where-filament-suppresses-it) |
| [`disabled` / `aria-disabled`](../../../packages/support/templates/components/IconButton/index.html.twig) | [`disabled`/`aria-disabled` merge](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | [diverges](#the-disabled-attribute-is-emitted-only-on-button) |
| [Link `href` / `target`](../../../packages/support/templates/components/IconButton/index.html.twig) | [`generate_href_html(…)`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | [detail](#details) |
| [`type` / `form` association](../../../packages/support/templates/components/IconButton/index.html.twig) | [`type`/`form` merge](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | — |
| [Color (gray-default + semantic)](../../../packages/support/templates/components/IconButton/index.html.twig) | [`->color(IconButtonComponent::class, $color)`](../../../vendor/filament/support/src/View/Components/IconButtonComponent.php) | in progress | color system (`Colors/`, `HasColor`) | [detail](#details) · [diverges](#color-is-rendered-as-sy-color--classes-not-the-colormap-css-variable-injection) |
| Loading indicator | [`generate_loading_indicator_html(…)` + `wire:*`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | blocked | UX LiveComponent state layer | [detail](#details) |
| Badge | [badge block + `BadgeComponent`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | blocked | `BadgeComponent` port | [detail](#details) |
| Key bindings | [`x-mousetrap` block](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | blocked | Stimulus mousetrap controller | [detail](#details) |
| Tooltip | [`x-tooltip` block + disabled-tooltip filter](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | blocked | Stimulus tooltip controller | [detail](#details) |
| SPA-mode href | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | blocked | panels SPA navigation | [detail](#details) |

## Details

The ported rows — tag/size classes, icon rendering and its size derivation,
`label` → `aria-label`/`title`, `disabled`/`aria-disabled`, link `href`/`target`,
and `type`/`form` — are faithful (allowing the systematic `fi-*` → `sy-*`,
Blade → Twig and `Htmlable` → `Markup` translations) and every one is exercised
by
[`IconButtonRenderTest`](../../../packages/schemas/tests/Integration/Components/IconButtonRenderTest.php):
default classes, size-derived icon sizing, explicit-over-derived icon size, an
unknown size emitted as a literal class, link `href`/`target` (including the
dropped non-blank target and absent href), `disabled` on both a button and a
link, the accessible-name-only `label` with markup stripped and escaped, and
external-form submission. That suite lives in **schemas** because only its test
kernel registers TwigComponentBundle (same as the standalone Icon coverage).

The **link `href`** row is done but noted because Sylament emits `href`/`target`
inline from the template, where Filament delegates to `generate_href_html()`
(which also encodes SPA navigation) — the SPA half is the separate blocked row
below; plain `href`/`target="_blank"` are fully ported and tested.

**Color** is partly ported: the `HasDefaultGrayColor` semantics are honoured — a
gray (or empty/iterable) color emits no color classes, any other color emits
`sy-color sy-color-<name>` — and both branches are tested. What is missing is the
actual color resolution: Filament's `->color(IconButtonComponent::class, $color)`
runs the `IconButtonComponentColorMap` to inject per-shade CSS custom properties
at a WCAG-AA non-text contrast ratio; Sylament emits a bare class name and leaves
the shade/contrast computation to the (unported) color system. See the
divergence. The row flips to **done** when the color system lands and the
class-only shim is either justified permanently or replaced.

**Loading indicator, badge, key bindings, tooltip, and SPA-mode href** are not
built at all. Each is blocked on infrastructure that has not been ported:

- **Loading indicator** — the `wire:target`/`wire:loading` wiring,
  `generate_loading_indicator_html()`, the `loadingIndicator`/`form`/`spaMode`
  props and the `livewire_loading_delay` config all presuppose the UX
  LiveComponent state layer. Its absence also removes Filament's reason to set
  `disabled` via `wire:loading.attr`, and the `form` prop (submit-target for the
  indicator) collapses to `formId` alone.
- **Badge** — the `badge`/`badgeColor`/`badgeSize` props and the
  `fi-icon-btn-badge-ctn` block depend on a `BadgeComponent` port (still `todo`).
- **Key bindings** — Filament's `x-mousetrap` global binding is an Alpine plugin;
  the Sylament equivalent is a Stimulus controller that does not yet exist.
- **Tooltip** — the `x-tooltip` block and the coupled disabled-with-tooltip
  behaviour (anchor `tabindex="0"`, and filtering `href`/`x-on:`/`wire:click` off
  a disabled-but-tooltipped control) need a Stimulus tooltip controller. Because
  no tooltip can be set, Filament's `$disabled && blank($tooltip)` reduces to
  `$disabled`, which is what the ported template assumes.
- **SPA-mode href** — the `spaMode` argument of `generate_href_html()` encodes
  panel single-page navigation, which the panels bundle has not reached.

## Divergences

### The `disabled` attribute is emitted only on `<button>`

Filament merges `'disabled' => $disabled && blank($tooltip)` onto the root
regardless of tag, so a disabled `<a>` (with no tooltip) receives a `disabled`
attribute — which is not valid on an anchor. Sylament guards the attribute with
`disabled and tag == 'button'`, so a disabled link carries only `aria-disabled`
and `sy-disabled`, never the invalid boolean attribute. The `aria-disabled`/
`sy-disabled` signalling is identical across tags; only the native attribute is
withheld from non-buttons. Verified against both sources and pinned by the
"disables a link without the invalid disabled attribute" case in
[`IconButtonRenderTest`](../../../packages/schemas/tests/Integration/Components/IconButtonRenderTest.php).

### Color is rendered as `sy-color-*` classes, not the ColorMap CSS-variable injection

The Filament file being ported, `IconButtonComponent.php`, exists only to supply
a `getColorMap()` that computes contrast-safe shades (WCAG-AA non-text, gray
surfaces, a `500` dark cap) which `->color(IconButtonComponent::class, $color)`
turns into inline `--c-*` custom properties. Sylament ports neither the PHP class
nor the color manager; the template emits a semantic `sy-color-<name>` class and
defers all shade/contrast resolution to the CSS layer. The `HasDefaultGrayColor`
behaviour (gray ⇒ no color styling) survives as the `colorName != 'gray'` guard.
This holds only while the color system (`Colors/`, `HasColor`) is unported — it
is the blocker on the Color row above, not a permanent decision.

### The `aria-label` is double-encoded, where Filament suppresses it

Filament escapes the accessible name with
`e(trim(strip_tags($label)), doubleEncode: false)` and merges it with
`escape: false`, so a label already carrying an escaped entity stays singly
encoded. Sylament runs `label|striptags|trim` through `attributes.defaults(...)`,
whose Twig escaper has no double-encode toggle, so the same pre-escaped input
renders doubly encoded (`&amp;` → `&amp;amp;`). Unescaped labels — the normal
case — render identically; only labels that already contain HTML entities differ.

_See also: the global [rename table](../renames.md) — `fi-*` → `sy-*` classes,
Blade → Twig (`view()` → `template()`), Alpine → Stimulus and `Htmlable` →
`Markup` are systematic translations recorded there, not per-component
divergences._
