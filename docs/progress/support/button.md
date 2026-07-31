# Button (support)

The `<twig:SylamentSupport:Button>` anonymous template — the shared text-button
widget. It has no Sylament PHP class: it ports Filament's Blade
`button/index.blade.php` (the rendered markup) together with the
`ButtonComponent.php` whose sole job is the runtime color map. The current Twig
is a thin subset — it renders a `<button>`/`<a>` with a size, a leading icon, a
disabled state, and a color class, and leaves Filament's badge, tooltip,
key-binding, loading-indicator, form-processing, and `labeledFrom` behavior
unported. The ported skeleton is render-tested from the actions suite
([`ButtonRenderTest`](../../../packages/actions/tests/Integration/Components/ButtonRenderTest.php));
the interactive and decorative surface is not built yet, so the component as a
whole stays **in progress**.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/resources/views/components/button/index.blade.php`](../../../vendor/filament/support/resources/views/components/button/index.blade.php)
  — the rendered markup this Twig ports
- [`support/src/View/Components/ButtonComponent.php`](../../../vendor/filament/support/src/View/Components/ButtonComponent.php)
  — the color-map class applied via `->color(ButtonComponent::make($outlined), $color)`

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Element tag + `href`/`target`](../../../packages/support/templates/components/Button/index.html.twig) | [`<{{ $tag }}>` + `generate_href_html`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | — |
| [`type` attribute](../../../packages/support/templates/components/Button/index.html.twig) | [`'type' => …`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | — |
| [`disabled` + `aria-disabled`](../../../packages/support/templates/components/Button/index.html.twig) | [`'disabled' => …`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | — |
| [`size` → `sy-size-*` classes](../../../packages/support/templates/components/Button/index.html.twig) | [`->class(['fi-size-…'])`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | — |
| [`color` → `sy-color*` classes + resolved shade classes](../../../packages/support/templates/components/Button/index.html.twig) | [`->color(ButtonComponent::make($outlined), $color)`](../../../vendor/filament/support/src/View/Components/ButtonComponent.php) | done | — | [diverges](#the-outlined-variant-is-selected-by-a-prop-and-by-a-second-policy-class) |
| [Leading icon](../../../packages/support/templates/components/Button/index.html.twig) | [`generate_icon_html(...)`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | in progress | — | [detail](#details) |
| `iconPosition` (before/after) | [`IconPosition::Before` / `::After`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#details) |
| [`outlined`](../../../packages/support/templates/components/Button/index.html.twig) | [`ButtonComponent::make($outlined)` + `fi-outlined`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | done | — | [diverges](#the-outlined-variant-is-selected-by-a-prop-and-by-a-second-policy-class) |
| `badge` / `badgeColor` / `badgeSize` | [`fi-btn-badge-ctn`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#details) |
| `tooltip` | [`x-tooltip="…"`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#details) |
| Loading indicator / `wire:target` / form processing | [`generate_loading_indicator_html(...)` + `filamentFormButton`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#details) |
| `keyBindings` | [`x-mousetrap.global.…`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#details) |
| `labeledFrom` → icon-button delegation | [`<x-filament::icon-button … />`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#details) |
| `labelSrOnly` / `aria-label` | [`'aria-label' => …`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#details) |
| `spaMode` SPA navigation | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#details) |
| `form` / `formId` | [`'form' => $formId`](../../../vendor/filament/support/resources/views/components/button/index.blade.php) | todo | — | [detail](#details) |

## Details

Specifics for rows that are not finished — what is in, what is missing, what the
next step is. The **done** rows above — tag/`href`/`target`, `type`, `disabled`,
`size`, and `color` (which diverges) — are covered by
[`ButtonRenderTest`](../../../packages/actions/tests/Integration/Components/ButtonRenderTest.php):
it renders `<twig:SylamentSupport:Button>` through a kernel that registers
`TwigComponentBundle` (the actions suite is the nearest such home, per CLAUDE.md)
and asserts the default primary/`md` classes and `type`, `<a>` tag-switching with
`href` + `target="_blank"` and no `type`, the `disabled`/`aria-disabled`/
`sy-disabled` trio, a gray color dropping the color classes, a semantic `danger`
color, and extra-attribute forwarding. So they carry no write-up here.

### Leading icon — renders, but does not derive icon size from button size

The Twig renders a single leading icon via
`generateIconHtml(icon, iconAlias, null, iconSize)`, and the render test asserts
an `<svg>` is emitted. One fidelity gap holds it at **in progress**: Filament
defaults `iconSize` from `size` (`xs`/`sm` → `IconSize::Small`, everything else
none), while the Twig passes the `iconSize` prop straight through with no
size-derived default. First step to **done**: derive the default in the template
and add a case asserting the icon's size class.

### Unported features — the interactive and decorative surface

Everything below `Leading icon` in the table is absent from the Twig prop list
and body. They split into two groups:

- **Static, portable now.** `iconPosition` (the Twig only ever renders the icon
  before the label; Filament also renders `IconPosition::After`),
  `badge`/`badgeColor`/`badgeSize`, `labelSrOnly`/`aria-label`, and the
  `labeledFrom` delegation to `IconButton`. These need no interactivity layer and
  are the natural next increments.
- **Awaiting the interactivity layer.** `tooltip` (Alpine `x-tooltip`),
  `keyBindings` (Alpine mousetrap), the loading indicators + `wire:target`
  plumbing, the `filamentFormButton` form-processing behavior, and `spaMode`
  SPA navigation all depend on the Livewire → UX LiveComponent and Alpine →
  Stimulus translations, plus the not-yet-ported `LoadingIndicator` view and
  `wire:` directive story. They stay **todo** until that layer exists; none is
  blocked on a single named, tracked row today, so none is marked **blocked**.

## Divergences

### The outlined variant is selected by a prop, and by a second policy class

The colour map is ported, and the button is the component where it does the most
work: `ButtonComponentColorMap` picks a background *and* a text shade together,
so for a pale palette it paints `bg-400` with dark text rather than white on
`bg-600` — see the
[semantics note](color-policies.md#the-filled-button-picks-a-background-first-and-a-text-shade-after).

What diverges is how the **variant** reaches it. Filament makes the map's owner
carry the flag (`ButtonComponent::make($outlined)`, a per-flag static instance
cache) and passes the *instance* to its Blade `->color()` macro. Twig cannot call
a static factory and `colorAttributes()` takes a class-string, so support's
`Button` gains an `outlined` prop and picks between
[`ButtonComponent`](../../../packages/support/src/Colors/Components/ButtonComponent.php)
and
[`OutlinedButtonComponent`](../../../packages/support/src/Colors/Components/OutlinedButtonComponent.php),
which is the same branch Filament puts behind the flag, expressed as a subclass
([renames](../renames.md), [why it is a subclass](color-policies.md#the-outlined-button-is-a-subclass-not-a-constructor-flag)).

One consequence for callers, and it is the reason actions had to change: the
`sy-outlined` class is now emitted **by this template** from the same prop, where
actions previously composed it into `class` itself. A caller that hand-writes
`sy-outlined` without passing `:outlined` gets the outlined *styling* with the
filled *colour map* — visually wrong in a way no test catches, since neither half
errors.

_See also: the global [rename table](../renames.md) — the `fi-*` → `sy-*` class
prefix and the Livewire → UX LiveComponent / Alpine → Stimulus translations the
unported rows depend on are systematic translations recorded there, not
per-component divergences._
