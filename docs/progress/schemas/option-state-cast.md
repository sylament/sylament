# OptionStateCast (schemas)

`Sylament\Schemas\State\Casts\OptionStateCast` — the state cast behind single-value option fields.
It normalises whatever a request delivers into the one shape an option value can take: a digit-string becomes an `int` so it compares identically to a PHP array key, a `BackedEnum` or `Stringable` is unwrapped, and anything that is not a scalar fails closed to `null`.

`forms`' [`Select`](../forms/select.md) is its only consumer today, where the int narrowing is load-bearing twice over — it is what makes `options([1 => 'One'])` round-trip, and what lets the auto-added `Choice(strict: true)` constraint match an integer option key.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/StateCasts/OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php)

## Surface

_Detail_ links a permanent note in [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Nullable-blank handling | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | — |
| Non-scalar fail-closed guards | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | — |
| `BackedEnum` / `Stringable` unwrapping | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | — |
| Digit-string → `int` narrowing (leading-zero and overflow arms) | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | [diverges](#an-int-state-skips-the-overflow-check) |
| `set()` stringification | [`OptionStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/OptionStateCast.php) | done | — | — |

Coverage: the full edge matrix — both nullable modes, the digit-string narrowing, leading-zero and `PHP_INT_MAX`-overflow preservation, enum unwrapping, both fail-closed guards, and the int passthrough — by [`OptionStateCastTest`](../../../packages/schemas/tests/Unit/State/Casts/OptionStateCastTest.php).

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### An int state skips the overflow check

Filament guards its narrowing branch with `is_int($state) || (is_string($state) && ctype_digit($state) && …)`, so an **already-int** state also runs the `strlen`/`strcmp` comparison against `PHP_INT_MAX` that follows — and is returned as a *string* when it compares wider.
That fires in practice: `PHP_INT_MIN` renders as 20 characters against `PHP_INT_MAX`'s 19, so Filament's cast turns the int `-9223372036854775808` into the string `"-9223372036854775808"`, and the same holds for every int at or below `-1000000000000000000`.

The port returns an int state unchanged, before the comparison.
The overflow arm exists to protect a digit-*string* too wide to survive `intval()`; an `int` has by construction already survived, so running the check on one can only produce a false positive.
Filament's behaviour is also actively wrong downstream here — an option value stringified this way no longer matches its own integer array key under the `Choice(strict: true)` constraint [`Select`](../forms/select.md#the-choice-constraint-is-the-fields-only-server-side-guard) adds, so a legitimate selection would be rejected.

Both behaviours are pinned by tests, so the difference is visible rather than incidental.
Reverses only if a case is found where Filament's stringification is the wanted answer, which would mean the option key was a string all along.
