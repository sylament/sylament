# Badge (support)

`packages/support/templates/components/Badge/index.html.twig` — the anonymous
Twig component (`<twig:SylamentSupport:Badge>`) that ports Filament's
`<x-filament::badge>`. It renders a short status pill — a `<span>` by default, a
`<button>` or an `<a>` when the caller switches the tag — carrying a label, an
optional icon before or after it, and a size/color pair that selects the visual
treatment. The Blade component's behaviour is split across two Filament files:
the `badge.blade.php` view (markup, props, attribute merge) and the
`BadgeComponent.php` PHP class (the WCAG-AA contrast color map). Sylament ports
the structural view faithfully — the deletable badge and the color map included —
and leaves the whole interactive layer unbuilt (Livewire loading indicator, key
bindings, tooltip, SPA-mode href), pending the infrastructure each depends on.

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
| [`color` → `sy-color*` classes](../../../packages/support/templates/components/Badge/index.html.twig) | [`->color(BadgeComponent::class, $color)`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php) | done | — | [diverges](#the-color-map-resolves-the-text-shade-not-the-ring-or-the-background) |
| [Icon + derived `sm` size + `iconPosition`](../../../packages/support/templates/components/Badge/index.html.twig) | [`generate_icon_html(…, size: $iconSize ?? IconSize::Small)`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | — |
| [`labelSrOnly` → `aria-label`](../../../packages/support/templates/components/Badge/index.html.twig) | — (no Filament badge equivalent) | done | — | [diverges](#labelsronly-is-a-sylament-addition) |
| [`deletable` / `deleteLabel` / `deleteAttributes`](../../../packages/support/templates/components/Badge/index.html.twig) | [`deleteButton` slot → `fi-badge-delete-btn`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | [diverges](#the-delete-button-is-three-props-not-an-attribute-carrying-slot) |
| `tooltip` | [`x-tooltip` block + disabled-tooltip filter](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | Stimulus tooltip controller | [detail](#details) |
| Key bindings | [`x-mousetrap` block](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | Stimulus mousetrap controller | [detail](#details) |
| Loading indicator | [`generate_loading_indicator_html(…)` + `wire:*`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | UX LiveComponent state layer | [detail](#details) |
| SPA-mode href | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | panels SPA navigation | [detail](#details) |
| `tag: 'form'` POST-to-URL | [`tag: $url ? $shouldPostToUrl ? 'form' : 'a' : 'button'`](../../../vendor/filament/actions/src/Action.php) | todo | — | [detail](#details) |

## Details

The ported rows — tag/`href`/`target`, `type`/`form`, `disabled`/`aria-disabled`,
`size`, `color`, the icon with its derived `sm` size and its position, the delete
button, and `labelSrOnly` — are faithful (allowing the systematic `fi-*` → `sy-*`,
Blade → Twig and `Htmlable` → `Markup` translations, and the four divergences
below) and every one is exercised by
[`BadgeRenderTest`](../../../packages/actions/tests/Integration/Components/BadgeRenderTest.php):
the default `span` with its primary/`md` class list and the two-span label, tag
switching to `button` (with `type` and an external `form`) and to `a` (with
`href`/`target="_blank"` and no `type`), the
`disabled`/`aria-disabled`/`sy-disabled` trio, a gray color dropping the color
classes, a semantic color, an unknown size emitted as a literal class, the
`sm`-by-default icon size, an icon rendered after the label, the
`aria-label`-only hidden label, extra-attribute forwarding, and the five
delete-button cases (absent unless `deletable`, rendered after the label spans
and inside the badge, the default and the stripped label, the caller's attribute
bag landing on the button, and the button taking the place of an `after` icon).
That suite lives in **actions** because only its kernel and schemas' register
TwigComponentBundle, and actions is where the badge's consumer lives.

**Tooltip, key bindings, loading indicator, and SPA-mode href** are not built at
all. Each is blocked on infrastructure that has not been ported:

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

### The color map resolves the text shade, not the ring or the background

The badge is the component the colour registry was built for, and it is now a
faithful port: the template calls
`colorAttributes(BadgeComponent::class, color)`, which runs
[`BadgeComponent`](../../../packages/support/src/Colors/Components/BadgeComponent.php)
— Filament's map verbatim — and emits `sy-color`, `sy-color-<name>` and the two
resolved shade classes, or the inline `--sy-color-*` palette when the colour is
handed over as an array. Gray and empty still emit nothing, now because the
policy implements `HasDefaultGrayColor` rather than because of a template guard,
so the `:not(.sy-color)` branch of
[`badge.css`](../../../packages/support/assets/src/styles/components/badge.css)
paints them.

What remains a divergence is narrower and worth stating, because it is easy to
read the map as covering the whole badge. The policy resolves **two** slots —
`text` and `dark:text` — and nothing else; the badge's background
(`bg-color-50`, `dark:bg-color-400/10`) and ring (`ring-color-600/10`,
`dark:ring-color-400/30`) stay hardcoded shades in the stylesheet, on both sides.
Filament does the same, and it is defensible for exactly the reason the text
slot is not: a 10 %-alpha ring and a shade-50 fill are decorative at any
lightness, while the text has to be read.

The defect this closed is measurable. `badge.css` applied `text-color-600`, which
against the palettes shipping at the time cleared AA only for gray (7.51:1) and
primary (4.86:1) — danger measured 4.35:1, info 3.73:1, warning 3.06:1 and
success 3.03:1. Under the policy, warning resolves to shade 700 at 4.86:1, and
every palette × slot pairing is held above its declared ratio by
[`PolicyContrastTest`](../../../packages/support/tests/Unit/Colors/Components/PolicyContrastTest.php).

### The delete button is three props, not an attribute-carrying slot

Filament's remove affordance is a slot whose **attributes** are the payload:
`<x-slot name="deleteButton" :wire:click="…" wire:target="…">` carries no content
at all, and `$isDeletable = count($deleteButton?->attributes->getAttributes() ?? []) > 0`
reads the presence of those attributes as the switch. A Twig `<twig:block>` has
no attribute bag — it is content and nothing else — so the same payload arrives
as props instead: `deletable` (the switch Blade infers), `deleteLabel` (the
accessible name) and `deleteAttributes` (the bag, spread onto the button through
`attrs({class: 'sy-badge-delete-btn', …}, deleteAttributes)`, so caller classes
append and caller values win). That is what lets tables' indicator chip hand the
button its `stimulus_action('live', 'action', parameters: …)` map where Filament
hands it a `wire:click` string.

Everything downstream of the switch is the port verbatim: the button renders
**inside** the badge capsule after the label spans, takes the place of an icon
positioned `after` (Filament's `@elseif`, so a deletable badge with a trailing
icon shows the X, not the icon), and draws
`SupportIconAlias::BADGE_DELETE_BUTTON` at the extra-small icon size with
`aria-hidden="true"`.

Three smaller shifts inside it. The accessible name is an `aria-label` on the
button rather than Filament's `fi-sr-only` span, matching
[`IconButton`](icon-button.md) — support ships no sr-only class of its own, and
the escaped-on-output caveat the `labelSrOnly` divergence records applies here
too. `deleteLabel` defaults to the literal `'Remove'` where Filament reads
`__('filament::components/badge.actions.delete.label')`, one more entry for the
i18n epic. And the icon colour reads `currentColor` at 50 % opacity (75 % on
hover and focus) where
[`badge.css`](../../../vendor/filament/support/resources/css/components/badge.css)
hardcodes `text-color-700/50` and `text-gray-700/50`: the badge's own text colour
is already the shade `BadgeComponent` resolved for this palette, so inheriting it
keeps the one contrast decision in the policy instead of re-fixing a shade in the
stylesheet — [rule 6](../../styling.md#the-rules) of the styling contract, whose
own example is the `text-color-600` defect this component shipped once already.

The hover and focus states moved with it, from the icon to the button:
`badge.css` hangs them off `.fi-badge-delete-btn > .fi-icon` as
`hover:`/`focus-visible:` utilities — pseudo-classes on the **`<svg>`** — where
ours reads `.sy-badge-delete-btn:hover > .sy-icon` and its `:focus-visible`
twin. An `svg` is not focusable, so the vendor's `focus-visible` variant can
never match: keyboard focus lands on the button, and the icon it contains is
never the `:focus-visible` element. Their uncoloured branch is also missing a
light-mode `hover:` entirely (it ships `focus-visible:text-gray-700/75` beside
`dark:hover:` and `dark:focus-visible:`, and no light `hover:`), which the
selector shift fills as a side effect rather than as a second decision.

One hazard rides along, and it is Filament's too: the delete button renders
*inside* `<{{ tag }}>`, so a badge built with `tag="button"` or `tag="a"` nests
interactive content inside interactive content — invalid HTML, with the nested
control's behaviour left to the browser. Filament's blade has the identical
shape (`tag` defaults to `span`, the `<button>` sits inside it), so this is
inherited rather than introduced, and no shipped call site reaches it: the only
`deletable` caller is tables' [filter indicator chip](../tables/table.md), which
leaves `tag` at its `span` default, and the only `tag="button"`/`tag="a"` caller
is actions' badge-variant trigger, which never sets `deletable`. It is recorded
because the props make the combination reachable in one line of userland Twig.

_See also: the global [rename table](../renames.md) — `fi-*` → `sy-*` classes,
Blade → Twig (`view()` → `template()`), Alpine → Stimulus and `Htmlable` →
`Markup` are systematic translations recorded there, not per-component
divergences._
