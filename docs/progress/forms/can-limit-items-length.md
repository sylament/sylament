---
description: The `minItems()` / `maxItems()` item-count bounds and the raw-state `getItemsCount()` of Repeater, CheckboxList and multiple Select.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php
owns:
  - packages/forms/src/Components/Concerns/CanLimitItemsLength.php
---
# CanLimitItemsLength (forms)

`Sylament\Forms\Components\Concerns\CanLimitItemsLength` holds the item-count bounds a multi-value field carries: `minItems()` / `maxItems()` configure them, `getMinItems()` / `getMaxItems()` read them back (each closure-evaluable), and `getItemsCount()` counts the field's raw state.

[`Repeater`](repeater.md), [`CheckboxList`](checkbox-list.md) and [`Select`](select.md) compose it.
`Select` takes `maxItems()` for its `multiple()` chips control (the JS control refuses a further pick and shows `getMaxItemsMessage()`) and both bounds as a `Count` in the same shape as the checkbox list's.
Filament composes the same trait on those three plus `Builder`, `TableSelect` and `ModalTableSelect`, which have no port, unchanged between them.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `minItems()` / `maxItems()` / `getMinItems()` / `getMaxItems()` — the bounds and their closure arms | [`Concerns/CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) | done | — | [diverges](#the-setters-register-no-rules-the-composing-field-builds-the-constraint) |
| `getItemsCount()` — the raw-state count | [`Concerns/CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) | done | — | — |
## Divergences

### The setters register no rules; the composing field builds the constraint

Filament's `maxItems()` and `minItems()` each append two conditional Laravel rules as a side effect of being called — an `array` rule and a `max:{$count}` / `min:{$count}` rule, both guarded by a closure re-reading the getter.
Ours are plain assignments.

The validation half moves to the composing field's `buildValidationConstraints()`, where one `Count(min: …, max: …)` covers both bounds beside the `Type('array')` that Filament's four registrations spell out twice ([`Repeater::buildValidationConstraints()`](../../../packages/forms/src/Components/Repeater.php)).
That is the [constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) applied — `Select::buildValidationConstraints()` builds the same `Count` for its `multiple()` arm: Symfony validates with constraint objects assembled at validation time, so there is no rule list for a setter to push onto, and building the constraint where the field already builds the rest of them keeps the bounds out of the setter's observable behaviour.
A caller who sets a bound and then unsets it (`maxItems(null)`) therefore leaves nothing behind, where Filament's registration survives with a condition closure that answers `false` forever after.
