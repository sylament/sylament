---
description: The shared concern that gives a component a configurable font weight. (draft)
ports:
  - vendor/filament/support/src/Concerns/HasWeight.php
  - vendor/filament/support/src/Enums/FontWeight.php
owns:
  - packages/support/src/Components/Concerns/HasWeight.php
---
# HasWeight (support)

`Sylament\Support\Components\Concerns\HasWeight` — the shared concern that gives a component a configurable font weight.
It ports Filament's `HasWeight`: a `FontWeight | string | Closure | null` property, a fluent `weight()` setter, a `getWeight()` reader that normalizes a known string to its [`FontWeight`](../../../packages/support/src/Enums/FontWeight.php) case, and a `hasWeight()` probe.
Sylament adds one method with no Filament counterpart — `getWeightClass()` — and drops the `$state` argument from `getWeight()`, so this port carries two divergences.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`weight()` setter + property](../../../packages/support/src/Components/Concerns/HasWeight.php) | [`HasWeight::weight()`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | done | — | — |
| [`getWeight()` normalization](../../../packages/support/src/Components/Concerns/HasWeight.php) | [`HasWeight::getWeight()`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | done | — | [diverges](#getweight-drops-the-state-argument) |
| [`hasWeight()`](../../../packages/support/src/Components/Concerns/HasWeight.php) | [`HasWeight::hasWeight()`](../../../vendor/filament/support/src/Concerns/HasWeight.php) | done | — | — |
| [`getWeightClass()`](../../../packages/support/src/Components/Concerns/HasWeight.php) | — (no Filament equivalent) | done | — | [diverges](#getweightclass-centralizes-the-weight-to-class-mapping) |

## Details

Every row above is **done** — the port is faithful and every method, including the added `getWeightClass()`, is exercised by the concern's sole consumer, schemas' `Text`: the string-normalization and class-fragment cases in [`TextTest`](../../../packages/schemas/tests/Unit/Components/TextTest.php) cover `getWeight()` (enum normalization and verbatim custom string) and `getWeightClass()` (`sy-font-<value>`, custom string as-is, `null` when unset), and the render case in [`TextRenderTest`](../../../packages/schemas/tests/Integration/Components/TextRenderTest.php) asserts the weight class reaches the emitted markup.

## Divergences

### `getWeightClass()` centralizes the weight-to-class mapping

Filament's `HasWeight` stops at `getWeight()`; each Blade template that renders a weighted component then maps the `FontWeight` case to a CSS class inline.
Sylament folds that mapping into the trait: `getWeightClass()` returns `sy-font-<value>` for an enum case, a custom string as-is, and `null` when unset — so Twig templates emit `component.getWeightClass` without type-sniffing the enum-vs-string union that `getWeight()` returns.
It is additive (nothing in Filament is removed or renamed) and covered by the class-building cases in [`TextTest`](../../../packages/schemas/tests/Unit/Components/TextTest.php).

### `getWeight()` drops the `$state` argument

Filament types the reader `getWeight(mixed $state = null)` and injects `['state' => $state]` into `evaluate()`, so a weight closure can key off a component's state (the infolists/tables text-column case).
Sylament's `getWeight()` takes no argument and evaluates the closure with no extra injections.
There is no state concept at this layer of the port and the only consumer — schemas' `Text` — has no per-record state, so nothing supplies or consumes the argument.
Re-add it in the same change as the first state-bearing consumer, where it finally has behavior to test against.
