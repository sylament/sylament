---
description: 'The per-option description map of Radio and CheckboxList: a literal array, a closure, or a map derived from the enum cases.'
ports:
  - vendor/filament/forms/src/Components/Concerns/HasDescriptions.php
owns:
  - packages/forms/src/Components/Concerns/HasDescriptions.php
---
# HasDescriptions (forms)

`Sylament\Forms\Components\Concerns\HasDescriptions` — the per-option description map: the secondary line of text an option carries beneath its label, configured as a literal array keyed by option value, as a closure, or derived from the field's [enum](has-enum.md) through support's [`HasDescription`](../../../packages/support/src/Contracts/HasDescription.php) contract.
The map is memoised per field instance, and `descriptions()` is what clears it.

Two fields compose it, `Radio` and `CheckboxList` — `ToggleButtons` does not, in Filament or here, since a button's extra text is its tooltip.
Both render the map under each option's label — [`Radio`](radio.md) and [`CheckboxList`](checkbox-list.md) each describe their own rendering — so this file describes the concern alone.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `descriptions()` / `getDescriptions()` — the literal map, the closure arm, and the memo the setter clears | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [diverges](#the-arrayable-arm-is-dropped) |
| `hasDescription()` / `getDescription()` — the per-option lookups | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [semantics](#only-a-described-case-reaches-the-map) · [semantics](#a-markup-description-renders-raw-and-filaments-does-too) |
| The enum fallback — support's [`HasDescription`](../../../packages/support/src/Contracts/HasDescription.php) over [`getEnum()`](has-enum.md)'s cases | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [semantics](#only-a-described-case-reaches-the-map) |

## Semantics

### Only a described case reaches the map

The enum fallback filters on [`filled()`](../support/helpers.md): a case whose `getDescription()` answers `null` — or an empty string — is **left out of the map entirely** rather than being stored against a null value.
`hasDescription()` is `array_key_exists()` over that map, so it answers `false` for such a case and the render site emits no description element at all, instead of an empty one.

This is Filament's behaviour, and it is the one asymmetry among the enum-backed map concerns: `HasColors` and `HasIcons` add **every** case, including the ones answering `null`, so their maps carry null values and their `getColor()` / `getIcon()` lookups lean on `?? null` instead.
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
A `blank()` gate would defend against a `null` the property type cannot hold.

## Divergences

### The `Arrayable` arm is dropped

Filament types `descriptions()` as `array<string | Htmlable> | Arrayable | Closure` and unwraps an `Illuminate\Contracts\Support\Arrayable` with `->toArray()` before the enum fallback runs.
That interface is Laravel's, with no Symfony counterpart and no port here, so the union is `array|Closure` and the getter has no `instanceof` branch.
A caller holding a collection-like object passes `$collection->toArray()`, or a closure returning it.

[`HasOptions`](has-options.md#the-arrayable-arm-is-dropped) and [`datalist()`](has-datalist-options.md#the-datalist-drops-the-arrayable-arm) drop the same arm for the same reason.
