# Link (support)

`packages/support/templates/components/Link/index.html.twig` — the anonymous Twig component (`<twig:SylamentSupport:Link>`) that ports Filament's `<x-filament::link>`.
It renders an `<a>` or `<button>` styled as inline text: a label span flanked by an optional icon, with a size, a font weight and a color.
As with the other trigger primitives, the Blade component's behaviour is split across two Filament files — the `link.blade.php` view (markup, props, size and attribute logic) and the `LinkComponent.php` class (the WCAG-AA contrast color map).
Sylament ports the structural view faithfully and leaves the whole interactive and decorative layer — Livewire loading indicator, badge slot, key bindings, tooltip, SPA-mode href — and the color-map machinery unbuilt, pending the infrastructure each depends on.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/resources/views/components/link.blade.php`](../../../vendor/filament/support/resources/views/components/link.blade.php) — the Blade view: props, size and weight resolution, attribute merge, markup
- [`support/src/View/Components/LinkComponent.php`](../../../vendor/filament/support/src/View/Components/LinkComponent.php) — the color-map provider the view's `->color(LinkComponent::class, …)` call resolves against

## Surface

Every point of this component.
_Blocked by_ names what has to land first.
_Detail_ carries any of three: **detail** → the write-up in [Details](#details), deleted once the row is **done**; **semantics** → the permanent note in [Semantics](#semantics); **diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Tag + `href`/`target`](../../../packages/support/templates/components/Link/index.html.twig) | [`<{{ $tag }}>` + `generate_href_html(…)`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| [`type` / `form` association](../../../packages/support/templates/components/Link/index.html.twig) | [`'type'`/`'form'` merge](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | [semantics](#form-is-emitted-regardless-of-tag-unlike-on-badge) |
| [`disabled` + `aria-disabled`](../../../packages/support/templates/components/Link/index.html.twig) | [`'disabled'`/`'aria-disabled'` merge](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| [`size` → `sy-size-*` classes](../../../packages/support/templates/components/Link/index.html.twig) | [`->class(['fi-size-…'])`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| [`weight` → `sy-font-*` classes](../../../packages/support/templates/components/Link/index.html.twig) | [`->class(['fi-font-…'])`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| [`color` → `sy-color*` classes](../../../packages/support/templates/components/Link/index.html.twig) | [`->color(LinkComponent::class, $color)`](../../../vendor/filament/support/src/View/Components/LinkComponent.php) | done | — | [diverges](#the-gray-shades-sit-in-a-notsy-color-branch-not-on-the-base-rule) |
| [Icon + size derived from `size` + `iconPosition`](../../../packages/support/templates/components/Link/index.html.twig) | [`generate_icon_html(…)` + `$iconSize ??= match ($size)`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| [`labelSrOnly` → `aria-label`](../../../packages/support/templates/components/Link/index.html.twig) | [`'aria-label' => $labelSrOnly ? … : null`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| `badge` / `badgeColor` / `badgeSize` | [`fi-link-badge-ctn` block](../../../vendor/filament/support/resources/views/components/link.blade.php) | todo | — | [detail](#the-badge-slot-is-unbuilt-not-blocked) |
| `tooltip` | [`x-tooltip` block + disabled-tooltip filter](../../../vendor/filament/support/resources/views/components/link.blade.php) | blocked | Stimulus tooltip controller | [detail](#the-interactive-layer) |
| Key bindings | [`x-mousetrap.global.…`](../../../vendor/filament/support/resources/views/components/link.blade.php) | blocked | Stimulus mousetrap controller | [detail](#the-interactive-layer) |
| Loading indicator | [`generate_loading_indicator_html(…)` + `wire:*`](../../../vendor/filament/support/resources/views/components/link.blade.php) | blocked | UX LiveComponent state layer | [detail](#the-interactive-layer) |
| SPA-mode href | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/link.blade.php) | blocked | panels SPA navigation | [detail](#the-interactive-layer) |
| `tag: 'form'` POST-to-URL | [`tag: $url ? ($shouldPostToUrl ? 'form' : 'a') : 'button'`](../../../vendor/filament/actions/src/Action.php) | todo | — | [detail](#tag-form-has-no-caller-that-could-request-it) |

## Details

The **done** rows — tag/`href`/`target`, `type`/`form`, `disabled`, `size`, `weight`, `color` (which diverges), the icon with its size derivation and position, and `labelSrOnly` — are faithful (allowing the systematic `fi-*` → `sy-*`, Blade → Twig and `Htmlable` → `Markup` translations) and every one is exercised by [`LinkRenderTest`](../../../packages/actions/tests/Integration/Components/LinkRenderTest.php): the default primary/`md` anchor with its label span and no `type`, a `<button>` with `type` and `form`, `target="_blank"`, the `disabled`/`aria-disabled`/ `sy-disabled` trio, a gray color dropping the color classes, a semantic `danger` color, an unknown size emitted as a literal class, a font-weight class, the icon size derived from a small link (and left underived at medium, and overridden by an explicit `iconSize`), the icon rendered after the label, the `aria-label`-only hidden label, and extra-attribute forwarding.
That suite lives in **actions** because only a kernel registering `TwigComponentBundle` renders the support Twig components, and actions is where the consumers are.
So those rows carry no write-up here.

### The badge slot is unbuilt, not blocked

`badge`/`badgeColor`/`badgeSize` render an absolutely-positioned badge over the link's end corner.
This row is **todo** rather than **blocked** because nothing structural stands in its way any more: the [`Badge`](badge.md) primitive exists, so the slot would be a wrapper div around a `<twig:SylamentSupport:Badge>` call.
What is missing is a caller.
Filament's badge slot is fed by `HasBadge`, which `support` has not ported, and `actions`' `Action::badge()` is the view-variant switch (it selects `BADGE_TEMPLATE`), not a badge label — so no consumer can reach the slot.
Building it now would be speculative provisioning; it lands with `HasBadge`.

### The interactive layer

`tooltip`, key bindings, the loading indicator and SPA-mode `href` are absent from the prop list and the body.
Each is blocked on infrastructure that has not been ported, and the blockers are the same ones [`icon-button.md`](icon-button.md) records:

- **Tooltip** — the `x-tooltip` block and the coupled disabled-with-tooltip behaviour (filtering `href`/`x-on:`/`wire:click` off a disabled-but-tooltipped control) need a Stimulus tooltip controller.
  Because no tooltip can be set, Filament's `$disabled && blank($tooltip)` reduces to `$disabled`, which is what the ported template assumes.
- **Key bindings** — Filament's `x-mousetrap` global binding is an Alpine plugin; the Sylament equivalent is a Stimulus controller that does not yet exist.
- **Loading indicator** — the `wire:target`/`wire:loading` wiring, `generate_loading_indicator_html()`, and the `loadingIndicator`/`form`/`spaMode` props all presuppose the UX LiveComponent state layer.
  Its absence also removes Filament's reason to set `disabled` via `wire:loading.attr`, and the `form` prop (submit-target for the indicator) collapses to `formId` alone.
  It is also the reason the CSS pins `.sy-link-label` to `self-baseline`: Filament keeps the label's baseline aligned with surrounding text even when the loading indicator is the first flex item, and the rule is ported ahead of the indicator that motivates it.
- **SPA-mode href** — the `spaMode` argument of `generate_href_html()` encodes panel single-page navigation, which the panels bundle has not reached.

### `tag: 'form'` has no caller that could request it

Filament's `Action::toLinkHtml()` passes `tag: $url ? ($shouldPostToUrl ? 'form' : 'a') : 'button'`, so a link action that posts to its URL renders the whole trigger as a `<form method="post">` with `action` set.
The Sylament port has no `postToUrl()` on `Action` and no `shouldPostToUrl()` anywhere, so nothing can pass `'form'` as the tag.
The primitive needs no change to accept it — `tag` is a free string — but the `action`/`method` attributes and the nested submit button that make the form work are unported along with their caller.

## Semantics

Faithful ports whose behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### `form` is emitted regardless of tag, unlike on Badge

The template writes `form: formId ?: false` with no tag guard, so a `formId` passed to an `<a>`-tagged link renders a `form` attribute that does nothing.
Its sibling primitive guards the same attribute with `tag == 'button'`.
The asymmetry is not an oversight on either side of the port: `link.blade.php` merges `'form' => $formId` unconditionally while `badge.blade.php` merges `'form' => $tag === 'button' ? $formId : null`, and both are carried verbatim.
Normalising the two to agree would be the divergence, so the difference stays.

## Divergences

### The gray shades sit in a `:not(.sy-color)` branch, not on the base rule

The colour map is ported — [`LinkComponent`](../../../packages/support/src/Colors/Components/LinkComponent.php) is Filament's verbatim, resolving `text` against gray-50 and `dark:text` against gray-700 — and the template hands `color` to `colorAttributes()`, which emits the marker, the swap class and the two shade classes, or the inline palette for an array colour.

The structural difference is where the *uncoloured* link's grays live.
Filament puts `text-gray-700 dark:text-gray-200` on the base `.fi-link` rule and lets the colour variables override them; `link.css` puts them in an explicit `&:not(.sy-color)` branch and the resolved slots in `&.sy-color`.
Both render the same thing, because `HasDefaultGrayColor` means a gray or empty colour emits no `sy-color` marker at all — so the two branches are exclusive by construction rather than by cascade order.

It is worth keeping rather than normalising: the branch states the rule the marker class already encodes, so a reader cannot mistake the grays for a default that a slot variable is expected to beat.
The same shape is used by [`Badge`](badge.md) and [`IconButton`](icon-button.md).

_See also: the global [rename table](../renames.md) — `fi-*` → `sy-*` classes, Blade → Twig (`view()` → `template()`), Alpine → Stimulus and `Htmlable` → `Markup` are systematic translations recorded there, not per-component divergences._
