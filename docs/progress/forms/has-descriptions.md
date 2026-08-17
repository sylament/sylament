# HasDescriptions (forms)

`Sylament\Forms\Components\Concerns\HasDescriptions` — the per-option
description map: the secondary line of text an option carries beneath its label,
configured as a literal array keyed by option value, as a closure, or derived
from the field's [enum](has-enum.md) through support's
[`HasDescription`](../../../packages/support/src/Contracts/HasDescription.php)
contract. The map is memoised per field instance, and `descriptions()` is what
clears it.

Two fields compose it, `Radio` and `CheckboxList` — `ToggleButtons` does not, in
Filament or here, since a button's extra text is its
tooltip. Neither is built yet: until they land the map is
configured and read but nothing renders it, which is why this file describes the
concern alone and each field's rendering will be described in its own.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php)
  — the trait
- [`support/src/Contracts/HasDescription.php`](../../../vendor/filament/support/src/Contracts/HasDescription.php)
  — the contract an enum case's description comes from
- [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php),
  [`CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php)
  — the two composers, and the render sites that escape a description

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics) or [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `descriptions()` / `getDescriptions()` — the literal map, the closure arm, and the memo the setter clears | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [diverges](#the-arrayable-arm-is-dropped) |
| `hasDescription()` / `getDescription()` — the per-option lookups | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [semantics](#only-a-described-case-reaches-the-map) · [semantics](#a-markup-description-renders-raw-and-filaments-does-too) |
| The enum fallback — support's [`HasDescription`](../../../packages/support/src/Contracts/HasDescription.php) over [`getEnum()`](has-enum.md)'s cases | [`Concerns/HasDescriptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDescriptions.php) | done | — | [semantics](#only-a-described-case-reaches-the-map) |

Coverage: the empty default, a literal map (string and `Markup` values alike),
the closure arm, the enum fallback over a backed enum and over a pure `UnitEnum`
keyed by case name, an enum without the contract deriving nothing, a literal map
winning over the enum, one closure evaluation across two reads, and the setter
clearing the memo — by
[`HasDescriptionsTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasDescriptionsTest.php)
against the
[`OptionPriority`](../../../packages/forms/tests/Fixtures/Components/Concerns/OptionPriority.php)
and
[`OptionStage`](../../../packages/forms/tests/Fixtures/Components/Concerns/OptionStage.php)
fixture enums, the first of which describes one of its two cases with `null`.

## Semantics

### Only a described case reaches the map

The enum fallback filters on
[`filled()`](../support/helpers.md): a case whose `getDescription()`
answers `null` — or an empty string — is **left out of the map entirely** rather
than being stored against a null value. `hasDescription()` is `array_key_exists()`
over that map, so it answers `false` for such a case and the render site emits no
description element at all, instead of an empty one.

This is Filament's behaviour, and it is the one asymmetry among the four
enum-backed map concerns: `HasColors` and `HasIcons` add **every** case,
including the ones answering `null`, so their maps carry null values and their
`getColor()` / `getIcon()` lookups lean on `?? null` instead. Only descriptions
filter. The asymmetry is inherited deliberately — the two lookups have different
questions to answer, since a null colour still means "this option is styled by
the default" while a null description means "this option has nothing to say" —
and the colours/icons half is written up with its consumer in
[`toggle-buttons.md`](toggle-buttons.md).

Note that the filter is on the **enum-derived** map only. A literal
`descriptions()` array is stored as given, so an author who writes
`['draft' => '']` gets a key that `hasDescription()` answers `true` for. Filament
draws the line in the same place.

### A `Markup` description renders raw, and Filament's does too

The map's value type is `string|Markup`, so a description may carry markup, and a
template rendering it with `{{ }}` will pass a `Markup` instance through
untouched while escaping the identical raw string. That is not a divergence from
Filament, despite its render sites reading `e($description)` unconditionally
where an option **label** is gated behind
[`allowHtml()`](../../../vendor/filament/forms/src/Components/Concerns/CanAllowHtml.php):
Laravel's `e()` returns `$value->toHtml()` for anything `Htmlable`
([`Illuminate/Support/helpers.php`](../../../vendor/laravel/framework/src/Illuminate/Support/helpers.php)),
so an `Htmlable` description is raw there too.

The escaping rule for a description is therefore carried by its **type**, in both
frameworks: a plain string is always escaped, and markup is always rendered, with
no setter in between. `allowHtml()` governs the label only.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### The `Arrayable` arm is dropped

Filament types `descriptions()` as `array<string | Htmlable> | Arrayable | Closure`
and unwraps an `Illuminate\Contracts\Support\Arrayable` with `->toArray()` before
the enum fallback runs. That interface is Laravel's, with no Symfony counterpart
and no port here, so the union is `array|Closure` and the getter has no
`instanceof` branch. A caller holding a collection-like object passes
`$collection->toArray()`, or a closure returning it.

[`HasOptions`](has-options.md#the-arrayable-arm-is-dropped) and `TextInput`'s
[`datalist()`](text-input.md#the-datalist-drops-the-arrayable-arm) drop the same
arm for the same reason.
