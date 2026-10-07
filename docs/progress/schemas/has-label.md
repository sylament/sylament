---
description: The HasLabel concern that schema components and forms fields share for label(), hiddenLabel() and hasCustomLabel().
ports:
  - vendor/filament/schemas/src/Components/Concerns/HasLabel.php
owns:
  - packages/schemas/src/Components/Concerns/HasLabel.php
---
# HasLabel (schemas)

[`Sylament\Schemas\Components\Concerns\HasLabel`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) is the human-facing label surface: `label()` / `getLabel()`, the `hiddenLabel()` / `isLabelHidden()` screen-reader toggle, and `hasCustomLabel()`.
Schemas' layout components such as [`Section`](section.md) and [`Fieldset`](fieldset.md) compose it, and so do forms' [`Field`](../../../packages/forms/src/Components/Field.php) and `FusedGroup`; actions and tables carry label concerns of their own.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`label()` / `getLabel()`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`label()` / `getLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| [`hiddenLabel()` / `isLabelHidden()`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`hiddenLabel()` / `isLabelHidden()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| [`hasCustomLabel()`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`hasCustomLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | — |
| `translateLabel()` / `shouldTranslateLabel` | [`translateLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | skipped | — | [diverges](#label-translation-is-not-ported) |
| `disableLabel()` (deprecated alias) | [`disableLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | skipped | — | [diverges](#the-deprecated-disablelabel-is-not-ported-hiddenlabel-replaces-it) |

## Divergences

### Label translation is not ported

Filament's `HasLabel` carries `shouldTranslateLabel` and `translateLabel()`, and its `getLabel()` runs a string label through Laravel's `__()` translator when the flag is set.
No translator is wired into Sylament's components, so `getLabel()` returns the evaluated label as given.
A component translation layer would reverse this.

### The deprecated `disableLabel()` is not ported; `hiddenLabel()` replaces it

Filament marks `disableLabel()` `@deprecated` and points to `hiddenLabel()`, which it forwards to.
The port ships `hiddenLabel()` alone.
