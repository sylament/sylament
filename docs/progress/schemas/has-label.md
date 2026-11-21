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
| [`translateLabel()` / `shouldTranslateLabel`](../../../packages/schemas/src/Components/Concerns/HasLabel.php) | [`translateLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | done | — | [semantics](#a-label-is-a-string-a-markup-or-a-message-translated-in-the-getter) |
| `disableLabel()` (deprecated alias) | [`disableLabel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasLabel.php) | skipped | — | [diverges](#the-deprecated-disablelabel-is-not-ported-hiddenlabel-replaces-it) |

## Semantics

### A label is a string, a Markup or a message, translated in the getter

`label()` takes `string|Markup|TranslatableInterface|Closure|null`, and `getLabel()` evaluates it and then translates it through the component's translator, so a template or a PHP reader always receives a string or a `Markup`.
A plain string stays a literal, as in Filament, unless `translateLabel()` is on: then a string label is handed to the translator as a key in the default `messages` domain, Filament's `__($label)`.
A message already names its own domain and is translated either way.
`hasCustomLabel()` still tests the raw property, so an unset label stays distinguishable from one that translates to an empty string.
A component built with `new` and handed a message or a `translateLabel()` key throws a `LogicException` naming its class, because it has no translator.

## Divergences

### The deprecated `disableLabel()` is not ported; `hiddenLabel()` replaces it

Filament marks `disableLabel()` `@deprecated` and points to `hiddenLabel()`, which it forwards to.
The port ships `hiddenLabel()` alone.
