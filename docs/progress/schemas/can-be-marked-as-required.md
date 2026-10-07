---
description: The required-marker toggle shared by forms' Field and FusedGroup and schemas' Fieldset, falling back to the using class's isRequired().
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php
owns:
  - packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php
---
# CanBeMarkedAsRequired (schemas)

[`Sylament\Schemas\Components\Concerns\CanBeMarkedAsRequired`](../../../packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php) is the required-marker toggle: `markAsRequired()` sets it, and `isMarkedAsRequired()` reads it and falls back to `$this->isRequired()` when nothing explicit was set.
That fallback is a contract on the **using class**, which must declare `isRequired()`: the forms [`Field`](../../../packages/forms/src/Components/Field.php) gets it from [`CanBeValidated`](../forms/can-be-validated.md) and reports the real validation state, forms' [`FusedGroup`](../forms/fused-group.md) answers from its child fields, and schemas' [`Fieldset`](fieldset.md) declares a constant `false`, so its legend takes an asterisk only after an explicit `markAsRequired()`.
Filament hosts the trait in forms; it sits in schemas here because schemas' `Fieldset` composes it and `forms` depends on `schemas`, not the reverse ([translations.md](../translations.md#canbemarkedasrequired-moves-down-into-schemas)).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`markAsRequired()`](../../../packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php) | [`markAsRequired()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php) | done | — | — |
| [`isMarkedAsRequired()`](../../../packages/schemas/src/Components/Concerns/CanBeMarkedAsRequired.php) | [`isMarkedAsRequired()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeMarkedAsRequired.php) | done | — | [diverges](../forms/field.md#the-native-required-attribute-follows-ismarkedasrequired-not-filaments-isrequired) |
