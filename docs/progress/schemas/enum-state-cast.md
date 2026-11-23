---
description: The state cast that turns a field's raw value into a single backed enum case and back to its string spelling.
ports:
  - vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php
owns:
  - packages/schemas/src/State/Casts/EnumStateCast.php
---
# EnumStateCast (schemas)

`Sylament\Schemas\State\Casts\EnumStateCast` — the state cast behind a field whose value is a single backed enum case.
`get()` turns whatever a request delivers into an instance of the configured enum, or `null`; `set()` writes an instance back as the string spelling of its backing value.

`Field::getEnumDefaultStateCast()` builds it for any field that calls `enum()`.
`Radio` reaches it through that base implementation unchanged; `ToggleButtons` and `Select` each override the method but still return this cast outside `multiple()`, falling back to [`EnumArrayStateCast`](enum-array-state-cast.md) — its plural — once `multiple()` is on.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Blank handling | [`EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | done | — | — |
| Already-cast instance passthrough | [`EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | done | — | — |
| Backing-value resolution | [`EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | done | — | [diverges](#tryfrom-is-replaced-by-a-scan-over-the-cases-string-spellings) |
| Non-scalar fail-closed guard | [`EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | done | — | [diverges](#only-an-int-or-a-string-is-a-candidate-spelling) |
| `set()` stringification | [`EnumStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php) | in progress | — | [detail](#set-passes-a-value-that-is-no-case-through-where-filament-answers-null) |

## Details

### `set()` passes a value that is no case through, where Filament answers `null`

Filament's `set()` resolves its input through `get()` first and answers `null` when no case matches, so only the string spelling of a case's backing value leaves the dehydrate direction ([`EnumStateCast.php:44-53`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php)).
The port's `set()` unwraps a `BackedEnum` and returns anything else untouched ([`EnumStateCast.php:48-55`](../../../packages/schemas/src/State/Casts/EnumStateCast.php)), so a raw string, an int or an array that names no case is written as it arrived.
The array cast inherits the gap: [`EnumArrayStateCast`](enum-array-state-cast.md) drops a non-scalar element but keeps a scalar that names no case, where Filament's drops every element its element cast answers `null` for.
Next step: resolve through `get()` as Filament does, so a case's spelling is normalised and anything else answers `null`, and pin both casts' miss with a test.

## Divergences

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
None is a spelling a browser or a column produces for an option value, and the leading-zero arm in particular is the same spelling [`OptionStateCast`](option-state-cast.md) declines to normalise to an int.
A **string-backed** enum has no such gap for an int or a string: weak mode only stringifies the incoming value, which is what the scan compares.
The two also part company for a non-numeric string against an int-backed enum (`'draft'`), where weak mode raises a `TypeError` and the scan returns `null`.

One smaller consequence: the scan is linear in the number of cases rather than a hash lookup, which is not worth optimising for enums of realistic size.

Reverses only if PHP grows a total, backing-type-agnostic lookup.

### Only an int or a string is a candidate spelling

Filament turns a `Stringable` into its string and then, as the port does, returns `null` for anything that is not an int or a string before it calls `tryFrom()` ([`EnumStateCast.php:29-35`](../../../vendor/filament/schemas/src/Components/StateCasts/EnumStateCast.php)).
The port guards on `is_int()` and `is_string()` alone, so a `Stringable` resolves to no case where Filament resolves its string.
A request never delivers a `Stringable`: it reaches the cast only from PHP code, as a value object an application set itself.
`null` is the honest answer: the field's own validation sees the untouched raw state and rejects the payload there.
The `Stringable` arm reverses if a state source delivers a value object as an enum's spelling.
