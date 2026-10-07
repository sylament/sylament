---
description: The `<twig:SylamentSupport:IconButton>` anonymous component, Filament's icon-only button with its size-derived icon, accessible name and colour map.
ports:
  - vendor/filament/support/resources/views/components/icon-button.blade.php
  - vendor/filament/support/src/View/Components/IconButtonComponent.php
  - vendor/filament/support/resources/css/components/icon-button.css
owns:
  - packages/support/assets/src/styles/components/icon-button.css
  - packages/support/templates/components/IconButton/
---
# Icon button (support)

[`IconButton/index.html.twig`](../../../packages/support/templates/components/IconButton/index.html.twig) ports Filament's `<x-filament::icon-button>`: a `<button>` or `<a>` whose sole content is an icon, deriving the icon size from the button size and exposing its `label` only as the accessible name.
Filament splits it between the Blade view and the `IconButtonComponent` colour map; the port's map is [`IconButtonComponent`](../../../packages/support/src/Colors/Components/IconButtonComponent.php).
The `## Details` entries below also serve the same rows on [`Badge`](badge.md) and [`Link`](link.md), whose templates share each gap.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Tag + size classes](../../../packages/support/templates/components/IconButton/index.html.twig) | [`icon-button.blade.php` `->class([…])`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | — |
| [Icon rendering + derived size](../../../packages/support/templates/components/IconButton/index.html.twig) | [`generate_icon_html(…)`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | — |
| [Label → `aria-label` + `title`](../../../packages/support/templates/components/IconButton/index.html.twig) | [`aria-label`/`title` merge](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | [diverges](#the-aria-label-is-double-encoded-where-filament-suppresses-it) |
| [`disabled` / `aria-disabled`](../../../packages/support/templates/components/IconButton/index.html.twig) | [`disabled`/`aria-disabled` merge](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | [diverges](#the-disabled-attribute-is-emitted-only-on-button) |
| [Link `href` / `target`](../../../packages/support/templates/components/IconButton/index.html.twig) | [`generate_href_html(…)`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | — |
| [`type` / `form` association](../../../packages/support/templates/components/IconButton/index.html.twig) | [`type`/`form` merge](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | done | — | — |
| [Color (gray-default + semantic + resolved shades)](../../../packages/support/templates/components/IconButton/index.html.twig) | [`->color(IconButtonComponent::class, $color)`](../../../vendor/filament/support/src/View/Components/IconButtonComponent.php) | done | — | [color-policies.md](color-policies.md#the-icon-button-map-steps-to-the-neighbouring-shade-not-by-100) |
| [`icon-button.css`](../../../packages/support/assets/src/styles/components/icon-button.css) | [`components/icon-button.css`](../../../vendor/filament/support/resources/css/components/icon-button.css) | done | — | — |
| `:has(+ .fi-btn.fi-labeled-from-*)` arms | [`components/icon-button.css`](../../../vendor/filament/support/resources/css/components/icon-button.css) | todo | — | [button.md](button.md#details) |
| Loading indicator | [`generate_loading_indicator_html(…)` + `wire:*`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | todo | — | [detail](#the-loading-indicator-is-unwritten-though-its-live-equivalent-ships) |
| Badge (+ the `.fi-icon-btn-badge-ctn` rule) | [badge block + `BadgeComponent`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | todo | — | [detail](#the-badge-waits-on-a-caller-not-on-the-badge-primitive) |
| Key bindings | [`x-mousetrap` block](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | blocked | Stimulus mousetrap controller | [detail](#key-bindings-wait-on-a-shared-key-binding-controller) |
| Tooltip | [`x-tooltip` block + disabled-tooltip filter](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | blocked | Stimulus tooltip controller | [detail](#the-tooltip-waits-on-a-stimulus-tooltip-controller-and-callers-pass-a-native-title-instead) |
| SPA-mode href | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/icon-button.blade.php) | blocked | panels SPA navigation | [detail](#spa-mode-href-waits-on-panels-spa-navigation) |

## Details

### The loading indicator is unwritten though its Live equivalent ships

Filament's `wire:target` / `wire:loading` wiring, `generate_loading_indicator_html()`, the `loadingIndicator` / `form` / `spaMode` props and the `livewire_loading_delay` config have no counterpart in the template.
What they would translate to ships: UX LiveComponent's `data-loading` directive, which takes an `action(name)` modifier as the `wire:target` equivalent, and support's [`LoadingIndicator`](../../../packages/support/templates/components/LoadingIndicator/index.html.twig) component, both already used by schemas' [`Tabs` partial](../../../packages/schemas/templates/partials/Tabs/index.html.twig) and tables' [`Table` partial](../../../packages/tables/templates/partials/Table/index.html.twig).
What is missing is the primitive's half: the props, the `data-loading` attributes on the icon and the indicator, and the `disabled` Filament sets through `wire:loading.attr`.
Until then the `form` prop, which Filament also uses as the indicator's submit target, collapses to `formId` alone.

### The badge waits on a caller, not on the Badge primitive

The `badge` / `badgeColor` / `badgeSize` props render a [`Badge`](badge.md) inside `fi-icon-btn-badge-ctn`, which `icon-button.css` positions over the button's corner; neither the props nor the rule is ported.
Nothing structural blocks them: the slot is a wrapper around a `<twig:SylamentSupport:Badge>` call.
Filament feeds it from `Action`'s `HasBadge`, and support ships [`HasBadge`](../../../packages/support/src/Components/Concerns/HasBadge.php), but the port's [`Action`](../../../packages/actions/src/Action.php) does not compose it ([action.md](../actions/action.md#surface)), so no caller can reach the slot.

### Key bindings wait on a shared key-binding controller

Filament's `x-mousetrap.global.…` binding is an Alpine plugin that clicks the control on a key combination.
The port has no shared Stimulus controller for it; panels' [`global_search_controller.ts`](../../../packages/panels/assets/src/controllers/global_search_controller.ts) parses its own `keyBindings` value for the search field alone.

### The tooltip waits on a Stimulus tooltip controller, and callers pass a native `title` instead

The `x-tooltip` block and its coupled disabled-with-tooltip behaviour (an anchor gains `tabindex="0"`, and `href`, `x-on:` and `wire:click` are filtered off a disabled control that has a tooltip) need a tooltip controller the port does not have.
Callers render a tooltip as a native `title` attribute instead: actions' trigger templates (for example [`Action/IconButton.html.twig`](../../../packages/actions/templates/partials/Action/IconButton.html.twig)) spread `{title: component.getTooltip}` onto the primitive.
Because the primitive takes no `tooltip`, Filament's `$disabled && blank($tooltip)` reduces to `$disabled`, which is what the template assumes, and the stylesheets apply `pointer-events-none` to every disabled control where Filament's exempt one carrying `[x-tooltip]`.

### SPA-mode href waits on panels SPA navigation

The `spaMode` argument of `generate_href_html()` emits `wire:navigate` for Livewire's single-page navigation, and panels has no SPA navigation ([global-search.md](../panels/global-search.md#there-is-no-spa-navigation-for-the-field-to-survive)).
The template emits `href` and `target="_blank"` itself, the half of the helper that does not depend on it.

## Divergences

### The `disabled` attribute is emitted only on `<button>`

Filament merges `'disabled' => $disabled && blank($tooltip)` onto the root regardless of tag, so a disabled `<a>` with no tooltip receives a `disabled` attribute, which is not valid on an anchor.
Sylament guards the attribute with `disabled and tag == 'button'`, so a disabled link carries only `aria-disabled` and `sy-disabled`.
The `aria-disabled` / `sy-disabled` signalling is identical across tags; only the native attribute is withheld from non-buttons.
The "disables a link without the invalid disabled attribute" case in [`IconButtonRenderTest`](../../../packages/schemas/tests/Integration/Components/IconButtonRenderTest.php) pins it.

### The `aria-label` is double-encoded, where Filament suppresses it

Filament escapes the accessible name with `e(trim(strip_tags($label)), doubleEncode: false)` and merges it with `escape: false`, so a label already carrying an escaped entity stays singly encoded.
Sylament runs `label|striptags|trim` through `attributes.defaults(...)`, whose Twig escaper has no double-encode toggle, so the same pre-escaped input renders doubly encoded (`&amp;` → `&amp;amp;`).
Unescaped labels, the normal case, render identically; only labels that already contain HTML entities differ.
