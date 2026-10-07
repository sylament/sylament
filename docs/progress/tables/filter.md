# Filter (tables)

[`Sylament\Tables\Filters\Filter`](../../../packages/tables/src/Filters/Filter.php) — the checkbox filter: a [`BaseFilter`](base-filter.md) whose form is one boolean field named `isActive`, and whose query pass that field gates.
It ports [`Filament\Tables\Filters\Filter`](../../../vendor/filament/tables/src/Filters/Filter.php), which declares seven members over its base — four of its own (`$formComponent`, `toggle()`, `checkbox()`, `formComponent()`) and three overrides (`setUp()`, `getFormField()`, `getResetState()`), folded into five surface rows below.
Everything the base carries — the name, the label, the schema resolution, the reset state, the query seam, the closure injections — is tracked in [`base-filter.md`](base-filter.md), not repeated here.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Filters/Filter.php`](../../../vendor/filament/tables/src/Filters/Filter.php) — the `$formComponent` property, the `setUp()` indicator, the three swappers, `getFormField()` and the `getResetState()` override

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$formComponent`](../../../packages/tables/src/Filters/Filter.php) | `Filter::$formComponent` | done | — | [semantics](#the-form-component-is-a-class-string-not-an-instance) |
| [`toggle()` / `checkbox()` / `formComponent()`](../../../packages/tables/src/Filters/Filter.php) | `Filter::toggle()`, `checkbox()`, `formComponent()` | done | — | — |
| [`getFormField()`](../../../packages/tables/src/Filters/Filter.php) | `Filter::getFormField()` | done | — | [semantics](#a-false-default-does-reach-the-field-because-filledfalse-is-true) |
| [`getResetState()`](../../../packages/tables/src/Filters/Filter.php) | `Filter::getResetState()` | done | — | [semantics](#a-schema-less-filter-ignores-resetstate-outright) |
| [`setUp()` — the default indicator](../../../packages/tables/src/Filters/Filter.php) | `Filter::setUp()` | done | — | [semantics](#the-indicator-gate-defaults-to-false-where-the-query-gate-defaults-to-true) |

Every row is pinned by [`FilterTest`](../../../packages/tables/tests/Unit/Filters/FilterTest.php).

## Semantics

### The form component is a class-string, not an instance

`formComponent()` takes a `class-string<Field>` and `getFormField()` calls `::make('isActive')` on it, so the field is rebuilt on every call and a swap after the fact is honoured.
The property is typed `string` natively with the class-string carried in PHPDoc — the same shape Filament uses, minus Laravel's container resolution, since our `Field::make()` already routes through `ComponentResolver`.

### A `false` default does reach the field, because `filled(false)` is true

`getFormField()` pushes the filter's default onto the field only `if (filled($defaultState))`, and [`filled()`](../support/helpers.md) — like Laravel's — reports `false` as **filled**.
So `default(false)` sets a field default of `false`, and only `null`, `''`, `[]` and whitespace-only strings are skipped.
This reads like a bug and is not one; it is Filament's behaviour exactly, and it matters because `Checkbox::setUp()` already defaults to `false`, so the two arms are observationally identical for that one value.

### A schema-less filter ignores `resetState()` outright

`getResetState()` returns the hard-coded `['isActive' => false]` whenever `hasSchema()` is false, so a `resetState()` set on a bare checkbox filter is dead configuration.
Only a filter with an explicit `schema()` reaches `CanResetState`.
Faithful to Filament.

### The indicator gate defaults to `false` where the query gate defaults to `true`

`setUp()`'s `indicateUsing` bails on `!($state['isActive'] ?? false)` — an absent key means *no indicator*.
The query seam in [`InteractsWithTableQuery`](base-filter.md) bails on `!(bool) ($data['isActive'] ?? true)` — an absent key means *apply anyway*.
The polarities are opposite on purpose: the query gate is opt-out so a custom-`schema()` filter (which has no `isActive` key at all) still applies, while the indicator gate is opt-in so a filter nobody has ticked shows no chip.
Both directions are pinned by one case in `FilterTest`.
