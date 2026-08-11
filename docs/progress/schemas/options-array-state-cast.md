# OptionsArrayStateCast (schemas)

`Sylament\Schemas\State\Casts\OptionsArrayStateCast` — the state cast behind
multi-value option fields. It is the plural of
[`OptionStateCast`](option-state-cast.md): the whole selection is wrapped into an
array, every element is run through a private `OptionStateCast`, and anything
that element cast fails closed on — a nested array, an object, a blank — is
dropped rather than kept as a bogus option value. The result is always a list, so
a drop reindexes.

`forms`' [`Select`](../forms/select.md) is its only consumer today, selected by
`multiple()`, and the to-many relationship arm builds on the same shape.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/StateCasts/OptionsArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionsArrayStateCast.php)

## Surface

_Detail_ links a permanent note in [Semantics](#semantics) or
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Per-element normalization | [`OptionsArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionsArrayStateCast.php) | done | — | [diverges](#the-element-body-is-optionstatecast-rather-than-a-second-copy) |
| Non-scalar element fail-closed guards | [`OptionsArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionsArrayStateCast.php) | done | — | [semantics](#a-dropped-element-is-a-silent-drop-not-an-error) |
| Blank handling | [`OptionsArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionsArrayStateCast.php) | done | — | [diverges](#blank-state-is-a-constructor-switch-rather-than-a-fixed-empty-list) |
| Scalar state wrapping | [`OptionsArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionsArrayStateCast.php) | done | — | [diverges](#the-json-string-arm-is-not-ported) |
| `set()` stringification | [`OptionsArrayStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionsArrayStateCast.php) | done | — | — |

Coverage: both blank modes, per-element narrowing on `get()` and stringification
on `set()`, the leading-zero and `PHP_INT_MAX`-overflow arms, enum unwrapping,
both fail-closed guards, blank-element dropping and the reindexing that follows —
by
[`OptionsArrayStateCastTest`](../../../packages/schemas/tests/Unit/State/Casts/OptionsArrayStateCastTest.php);
the `Select` integration — cast selection, the round-trip and the multi-value
`Choice` guard — by
[`SelectMultipleTest`](../../../packages/forms/tests/Integration/Components/SelectMultipleTest.php).

## Semantics

### A dropped element is a silent drop, not an error

A tampered payload can deliver a nested array or an object where an option value
belongs, and stringifying one would produce the literal `"Array"` — a value that
is not an option but looks like one. Both sides therefore drop such an element
and keep the rest of the selection, exactly as Filament does. Nothing reports the
drop, and nothing needs to: the field's
[`Choice` constraint](../forms/select.md#the-choice-constraint-is-the-fields-only-server-side-guard)
validates the **raw** state, where the tampered element is still present, so the
payload is rejected there rather than quietly accepted minus one element.

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### Blank state is a constructor switch rather than a fixed empty list

Filament always returns `[]` for a blank state, on both sides. The port takes
`__construct(bool $isNullable = false)`, mirroring
[`OptionStateCast`](option-state-cast.md)'s: the default keeps Filament's
behaviour, and `isNullable: true` returns `null` instead, which is what a
nullable column or an emptied to-many association has to write to be cleared. An
empty array and `null` are different values to Doctrine, and only the field
knows which one its target takes — so the choice belongs on the cast, not in it.

The nullable default is the opposite of `OptionStateCast`'s (`true`) because the
empty *selection* has an honest non-null spelling — `[]` — while an empty single
option does not.

### The element body is `OptionStateCast` rather than a second copy

Filament inlines the same normalization body twice: once in `OptionStateCast`,
once per element inside `OptionsArrayStateCast`'s `array_reduce`. The port
composes a private `OptionStateCast` and maps it over the wrapped state, so the
two casts cannot drift, and the array cast inherits the element cast's
[int-passthrough divergence](option-state-cast.md#an-int-state-skips-the-overflow-check)
by construction. Blank and fail-closed elements come back as `null` from that
cast, which is exactly the filter this one applies before reindexing — Filament's
per-element `blank()` and non-scalar guards land in the same place.

### The JSON-string arm is not ported

Filament runs `json_decode()` over a non-array state before wrapping it, so a
JSON-encoded selection stored in a text column round-trips. The port wraps
instead, without decoding: a scalar becomes a single-element list, and a JSON
string becomes a one-element list holding that string. The decode arm is
untestable against any shipped field — no port stores a selection as JSON today —
and it silently swallows malformed input (`json_decode` returns `null`, which
`Arr::wrap` turns into `[]`), which is the shape a payload guard should never
have. Reverses if a field lands whose column genuinely holds JSON, at which point
the decode belongs to that field's own cast chain.
