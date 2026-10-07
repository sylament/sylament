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
| [`tooltip()`](../../../packages/support/src/Components/Concerns/HasTooltip.php) | [`tooltip()`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | [translations.md](../translations.md#idiom) |
| [`getTooltip()`](../../../packages/support/src/Components/Concerns/HasTooltip.php) | [`getTooltip()`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | — |
| [`hasTooltip()`](../../../packages/support/src/Components/Concerns/HasTooltip.php) | [`hasTooltip()`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | — |
