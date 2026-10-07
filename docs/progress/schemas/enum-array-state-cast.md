---
description: The state cast behind a field whose value is a *set* of backed enum cases. (draft)
ports:
  - vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php
owns:
  - packages/schemas/src/State/Casts/EnumArrayStateCast.php
---
# EnumArrayStateCast (schemas)

`Sylament\Schemas\State\Casts\EnumArrayStateCast` — the state cast behind a field whose value is a *set* of backed enum cases.
It is the plural of [`EnumStateCast`](enum-state-cast.md): the whole selection is wrapped into an array, every element is run through a composed `EnumStateCast`, and anything that element cast cannot resolve — an unknown spelling, a blank, a tampered non-scalar — is dropped rather than kept as a null hole.
`get()` returns a `list<BackedEnum>`, `set()` a `list<string>`, so a drop reindexes on both sides.

`CheckboxList` and a `multiple()` `ToggleButtons` or `Select` all apply it — each overrides `getEnumDefaultStateCast()` to select this cast over the scalar one exactly when the field takes more than one value, which for `CheckboxList` is every time.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Blank handling | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | — | [semantics](#blank-state-is-always-an-empty-list-with-no-nullable-switch) |
| Per-element enum resolution | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | — | [diverges](#the-element-body-is-enumstatecast-rather-than-a-second-copy) |
| Unresolvable-element handling | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | — | [diverges](#an-unresolvable-element-is-dropped-rather-than-pushed-as-null) |
| Scalar state wrapping | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | — | [diverges](#the-json-string-arm-is-not-ported) |
| `set()` stringification | [`EnumArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php) | done | — | [diverges](#set-returns-a-list-of-strings-rather-than-a-mixed-array) |

Coverage: blank state on both sides, per-element resolution, instance passthrough, bare-scalar wrapping, the unresolvable drop with the reindexing that follows, the per-element blank and non-scalar drops on both sides, and the string coercion of a non-enum element on `set()` — by [`EnumArrayStateCastTest`](../../../packages/schemas/tests/Unit/State/Casts/EnumArrayStateCastTest.php), against the same string-backed and int-backed fixture enums [`EnumStateCastTest`](../../../packages/schemas/tests/Unit/State/Casts/EnumStateCastTest.php) uses.

## Semantics

### Blank state is always an empty list, with no nullable switch

`get(null)`, `get('')` and `get([])` all return `[]`, and so do their `set()` counterparts — there is no `isNullable` constructor flag.
That matches Filament, which has none either, and it is the sibling [`OptionsArrayStateCast`](options-array-state-cast.md#blank-state-is-a-constructor-switch-rather-than-a-fixed-empty-list) that is the outlier: its flag was added for a nullable column or an emptied to-many association, neither of which an enum *set* has met yet.
The flag lands here the day a field needs `null` written rather than `[]`, and not before — adding it speculatively would put a second unexercised arm on every consumer.

The behaviour needs no guard of its own: [`wrap()`](../support/helpers.md) maps `null` to `[]`, and every other blank spelling survives wrapping only to be dropped by the element cast, so the empty result falls out of the composition.

## Divergences

### An unresolvable element is dropped rather than pushed as null

Filament's `get()` reduce pushes the result of `tryFrom()` unconditionally ([`EnumArrayStateCast.php:47`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumArrayStateCast.php)), so an element whose spelling matches no case lands in the returned array as `null`.
The declared return type is `array<BackedEnum>`, and the value that comes back holds `null`s — a selection of three checkboxes where one was tampered hydrates as `[Case, null, Case]`, and every consumer of the state then has to survive a hole its type says cannot be there.

The port drops it, matching what the sibling [`OptionsArrayStateCast`](options-array-state-cast.md#the-element-body-is-optionstatecast-rather-than-a-second-copy) does with a fail-closed element and keeping `list<BackedEnum>` honest.
A drop is silent for the same reason it is there: the field's validation sees the **raw** state, where the unresolvable element is still present, so the payload is rejected at that gate rather than quietly accepted minus one element.

### The element body is `EnumStateCast` rather than a second copy

Filament inlines the resolution twice — once in `EnumStateCast`, once per element inside this cast's `array_reduce` — and the two bodies have already drifted: the scalar cast returns `tryFrom()`'s `null` to its caller as a miss, while the array cast keeps that same `null` as a member of the selection.

The port composes an `EnumStateCast` — a `protected` property, so a subclass may swap it — and maps it over the wrapped state, so the two casts cannot drift and the array cast inherits the element cast's [case-scan resolution](enum-state-cast.md#tryfrom-is-replaced-by-a-scan-over-the-cases-string-spellings) and its [non-scalar guard](enum-state-cast.md#a-non-scalar-state-fails-closed-rather-than-fataling) by construction.
Filament's per-element `blank()` check lands in the same place: a blank element comes back from the element cast as `null`, which is exactly the filter this one applies before reindexing.

### `set()` returns a list of strings rather than a mixed array

Filament's `set()` stringifies a `BackedEnum` element and passes every other element through untouched, typed `array<mixed>`.
The port pins `list<string>` instead: after the element cast has unwrapped the enums, a surviving scalar is `(string)`-coerced and a non-scalar is dropped.

The dehydrate direction writes to a column or an association that holds the backing values, so a `list<string>` is the only shape the destination accepts — leaving an int or an array in it defers the failure to a place with no context about which element caused it.
The drop is the same tamper defence [`OptionStateCast`](option-state-cast.md) applies: stringifying an array yields the literal `"Array"`, a value that is not an option but looks like one.

The scalar cast keeps Filament's passthrough ([why](enum-state-cast.md#set-is-a-passthrough-not-a-guard)) — only the plural one coerces, because only it has a return type to keep honest.

### The JSON-string arm is not ported

Filament runs `json_decode()` over a non-array state before wrapping it, on both sides, so a JSON-encoded selection stored in a text column round-trips.
The port wraps instead, without decoding: a scalar becomes a single-element list, and a JSON string becomes a one-element list holding that string, which then fails to resolve and drops.

The reasoning is [`OptionsArrayStateCast`](options-array-state-cast.md#the-json-string-arm-is-not-ported)'s verbatim — the decode arm is untestable against any shipped field, and it silently swallows malformed input (`json_decode` returns `null`, which `wrap()` turns into `[]`), which is the shape a payload guard should never have.
Reverses if a field lands whose column genuinely holds JSON, at which point the decode belongs to that field's own cast chain.
