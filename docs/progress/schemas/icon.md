---
description: The schemas content component that renders a single icon with a size, a colour and a tooltip.
ports:
  - vendor/filament/schemas/src/Components/Icon.php
  - vendor/filament/schemas/src/View/Components/IconComponent.php
owns:
  - packages/schemas/src/Components/Icon.php
  - packages/schemas/src/Colors/Components/IconComponent.php
---
# Icon (schemas)

[`Sylament\Schemas\Components\Icon`](../../../packages/schemas/src/Components/Icon.php) is the schema content component that renders a single icon.
It ports Filament's [`Schemas\Components\Icon`](../../../vendor/filament/schemas/src/Components/Icon.php): an `icon()` value (name / `BackedEnum` / `Markup` / closure), a `size()` that normalizes a known string to an [`IconSize`](../../../packages/support/src/Enums/IconSize.php) case, plus color, tooltip, and visibility folded in from support concerns.
Its template is [`partials/Icon/index.html.twig`](../../../packages/schemas/templates/partials/Icon/index.html.twig).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`icon()` / `getIcon()`](../../../packages/schemas/src/Components/Icon.php) | [`Icon::getIcon()`](../../../vendor/filament/schemas/src/Components/Icon.php) | done | — | — |
| [`size()` / `getSize()` normalization](../../../packages/schemas/src/Components/Icon.php) | [`Icon::getSize()`](../../../vendor/filament/schemas/src/Components/Icon.php) | done | — | [diverges](#getsize-drops-filaments-base--null-shortcut) |
| [`color()` / `getColor()` (HasColor)](../../../packages/schemas/src/Components/Icon.php) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | [has-color.md](../support/has-color.md) |
| [`tooltip()` / `getTooltip()` (HasTooltip)](../../../packages/schemas/src/Components/Icon.php) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | [has-tooltip.md](../support/has-tooltip.md) · [diverges](#the-tooltip-renders-as-a-native-title) |
| [`isVisible()` / `hidden()` (base `CanBeHidden`)](../../../packages/schemas/src/Components/Icon.php) | [`Concerns/CanBeHidden.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php) | done | — | [can-be-hidden.md](can-be-hidden.md) |
| [Twig render (template + `generateIconHtml`)](../../../packages/schemas/templates/partials/Icon/index.html.twig) | [`Icon::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Icon.php) | done | — | [diverges](#the-default-icon-color-is-gray-with-no-color-class-not-filaments-primary) |
| [`Colors\Components\IconComponent`](../../../packages/schemas/src/Colors/Components/IconComponent.php) — the icon's colour policy | [`View/Components/IconComponent.php`](../../../vendor/filament/schemas/src/View/Components/IconComponent.php) | done | — | [diverges](#the-default-icon-color-is-gray-with-no-color-class-not-filaments-primary) |
| Accessibility: sr-only text alternative + `aria-label` reconciliation | [`Icon::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Icon.php) | todo | — | [detail](#the-accessible-text-alternative-is-not-rendered) |
| `extraAttributes` forwarding into the icon markup | [`Icon::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Icon.php) | todo | — | [detail](#extraattributes-are-not-forwarded-into-the-icon) |

## Details

### The accessible text alternative is not rendered

Filament's `toEmbeddedHtml()` gives the decorative (`aria-hidden`) SVG a visually-hidden text alternative in a `fi-sr-only` span: an explicit user `aria-label` pulled off the SVG, or else the tooltip text, skipped when the user set `aria-labelledby`.
The port's template emits neither the span nor the reconciliation, so the `title` attribute is the only accessible-name source.
The row waits on a decision: accept the `title` attribute as the accessible-name mechanism and record the dropped span as a divergence, or port the text alternative into [`Icon/index.html.twig`](../../../packages/schemas/templates/partials/Icon/index.html.twig).

### `extraAttributes` are not forwarded into the icon

Filament merges `getExtraAttributes()` onto the icon's attribute bag before `generate_icon_html()`.
The port's template emits only the class, the colour style and an optional `title`, so per-instance `extraAttributes()` do not reach the icon markup.
Forwarding them meets the `aria-label` handling above, which Filament reads from the same bag.

## Divergences

### The default icon color is gray with no color class, not Filament's primary

Filament's `toEmbeddedHtml()` passes `color(IconComponent::class, $this->getColor() ?? 'primary')`, so an uncolored icon defaults to the primary palette.
Sylament's `Icon` leaves `getColor()` at `null` and its policy [`IconComponent`](../../../packages/schemas/src/Colors/Components/IconComponent.php) implements `HasDefaultGrayColor`, so both an unset color and an explicit `'gray'` emit no color class and the icon takes the base `text-gray-400 dark:text-gray-500` that [`icon.css`](../../../packages/schemas/assets/src/styles/components/icon.css) gives `.sy-sc-icon` — Filament carries the same base rule, but reaches it only when a consumer asks for gray.
Every other color goes through the policy like any other component's: a palette name emits `sy-color sy-color-<name>`, an array palette emits `sy-color` plus the inline `--sy-color-*` variables, and neither carries a shade class, because the policy declares no slots and the stylesheet fixes the icon at 500/400.
Reverses if the toolkit adopts Filament's primary-default convention for bare icons.

### `getSize()` drops Filament's `'base'` → `null` shortcut

Filament's `getSize()` returns `null` when the size is the literal `'base'`.
Sylament's `getSize()` has no such branch: `'base'` is neither an `IconSize` case nor blank, so it is returned verbatim as a string.
The render is the same, because [`generateIconHtml()`](../../../packages/support/src/Twig/Extension/IconExtension.php) turns an unknown string into `null` with `IconSize::tryFrom()` and both sides then fall back to `IconSize::Medium`.
The getter contract differs, since neither side's `IconSize` has a `base` case.
Restoring the branch reverses it.

### The tooltip renders as a native `title`

Filament gives the icon an Alpine `x-tooltip` attribute, rendering a `Htmlable` tooltip as markup.
The port's template sets the tooltip as the icon's `title` attribute, because the toolkit ships no tooltip primitive, the call [column.md](../tables/column.md#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text) records for table columns.
