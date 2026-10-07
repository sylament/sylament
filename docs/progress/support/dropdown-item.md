---
description: The anonymous `<twig:SylamentSupport:DropdownItem>` row primitive inside a dropdown panel, and its stylesheet.
ports:
  - vendor/filament/support/resources/views/components/dropdown/list/item.blade.php
  - vendor/filament/support/src/View/Concerns/CanGenerateDropdownItemHtml.php
  - vendor/filament/support/resources/css/components/dropdown/list/item.css
owns:
  - packages/support/assets/src/styles/components/dropdown-item.css
  - packages/support/templates/components/DropdownItem/
---
# Dropdown item (support)

[`DropdownItem/index.html.twig`](../../../packages/support/templates/components/DropdownItem/index.html.twig) is the anonymous `<twig:SylamentSupport:DropdownItem>` primitive: one row inside a [dropdown](dropdown.md) panel, rendered as a `<button>` or an `<a>` carrying an optional leading icon and a label span.
It ports Filament's `<x-filament::dropdown.list.item>`, the component `Action`'s `GROUPED_VIEW` points at.
There is no Sylament PHP class, matching the other trigger primitives: the colour map Filament's `ItemComponent` computes is the [`ItemComponent`](color-policies.md) policy, and the template is the rest of the component.
Its render tests live in the actions suite ([`DropdownItemRenderTest`](../../../packages/actions/tests/Integration/Components/DropdownItemRenderTest.php)), whose kernel registers TwigComponentBundle.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Tag + `href`/`target`](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`<{{ $tag }}>` + `generate_href_html($href, …)`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | — |
| [`type` / `form` association](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`'type' => match ($tag) …`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | [semantics](#type-is-passed-in-not-derived-from-the-tag) · [diverges](#the-item-takes-a-formid-filaments-does-not) |
| [`disabled` + `aria-disabled`](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`'aria-disabled'`/`'disabled'` merge](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | — |
| [`color` → `sy-color*` classes](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`->color(ItemComponent::class, $color)`](../../../vendor/filament/support/src/View/Components/DropdownComponent/ItemComponent.php) | done | — | [diverges](#the-colour-slots-ride-on-the-row-the-label-reads-them) |
| [Leading icon](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`generate_icon_html($icon, $iconAlias, …)`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | — |
| [Label span](../../../packages/support/templates/components/DropdownItem/index.html.twig) | [`fi-dropdown-list-item-label`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | done | — | — |
| `iconColor` | [`->color(IconComponent::class, $iconColor)`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#there-is-no-iconcolor-prop-so-the-icon-inherits-the-rows-colour) |
| `image` | [`fi-dropdown-list-item-image`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#no-consumer-passes-an-image) |
| `badge` / `badgeColor` / `badgeTooltip` | [badge block + `BadgeComponent`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#the-badge-has-no-consumer-until-action-composes-hasbadge) |
| Deferred Alpine badge (`alpineDeferredBadgeData`/`Loading`) | [`x-if` deferred-badge block](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | blocked | the `badge` row | [detail](#the-deferred-badge-is-an-alpine-expression-contract) |
| [`sy-selected` selected state](../../../packages/support/assets/src/styles/components/dropdown-item.css) | [`&.fi-selected` rules](../../../vendor/filament/support/resources/css/components/dropdown/list/item.css) | done | — | [semantics](#the-selected-state-is-a-class-callers-add) |
| `tooltip` | [`x-tooltip` block + disabled-tooltip filter](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#a-tooltip-arrives-as-a-native-title-and-the-disabled-tooltip-handling-is-unported) |
| Key bindings | [`x-mousetrap` block](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#key-bindings-loading-indicators-and-the-form-wrapper-are-not-built) |
| Loading indicator | [`generate_loading_indicator_html(…)` + `wire:*`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#key-bindings-loading-indicators-and-the-form-wrapper-are-not-built) · [icon-button.md](icon-button.md#the-loading-indicator-is-unwritten-though-its-live-equivalent-ships) |
| SPA-mode href | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | blocked | panels SPA navigation | — |
| `tag: 'form'` POST-to-URL | [`($tag === 'form') ? '<form …>'`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | — | [detail](#key-bindings-loading-indicators-and-the-form-wrapper-are-not-built) |

## Details

### There is no `iconColor` prop, so the icon inherits the row's colour

Filament colors the leading icon independently of the row, defaulting `$iconColor ??= $color` and resolving it through a second color map (`ItemComponent\IconComponent`).
The port has no `iconColor` prop: the icon inherits whatever the row's own color classes cascade to it.
That is why `item.css`'s `& > .fi-icon` rules, the gray default and its `.fi-color` override, are not in [`dropdown-item.css`](../../../packages/support/assets/src/styles/components/dropdown-item.css): with no prop to drive them, they would style a class nothing emits.
They land with the prop.

### No consumer passes an image

Filament's `image` is an avatar-style leading `<div>` with a `background-image`, used for user-picker-shaped menus.
Nothing in the port passes an image to a dropdown item, and no `actions` API exposes one.

### The badge has no consumer until `Action` composes `HasBadge`

Filament renders a trailing `fi-badge` span, optionally tooltipped.
`Action` does not compose support's `HasBadge`, so the Grouped template has nothing to pass, and building the badge now would be speculative provisioning.

### The deferred badge is an Alpine-expression contract

The `alpineDeferredBadgeData`/`alpineDeferredBadgeLoading` pair renders a placeholder loading indicator and then swaps in an `x-if`-gated badge once the data arrives, an Alpine-expression contract between the item and whatever resolves the badge asynchronously.
It needs the plain badge first, and a Stimulus-shaped contract in place of the Alpine expressions.

### A tooltip arrives as a native `title`, and the disabled-tooltip handling is unported

The primitive has no `tooltip` prop: callers forward a native `title` attribute, the toolkit's tooltip shape, as `actions`' Grouped template does with `getTooltip`.
What is missing is the behaviour Filament couples to its `x-tooltip`: it withholds the native `disabled` attribute from a disabled-but-tooltipped control and filters `href`, `x-on:` and `wire:click` off it, so the tooltip stays hoverable.
The ported template sets `disabled` on any disabled button, and `item.css`'s `&:not([x-tooltip])` guard on `pointer-events-none` reduces to an unconditional `pointer-events-none`, so a disabled item's `title` never shows.

### Key bindings, loading indicators and the form wrapper are not built

- **Key bindings.**
  Filament binds a global `x-mousetrap` shortcut that clicks the item; the port has no shared key-binding controller for it to use.
- **Loading indicator.**
  `generate_loading_indicator_html()`, the `wire:loading` / `wire:target` plumbing, the `loadingIndicator` prop and the `livewire_loading_delay` config have no counterpart in the template, and with them goes Filament's reason to set `disabled` through `wire:loading.attr`.
  Their live equivalents ship, and what the primitive's half would translate to is written up for [`IconButton`](icon-button.md#the-loading-indicator-is-unwritten-though-its-live-equivalent-ships).
- **`tag: 'form'`.**
  Filament wraps the item in a `<form method="post">` and renders the row as that form's submit button, which is how a grouped action POSTs to its URL rather than navigating to it.
  No caller can ask for it: the port has no `shouldPostToUrl()`, so `tag` is only ever `'a'` or `'button'`, and it lands with that `Action` concern.

## Semantics

### `type` is passed in, not derived from the tag

[`item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) has no `type` prop and derives the attribute from the tag, `match ($tag) { 'button' => 'button', 'form' => 'submit', default => null }`, so reading the Blade view alone says the port should derive it too.
The generator that `Action::toGroupedHtml()` calls, [`CanGenerateDropdownItemHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateDropdownItemHtml.php), takes `?string $type = 'button'` and emits the passed value for a `<button>` tag, and `toGroupedHtml()` passes `type: $this->canSubmitForm() ? 'submit' : 'button'`.
The passed value has to win: a form-submitting grouped action is a `<button>` needing `type="submit"`, which the tag-derived form cannot express.

### The selected state is a class callers add

Nothing in this template emits `sy-selected`, and no prop drives `fi-selected` in Filament's view either: callers apply the class.
Forms' select engine ([forms styles.md](../forms/styles.md#the-js-select-skin-is-filaments-on-the-ported-controls-container)) and schemas' tabs overflow dropdown add it, and `dropdown-item.css` carries Filament's three arms: the neutral background, the `sy-color` background, and the label's text swap.

## Divergences

### The item takes a `formId`; Filament's does not

Neither the Blade view nor `CanGenerateDropdownItemHtml` has a form-association prop, and `toGroupedHtml()` passes none: `getFormId()` reaches only the button and icon-button generators.
So a Filament grouped action can render `type="submit"` with nothing naming the form it submits, and works only while the item sits inside that form.
The port takes `formId` and emits `form`, matching the pair the other trigger primitives carry ([`Button`](button.md), [`IconButton`](icon-button.md)), and it is load-bearing: the grouped case of [`ActionViewVariantsRenderTest`](../../../packages/actions/tests/Integration/ActionViewVariantsRenderTest.php) pins `type="submit"` together with `form="my-form"`.
Filament's own route to a submitting item, the `tag: 'form'` wrapper, costs a `shouldPostToUrl()` port and solves a different problem: POSTing to a URL, not submitting a named form.

### The colour slots ride on the row, the label reads them

[`ItemComponent`](../../../packages/support/src/Colors/Components/DropdownComponent/ItemComponent.php) is Filament's map verbatim: four text slots, resting and hover, light and dark, each measured against a different surface (white, the palette's own shade 50, and gray-900 or the palette's 950 in dark mode).
It is the only shipped policy that resolves a `hover:` slot as well as its resting one, which is what the dropdown row needs: the row's hover background is the palette's shade 50, so the label has to be readable against two surfaces rather than one.

The structural note is where the emitted classes land.
`colorAttributes(ItemComponent::class, color)` is spread onto the row (`.sy-dropdown-item`), because that is what carries the `.sy-color` marker the hover-background rules are gated on, but the slot variables it sets are read one level in, by `.sy-dropdown-item-label`.
That works because custom properties inherit, and it is deliberate: putting the colour on the label instead would leave the row's `hover:bg-color-50` with no palette.
A future variant that colours the row and the label differently would have to split them.
The row also carries the base text colour, `text-gray-700 dark:text-gray-200`, and `text-start`, which Filament puts on the label, and `HasDefaultGrayColor` makes a gray item emit no colour classes, so an uncoloured item renders in that base colour.
