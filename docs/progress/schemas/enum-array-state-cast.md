---
description: The state cast that converts a multi-value field between a list of backed enum cases and the list of strings its inputs speak.
ports:
  - vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php
owns:
  - packages/schemas/src/State/Casts/EnumArrayStateCast.php
---
# EnumArrayStateCast (schemas)

[`Sylament\Schemas\State\Casts\EnumArrayStateCast`](../../../packages/schemas/src/State/Casts/EnumArrayStateCast.php) is the state cast behind a field whose value is a *set* of backed enum cases.
It is the plural of [`EnumStateCast`](enum-state-cast.md): the whole selection is wrapped into an array, every element is run through a composed `EnumStateCast`, and anything that element cast cannot resolve (an unknown spelling, a blank, a tampered non-scalar) is dropped rather than kept as a null hole.
`get()` returns a `list<BackedEnum>`, `set()` a `list<string>`, so a drop reindexes on both sides.

`CheckboxList` and a `multiple()` `ToggleButtons` or `Select` all apply it: each overrides `getEnumDefaultStateCast()` to select this cast over the scalar one exactly when the field takes more than one value, which for `CheckboxList` is every time.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Blank handling | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | — | [semantics](#blank-state-is-always-an-empty-list-with-no-nullable-switch) |
| Per-element enum resolution | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | — | [semantics](#the-element-body-is-the-composed-enumstatecast) · [diverges](#the-element-cast-is-built-with-new-where-filament-resolves-it-through-the-container) |
| Unresolvable-element handling | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | — | [semantics](#an-unresolvable-element-is-dropped-and-validation-still-sees-it) |
| Scalar state wrapping | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | — | [diverges](#the-json-string-arm-is-not-ported) |
| `set()` stringification | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | in progress | — | [detail](enum-state-cast.md#set-passes-a-value-that-is-no-case-through-where-filament-answers-null) |

## Semantics

### Blank state is always an empty list, with no nullable switch

`get(null)`, `get('')` and `get([])` all return `[]`, and so do their `set()` counterparts: there is no `isNullable` constructor flag.
That matches Filament, which has none either; the sibling [`OptionsArrayStateCast`](options-array-state-cast.md#blank-state-is-a-constructor-switch-rather-than-a-fixed-empty-list) is the outlier, with a flag for a nullable column or an emptied to-many association.
The flag lands here when a field needs `null` written rather than `[]`, because adding it speculatively would put a second unexercised arm on every consumer.

The behaviour needs no guard of its own: [`wrap()`](../support/helpers.md) maps `null` to `[]`, and every other blank spelling survives wrapping only to be dropped by the element cast, so the empty result falls out of the composition.

### An unresolvable element is dropped, and validation still sees it

Filament's `get()` reduce runs each element through its composed `EnumStateCast` and returns the carry unchanged when that answers `null` ([`EnumArrayStateCast.php:43-45`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php)), so an element whose spelling matches no case is dropped, as the port drops it, and the result holds no `null` hole.
A drop is silent because the field's validation sees the **raw** state, where the unresolvable element is still present, so the payload is rejected at that gate rather than quietly accepted minus one element.

### The element body is the composed `EnumStateCast`

Filament and the port both compose an `EnumStateCast` in a `protected` property and run every element of both directions through it ([`EnumArrayStateCast.php:11-20`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php)), so the scalar and the array cast cannot drift.
The array cast therefore inherits the port's element cast's [case-scan resolution](enum-state-cast.md#tryfrom-is-replaced-by-a-scan-over-the-cases-string-spellings) and its [non-scalar guard](enum-state-cast.md#only-an-int-or-a-string-is-a-candidate-spelling).
A blank element comes back from the element cast as `null`, which is the filter both casts apply before reindexing.

## Divergences

### The element cast is built with `new`, where Filament resolves it through the container

Filament builds its element cast with `app(EnumStateCast::class, ['enum' => $enum])` (`EnumArrayStateCast.php:19`), so an application that rebinds `EnumStateCast` in Laravel's container changes the array cast's elements too.
The port calls `new EnumStateCast($enum)`: the enum class is a runtime argument Symfony's container cannot pass to a service, and reaching the container from a value object is the service locator the port's first principle refuses.
An application that wants other element behaviour subclasses `EnumArrayStateCast` and sets its own `$enumStateCast`.

### The JSON-string arm is not ported

Filament runs `json_decode()` over a non-array state before wrapping it, on both sides, so a JSON-encoded selection stored in a text column round-trips.
The port wraps instead, without decoding: a scalar becomes a single-element list, and a JSON string becomes a one-element list holding that string, which then fails to resolve and drops.

The reasoning is [`OptionsArrayStateCast`](options-array-state-cast.md#the-json-string-arm-is-not-ported)'s: no shipped field stores a selection as JSON, and the decode arm silently swallows malformed input (`json_decode` returns `null`, which `Arr::wrap()` turns into `[]`), which is the shape a payload guard should never have.
It reverses if a field lands whose column genuinely holds JSON, at which point the decode belongs to that field's own cast chain.
