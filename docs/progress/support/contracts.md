---
description: The state contracts a user's enum implements to give its cases a colour, description, icon or label, and the ScalableIcon contract for size-aware icon sets.
ports:
  - vendor/filament/support/src/Contracts/HasColor.php
  - vendor/filament/support/src/Contracts/HasDescription.php
  - vendor/filament/support/src/Contracts/HasIcon.php
  - vendor/filament/support/src/Contracts/HasLabel.php
  - vendor/filament/support/src/Contracts/ScalableIcon.php
owns:
  - packages/support/src/Contracts/
---
# Contracts (support)

Each contract declares Filament's one method, with `Twig\Markup` where Filament's declares `Htmlable` ([translations](../translations.md#idiom)), and the two text contracts also admit a `TranslatableInterface`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`HasColor::getColor()`](../../../packages/support/src/Contracts/HasColor.php) | [`Contracts/HasColor.php`](../../../vendor/filament/support/src/Contracts/HasColor.php) | done | — | [semantics](#three-support-symbols-are-named-hascolor-and-this-one-is-the-state-contract) |
| [`HasDescription::getDescription()`](../../../packages/support/src/Contracts/HasDescription.php) | [`Contracts/HasDescription.php`](../../../vendor/filament/support/src/Contracts/HasDescription.php) | done | — | [semantics](#text-a-contract-answers-may-be-a-message-which-its-reader-translates) |
| [`HasIcon::getIcon()`](../../../packages/support/src/Contracts/HasIcon.php) | [`Contracts/HasIcon.php`](../../../vendor/filament/support/src/Contracts/HasIcon.php) | done | — | — |
| [`HasLabel::getLabel()`](../../../packages/support/src/Contracts/HasLabel.php) | [`Contracts/HasLabel.php`](../../../vendor/filament/support/src/Contracts/HasLabel.php) | done | — | [semantics](#text-a-contract-answers-may-be-a-message-which-its-reader-translates) |
| [`ScalableIcon::getIconForSize()`](../../../packages/support/src/Contracts/ScalableIcon.php) | [`Contracts/ScalableIcon.php`](../../../vendor/filament/support/src/Contracts/ScalableIcon.php) | done | — | — |

## Semantics

### Three support symbols are named `HasColor`, and this one is the state contract

[`Contracts\HasColor`](../../../packages/support/src/Contracts/HasColor.php) is implemented by a user's enum, so that a field or a column can colour an option or a cell from its value.
Forms' [`HasColors`](../../../packages/forms/src/Components/Concerns/HasColors.php) and tables' [`Columns\Concerns\HasColor`](../../../packages/tables/src/Columns/Concerns/HasColor.php) read it.
[`Components\Concerns\HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) is the trait that gives a component `color()`, `defaultColor()`, `getColor()` and `hasColor()`.
[`Colors\Contracts\HasColor`](../../../packages/support/src/Colors/Contracts/HasColor.php) is the contract every colour policy implements, declaring `getColorMap()`.
Filament has the same clash: its infolists `Concerns\HasColor` trait imports the enum contract `as ColorInterface`, and tables' `Columns\Concerns\HasColor` here imports it `as ColorContract` for the same reason.

### Text a contract answers may be a message, which its reader translates

[`HasLabel::getLabel()`](../../../packages/support/src/Contracts/HasLabel.php) and [`HasDescription::getDescription()`](../../../packages/support/src/Contracts/HasDescription.php) return `string|Markup|TranslatableInterface|null`, where Filament's return `string | Htmlable | null`.
A user's enum is built by PHP, not by the container, so no translator can reach it, and the component reading the contract translates what it answers through its own [`translateText()`](component.md#a-message-is-translated-through-an-injected-translator-which-a-new-built-component-lacks) before any test, cast or concatenation.
That is the second mechanism beside a text getter's own translation: where the text's source holds no translator, the reader translates.
The readers are forms' [`HasOptions`](../forms/has-options.md#an-option-label-may-be-a-message-translated-label-by-label) and [`HasDescriptions`](../forms/has-descriptions.md#only-a-described-case-reaches-the-map), and tables' `formatState()` of a [column](../tables/text-column.md#a-markup-state-skips-truncation-and-affixing-escapes-whichever-side-is-not-already-markup) and of a [summarizer](../tables/summarizer.md#the-formatting-trait-is-a-copy-of-the-columns-and-the-hoist-is-owed), [`IconColumn`'s text alternative](../tables/icon-column.md#the-icon-is-hidden-and-the-span-beside-it-is-the-only-name) and a [group's title](../tables/grouping.md#a-value-that-cannot-name-a-group-answers-null-rather-than-failing).
A plain string stays a literal, as in Filament, and every implementation declaring the narrower return type stays valid, because PHP lets an implementation narrow a return type.
