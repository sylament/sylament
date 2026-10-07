---
description: The shared concern that gives a component a configurable icon size.
ports:
  - vendor/filament/support/src/Concerns/HasIconSize.php
owns:
  - packages/support/src/Components/Concerns/HasIconSize.php
---
# HasIconSize (support)

`Sylament\Support\Components\Concerns\HasIconSize` ports Filament's trait of the same name.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`iconSize()`](../../../packages/support/src/Components/Concerns/HasIconSize.php) | [`iconSize()`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — | — |
| [`getIconSize()`](../../../packages/support/src/Components/Concerns/HasIconSize.php) | [`getIconSize()`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — | [semantics](#geticonsize-returns-a-size-string-as-set-not-as-an-iconsize-case) |
| [`hasIconSize()`](../../../packages/support/src/Components/Concerns/HasIconSize.php) | [`hasIconSize()`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — | — |

## Semantics

### `getIconSize()` returns a size string as set, not as an `IconSize` case

`iconSize('lg')` stores the string, and `getIconSize()` returns `'lg'` rather than `IconSize::Large`.
The sibling readers [`getAlignment()`](has-alignment.md) and [`getWeight()`](has-weight.md) normalize a known string to its enum case, so a caller of this one has to accept both shapes.
Filament's `getIconSize()` is the same, and [`HasIconSizeTest`](../../../packages/support/tests/Unit/Components/Concerns/HasIconSizeTest.php) pins it.
