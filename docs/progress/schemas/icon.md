# Icon (schemas)

[`Sylament\Schemas\Components\Icon`](../../../packages/schemas/src/Components/Icon.php)
is the schema content component that renders a single icon. It ports Filament's
[`Schemas\Components\Icon`](../../../vendor/filament/schemas/src/Components/Icon.php):
an `icon()` value (name / `BackedEnum` / `Markup` / closure), a `size()` that
normalizes a known string to an [`IconSize`](../../../packages/support/src/Enums/IconSize.php)
case, plus color, tooltip, and visibility folded in from support concerns. The
fluent API and the basic Twig render are ported and tested; Filament's
accessibility render path (a visually-hidden text alternative + `aria-label`
reconciliation) and `extraAttributes` forwarding are not yet implemented.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Icon.php`](../../../vendor/filament/schemas/src/Components/Icon.php)
  — the component, its getters, and the `toEmbeddedHtml()` render path
- [`support/src/Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php),
  [`support/src/Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php)
  — the two support concerns composed here (shared with [Text](text.md))

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries **detail** → the write-up in [Details](#details) (deleted once
the row is **done**), **diverges** → the permanent note in
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`icon()` / `getIcon()`](../../../packages/schemas/src/Components/Icon.php) | [`Icon::getIcon()`](../../../vendor/filament/schemas/src/Components/Icon.php) | done | — | — |
| [`size()` / `getSize()` normalization](../../../packages/schemas/src/Components/Icon.php) | [`Icon::getSize()`](../../../vendor/filament/schemas/src/Components/Icon.php) | done | — | [diverges](#getsize-drops-filaments-base--null-shortcut) |
| [`color()` / `getColor()` (HasColor)](../../../packages/schemas/src/Components/Icon.php) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| [`tooltip()` / `getTooltip()` (HasTooltip)](../../../packages/schemas/src/Components/Icon.php) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | — |
| [`isVisible()` / `hidden()` (base `CanBeHidden`)](../../../packages/schemas/src/Components/Icon.php) | [`Concerns/CanBeHidden.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | done | — | — |
| [Twig render (template + `generateIconHtml`)](../../../packages/schemas/src/Components/Icon.php) | [`Icon::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Icon.php) | done | — | [diverges](#the-default-icon-color-is-gray-with-no-color-class-not-filaments-primary) |
| Accessibility: sr-only text alternative + `aria-label` reconciliation | [`Icon::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Icon.php) | in progress | — | [detail](#the-accessible-text-alternative-is-not-rendered) |
| `extraAttributes` forwarding into the icon markup | [`Icon::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Icon.php) | in progress | — | [detail](#extraattributes-are-not-forwarded-into-the-icon) |

## Details

### The accessible text alternative is not rendered

In: `icon()`/`getIcon()` (literal, closure, `$component` injection), size
normalization, color, tooltip, visibility, and the basic Twig render (the `<svg>`,
`sy-sc-icon` + `sy-icon` classes, `sy-color-*` palette class, size class,
tooltip-as-`title`) — all covered by
[`IconTest`](../../../packages/schemas/tests/Unit/Components/IconTest.php) and
[`IconComponentRenderTest`](../../../packages/schemas/tests/Integration/Components/IconComponentRenderTest.php).

Left: Filament's `toEmbeddedHtml()` gives the decorative (`aria-hidden`) SVG a
visually-hidden text alternative — an explicit user `aria-label` pulled off the
SVG, or the tooltip text as fallback, skipped when the user set `aria-labelledby`
— emitted inside a `fi-sr-only` (→ `sy-sr-only`) span. Sylament's template emits
neither the span nor the reconciliation; the `title` attribute is the only
accessible-name source today. Because it is not a declared non-goal, it is tracked
as an open gap, not a settled divergence.

Next step: decide whether the `title` attribute is accepted as the accessible-name
mechanism (then record the dropped sr-only span as a divergence) or port the text
alternative + `aria-label` reconciliation into
[`Icon/index.html.twig`](../../../packages/schemas/templates/partials/Icon/index.html.twig) /
`getExtraTemplateData()`; then add a render test asserting the visually-hidden name.

### `extraAttributes` are not forwarded into the icon

Filament merges `getExtraAttributes()` onto the icon's attribute bag before
`generate_icon_html()`. Sylament's template emits only the class and optional
`title`, so per-instance `extraAttributes()` do not reach the icon markup.

Next step: forward the component's extra attributes onto the bag in the template
(coordinated with the `aria-label` handling above, which reads from the same bag),
and cover the passthrough with a render test.

## Divergences

### The default icon color is gray with no color class, not Filament's primary

Filament's `toEmbeddedHtml()` passes `color(IconComponent::class, $this->getColor()
?? 'primary')`, so an uncolored icon defaults to the primary palette. Sylament's
template treats an empty/iterable color as `gray` and emits no `sy-color-*` class
at all —
[`IconComponentRenderTest`](../../../packages/schemas/tests/Integration/Components/IconComponentRenderTest.php)
pins that the default omits `sy-color-`. Reverses if the toolkit adopts Filament's
primary-default convention for bare icons.

### `getSize()` drops Filament's `'base'` → `null` shortcut

Filament's `getSize()` returns `null` when the size is the literal `'base'`, so no
class is emitted and the icon keeps its base size. Sylament's `getSize()` has no
such branch: `'base'` is neither an `IconSize` case nor blank, so it is returned
verbatim as a string (the render-side `generate_icon_html` then `IconSize::tryFrom`s
it to `null` anyway). Behaviorally equivalent at render time, but the getter
contract differs — Sylament's `IconSize` enum has no `base` token.

_See also: the global [rename table](../renames.md) — the `Htmlable` → `Markup`
escaping-contract translation and the Blade/`toEmbeddedHtml()` → Twig-template
render idiom are systematic translations recorded there, not per-component
divergences._
