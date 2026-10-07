# EnumStateCast (schemas)

`Sylament\Schemas\State\Casts\EnumStateCast` — the state cast behind a field whose value is a single backed enum case.
`get()` turns whatever a request delivers into an instance of the configured enum, or `null`; `set()` writes an instance back as the string spelling of its backing value.

`Field::getEnumDefaultStateCast()` builds it for any field that calls `enum()`.
`Radio` reaches it through that base implementation unchanged; `ToggleButtons` and `Select` each override the method but still return this cast outside `multiple()`, falling back to [`EnumArrayStateCast`](enum-array-state-cast.md) — its plural — once `multiple()` is on.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/StateCasts/EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php)

## Surface

_Detail_ links a permanent note in [Semantics](#semantics) or [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Blank handling | [`EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | done | — | — |
| Already-cast instance passthrough | [`EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | done | — | — |
| Backing-value resolution | [`EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | done | — | [diverges](#tryfrom-is-replaced-by-a-scan-over-the-cases-string-spellings) |
| Non-scalar fail-closed guard | — | done | — | [diverges](#a-non-scalar-state-fails-closed-rather-than-fataling) |
| `set()` stringification | [`EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | done | — | [semantics](#set-is-a-passthrough-not-a-guard) |

Coverage: blank state, instance passthrough, resolution from both a string and a digit-string spelling, the unknown-value miss, and all three fail-closed arms — a non-scalar payload, a scalar of the wrong backing type, and an instance of a different enum — by [`EnumStateCastTest`](../../../packages/schemas/tests/Unit/State/Casts/EnumStateCastTest.php), which exercises a string-backed and an int-backed fixture enum against each other so the backing-type arms are pinned in both directions.

## Semantics

### `set()` is a passthrough, not a guard

`get()` fails closed, `set()` does not: a non-`BackedEnum` returns from `set()` untouched, so a raw string, an int or even an array survives the dehydrate direction unchanged.
That is Filament's behaviour and the port keeps it, which is worth stating because the sibling [`OptionStateCast::set()`](option-state-cast.md) mirrors its own `get()` guard and returns `null` for the same input.

The asymmetry is deliberate on both casts.
`OptionStateCast` normalises *to* a string on both sides, so a value it cannot stringify honestly has no answer but `null`.
`EnumStateCast` only knows how to unwrap an enum; anything else is a value some other cast in the chain owns, and swallowing it here would hide it from whatever runs next.
The array cast is where a non-enum element does get coerced, because there the result type is [pinned to `list<string>`](enum-array-state-cast.md#set-returns-a-list-of-strings-rather-than-a-mixed-array).

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### `tryFrom()` is replaced by a scan over the cases' string spellings

Filament resolves a raw value with `$this->enum::tryFrom($state)`.
That call is not portable to a file under `declare(strict_types=1)`: `tryFrom()` is typed to the enum's *own* backing type, so an `int` reaching a `string`-backed enum — or a digit string reaching an `int`-backed one — raises

```
TypeError: S::tryFrom(): Argument #1 ($value) must be of type string, int given
```

rather than returning `null`.
Filament's files declare no `strict_types`, so weak mode coerces the argument and the same call is a plain miss there.
Every file in this repo declares it, and a JSON request body routinely delivers an int where a string-backed enum expects its spelling, so the ported call would fatal on exactly the payload the cast exists to reject.

The port compares each case's backing value to the incoming state by their string spellings instead:

```php
foreach ($this->enum::cases() as $case) {
    if ((string) $case->value === (string) $state) {
        return $case;
    }
}
```

It is total — no `int`/`string` input can raise against any backing type — and it is strictly conservative rather than equivalent: **it resolves a subset of what weak-mode `tryFrom()` resolves, never a superset**.
Nothing the scan accepts is a value weak mode would have rejected, so no payload becomes valid here that was invalid in Filament.
The subset is where the two part company, and it is worth naming because it is not obvious.

For an **int-backed** enum, weak mode coerces any numeric string to the case, while the scan matches only the canonical decimal spelling.
So `'01'`, `' 1'`, `'1 '`, `'+1'`, `'1.0'` and `'1e0'` all resolve to case `1` under weak-mode `tryFrom()` and all resolve to `null` here.
None is a spelling a browser or a column produces for an option value — a padded, signed or exponent-notation integer was never the value — and the leading-zero arm in particular is the same rejection [`OptionStateCast`](option-state-cast.md) already applies.
A **string-backed** enum has no such gap: weak mode only ever stringifies the incoming value, which is what the scan compares.
The gap also runs the other way in our favour for a non-numeric string against an int-backed enum (`'draft'`, `''`), where weak mode raises a `TypeError` and the scan returns `null`.

One smaller consequence: the scan is linear in the number of cases rather than a hash lookup, which is not worth optimising for enums of realistic size.

Reverses only if PHP grows a total, backing-type-agnostic lookup.

### A non-scalar state fails closed rather than fataling

Filament hands whatever survives its `blank()` and `instanceof` checks straight to `tryFrom()`, so an array, an object or an instance of a *different* enum raises a `TypeError` out of the cast.
The port guards first — only an `int` or a `string` is a candidate spelling — and returns `null` for everything else, matching the tamper defence [`OptionStateCast`](option-state-cast.md) already carries.

`null` is the honest answer: the field's own validation sees the untouched raw state and rejects the payload there, whereas a `TypeError` escaping a state cast is a 500 on a request the application was equipped to refuse.
Booleans and floats fall on the same side of the guard, since neither is a spelling any backed enum can carry.
