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

Each contract declares Filament's one method, with `Twig\Markup` where Filament's declares `Htmlable` ([translations](../translations.md#idiom)).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`HasColor::getColor()`](../../../packages/support/src/Contracts/HasColor.php) | [`Contracts/HasColor.php`](../../../vendor/filament/support/src/Contracts/HasColor.php) | done | — | [semantics](#three-support-symbols-are-named-hascolor-and-this-one-is-the-state-contract) |
| [`HasDescription::getDescription()`](../../../packages/support/src/Contracts/HasDescription.php) | [`Contracts/HasDescription.php`](../../../vendor/filament/support/src/Contracts/HasDescription.php) | done | — | — |
| [`HasIcon::getIcon()`](../../../packages/support/src/Contracts/HasIcon.php) | [`Contracts/HasIcon.php`](../../../vendor/filament/support/src/Contracts/HasIcon.php) | done | — | — |
| [`HasLabel::getLabel()`](../../../packages/support/src/Contracts/HasLabel.php) | [`Contracts/HasLabel.php`](../../../vendor/filament/support/src/Contracts/HasLabel.php) | done | — | — |
| [`ScalableIcon::getIconForSize()`](../../../packages/support/src/Contracts/ScalableIcon.php) | [`Contracts/ScalableIcon.php`](../../../vendor/filament/support/src/Contracts/ScalableIcon.php) | done | — | — |

## Semantics

### Three support symbols are named `HasColor`, and this one is the state contract

[`Contracts\HasColor`](../../../packages/support/src/Contracts/HasColor.php) is implemented by a user's enum, so that a field or a column can colour an option or a cell from its value.
Forms' [`HasColors`](../../../packages/forms/src/Components/Concerns/HasColors.php) and tables' [`Columns\Concerns\HasColor`](../../../packages/tables/src/Columns/Concerns/HasColor.php) read it.
[`Components\Concerns\HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) is the trait that gives a component `color()`, `defaultColor()`, `getColor()` and `hasColor()`.
[`Colors\Contracts\HasColor`](../../../packages/support/src/Colors/Contracts/HasColor.php) is the contract every colour policy implements, declaring `getColorMap()`.
Filament has the same clash: its infolists `Concerns\HasColor` trait imports the enum contract `as ColorInterface`, and tables' `Columns\Concerns\HasColor` here imports it `as ColorContract` for the same reason.
