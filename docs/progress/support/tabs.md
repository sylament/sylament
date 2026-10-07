---
description: The tab chrome, the `<twig:SylamentSupport:Tabs>` nav shell and the `<twig:SylamentSupport:TabsItem>` trigger, with their shared stylesheet.
ports:
  - vendor/filament/support/resources/views/components/tabs/index.blade.php
  - vendor/filament/support/resources/views/components/tabs/item.blade.php
  - vendor/filament/support/resources/css/components/tabs.css
owns:
  - packages/support/assets/src/styles/components/tabs.css
  - packages/support/templates/components/Tabs/
  - packages/support/templates/components/TabsItem/
---
# Tabs (support)

The tab chrome: two anonymous Twig components, [`Tabs/index.html.twig`](../../../packages/support/templates/components/Tabs/index.html.twig) (`<twig:SylamentSupport:Tabs>`, the `<nav>` shell) and [`TabsItem/index.html.twig`](../../../packages/support/templates/components/TabsItem/index.html.twig) (`<twig:SylamentSupport:TabsItem>`, one tab trigger), plus their shared stylesheet [`tabs.css`](../../../packages/support/assets/src/styles/components/tabs.css).
They port Filament's `<x-filament::tabs>` / `<x-filament::tabs.item>`.

The chrome is *only* chrome: it carries no active-tab state, no switching and no `role`.
Whoever renders it says what the elements mean — schemas' [`Tabs`](../schemas/tabs.md) partial passes `role="tablist"`/`role="tab"` and the Stimulus wiring, and a plain link-tab nav (Filament's other consumer) passes `tag="a"` and nothing else.
That split is Filament's, and it is why `role` is the caller's attribute rather than a template literal.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Root `<nav>` + `sy-contained` / `sy-vertical`](../../../packages/support/templates/components/Tabs/index.html.twig) | [`tabs/index.blade.php`](../../../vendor/filament/support/resources/views/components/tabs/index.blade.php) | done | — | — |
| [`aria-label` with a fallback](../../../packages/support/templates/components/Tabs/index.html.twig) | [`__('filament::components/tabs.label')`](../../../vendor/filament/support/resources/views/components/tabs/index.blade.php) | done | — | [diverges](#the-navigation-label-fallback-is-a-literal-not-a-translation-key) |
| [Item tag arms — `button` `type`, `a` `href`/`target`](../../../packages/support/templates/components/TabsItem/index.html.twig) | [the `@if ($tag === 'button')` pair](../../../vendor/filament/support/resources/views/components/tabs/item.blade.php) | done | — | [diverges](#the-anchor-arm-emits-href-and-target-directly-instead-of-generate_href_html) |
| [`active` → `sy-active` + `aria-current`](../../../packages/support/templates/components/TabsItem/index.html.twig) | [the `aria-current` merge + `fi-active`](../../../vendor/filament/support/resources/views/components/tabs/item.blade.php) | done | — | — |
| [Leading / trailing icon + `iconAlias`](../../../packages/support/templates/components/TabsItem/index.html.twig) | [the two `generate_icon_html()` calls](../../../vendor/filament/support/resources/views/components/tabs/item.blade.php) | done | — | [diverges](#the-item-takes-an-iconalias-prop-and-drops-the-unused-iconcolor-one) |
| [Scalar `badge` → the Badge component](../../../packages/support/templates/components/TabsItem/index.html.twig) | [the `<x-filament::badge>` arm](../../../vendor/filament/support/resources/views/components/tabs/item.blade.php) | done | — | [diverges](#the-badge-defaults-to-primary-and-its-tooltip-renders-as-a-native-title) |
| [`badge` block → raw badge markup](../../../packages/support/templates/components/TabsItem/index.html.twig) | [the `$badge instanceof ComponentSlot` arm](../../../vendor/filament/support/resources/views/components/tabs/item.blade.php) | done | — | [semantics](#the-blade-slot-test-is-two-props-here-badge-and-the-badge-block) |
| Alpine props (`alpineActive`, `alpineDeferredBadgeData`, `alpineDeferredBadgeLoading`) | [the three `x-bind`/`x-if` arms](../../../vendor/filament/support/resources/views/components/tabs/item.blade.php) | skipped | — | [diverges](#the-alpine-props-have-no-twin-the-deferred-badge-arrives-through-the-badge-block) |
| `spaMode` | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/tabs/item.blade.php) | skipped | — | [diverges](#the-anchor-arm-emits-href-and-target-directly-instead-of-generate_href_html) |
| [`tabs.css`](../../../packages/support/assets/src/styles/components/tabs.css) | [`components/tabs.css`](../../../vendor/filament/support/resources/css/components/tabs.css) | done | — | [diverges](#the-inactive-label-rule-is-written-as-notsy-active-vendors-selector-is-dead) |

## Semantics

### The Blade slot test is two props here: `badge` and the `badge` block

Blade's `$badge` is one prop holding either a scalar or a `ComponentSlot`, and the view branches on `$badge instanceof ComponentSlot` to render pre-built badge markup raw instead of wrapping the value in `<x-filament::badge>`.
Twig has no equivalent value, because a `<twig:block>` is captured, not passed, so the port splits it in two: the scalar `badge` prop keeps the wrapping arm, and `badgeHtml = markup(block('badge') ?? null)` keeps the raw arm.
The block wins when both are present, matching Blade's branch order.
`markup()` returns `null` for a blank capture, so a block that renders only whitespace (schemas' Tabs partial emits the badge block unconditionally and fills it only for a deferred tab) falls through to the scalar arm instead of swallowing it.

## Divergences

### The navigation label fallback is a literal, not a translation key

Filament falls back to `__('filament::components/tabs.label')`; ours falls back to the literal `'Tabs'`, the same call [breadcrumbs.md](breadcrumbs.md#the-navigation-label-is-a-literal-not-a-translation-key) makes.
The port has no translation layer ([i18n](../../internals/i18n.md)), so every user-facing literal is English at the point of use and moves in one pass when one ships.

### The anchor arm emits `href` and `target` directly instead of `generate_href_html()`

Filament routes the anchor's attributes through `generate_href_html($href, $target === '_blank', $spaMode)`, which also emits `wire:navigate` for Livewire's SPA mode.
No SPA-navigation layer exists in the port, so the item emits `href` and `target="_blank"` itself and `spaMode` has nothing to be.
For a blank url `generate_href_html()` emits nothing at all; ours drops `href` too (`tag == 'a' and href ? href : false`) but still emits `target="_blank"` when the caller asks for it, an inert attribute on an anchor with no `href`.

### The item takes an `iconAlias` prop and drops the unused `iconColor` one

Filament's item declares an `iconColor` prop and never reads it, rendering the icon with a bare `generate_icon_html($icon)`, so porting it would ship a lever that does nothing.
It is dropped.
In its place the port adds `iconAlias`, forwarded to `generateIconHtml(icon, iconAlias)`, because the icon-override seam is an alias lookup here rather than Filament's facade call: schemas' Tabs partial needs it for the dropdown trigger's chevron (`SchemaIconAlias::COMPONENTS_TABS_DROPDOWN_TRIGGER_BUTTON`), which Filament resolves inside its `toEmbeddedHtml()` before the view ever sees the icon.

### The badge defaults to `primary` and its tooltip renders as a native `title`

Filament's item passes `$badgeColor` straight through, so a null colour reaches `<x-filament::badge>` and its own `primary` default applies; ours passes `badgeColor ?? 'primary'` for the same result on a component whose default colour is also `primary`, written explicitly because schemas' Tabs partial and `getDeferredTabBadges()` apply the same `?? 'primary'` on the PHP side (Filament's `generateTabBadgeHtml()` does too), and one spelling for the three is worth more than the indirection.
`badgeTooltip` renders as a native `title` attribute rather than Filament's tippy binding, the same shape [icon.md](../schemas/icon.md#the-tooltip-renders-as-a-native-title) uses.

### The Alpine props have no twin; the deferred badge arrives through the `badge` block

`alpineActive`, `alpineDeferredBadgeData` and `alpineDeferredBadgeLoading` are Alpine expression strings the item interpolates into `x-bind:class`, `x-show` and two `x-if` templates.
There is no Alpine in this port: the active class is rendered server-side and then owned by the `sylament--schemas--tabs` Stimulus controller, and a deferred badge is a placeholder `<span>` handed in through the `badge` block, which the controller fills from [`Tabs::getDeferredTabBadges()`](../schemas/tabs.md).
The three props therefore have nothing to carry.
Skipped permanently.

### The inactive-label rule is written as `&:not(.sy-active)`; vendor's selector is dead

Vendor's `tabs.css:41` opens a `:not(.fi-active)` block *without* the `&`, which CSS nesting reads as a descendant selector, `.fi-tabs-item :not(.fi-active)`, so its hover/focus label colours apply to every non-active descendant of *any* item rather than to a non-active item.
The port writes `&:not(.sy-active)`, the rule as intended.
Everything else in the stylesheet is vendor's, prefix-rewritten; the active accent stays the literal `text-primary-700 dark:text-primary-400` because Filament defines no colour policy for tabs, so [the colour-policy layer](../../internals/styling.md#why-does-a-policy-pick-the-shade-instead-of-the-stylesheet) does not bind here.
