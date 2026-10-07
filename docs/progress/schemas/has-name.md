---
description: The schemas-level HasName trait holding a field's machine name behind name() and getName().
ports:
  - vendor/filament/schemas/src/Components/Concerns/HasName.php
owns:
  - packages/schemas/src/Components/Concerns/HasName.php
---
# HasName (schemas)

`Sylament\Schemas\Components\Concerns\HasName` holds a component's machine name: a `name` property, the `name()` setter and the `getName()` accessor.
It ports Filament's identically-named trait, which lives in the `schemas` namespace but is consumed by the field layers above it.
Its consumer in the port is [`Sylament\Forms\Components\Field`](../../../packages/forms/src/Components/Field.php); it stays in schemas because Filament shares it across `Field`, infolists' `Entry`, `MorphToSelect`, `Builder\Block` and the rich editor's `RichEditorTool` and `ToolbarButtonGroup`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`name()`](../../../packages/schemas/src/Components/Concerns/HasName.php) | [`name()`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) | done | — | [diverges](#hasname-is-nullable-unlike-filaments-required-string-name) |
| [`getName()`](../../../packages/schemas/src/Components/Concerns/HasName.php) | [`getName()`](../../../vendor/filament/schemas/src/Components/Concerns/HasName.php) | done | — | [diverges](#hasname-is-nullable-unlike-filaments-required-string-name) |

## Divergences

### HasName is nullable, unlike Filament's required string name

Filament types the property `string $name` with no default and returns `getName(): string`.
Sylament types it `?string $name = null`, with `name(?string)` and `getName(): ?string`, so a component that never received a name reads `null` rather than raising an uninitialized-property error.
[`Field::make()`](../../../packages/forms/src/Components/Field.php) refuses a blank name as Filament's does, so a field built through `make()` always carries one; the nullable setter still admits a later `name(null)`, and the label and validation paths that read the name keep their null branches ([field.md](../forms/field.md#the-name-moves-from-the-constructor-to-a-setter)).
Reverse it by typing the trait `string` once those null branches go.
