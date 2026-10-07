---
description: The `<twig:SylamentSupport:Badge>` anonymous component, Filament's status pill with its icon, colour map and delete button.
ports:
  - vendor/filament/support/resources/views/components/badge.blade.php
  - vendor/filament/support/src/View/Components/BadgeComponent.php
  - vendor/filament/support/resources/css/components/badge.css
owns:
  - packages/support/assets/src/styles/components/badge.css
  - packages/support/templates/components/Badge/
---
# Badge (support)

[`Badge/index.html.twig`](../../../packages/support/templates/components/Badge/index.html.twig) ports Filament's `<x-filament::badge>`: a short status pill, a `<span>` by default or a `<button>` or `<a>` when the caller switches the tag, carrying a label, an optional icon before or after it, an optional delete button, and a size and colour pair that selects the visual treatment.
Filament splits it between the Blade view and the `BadgeComponent` colour map; the port's map is [`BadgeComponent`](../../../packages/support/src/Colors/Components/BadgeComponent.php), and the stylesheet is [`badge.css`](../../../packages/support/assets/src/styles/components/badge.css).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Tag + `href`/`target`](../../../packages/support/templates/components/Badge/index.html.twig) | [`<{{ $tag }}>` + `generate_href_html(…)`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | — |
| [`type` / `form` association](../../../packages/support/templates/components/Badge/index.html.twig) | [`'type'`/`'form'` merge](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | — |
| [`disabled` + `aria-disabled`](../../../packages/support/templates/components/Badge/index.html.twig) | [`'disabled'`/`'aria-disabled'` merge](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | [diverges](#the-disabled-attribute-is-emitted-only-on-button) |
| [`size` → `sy-size-*` classes](../../../packages/support/templates/components/Badge/index.html.twig) | [`->class(['fi-size-…'])`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | — |
| [`color` → `sy-color*` classes](../../../packages/support/templates/components/Badge/index.html.twig) | [`->color(BadgeComponent::class, $color)`](../../../vendor/filament/support/src/View/Components/BadgeComponent.php) | done | — | [semantics](#the-color-map-resolves-the-text-shade-not-the-ring-or-the-background) |
| [Icon + derived `sm` size + `iconPosition`](../../../packages/support/templates/components/Badge/index.html.twig) | [`generate_icon_html(…, size: $iconSize ?? IconSize::Small)`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | — |
| [The label's two spans](../../../packages/support/templates/components/Badge/index.html.twig) | [`fi-badge-label-ctn` wrapping `fi-badge-label`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | [semantics](#the-outer-label-span-is-what-pins-the-badges-baseline) |
| [`labelSrOnly` → `aria-label`](../../../packages/support/templates/components/Badge/index.html.twig) | — | done | — | [diverges](#labelsronly-is-a-sylament-addition) |
| [`deletable` / `deleteLabel` / `deleteAttributes`](../../../packages/support/templates/components/Badge/index.html.twig) | [`deleteButton` slot → `fi-badge-delete-btn`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | done | — | [diverges](#the-delete-button-is-three-props-not-an-attribute-carrying-slot) |
| [`badge.css`](../../../packages/support/assets/src/styles/components/badge.css) | [`components/badge.css`](../../../vendor/filament/support/resources/css/components/badge.css) | done | — | [diverges](#the-delete-button-is-three-props-not-an-attribute-carrying-slot) |
| `tooltip` | [`x-tooltip` block + disabled-tooltip filter](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | Stimulus tooltip controller | [icon-button.md](icon-button.md#the-tooltip-waits-on-a-stimulus-tooltip-controller-and-callers-pass-a-native-title-instead) |
| Key bindings | [`x-mousetrap` block](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | Stimulus mousetrap controller | [icon-button.md](icon-button.md#key-bindings-wait-on-a-shared-key-binding-controller) |
| Loading indicator | [`generate_loading_indicator_html(…)` + `wire:*`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | todo | — | [icon-button.md](icon-button.md#the-loading-indicator-is-unwritten-though-its-live-equivalent-ships) |
| SPA-mode href | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/badge.blade.php) | blocked | panels SPA navigation | [icon-button.md](icon-button.md#spa-mode-href-waits-on-panels-spa-navigation) |
| `tag: 'form'` POST-to-URL | [`CanGenerateBadgeHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateBadgeHtml.php) | skipped | — | [can-open-url.md](../actions/can-open-url.md#post-to-url-support-is-not-ported) |

## Semantics

### The outer label span is what pins the badge's baseline

The label is two spans, `sy-badge-label-ctn` wrapping `sy-badge-label`, and the pair is not one span written twice.
The outer one is the badge's flex item, so it is the box `align-self` applies to, and [`badge.css`](../../../packages/support/assets/src/styles/components/badge.css) gives it `grid self-baseline`: that rule is what makes an `inline-flex` badge sit on the surrounding text's baseline by its **label** rather than by whichever flex item comes first.
The inner span is the text box, and it is what the wrap and truncate arms target.
Filament splits them the same way, on the same two class names, and states the purpose in a comment above the same rule in [`badge.css`](../../../vendor/filament/support/resources/css/components/badge.css); this port drops the comment, because the reasoning lives here.

The pin is load-bearing without the loading indicator Filament's comment names ([todo](icon-button.md#the-loading-indicator-is-unwritten-though-its-live-equivalent-ships)): an icon positioned `before` is already a flex sibling ahead of the label, and the badge is `inline-flex items-center`, so without the rule the badge offers the surrounding line whatever baseline the icon has.
Support's [`Link`](link.md) reaches the same alignment with one span, `sy-link-label` carrying `self-baseline` and nothing else, which is why the badge reads as one span too many until the wrap arms on the inner span are noticed.

Folding the pair into one span costs the alignment and nothing names it.
[`BadgeRenderTest`](../../../packages/actions/tests/Integration/Components/BadgeRenderTest.php) asserts the two spans as a literal markup string, so the change surfaces as a failed string comparison that reads like a stale assertion and invites updating; no test asserts the baseline.

### The color map resolves the text shade, not the ring or the background

The template calls `colorAttributes(BadgeComponent::class, color)`, which runs [`BadgeComponent`](../../../packages/support/src/Colors/Components/BadgeComponent.php), Filament's map verbatim, and emits `sy-color`, `sy-color-<name>` and the two resolved shade classes, or the inline `--sy-color-*` palette when the colour is handed over as an array.
Gray and empty emit nothing, because the policy implements `HasDefaultGrayColor`, so the `:not(.sy-color)` branch of [`badge.css`](../../../packages/support/assets/src/styles/components/badge.css) paints them.

The policy resolves **two** slots, `text` and `dark:text`, and nothing else: the badge's background (`bg-color-50`, `dark:bg-color-400/10`) and ring (`ring-color-600/10`, `dark:ring-color-400/30`) stay hardcoded shades in the stylesheet.
Filament does the same, and it is defensible for exactly the reason the text slot is not: a 10 %-alpha ring and a shade-50 fill are decorative at any lightness, while the text has to be read.
[`PolicyContrastTest`](../../../packages/support/tests/Unit/Colors/Components/PolicyContrastTest.php) holds every palette × slot pairing above its declared ratio.

## Divergences

### `labelSrOnly` is a Sylament addition

Filament's `badge.blade.php` has no hidden-label mode, because its `Action::toBadgeHtml()` passes no `isLabelSrOnly`, so a badge action ignores `hiddenLabel()`.
This port honours `hiddenLabel()` on badge actions ([`Action/Badge.html.twig`](../../../packages/actions/templates/partials/Action/Badge.html.twig) passes `labelSrOnly`), so the primitive has to express it, and mirroring the prop Filament's sibling [`link.blade.php`](../../../vendor/filament/support/resources/views/components/link.blade.php) carries is smaller than either alternative: gating the label spans on empty content makes the primitive silently drop structure, and having the caller hand-build the `aria-label` leaves a pair of empty label spans behind.

The accessible name is `striptags`'d and trimmed off the content, matching the `trim(strip_tags(…))` Filament runs for the same prop on `link.blade.php`.
The `// Security:` comment there covers the third step Filament needs and this port does not: Blade merges the attribute with `escape: false`, so an `Htmlable` label would break out of the attribute unless explicitly escaped, while `attributes.defaults()` escapes on output.
The strip is still load-bearing: a `Markup` label's tags would otherwise be announced as part of the name.
Escaping on output brings the same double-encoding caveat recorded on [icon-button.md](icon-button.md#the-aria-label-is-double-encoded-where-filament-suppresses-it): a label that already carries an escaped entity renders doubly encoded.

### The `disabled` attribute is emitted only on `<button>`

Filament merges `'disabled' => $disabled && blank($tooltip)` onto the root regardless of tag, so a disabled badge receives a `disabled` attribute on whatever it renders as, including the default `<span>`, where the attribute is not valid.
Sylament guards it with `disabled and tag == 'button'`, so a disabled span or anchor carries only `aria-disabled` and `sy-disabled`.
The signalling is identical across tags; only the native attribute is withheld from non-buttons.
It is the same shape as the [icon-button divergence](icon-button.md#the-disabled-attribute-is-emitted-only-on-button), and it bites more often here because a badge is a `<span>` by default.

### The delete button is three props, not an attribute-carrying slot

Filament's remove affordance is a slot whose **attributes** are the payload: `<x-slot name="deleteButton" :wire:click="…" wire:target="…">` carries no content at all, and `$isDeletable = count($deleteButton?->attributes->getAttributes() ?? []) > 0` reads the presence of those attributes as the switch.
A Twig `<twig:block>` has no attribute bag, only content, so the same payload arrives as props instead: `deletable` (the switch Blade infers), `deleteLabel` (the accessible name) and `deleteAttributes` (the bag, spread onto the button through `attrs({class: 'sy-badge-delete-btn', …}, deleteAttributes)`, so caller classes append and caller values win).
That is what lets tables' filter indicator chip and forms' tags-input chip hand the button a `stimulus_action(…)` map where Filament hands it a `wire:click` string.

The placement follows Filament: the button renders **inside** the badge capsule after the label spans and takes the place of an icon positioned `after` (Filament's `@elseif`, so a deletable badge with a trailing icon shows the X, not the icon).

The glyph does not.
Filament renders an empty `<span class="fi-badge-delete-btn-icon">` that [`badge.css`](../../../vendor/filament/support/resources/css/components/badge.css) paints as a `mask-image` filled with `currentColor`, and reads no icon alias for it; `SupportIconAlias::BADGE_DELETE_BUTTON` survives in Filament only as a constant.
The port draws an SVG through `generateIconHtml()` under that alias, at the extra-small icon size with `aria-hidden="true"`, so an application can swap the glyph the way every other support icon is swapped.

Three smaller shifts ride with it.
The accessible name is an `aria-label` on the button rather than Filament's `fi-sr-only` span, matching [`IconButton`](icon-button.md): support ships no sr-only class of its own, and the escaped-on-output caveat the `labelSrOnly` divergence records applies here too.
`deleteLabel` defaults to the literal `'Remove'` where Filament reads `__('filament::components/badge.actions.delete.label')`, one more literal for the [deferred i18n pass](../../internals/i18n.md).
And the glyph colour reads `currentColor` at 50 % opacity (75 % on hover and focus) where Filament's stylesheet sets `text-color-700/50` and `text-gray-700/50` on the glyph: the badge's own text colour is already the shade `BadgeComponent` resolved for this palette, so inheriting it keeps the one contrast decision in the policy instead of re-fixing a shade in the stylesheet, [the policy layer](../../internals/styling.md#why-does-a-policy-pick-the-shade-instead-of-the-stylesheet) of the styling contract.

The hover and focus states hang off the button, not the glyph: Filament writes them as `hover:` / `focus-visible:` utilities on `.fi-badge-delete-btn-icon`, pseudo-classes on the glyph `<span>`, where ours reads `.sy-badge-delete-btn:hover > .sy-icon` and its `:focus-visible` twin.
The glyph is not focusable, so Filament's `focus-visible` variant can never match: keyboard focus lands on the button, and the glyph inside it is never the `:focus-visible` element.
Filament's uncoloured branch also has no light-mode `hover:` (it ships `focus-visible:text-gray-700/75` beside `dark:hover:` and `dark:focus-visible:`), which the selector shift fills as a side effect rather than as a second decision.

One hazard rides along, and it is Filament's too: the delete button renders *inside* `<{{ tag }}>`, so a badge built with `tag="button"` or `tag="a"` nests interactive content inside interactive content, which is invalid HTML with the nested control's behaviour left to the browser.
Filament's blade has the identical shape (`tag` defaults to `span`, the `<button>` sits inside it), so this is inherited rather than introduced, and no shipped call site reaches it: the `deletable` callers, tables' [filter indicator chip](../../../packages/tables/templates/partials/Table/index.html.twig) and forms' [tags-input chip](../../../packages/forms/templates/partials/TagsInput/Chip.html.twig), leave `tag` at its `span` default, and the only `tag="button"` / `tag="a"` caller is actions' badge-variant trigger, which never sets `deletable`.
It is recorded because the props make the combination reachable in one line of userland Twig.
