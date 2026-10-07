---
description: The `<twig:SylamentSupport:Link>` anonymous component, Filament's inline text link or button with its icon, size, weight and colour map.
ports:
  - vendor/filament/support/resources/views/components/link.blade.php
  - vendor/filament/support/src/View/Components/LinkComponent.php
  - vendor/filament/support/resources/css/components/link.css
owns:
  - packages/support/assets/src/styles/components/link.css
  - packages/support/templates/components/Link/
---
# Link (support)

[`Link/index.html.twig`](../../../packages/support/templates/components/Link/index.html.twig) ports Filament's `<x-filament::link>`: an `<a>` or `<button>` styled as inline text, a label span flanked by an optional icon, with a size, a font weight and a colour.
Filament splits it between the Blade view and the `LinkComponent` colour map; the port's map is [`LinkComponent`](../../../packages/support/src/Colors/Components/LinkComponent.php).
The rows it shares with [`IconButton`](icon-button.md) link that file's entries.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Tag + `href`/`target`](../../../packages/support/templates/components/Link/index.html.twig) | [`<{{ $tag }}>` + `generate_href_html(…)`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| [`type` / `form` association](../../../packages/support/templates/components/Link/index.html.twig) | [`'type'`/`'form'` merge](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | [semantics](#form-is-emitted-regardless-of-tag-unlike-on-badge) |
| [`disabled` + `aria-disabled`](../../../packages/support/templates/components/Link/index.html.twig) | [`'disabled'`/`'aria-disabled'` merge](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | [icon-button.md](icon-button.md#the-disabled-attribute-is-emitted-only-on-button) |
| [`size` → `sy-size-*` classes](../../../packages/support/templates/components/Link/index.html.twig) | [`->class(['fi-size-…'])`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| [`weight` → `sy-font-*` classes](../../../packages/support/templates/components/Link/index.html.twig) | [`->class(['fi-font-…'])`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| [`color` → `sy-color*` classes](../../../packages/support/templates/components/Link/index.html.twig) | [`->color(LinkComponent::class, $color)`](../../../vendor/filament/support/src/View/Components/LinkComponent.php) | done | — | [diverges](#the-gray-shades-sit-in-a-notsy-color-branch-not-on-the-base-rule) |
| [Icon + size derived from `size` + `iconPosition`](../../../packages/support/templates/components/Link/index.html.twig) | [`generate_icon_html(…)` + `$iconSize ??= match ($size)`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | — |
| [`labelSrOnly` → `aria-label`](../../../packages/support/templates/components/Link/index.html.twig) | [`'aria-label' => $labelSrOnly ? … : null`](../../../vendor/filament/support/resources/views/components/link.blade.php) | done | — | [icon-button.md](icon-button.md#the-aria-label-is-double-encoded-where-filament-suppresses-it) |
| [`link.css`](../../../packages/support/assets/src/styles/components/link.css) | [`components/link.css`](../../../vendor/filament/support/resources/css/components/link.css) | in progress | — | [detail](#the-focus-outline-never-paints-because-outline-solid-is-missing) · [diverges](#the-gray-shades-sit-in-a-notsy-color-branch-not-on-the-base-rule) |
| `badge` / `badgeColor` / `badgeSize` (+ the `.fi-link-badge-ctn` rule) | [`fi-link-badge-ctn` block](../../../vendor/filament/support/resources/views/components/link.blade.php) | todo | — | [icon-button.md](icon-button.md#the-badge-waits-on-a-caller-not-on-the-badge-primitive) |
| `tooltip` | [`x-tooltip` block + disabled-tooltip filter](../../../vendor/filament/support/resources/views/components/link.blade.php) | blocked | Stimulus tooltip controller | [icon-button.md](icon-button.md#the-tooltip-waits-on-a-stimulus-tooltip-controller-and-callers-pass-a-native-title-instead) |
| Key bindings | [`x-mousetrap.global.…`](../../../vendor/filament/support/resources/views/components/link.blade.php) | blocked | Stimulus mousetrap controller | [icon-button.md](icon-button.md#key-bindings-wait-on-a-shared-key-binding-controller) |
| Loading indicator | [`generate_loading_indicator_html(…)` + `wire:*`](../../../vendor/filament/support/resources/views/components/link.blade.php) | todo | — | [icon-button.md](icon-button.md#the-loading-indicator-is-unwritten-though-its-live-equivalent-ships) |
| SPA-mode href | [`generate_href_html($href, …, $spaMode)`](../../../vendor/filament/support/resources/views/components/link.blade.php) | blocked | panels SPA navigation | [icon-button.md](icon-button.md#spa-mode-href-waits-on-panels-spa-navigation) |
| `tag: 'form'` POST-to-URL | [`CanGenerateLinkHtml`](../../../vendor/filament/support/src/View/Concerns/CanGenerateLinkHtml.php) | skipped | — | [can-open-url.md](../actions/can-open-url.md#post-to-url-support-is-not-ported) |

## Details

### The focus outline never paints, because `outline-solid` is missing

The focus arm in [`link.css`](../../../packages/support/assets/src/styles/components/link.css) applies `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current` without Filament's `focus-visible:outline-solid`.
Tailwind 4's `outline-2` sets `outline-style: var(--tw-outline-style)`, and the base rule's `outline-none` pins `--tw-outline-style: none`, so the keyboard focus outline resolves to no outline at all; Filament's [`link.css`](../../../vendor/filament/support/resources/css/components/link.css) carries a comment above the same arm saying exactly that.
The rest of the stylesheet is Filament's, prefix-rewritten, except the colour branch below and the `.fi-link-badge-ctn` rule, which goes with the badge row.

## Semantics

### `form` is emitted regardless of tag, unlike on Badge

The template writes `form: formId ?: false` with no tag guard, so a `formId` passed to an `<a>`-tagged link renders a `form` attribute that does nothing.
Its sibling [`Badge`](badge.md) guards the same attribute with `tag == 'button'`.
The asymmetry is carried from Filament on both sides: `link.blade.php` merges `'form' => $formId` unconditionally while `badge.blade.php` merges `'form' => $tag === 'button' ? $formId : null`.
Normalising the two to agree would be the divergence, so the difference stays.

## Divergences

### The gray shades sit in a `:not(.sy-color)` branch, not on the base rule

The colour map is Filament's verbatim: [`LinkComponent`](../../../packages/support/src/Colors/Components/LinkComponent.php) resolves `text` against gray-50 and `dark:text` against gray-700, and the template hands `color` to `colorAttributes()`, which emits the marker, the swap class and the two shade classes, or the inline palette for an array colour.

The structural difference is where the *uncoloured* link's grays live.
Filament puts `text-gray-700 dark:text-gray-200` on the base `.fi-link` rule and lets the colour variables override them; `link.css` puts them in an explicit `&:not(.sy-color)` branch and the resolved slots in `&.sy-color`.
Both render the same thing, because `HasDefaultGrayColor` means a gray or empty colour emits no `sy-color` marker at all, so the two branches are exclusive by construction rather than by cascade order.

It is worth keeping rather than normalising: the branch states the rule the marker class already encodes, so a reader cannot mistake the grays for a default that a slot variable is expected to beat.
[`Badge`](badge.md) uses the same shape; [`IconButton`](icon-button.md) keeps Filament's, with its grays on the base rule.
