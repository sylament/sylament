# Dropdown item (support)

`packages/support/templates/components/DropdownItem/index.html.twig` — the
anonymous `<twig:SylamentSupport:DropdownItem>` primitive: one row inside a
[dropdown](dropdown.md) panel, rendered as a `<button>` or an `<a>` carrying an
optional leading icon and a label span. It ports Filament's
`<x-filament::dropdown.list.item>`, the component `Action`'s `GROUPED_VIEW`
points at. There is no Sylament PHP class, matching the other trigger
primitives: Filament's `ItemComponent` exists only to compute a runtime color
map this port does not use, so the template is the whole component. The
structural surface — tag switching, `type`/`form`, `disabled`, color, icon,
label — is ported; the decorative surface (image, badge, selected state) and the
whole interactive layer (tooltip, key bindings, loading indicator, SPA mode) are
not built.

Its stylesheet predates it. `.sy-dropdown-item` and `.sy-dropdown-item-label`
shipped inside
[`dropdown.css`](../../../packages/support/assets/src/styles/components/dropdown.css)
with the dropdown epic, styling item markup only `actions` hand-wrote — in
[`Action/Grouped.html.twig`](../../../packages/actions/templates/partials/Action/Grouped.html.twig)
and
[`ActionGroup/GroupedTrigger.html.twig`](../../../packages/actions/templates/partials/ActionGroup/GroupedTrigger.html.twig)
— and this file is where that markup gets an owner. Both rules moved
byte-for-byte into
[`dropdown-item.css`](../../../packages/support/assets/src/styles/components/dropdown-item.css),
so the extraction changes nothing on the page; the `.sy-color` rules are the only
addition, and they are reachable only because `color` is a new prop.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/resources/views/components/dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php)
  — the Blade view: props, attribute merge, markup
- [`support/src/View/Concerns/CanGenerateDropdownItemHtml.php`](../../../vendor/filament/support/src/View/Concerns/CanGenerateDropdownItemHtml.php)
  — the v5 PHP generator `Action::toGroupedHtml()` actually calls, whose props
  differ from the Blade view's in one place that matters
- [`support/src/View/Components/DropdownComponent/ItemComponent.php`](../../../vendor/filament/support/src/View/Components/DropdownComponent/ItemComponent.php)
  — the color-map provider `->color(ItemComponent::class, $color)` resolves against
- [`support/resources/css/components/dropdown/list/item.css`](../../../vendor/filament/support/resources/css/components/dropdown/list/item.css)
  — the stylesheet the shipped rules are diffed against

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries any of: **detail** → the write-up in [Details](#details), deleted
once the row is **done**; **semantics** → the permanent note in
[Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Tag + `href`/`target`](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`<{{ $tag }}>` + `generate_href_html($href, …)`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | — |
| [`type` / `form` association](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`'type' => match ($tag) …`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | [semantics](#type-is-passed-in-not-derived-from-the-tag) · [diverges](#the-item-takes-a-formid-filaments-does-not) |
| [`disabled` + `aria-disabled`](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`'aria-disabled'`/`'disabled'` merge](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | — |
| [`color` → `sy-color*` classes](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`->color(ItemComponent::class, $color)`](../../../vendor/filament/support/src/View/Components/DropdownComponent/ItemComponent.php) | done | — | [diverges](#the-color-map-is-not-ported--colors-resolve-through-css-classes) |
| [Leading icon](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`generate_icon_html($icon, $iconAlias, …)`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | — |
| [Label span](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`fi-dropdown-list-item-label`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | — |
| `iconColor` | [`->color(IconComponent::class, $iconColor)`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#iconcolor) |
| `image` | [`fi-dropdown-list-item-image`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#image) |
| `badge` / `badgeColor` / `badgeTooltip` | [badge block + `BadgeComponent`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#badge--badgecolor--badgetooltip) |
| Deferred Alpine badge (`alpineDeferredBadgeData`/`Loading`) | [`x-if` deferred-badge block](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | blocked | UX LiveComponent state layer | [detail](#deferred-alpine-badge) |
| `fi-selected` selected state | [`&.fi-selected` rules](../../../vendor/filament/support/resources/css/components/dropdown/list/item.css) | todo | — | [detail](#fi-selected-selected-state) |
| `tooltip` | [`x-tooltip` block + disabled-tooltip filter](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | blocked | Stimulus tooltip controller | [detail](#tooltip) |
| Key bindings | [`x-mousetrap` block](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | blocked | Stimulus mousetrap controller | [detail](#key-bindings) |
| Loading indicator | [`generate_loading_indicator_html(…)` + `wire:*`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | blocked | UX LiveComponent state layer | [detail](#loading-indicator) |
| SPA-mode href | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | blocked | panels SPA navigation | [detail](#spa-mode-href) |
| `tag: 'form'` POST-to-URL | [`($tag === 'form') ? '<form …>'`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#tag-form-post-to-url) |

The **done** rows are exercised by
[`DropdownItemRenderTest`](../../../packages/actions/tests/Integration/Components/DropdownItemRenderTest.php)
— a support-owned primitive render-tested from the `actions` suite, the same
placement as the Button and Dropdown render tests, because only that kernel
registers TwigComponentBundle and both consumers live there. It asserts the
default gray `<button>` row and its label span, `<a>` tag-switching with
`href` + `target="_blank"` and no `type`, `type="submit"` with `form`, the
`disabled`/`aria-disabled`/`sy-disabled` trio, a semantic color, a leading icon
ordered before the label, and extra-attribute forwarding.

## Details

### `iconColor`

Filament colors the leading icon independently of the row, defaulting
`$iconColor ??= $color` and resolving it through a second color map
(`ItemComponent\IconComponent`). The port has no `iconColor` prop: the icon
inherits whatever the row's own color classes cascade to it. This is why
`item.css`'s `& > .fi-icon` rules — the gray default and its `.fi-color`
override — are **not** in
[`dropdown-item.css`](../../../packages/support/assets/src/styles/components/dropdown-item.css):
with no prop to drive them, a rule ported now would be dead CSS styling a class
nothing emits. They land with the prop.

### `image`

An avatar-style leading `<div>` with a `background-image`, used for
user-picker-shaped menus. Nothing in the port passes an image to a dropdown item,
and no `actions` API exposes one.

### `badge` / `badgeColor` / `badgeTooltip`

A trailing `fi-badge` span, optionally tooltipped. Unbuilt because no consumer
reaches it: `HasBadge` is deferred on `Action`, so `toGroupedHtml()` has nothing
to pass, and building it would be speculative provisioning. The `badgeTooltip`
half additionally needs the tooltip controller below.

### Deferred Alpine badge

The `alpineDeferredBadgeData`/`alpineDeferredBadgeLoading` pair renders a
placeholder loading indicator and then swaps in an `x-if`-gated badge once the
data arrives — an Alpine-expression contract between the item and whatever
resolves the badge asynchronously. It presupposes the UX LiveComponent state
layer, plus the plain badge row above.

### `fi-selected` selected state

Filament's `item.css` keeps the hover background pinned for a `.fi-selected`
row, in both the gray and colored branches, so a dropdown can mark the current
choice. No prop drives it in the Blade view — the class is applied by callers —
and no port consumer applies one, so neither the class nor its rules are shipped.

### `tooltip`

The `x-tooltip` block and the behavior coupled to it: Filament withholds the
native `disabled` attribute from a disabled-but-tooltipped control and filters
`href`/`x-on:`/`wire:click` off it, so the tooltip stays hoverable. Needs a
Stimulus tooltip controller. Because no tooltip can be set, Filament's
`$disabled && blank($tooltip)` reduces to `$disabled`, which is what the ported
template assumes — and `item.css`'s `&:not([x-tooltip])` guard on
`pointer-events-none` reduces the same way.

### Key bindings

Filament binds a global `x-mousetrap` shortcut that clicks the item. The
Sylament equivalent is a Stimulus controller that does not exist yet.

### Loading indicator

`generate_loading_indicator_html()`, the `wire:loading`/`wire:target` plumbing,
the `loadingIndicator` prop and the `livewire_loading_delay` config all
presuppose the UX LiveComponent state layer. Its absence also removes Filament's
reason to set `disabled` via `wire:loading.attr`.

### SPA-mode href

The `spaMode` argument of `generate_href_html()` encodes panel single-page
navigation, which the panels bundle has not reached. Plain `href`/`target` are
ported and tested.

### `tag: 'form'` POST-to-URL

Filament wraps the item in a `<form method="post">` and renders the row as that
form's submit button, which is how a grouped action POSTs to its URL rather than
navigating to it. No caller could ask for it here: the port has no
`shouldPostToUrl()`, so `tag` is only ever `'a'` or `'button'`. It lands with
that `Action` concern, not before.

## Semantics

### `type` is passed in, not derived from the tag

[`item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php)
has no `type` prop and derives the attribute from the tag —
`match ($tag) { 'button' => 'button', 'form' => 'submit', default => null }` —
so reading the Blade view alone says the port should derive it too. The v5
generator that `Action::toGroupedHtml()` actually calls,
[`CanGenerateDropdownItemHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateDropdownItemHtml.php),
takes `?string $type = 'button'` and emits the passed value for a `<button>`
tag, and `toGroupedHtml()` passes
`type: $this->canSubmitForm() ? 'submit' : 'button'`. The passed value has to
win: a form-submitting grouped action is a `<button>` needing `type="submit"`,
which the tag-derived form cannot express.

## Divergences

### The item takes a `formId`; Filament's does not

Neither the Blade view nor `CanGenerateDropdownItemHtml` has a form-association
prop, and `toGroupedHtml()` passes none — `getFormId()` reaches only the button
and icon-button generators. So a Filament grouped action can render
`type="submit"` with nothing naming the form it submits, and works only while the
item happens to sit inside that form. The port takes `formId` and emits `form`,
matching the pair every other trigger primitive already carries
([`Button`](button.md), [`IconButton`](icon-button.md)), and it is load-bearing
rather than symmetry for its own sake: the grouped case of
[`ActionViewVariantsRenderTest`](../../../packages/actions/tests/Integration/ActionViewVariantsRenderTest.php)
pins `type="submit"` together with `form="my-form"`. The alternative — reaching
Filament's own route to a submitting item, the
[`tag: 'form'` wrapper](#tag-form-post-to-url) — costs a `shouldPostToUrl()` port
and solves a different problem (POSTing to a URL, not submitting a named form).

### The color map is not ported — colors resolve through CSS classes

`ItemComponent` exists only to compute a contrast-checked per-shade color map
that `->color(ItemComponent::class, $color)` turns into inline `--text` /
`--hover-text` custom properties on the row, which `item.css` then reads. Sylament
ports neither the class nor the color manager: the template emits static
`sy-color` / `sy-color-<name>` marker classes (treating an iterable or empty
color as `gray`, which drops them), and
[`dropdown-item.css`](../../../packages/support/assets/src/styles/components/dropdown-item.css)
resolves the label color and the hover background from the `--sy-*` runtime
tokens instead. This is the project-wide styling contract rather than an
item-local choice — the same divergence
[`Button` records at length](button.md#the-color-map-is-not-ported--colors-resolve-through-css-classes).
The `HasDefaultGrayColor` half survives intact as the `colorName != 'gray'`
guard, which is what keeps an uncolored item byte-identical to what shipped
before this component existed.

_See also: the global [rename table](../renames.md) — `fi-*` → `sy-*` classes,
Blade → Twig and Alpine → Stimulus are systematic translations recorded there,
not per-component divergences._
