---
description: The support enums with no file of their own, from alignment, size and font cases to icon position, grid direction, operation and the not-passed sentinel.
ports:
  - vendor/filament/support/src/Enums/Alignment.php
  - vendor/filament/support/src/Enums/ArgumentValue.php
  - vendor/filament/support/src/Enums/FontFamily.php
  - vendor/filament/support/src/Enums/FontWeight.php
  - vendor/filament/support/src/Enums/GridDirection.php
  - vendor/filament/support/src/Enums/IconPosition.php
  - vendor/filament/support/src/Enums/Operation.php
  - vendor/filament/support/src/Enums/Size.php
  - vendor/filament/support/src/Enums/SlideOverPosition.php
  - vendor/filament/support/src/Enums/TextSize.php
owns:
  - packages/support/src/Enums/
---
# Enums (support)

Each enum here carries Filament's cases and backing values unchanged.
[`IconSize`](icon-size.md) and [`VerticalAlignment`](vertical-alignment.md) have files of their own.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Alignment`](../../../packages/support/src/Enums/Alignment.php) | [`Enums/Alignment.php`](../../../vendor/filament/support/src/Enums/Alignment.php) | done | — | — |
| [`ArgumentValue`](../../../packages/support/src/Enums/ArgumentValue.php) | [`Enums/ArgumentValue.php`](../../../vendor/filament/support/src/Enums/ArgumentValue.php) | done | — | [semantics](#an-explicit-null-counts-as-passed-because-argumentvaluedefault-marks-the-argument-left-out) |
| [`FontFamily`](../../../packages/support/src/Enums/FontFamily.php) | [`Enums/FontFamily.php`](../../../vendor/filament/support/src/Enums/FontFamily.php) | done | — | — |
| [`FontWeight`](../../../packages/support/src/Enums/FontWeight.php) | [`Enums/FontWeight.php`](../../../vendor/filament/support/src/Enums/FontWeight.php) | done | — | — |
| [`GridDirection`](../../../packages/support/src/Enums/GridDirection.php) | [`Enums/GridDirection.php`](../../../vendor/filament/support/src/Enums/GridDirection.php) | done | — | — |
| [`IconPosition`](../../../packages/support/src/Enums/IconPosition.php) | [`Enums/IconPosition.php`](../../../vendor/filament/support/src/Enums/IconPosition.php) | done | — | — |
| [`Operation`](../../../packages/support/src/Enums/Operation.php) | [`Enums/Operation.php`](../../../vendor/filament/support/src/Enums/Operation.php) | done | — | — |
| [`Size`](../../../packages/support/src/Enums/Size.php) | [`Enums/Size.php`](../../../vendor/filament/support/src/Enums/Size.php) | done | — | — |
| [`SlideOverPosition`](../../../packages/support/src/Enums/SlideOverPosition.php) | [`Enums/SlideOverPosition.php`](../../../vendor/filament/support/src/Enums/SlideOverPosition.php) | done | — | — |
| [`TextSize`](../../../packages/support/src/Enums/TextSize.php) | [`Enums/TextSize.php`](../../../vendor/filament/support/src/Enums/TextSize.php) | done | — | — |

## Semantics

### An explicit `null` counts as passed, because `ArgumentValue::Default` marks the argument left out

`ArgumentValue` is a pure enum with one case, `Default`, used as a parameter default where `null` is itself a meaningful argument, so `null` cannot stand for *not passed*.
Its consumer is `numeric()`'s two separators, whose routing [text-column.md](../tables/text-column.md#the-money-and-numeric-arms-are-numberformatter-with-two-sentinels) states; Filament's `numeric()` makes the same test in [`CanFormatState`](../../../vendor/filament/infolists/src/Components/Concerns/CanFormatState.php).
