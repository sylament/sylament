---
description: The BooleanStateCast, which reads state as a bool and writes it as a bool, an int or null.
ports:
  - vendor/filament/schemas/src/Components/StateCasts/BooleanStateCast.php
owns:
  - packages/schemas/src/State/Casts/BooleanStateCast.php
---
# BooleanStateCast (schemas)

[`Sylament\Schemas\State\Casts\BooleanStateCast`](../../../packages/schemas/src/State/Casts/BooleanStateCast.php) implements [`StateCast`](state-cast.md).
[`Checkbox`](../forms/checkbox.md) and [`Toggle`](../forms/toggle.md) append it with `isNullable: false` to their default casts, and the `boolean()` setters of [`Radio`](../forms/radio.md), [`Select`](../forms/select.md) and [`ToggleButtons`](../forms/toggle-buttons.md) add it with `isStoredAsInt: true`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`__construct(bool $isNullable = true, bool $isStoredAsInt = false)`](../../../packages/schemas/src/State/Casts/BooleanStateCast.php) | [`StateCasts/BooleanStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/BooleanStateCast.php) | done | — | — |
| [`get()` — `null` for blank state when nullable, otherwise a bool](../../../packages/schemas/src/State/Casts/BooleanStateCast.php) | [`StateCasts/BooleanStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/BooleanStateCast.php) | done | — | — |
| [`set()` — `null` for blank state when nullable, `1` or `0` when stored as an int, otherwise a bool](../../../packages/schemas/src/State/Casts/BooleanStateCast.php) | [`StateCasts/BooleanStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/BooleanStateCast.php) | done | — | — |
