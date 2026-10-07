---
description: 'The per-option description map: the secondary line of text an option carries beneath its label, configured as a literal array keyed by option value… (draft)'
ports:
  - vendor/filament/forms/src/Components/Concerns/HasDescriptions.php
  - vendor/filament/support/src/Contracts/HasDescription.php
  - vendor/filament/forms/src/Components/Radio.php
  - vendor/filament/forms/src/Components/CheckboxList.php
owns:
  - packages/forms/src/Components/Concerns/HasDescriptions.php
  - packages/support/src/Contracts/HasDescription.php
---
# HasDescriptions (forms)

`Sylament\Forms\Components\Concerns\HasDescriptions` — the per-option description map: the secondary line of text an option carries beneath its label, configured as a literal array keyed by option value, as a closure, or derived from the field's [enum](has-enum.md) through support's [`HasDescription`](../../../packages/support/src/Contracts/HasDescription.php) contract.
The map is memoised per field instance, and `descriptions()` is what clears it.

Two fields compose it, `Radio` and `CheckboxList` — `ToggleButtons` does not, in Filament or here, since a button's extra text is its tooltip.
Both are built and both render the map — [`Radio`](radio.md) under each option's label, [`CheckboxList`](checkbox-list.md) under each option's — so this file describes the concern alone and each field's rendering is described in its own.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `descriptions()` / `getDescriptions()` — the literal map, the closure arm, and the memo the setter clears | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [diverges](#the-arrayable-arm-is-dropped) |
| `hasDescription()` / `getDescription()` — the per-option lookups | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [semantics](#only-a-described-case-reaches-the-map) · [semantics](#a-markup-description-renders-raw-and-filaments-does-too) |
| The enum fallback — support's [`HasDescription`](../../../packages/support/src/Contracts/HasDescription.php) over [`getEnum()`](has-enum.md)'s cases | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [semantics](#only-a-described-case-reaches-the-map) |

Coverage: the empty default, a literal map (string and `Markup` values alike), the closure arm, the enum fallback over a backed enum and over a pure `UnitEnum` keyed by case name, an enum without the contract deriving nothing, a literal map winning over the enum, one closure evaluation across two reads, and the setter clearing the memo — by [`HasDescriptionsTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasDescriptionsTest.php) against the [`OptionPriority`](../../../packages/forms/tests/Fixtures/Components/Concerns/OptionPriority.php) and [`OptionStage`](../../../packages/forms/tests/Fixtures/Components/Concerns/OptionStage.php) fixture enums, the first of which describes one of its two cases with `null`.
The rendered half — the description `<p>` under a described option and its absence under an undescribed one, from both a literal map and the enum fallback — is covered by [`RadioTest`](../../../packages/forms/tests/Integration/Components/RadioTest.php) and [`CheckboxListTest`](../../../packages/forms/tests/Integration/Components/CheckboxListTest.php).
The map being **written** by a component rather than a caller — from [`CheckboxList`'s record-shaped description closure](checkbox-list.md#the-two-record-shaped-closures-build-labels-and-descriptions-off-one-query) — is covered a file over, in [`CheckboxListRelationshipTest`](../../../packages/forms/tests/Integration/Components/CheckboxListRelationshipTest.php), which is where the relationship arm's Doctrine rig lives.

## Semantics

### Only a described case reaches the map

The enum fallback filters on [`filled()`](../support/helpers.md): a case whose `getDescription()` answers `null` — or an empty string — is **left out of the map entirely** rather than being stored against a null value.
`hasDescription()` is `array_key_exists()` over that map, so it answers `false` for such a case and the render site emits no description element at all, instead of an empty one.

This is Filament's behaviour, and it is the one asymmetry among the four enum-backed map concerns: `HasColors` and `HasIcons` add **every** case, including the ones answering `null`, so their maps carry null values and their `getColor()` / `getIcon()` lookups lean on `?? null` instead.
Only descriptions filter.
The asymmetry is inherited deliberately — the two lookups have different questions to answer, since a null colour still means "this option is styled by the default" while a null description means "this option has nothing to say" — and the colours/icons half is written up with its consumer in [`toggle-buttons.md`](toggle-buttons.md).

Note that the filter is on the **enum-derived** map only.
A literal `descriptions()` array is stored as given, so an author who writes `['draft' => '']` gets a key that `hasDescription()` answers `true` for.
Filament draws the line in the same place.

### A `Markup` description renders raw, and Filament's does too

The map's value type is `string|Markup`, so a description may carry markup, and a template rendering it with `{{ }}` will pass a `Markup` instance through untouched while escaping the identical raw string.
That is not a divergence from Filament, despite its render sites reading `e($description)` unconditionally where an option **label** is gated behind [`allowHtml()`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php): Laravel's `e()` returns `$value->toHtml()` for anything `Htmlable` ([`Illuminate/Support/helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php)), so an `Htmlable` description is raw there too.

The escaping rule for a description is therefore carried by its **type**, in both frameworks: a plain string is always escaped, and markup is always rendered, with no setter in between.
`allowHtml()` governs the label only.

### The enum fallback tests the array, not `blank()`

The fallback gate reads `$descriptions === []` where Filament reads `blank($descriptions)`.
The two are the same test for the only type that can reach it — `blank()`'s last line *is* `$value === []`, an array being neither `Countable` nor `Stringable` — and the port's property is `array|Closure` with [the `Arrayable` arm dropped](#the-arrayable-arm-is-dropped), so after `evaluate()` an array is all there is.

Writing it out is what support's [`@phpstan-assert` decision](../support/helpers.md#the-phpstan-assert-tags-model-these-as-null-checks-only) prescribes: the tags model both helpers as **null-checks only**, so a call on a value already narrowed past `null` is `function.impossibleType` at level max, and the prescribed response is to remove the call rather than suppress it.
The gate here was defending against a `null` the property type cannot hold.

Nothing surfaced it until [`Radio`](radio.md) composed the trait and gave PHPStan a concrete `$this` to resolve `evaluate()` through — the same [analysed-only-through-a-composer](../tables/has-table.md#a-host-trait-is-analysed-only-through-a-class-that-composes-it) effect that named the rule, one package over.

## Divergences

### The `Arrayable` arm is dropped

Filament types `descriptions()` as `array<string | Htmlable> | Arrayable | Closure` and unwraps an `Illuminate\Contracts\Support\Arrayable` with `->toArray()` before the enum fallback runs.
That interface is Laravel's, with no Symfony counterpart and no port here, so the union is `array|Closure` and the getter has no `instanceof` branch.
A caller holding a collection-like object passes `$collection->toArray()`, or a closure returning it.

[`HasOptions`](has-options.md#the-arrayable-arm-is-dropped) and [`datalist()`](has-datalist-options.md#the-datalist-drops-the-arrayable-arm) drop the same arm for the same reason.
