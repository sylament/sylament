---
description: The schemas content component that renders a piece of styled text in a span, with a size, a weight, a font family and a colour.
ports:
  - vendor/filament/schemas/src/Components/Text.php
  - vendor/filament/schemas/src/View/Components/TextComponent.php
  - vendor/filament/schemas/src/JsContent.php
owns:
  - packages/schemas/src/Components/Text.php
  - packages/schemas/src/Colors/Components/TextComponent.php
  - packages/schemas/templates/partials/Text/
---
# Text (schemas)

[`Sylament\Schemas\Components\Text`](../../../packages/schemas/src/Components/Text.php) — the inline text content component, whose value is a piece of styled text rendered in a `<span>`.
It ports Filament's schema [`Text`](../../../vendor/filament/schemas/src/Components/Text.php) through the [`Text/index.html.twig`](../../../packages/schemas/templates/partials/Text/index.html.twig) template.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(content)`](../../../packages/schemas/src/Components/Text.php) | [`Text::make()`](../../../vendor/filament/schemas/src/Components/Text.php) | done | — | — |
| [`content()` / `getContent()`](../../../packages/schemas/src/Components/Text.php) | [`Text::content()`](../../../vendor/filament/schemas/src/Components/Text.php) | done | — | — |
| [`size()` / `getSize()` normalization](../../../packages/schemas/src/Components/Text.php) | [`Text::size()`](../../../vendor/filament/schemas/src/Components/Text.php) | done | — | — |
| [`getSizeClass()` (Sylament template helper)](../../../packages/schemas/src/Components/Text.php) | — | done | — | — |
| [`HasColor` color/getColor/hasColor + default gray](../../../packages/support/src/Components/Concerns/HasColor.php) | [`HasColor`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | [has-color.md](../support/has-color.md) · [diverges](#the-default-gray-color-lives-in-the-color-policy-not-setup) |
| [`HasWeight` weight/getWeight/getWeightClass](../../../packages/support/src/Components/Concerns/HasWeight.php) | [`HasWeight`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | done | — | [has-weight.md](../support/has-weight.md) |
| [`HasFontFamily` fontFamily/getFontFamily/getFontFamilyClass](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | [`HasFontFamily`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | done | — | [has-font-family.md](../support/has-font-family.md) |
| [Twig span render: `sy`/`sy-sc-text` classes, escaping, raw `Markup`, extra attrs](../../../packages/schemas/templates/partials/Text/index.html.twig) | [`Text::toEmbeddedHtml()`](../../../vendor/filament/schemas/src/Components/Text.php) | done | — | — |
| [`Colors\Components\TextComponent`](../../../packages/schemas/src/Colors/Components/TextComponent.php) — the text's colour policy | [`View/Components/TextComponent.php`](../../../vendor/filament/schemas/src/View/Components/TextComponent.php) | done | — | [diverges](#the-default-gray-color-lives-in-the-color-policy-not-setup) |
| `badge()` / `isBadge()` badge mode | [`Text::badge()`](../../../vendor/filament/schemas/src/Components/Text.php) | todo | — | [detail](#badge-mode) |
| `CanBeCopied` (copyable + copy interaction) | [`CanBeCopied`](../../../vendor/filament/support/src/Concerns/CanBeCopied.php) | todo | — | [detail](#copyable) |
| `HasIcon` (`icon()`/`getIcon()`) | [`HasIcon`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | todo | — | [detail](#icon-support) |
| `HasIconPosition` (`IconPosition` Before/After) | [`HasIconPosition`](../../../vendor/filament/support/src/Concerns/HasIconPosition.php) | todo | — | [detail](#icon-support) |
| `HasIconSize` (`IconSize`) | [`HasIconSize`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | todo | — | [detail](#icon-support) |
| `HasTooltip` (`tooltip()`/`getTooltip()`) | [`HasTooltip`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | todo | — | [detail](#tooltip) |
| `js()` / `JsContent` | [`JsContent`](../../../vendor/filament/schemas/src/JsContent.php) | todo | — | [detail](#js-and-jscontent) |
| `HasEmbeddedView` contract / `toEmbeddedHtml()` | [`HasEmbeddedView`](../../../vendor/filament/support/src/Components/Contracts/HasEmbeddedView.php) | skipped | — | [diverges](../support/view-component.md#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |
| `publishedViewOverrideCheckPath` (Blade view-override detection) | [`Text`](../../../vendor/filament/schemas/src/Components/Text.php) | skipped | — | [diverges](../support/view-component.md#render-returns-a-string-the-htmlable--embedded-view-machinery-is-dropped) |

## Details

### Badge mode

Filament's `badge()` / `isBadge()` switch the root from a `<span>` to a badge (a `<button>` when copyable), coloured through `BadgeComponent` with a `primary` default, wrapping the label in badge containers and defaulting the size to `Size::Medium`.
The port's `Text` has no badge flag, and its template has no badge branch; support's [`Badge`](../support/badge.md) component is the markup such a branch would render through.

### Copyable

Support's [`CanBeCopied`](../support/can-be-copied.md) exists, and `Text` does not compose it.
Filament's template makes a copyable text clickable and keyboard-operable, writing the copyable state to the clipboard and confirming with a timed tooltip; the port's template has no copy interaction.

### Icon support

Support's [`HasIcon`](../support/has-icon.md), [`HasIconPosition`](../../../packages/support/src/Components/Concerns/HasIconPosition.php) and [`HasIconSize`](../support/has-icon-size.md) exist, and `Text` composes none of them.
Filament renders the icon only in badge mode, before or after the label, so these rows follow [badge mode](#badge-mode).

### Tooltip

Support's [`HasTooltip`](../support/has-tooltip.md) exists, and `Text` does not compose it.
Filament wires the tooltip through Alpine's `x-tooltip`, with `allowHTML` derived from `Htmlable`-ness, where the toolkit renders a tooltip as a native `title` ([column.md](../tables/column.md#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text)).

### `js()` and JsContent

Filament's `js()` wraps the content in [`JsContent`](../../../vendor/filament/schemas/src/JsContent.php) so it renders as an evaluated JS expression rather than literal text.
Neither `Text::js()` nor `JsContent` exists in the port.

## Divergences

### The default gray color lives in the color policy, not `setUp()`

Filament's `Text::setUp()` calls `defaultColor('gray')`, so `getColor()` returns `'gray'` when no color is set.
Sylament's `Text` has no `setUp()` override: `getColor()` returns `null` by default, and [`Text/index.html.twig`](../../../packages/schemas/templates/partials/Text/index.html.twig) computes nothing — it hands `getColor()` straight to `colorAttributes()`, which returns no classes for a blank color.
Gray asked for **by name** is suppressed a step further in, by [`TextComponent`](../../../packages/schemas/src/Colors/Components/TextComponent.php) implementing `HasDefaultGrayColor`: `ColorManager::getComponentClasses()` returns an empty list for that pair, so both spellings fall through to the base `text-gray-600 dark:text-gray-400` that [`text.css`](../../../packages/support/assets/src/styles/components/text.css) gives `.sy-sc-text`.
Rendered output matches Filament — gray is the visual default — but the `getColor()` return value diverges (`null` vs `'gray'`).
Reverses if a consumer needs `getColor()` to report the effective default programmatically.
