---
description: The shared concern that gives a component a tooltip, as a string or as `Twig\Markup`.
ports:
  - vendor/filament/support/src/Concerns/HasTooltip.php
owns:
  - packages/support/src/Components/Concerns/HasTooltip.php
---
# HasTooltip (support)

`Sylament\Support\Components\Concerns\HasTooltip` ports Filament's trait of the same name.
Tables' column tier has its own [`Columns\Concerns\HasTooltip`](../../../packages/tables/src/Columns/Concerns/HasTooltip.php), which [column.md](../tables/column.md) tracks.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`tooltip()`](../../../packages/support/src/Components/Concerns/HasTooltip.php) | [`tooltip()`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | [translations.md](../translations.md#idiom) · [semantics](#a-message-is-translated-by-the-getter-so-a-reader-still-gets-a-string-or-markup) |
| [`getTooltip()`](../../../packages/support/src/Components/Concerns/HasTooltip.php) | [`getTooltip()`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | — |
| [`hasTooltip()`](../../../packages/support/src/Components/Concerns/HasTooltip.php) | [`hasTooltip()`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | — |

## Semantics

### A message is translated by the getter, so a reader still gets a string or `Markup`

`tooltip()` accepts a `TranslatableInterface` beside a string, a `Markup` and a closure, and `getTooltip()` returns `$this->translateText($this->evaluate($this->tooltip))`, so a closure that answers a message is translated too.
`hasTooltip()` still tests the stored value against `null`, which a message is not.
The translator is the component's own, [injected](component.md#a-message-is-translated-through-an-injected-translator-which-a-new-built-component-lacks), and a plain string never reaches it.
The same holds for `badgeTooltip()`, `badge()` and `placeholder()`, with one rule for `badge()`: a number is cast to a string by `getBadge()` after any translation and never offered to the translator, since a count is not text.

## Divergences

### A tooltip renders as a native `title` attribute

Filament renders a tooltip through the `@ryangjchandler/alpine-tooltip` plugin, a Tippy popover, plus its own `Escape` handler in `tooltip.js`.
The port has no tooltip control: each template that renders a tooltip writes it into a native `title` attribute, and a `Markup` tooltip is flattened to text on the way ([column.md](../tables/column.md#a-tooltip-renders-as-a-native-title-and-markup-is-flattened-to-text)).
[`Link`](link.md), [`IconButton`](icon-button.md) and [`Badge`](badge.md) each carry a row blocked on the Stimulus tooltip controller that would replace the attribute, and [`DropdownItem`](dropdown-item.md#a-tooltip-arrives-as-a-native-title-and-the-disabled-tooltip-handling-is-unported) a row for the disabled-tooltip handling the attribute cannot give.
The PHP surface does not change when the controller lands, only the rendering.
