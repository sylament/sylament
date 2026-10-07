---
description: The shared concern that gives a component a palette colour, with a fallback default, through `color()` and `defaultColor()`.
ports:
  - vendor/filament/support/src/Concerns/HasColor.php
owns:
  - packages/support/src/Components/Concerns/HasColor.php
---
# HasColor (support)

`Sylament\Support\Components\Concerns\HasColor` ports Filament's trait of the same name.
Two contracts share its short name, and [contracts.md](contracts.md#three-support-symbols-are-named-hascolor-and-this-one-is-the-state-contract) tells the three apart.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`color()`](../../../packages/support/src/Components/Concerns/HasColor.php) | [`color()`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| [`defaultColor()`](../../../packages/support/src/Components/Concerns/HasColor.php) | [`defaultColor()`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| [`getColor()`, falling back to the default colour](../../../packages/support/src/Components/Concerns/HasColor.php) | [`getColor()`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
| [`hasColor()`](../../../packages/support/src/Components/Concerns/HasColor.php) | [`hasColor()`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | — |
