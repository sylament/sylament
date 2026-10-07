---
description: The state cast behind single-value option fields, which narrows a digit-string to an int key and fails closed on a non-scalar payload.
ports:
  - vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php
owns:
  - packages/schemas/src/State/Casts/OptionStateCast.php
---
# OptionStateCast (schemas)

[`Sylament\Schemas\State\Casts\OptionStateCast`](../../../packages/schemas/src/State/Casts/OptionStateCast.php) is the state cast behind single-value option fields.
It normalises whatever a request delivers into the one shape an option value can take: a digit-string becomes an `int` so it compares identically to a PHP array key, a `BackedEnum` or `Stringable` is unwrapped, and anything that is not a scalar fails closed to `null`.

Forms' [`Select`](../forms/select.md), `Radio` and `ToggleButtons` use it, and so does [`OptionsArrayStateCast`](options-array-state-cast.md) for each element.
In `Select` the int narrowing is load-bearing twice over: it is what makes `options([1 => 'One'])` round-trip, and what lets the auto-added `Choice(strict: true)` constraint match an integer option key.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Nullable-blank handling](../../../packages/schemas/src/State/Casts/OptionStateCast.php) | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | — |
| [Non-scalar fail-closed guards](../../../packages/schemas/src/State/Casts/OptionStateCast.php) | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | — |
| [`BackedEnum` / `Stringable` unwrapping](../../../packages/schemas/src/State/Casts/OptionStateCast.php) | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | — |
| [Digit-string → `int` narrowing](../../../packages/schemas/src/State/Casts/OptionStateCast.php) (leading-zero and overflow arms) | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | [diverges](#an-int-state-skips-the-overflow-check) |
| [`set()` stringification](../../../packages/schemas/src/State/Casts/OptionStateCast.php) | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | — |

## Divergences

### An int state skips the overflow check

Filament guards its narrowing branch with `is_int($state) || (is_string($state) && ctype_digit($state) && …)`, so an **already-int** state also runs the `strlen`/`strcmp` comparison against `PHP_INT_MAX` that follows, and is returned as a *string* when it compares wider.
That fires in practice: `PHP_INT_MIN` renders as 20 characters against `PHP_INT_MAX`'s 19, so Filament's cast turns the int `-9223372036854775808` into the string `"-9223372036854775808"`, and the same holds for every int at or below `-1000000000000000000`.

The port returns an int state unchanged, before the comparison.
The overflow arm exists to protect a digit-*string* too wide to survive `intval()`; an `int` has by construction already survived, so running the check on one can only produce a false positive.
Filament's answer is also wrong downstream: an option value stringified this way does not match its own integer array key under the `Choice(strict: true)` constraint [`Select`](../forms/select.md#the-choice-constraint-is-the-fields-only-server-side-guard) adds, so a legitimate selection would be rejected.

`OptionStateCastTest` pins the port's answer for both `PHP_INT_MAX` and `PHP_INT_MIN`.
This reverses only if a case is found where Filament's stringification is the wanted answer, which would mean the option key was a string all along.
