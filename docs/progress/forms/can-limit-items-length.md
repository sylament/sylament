---
description: 'The item-count bounds a multi-value field carries: `minItems()` / `maxItems()` configure them, `getMinItems()` / `getMaxItems()` read them back (each… (draft)'
ports:
  - vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php
  - vendor/filament/forms/src/Components/Repeater.php
  - vendor/filament/forms/src/Components/CheckboxList.php
  - vendor/filament/forms/src/Components/Select.php
owns:
  - packages/forms/src/Components/Concerns/CanLimitItemsLength.php
---
# CanLimitItemsLength (forms)

`Sylament\Forms\Components\Concerns\CanLimitItemsLength` — the item-count bounds a multi-value field carries: `minItems()` / `maxItems()` configure them, `getMinItems()` / `getMaxItems()` read them back (each closure-evaluable), and `getItemsCount()` counts the field's raw state.

Three fields compose it.
`Repeater` was the first, while the trait was still folded into [`repeater.md`](repeater.md); `CheckboxList` is the second consumer, which is what promoted it to a file of its own per [one row per file](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature); and [`Select`](select.md) is the third, taking `maxItems()` for its `multiple()` chips control (the JS control refuses a further pick and shows `getMaxItemsMessage()`) and both bounds as a `Count` in the same shape as the checkbox list's.
Filament composes the same trait on all three fields, unchanged between them.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `minItems()` / `maxItems()` / `getMinItems()` / `getMaxItems()` — the bounds and their closure arms | [`Concerns/CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) | done | — | [diverges](#the-setters-register-no-rules-the-composing-field-builds-the-constraint) |
| `getItemsCount()` — the raw-state count | [`Concerns/CanLimitItemsLength.php`](../../../vendor/filament/forms/src/Components/Concerns/CanLimitItemsLength.php) | done | — | — |

Coverage: the null default, both setters, closure bounds evaluated against each other, and the count over a wired raw-state slice — including the non-array arm answering `0` — by [`CanLimitItemsLengthTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanLimitItemsLengthTest.php).
Each composer covers what it does with the bounds in its own suite: the repeater's `isAddable()` gate and its `Count` constraint in [`repeater.md`](repeater.md#surface), the checkbox list's in [`checkbox-list.md`](checkbox-list.md), the select's `Count` and its `maxItemsMessage()` substitution in [`select.md`](select.md).

## Divergences

### The setters register no rules; the composing field builds the constraint

Filament's `maxItems()` and `minItems()` each append two conditional Laravel rules as a side effect of being called — an `array` rule and a `max:{$count}` / `min:{$count}` rule, both guarded by a closure re-reading the getter.
Ours are plain assignments.

The validation half moves to the composing field's `buildValidationConstraints()`, where one `Count(min: …, max: …)` covers both bounds beside the `Type('array')` that Filament's four registrations spell out twice ([`Repeater::buildValidationConstraints()`](../../../packages/forms/src/Components/Repeater.php)).
That is the [constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary) applied — `Select::buildValidationConstraints()` builds the same `Count` for its `multiple()` arm: Symfony validates with constraint objects assembled at validation time, so there is no rule list for a setter to push onto, and building the constraint where the field already builds the rest of them keeps the bounds out of the setter's observable behaviour.
A caller who sets a bound and then unsets it (`maxItems(null)`) therefore leaves nothing behind, where Filament's registration survives with a condition closure that answers `false` forever after.
