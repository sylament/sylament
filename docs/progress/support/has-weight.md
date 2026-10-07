---
description: The concern that gives a component a font weight and the class a template emits for it.
ports:
  - vendor/filament/support/src/Concerns/HasWeight.php
owns:
  - packages/support/src/Components/Concerns/HasWeight.php
---
# HasWeight (support)

`Sylament\Support\Components\Concerns\HasWeight` ports Filament's trait of the same name; `getWeight()` normalizes a known string to its [`FontWeight`](../../../packages/support/src/Enums/FontWeight.php) case and passes any other string through.
Schemas' [`Text`](../schemas/text.md) and tables' [`TextColumn`](../tables/text-column.md) compose it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`weight()` setter + property](../../../packages/support/src/Components/Concerns/HasWeight.php) | [`HasWeight::weight()`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | done | — | — |
| [`getWeight()` normalization](../../../packages/support/src/Components/Concerns/HasWeight.php) | [`HasWeight::getWeight()`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | done | — | [diverges](#getweight-drops-the-state-argument) |
| [`hasWeight()`](../../../packages/support/src/Components/Concerns/HasWeight.php) | [`HasWeight::hasWeight()`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | done | — | — |
| [`getWeightClass()`](../../../packages/support/src/Components/Concerns/HasWeight.php) | — (no Filament equivalent) | done | — | [diverges](#getweightclass-centralizes-the-weight-to-class-mapping) |

## Divergences

### `getWeightClass()` centralizes the weight-to-class mapping

Filament's `HasWeight` stops at `getWeight()`; each consumer maps the `FontWeight` case to a CSS class inline.
Sylament folds that mapping into the trait: `getWeightClass()` returns `sy-font-<value>` for an enum case, a custom string as-is, and `null` when unset, so a Twig template emits `component.getWeightClass` without type-sniffing the enum-or-string union `getWeight()` returns.
It is additive, and it is one member of the [`get*Class()` family](../translations.md#the-getclass-family-is-one-pattern-and-filaments-getclass-means-a-class-string).

### `getWeight()` drops the `$state` argument

Filament types the reader `getWeight(mixed $state = null)` and injects `['state' => $state]` into `evaluate()`, so a weight closure can key off one item of a text column's or entry's state.
Sylament's `getWeight()` takes no argument and evaluates the closure with no extra injection.
On `Text` nothing carries state; on `TextColumn` a `$state` closure parameter falls through to the column's ambient resolver and receives the whole column state, never one item ([text-column.md](../tables/text-column.md#weight-and-fontfamily-are-column-level-where-filaments-are-per-item)).
Reversing it means adding the parameter and the injection here and in [`HasFontFamily`](has-font-family.md#getfontfamily-drops-the-state-parameter), and passing each item's state from the text column's partial.
