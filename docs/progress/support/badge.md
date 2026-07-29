# Badge (support)

`packages/support/templates/components/Badge/index.html.twig` — the anonymous
Twig component (`<twig:SylamentSupport:Badge>`) that ports Filament's
`<x-filament::badge>`. It renders a short status pill — a `<span>` by default, a
`<button>` or an `<a>` when the caller switches the tag — carrying a label, an
optional icon before or after it, and a size/color pair that selects the visual
treatment. The Blade component's behaviour is split across two Filament files:
the `badge.blade.php` view (markup, props, attribute merge) and the
`BadgeComponent.php` PHP class (the WCAG-AA contrast color map). Sylament ports
the structural view faithfully and leaves the whole interactive layer — Livewire
loading indicator, key bindings, tooltip, SPA-mode href — plus the deletable
badge and the color-map machinery unbuilt, pending the infrastructure each
depends on.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/resources/views/components/badge.blade.php`](../../../vendor/filament/support/resources/views/components/badge.blade.php)
  — the Blade view: props, size resolution, attribute merge, markup
- [`support/src/View/Components/BadgeComponent.php`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php)
  — the color-map provider the view's `->color(BadgeComponent::class, …)` call resolves against
- [`support/resources/css/components/badge.css`](../../../vendor/filament/support/resources/css/components/badge.css)
  — the rules [`badge.css`](../../../packages/support/assets/src/styles/components/badge.css) ports

## Surface

Every point of this component. _Blocked by_ names what has to land first. _Detail_
carries either or both: **detail** → the write-up in [Details](#details), deleted
once the row is **done**; **diverges** → the permanent note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Tag + `href`/`target`](../../../packages/support/templates/components/Badge/index.html.twig) | [`<{{ $tag }}>` + `generate_href_html(…)`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | — |
| [`type` / `form` association](../../../packages/support/templates/components/Badge/index.html.twig) | [`'type'`/`'form'` merge](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | — |
| [`disabled` + `aria-disabled`](../../../packages/support/templates/components/Badge/index.html.twig) | [`'disabled'`/`'aria-disabled'` merge](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | [diverges](#the-disabled-attribute-is-emitted-only-on-button) |
| [`size` → `sy-size-*` classes](../../../packages/support/templates/components/Badge/index.html.twig) | [`->class(['fi-size-…'])`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | — |
| [`color` → `sy-color*` classes](../../../packages/support/templates/components/Badge/index.html.twig) | [`->color(BadgeComponent::class, $color)`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php) | done | — | [diverges](#the-color-map-is-not-ported--colors-resolve-through-css-classes) |
| [Icon + derived `sm` size + `iconPosition`](../../../packages/support/templates/components/Badge/index.html.twig) | [`generate_icon_html(…, size: $iconSize ?? IconSize::Small)`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | — |
| [`labelSrOnly` → `aria-label`](../../../packages/support/templates/components/Badge/index.html.twig) | — (no Filament badge equivalent) | done | — | [diverges](#labelsronly-is-a-sylament-addition) |
| `deleteButton` | [`fi-badge-delete-btn` block](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | [`BADGE_DELETE_BUTTON` icon alias](support-icon-alias.md) + a label translation | [detail](#details) |
| `tooltip` | [`x-tooltip` block + disabled-tooltip filter](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | Stimulus tooltip controller | [detail](#details) |
| Key bindings | [`x-mousetrap` block](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | Stimulus mousetrap controller | [detail](#details) |
| Loading indicator | [`generate_loading_indicator_html(…)` + `wire:*`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | UX LiveComponent state layer | [detail](#details) |
| SPA-mode href | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | panels SPA navigation | [detail](#details) |
| `tag: 'form'` POST-to-URL | [`tag: $url ? $shouldPostToUrl ? 'form' : 'a' : 'button'`](../../../vendor/filament/actions/src/Action.php) | todo | — | [detail](#details) |

## Details

The ported rows — tag/`href`/`target`, `type`/`form`, `disabled`/`aria-disabled`,
`size`, `color`, the icon with its derived `sm` size and its position, and
`labelSrOnly` — are faithful (allowing the systematic `fi-*` → `sy-*`,
Blade → Twig and `Htmlable` → `Markup` translations, and the three divergences
below) and every one is exercised by
[`BadgeRenderTest`](../../../packages/actions/tests/Integration/Components/BadgeRenderTest.php):
the default `span` with its primary/`md` class list and the two-span label, tag
switching to `button` (with `type` and an external `form`) and to `a` (with
`href`/`target="_blank"` and no `type`), the
`disabled`/`aria-disabled`/`sy-disabled` trio, a gray color dropping the color
classes, a semantic color, an unknown size emitted as a literal class, the
`sm`-by-default icon size, an icon rendered after the label, the
`aria-label`-only hidden label, and extra-attribute forwarding. That suite lives
in **actions** because only its kernel and schemas' register TwigComponentBundle,
and actions is where the badge's consumer lives.

**Deletable badge, tooltip, key bindings, loading indicator, and SPA-mode href**
are not built at all. Each is blocked on infrastructure that has not been ported:

- **`deleteButton`** — the `fi-badge-delete-btn` block needs the
  `BADGE_DELETE_BUTTON` alias (still unported, see
  [support-icon-alias.md](support-icon-alias.md)) and the
  `filament::components/badge.actions.delete.label` translation, and its own
  loading indicator is Livewire-bound. Its CSS is likewise unported, so
  [`badge.css`](../../../packages/support/assets/src/styles/components/badge.css)
  carries no `sy-badge-delete-btn` rules — a rule no shipped markup can trigger
  is a dead rule.
- **Tooltip** — the `x-tooltip` block and the coupled disabled-with-tooltip
  behaviour (filtering `href`/`x-on:`/`wire:click` off a disabled-but-tooltipped
  control) need a Stimulus tooltip controller. Because no tooltip can be set,
  Filament's `$disabled && blank($tooltip)` reduces to `$disabled`, which is what
  the ported template assumes.
- **Key bindings** — Filament's `x-mousetrap` global binding is an Alpine plugin;
  the Sylament equivalent is a Stimulus controller that does not yet exist.
- **Loading indicator** — the `wire:target`/`wire:loading` wiring,
  `generate_loading_indicator_html()`, the `loadingIndicator`/`form`/`spaMode`
  props and the `livewire_loading_delay` config all presuppose the UX
  LiveComponent state layer. Its absence also removes Filament's reason to set
  `disabled` via `wire:loading.attr`, and the `form` prop (submit-target for the
  indicator) collapses to `formId` alone.
- **SPA-mode href** — the `spaMode` argument of `generate_href_html()` encodes
  panel single-page navigation, which the panels bundle has not reached.

**`tag: 'form'`** is **todo** rather than blocked: nothing has to land first, and
nothing would call it. Filament's `toBadgeHtml()` renders the whole badge as a
`<form action=… method="post">` when the action posts to its URL, but this port
has no `postToUrl()` / `shouldPostToUrl()` on `Action`, so no caller can pass the
tag. The template already interpolates `tag` freely, so the work is the missing
`action`/`method` attributes and the `type` derivation Filament's tag `match`
does — reachable the moment `postToUrl()` is ported.

## Divergences

### `labelSrOnly` is a Sylament addition

Filament's `badge.blade.php` has no hidden-label mode, because its badge action
ignores `hiddenLabel()`. This port honours `hiddenLabel()` on badge actions, so
the primitive has to express it, and mirroring the prop Filament's sibling
[`link.blade.php`](../../../vendor/filament/support/resources/views/components/link.blade.php)
already carries is smaller than either alternative: gating the label spans on
empty content makes the primitive silently drop structure, and having the caller
hand-build the `aria-label` leaves a pair of empty label spans behind.

The accessible name is `striptags`'d and trimmed off the content, matching the
`trim(strip_tags(…))` Filament runs for the same prop on `link.blade.php`. The
`// Security:` comment there covers the third step Filament needs and this port
does not — Blade merges the attribute with `escape: false`, so an `Htmlable`
label would break out of the attribute unless explicitly escaped, while
`attributes.defaults()` escapes on output. The strip is still load-bearing: a
`Markup` label's tags would otherwise be announced as part of the name. Escaping
on output brings the same double-encoding caveat recorded on
[icon-button.md](icon-button.md#the-aria-label-is-double-encoded-where-filament-suppresses-it):
a label that already carries an escaped entity renders doubly encoded.

### The `disabled` attribute is emitted only on `<button>`

Filament merges `'disabled' => $disabled && blank($tooltip)` onto the root
regardless of tag, so a disabled badge receives a `disabled` attribute on
whatever it renders as — including the default `<span>`, where the attribute is
not valid. Sylament guards it with `disabled and tag == 'button'`, so a disabled
span or anchor carries only `aria-disabled` and `sy-disabled`. The signalling is
identical across tags; only the native attribute is withheld from non-buttons.
Same shape as the
[icon-button divergence](icon-button.md#the-disabled-attribute-is-emitted-only-on-button),
and it bites more often here because a badge is a `<span>` by default.

### The color map is not ported — colors resolve through CSS classes

`BadgeComponent.php` exists only to supply a `getColorMap()` that the Blade view
applies with `->color(BadgeComponent::class, $color)` to inject per-shade inline
custom properties. Sylament ports neither the PHP class nor the color manager:
the template emits static `sy-color` / `sy-color-<name>` marker classes and
defers every shade and contrast decision to the `--sy-*` runtime tokens and
[`badge.css`](../../../packages/support/assets/src/styles/components/badge.css),
whose `.sy-color` / `:not(.sy-color)` branches carry what Filament splits between
the map and the stylesheet. The `HasDefaultGrayColor` behaviour — gray, empty or
iterable ⇒ no color classes — survives as the `colorName != 'gray'` guard. This
is the project-wide styling contract rather than a badge-local choice; the
fullest statement of it is on
[button.md](button.md#the-color-map-is-not-ported--colors-resolve-through-css-classes).

_See also: the global [rename table](../renames.md) — `fi-*` → `sy-*` classes,
Blade → Twig (`view()` → `template()`), Alpine → Stimulus and `Htmlable` →
`Markup` are systematic translations recorded there, not per-component
divergences._
