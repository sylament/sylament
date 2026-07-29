# CanBeLengthConstrained (forms)

`Sylament\Forms\Components\Concerns\CanBeLengthConstrained` — the shared
character-length concern composed on both `TextInput` and `Textarea`, together
with the
[`Contracts\CanBeLengthConstrained`](../../../packages/forms/src/Components/Contracts/CanBeLengthConstrained.php)
interface those two fields implement. It carries the `length` / `maxLength` /
`minLength` setters and getters, and turns them into Symfony `Length`
constraints for [`CanBeValidated`](can-be-validated.md) to fold into the field's
constraint list.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php)
  — the trait
- [`Components/Contracts/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Contracts/CanBeLengthConstrained.php)
  — the interface the trait satisfies (its own [Supporting row](README.md#supporting-surface))
- [`Components/Contracts/CanHaveNumericState.php`](../../../vendor/filament/forms/src/Components/Contracts/CanHaveNumericState.php)
  — the contract Filament's rule builder branches on (unported)

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `minLength` / `maxLength` (+ getters) | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | — |
| `length()` / `getLength()` | [`Concerns/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | — |
| `getLengthValidationConstraints()` | [`getLengthValidationRules()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeLengthConstrained.php) | done | — | [diverges](#numeric-fields-get-plain-length-constraints-not-digit-counts) · [diverges](#a-zero-length-bound-builds-no-constraint) |
| [`Contracts\CanBeLengthConstrained`](../../../packages/forms/src/Components/Contracts/CanBeLengthConstrained.php) | [`Components/Contracts/CanBeLengthConstrained.php`](../../../vendor/filament/forms/src/Components/Contracts/CanBeLengthConstrained.php) | done | — | [diverges](#the-contract-declares-only-the-constraint-builder) |

Coverage: the setter fan-out, the exact-`Length` build, the separate max/min
builds, the no-bounds case and closure evaluation by
[`CanBeLengthConstrainedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeLengthConstrainedTest.php);
the hand-off from the field's constraint list by
[`CanBeValidatedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeValidatedTest.php)
and, ordered against the other arms, by
[`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php);
the rendered `maxlength` / `minlength` attributes by the `TextInput` and
`Textarea` integration suites.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### Numeric fields get plain Length constraints, not digit counts

Filament's `getLengthValidationRules()` branches on `$this instanceof
CanHaveNumericState && $this->isNumeric()` and swaps the whole rule family:
`size:` → `digits:`, `max:` → `max_digits:`, `min:` → `min_digits:` — Laravel
rules that count *digits* in a numeric value rather than characters in a string.
Symfony ships no digit-count constraint, so a numeric field gets the same
`Length` every other field gets: the value is cast to string and every character
counts, sign and decimal point included. `-1.5` is therefore length 4 here where
Laravel's `digits` sees 2. The
[`CanHaveNumericState`](../../../vendor/filament/forms/src/Components/Contracts/CanHaveNumericState.php)
contract stays unported for the same reason — it exists to drive this branch.
Reverses if a digit-count constraint is written (a `Regex` bounding the digit run
would be the port's shape); until then `maxLength()` on a numeric input is a
character cap, which is also what the rendered `maxlength` attribute enforces
client-side.

### A zero-length bound builds no constraint

Filament guards each bound with `filled()`, and `filled(0)` is true, so
`maxLength(0)` emits `max:0`. The port guards with `> 0`, so a zero bound is
dropped and no `Length` is built — `Length(max: 0)` constructs fine in Symfony,
so this is the guard's choice, not a library limit. The three zero bounds are
degenerate in different ways: `minLength(0)` is a constraint that can never fail,
while `maxLength(0)` / `length(0)` accept only the empty string, which the
absence of `required()` already expresses. Provisional: the reversal is the
one-token `!== null`, worth taking the moment a caller has a real reason to pin
an empty value.

### The contract declares only the constraint builder

Filament's `Contracts\CanBeLengthConstrained` declares `getLength()`,
`getMaxLength()`, `getMinLength()` **and** `getLengthValidationRules()`. The
port's declares only
[`getLengthValidationConstraints(): list<Constraint>`](../../../packages/forms/src/Components/Contracts/CanBeLengthConstrained.php),
because that is the only method anything type-checks against: the `instanceof`
test in
[`CanBeValidated::getValidationConstraints()`](../../../packages/forms/src/Components/Concerns/CanBeValidated.php)
exists to know whether a field *has* length constraints to fold in, and the three
getters are read by the templates through the concrete field. Narrowing keeps the
contract honest about its one consumer; widening it later is additive. The
`getLengthValidationRules()` → `getLengthValidationConstraints()` rename is the
[rule → constraint vocabulary](../renames.md), not a shape change.
