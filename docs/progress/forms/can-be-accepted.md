# CanBeAccepted (forms)

`Sylament\Forms\Components\Concerns\CanBeAccepted` — the shared acceptance
concern the boolean fields compose. `accepted()` demands a true value and
`declined()` a false one, each registering a Symfony constraint for
[`CanBeValidated`](can-be-validated.md) to fold into the field's constraint list.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Concerns/CanBeAccepted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAccepted.php)
  — the trait

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `accepted()` | [`Concerns/CanBeAccepted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAccepted.php) | done | — | [diverges](#the-accepted-and-declined-rules-become-istrue-and-isfalse) |
| `declined()` | [`Concerns/CanBeAccepted.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeAccepted.php) | done | — | [diverges](#the-accepted-and-declined-rules-become-istrue-and-isfalse) |

Coverage: the empty default, both constraint builds, the false-condition skip,
the message hand-off, and — pinning the order-independence below — a message
set *after* the `accepted()` / `declined()` call, by
[`CanBeAcceptedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeAcceptedTest.php)
against the
[`FixtureBooleanField`](../../../packages/forms/tests/Fixtures/Components/FixtureBooleanField.php)
fixture. That the trait actually reaches its consumers is pinned separately, by
[`CheckboxTest`](../../../packages/forms/tests/Integration/Components/CheckboxTest.php).

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### The accepted and declined rules become IsTrue and IsFalse

Filament writes `rule('accepted', $condition)` / `rule('declined', $condition)`.
This is the
[rule → constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary),
not a new mechanism — but the two accepted-value sets are not identical, and the
difference is smaller and differently shaped than "strict identity" would
suggest.

Laravel's `accepted` passes for `"yes"`, `"on"`, `"1"`, `1`, `true` and
`"true"`; Symfony's
[`IsTrue`](https://symfony.com/doc/current/reference/constraints/IsTrue.html)
passes for `true`, `1`, `"1"` **and `null`**
([`IsTrueValidator.php`](../../../vendor/symfony/validator/Constraints/IsTrueValidator.php)),
with `IsFalse` mirroring it over `false`, `0`, `"0"` and `null`. So the delta
runs both ways: the port loses Laravel's word aliases (`"yes"` / `"on"` /
`"true"` and their negatives) and *gains* a `null` pass Laravel rejects. The
numeric and numeric-string forms are common to both.

Whether either half reaches a consumer turns on a mechanism neither name
suggests: the host validates the **raw** state property, so a state cast never
runs in front of validation
([the mechanism, in full](checkbox.md#validation-reads-the-raw-state-so-the-boolean-type-constraint-is-live)).
`BooleanStateCast(isNullable: false)` normalizes what `state()` writes and what
`getState()` reads back — not what the validator sees. What keeps the validated
value a real `bool` in practice is the client (`live_controller` sends
`element.checked` for a valueless checkbox) plus the mount-time `hydrateState()`
pass seeding the `false` default.

The two halves then land differently, and the asymmetry is the part to remember:

- **The narrowing is masked** on `Checkbox` and `Toggle`. A raw `"yes"` fails
  `IsTrue` where Laravel's `accepted` would pass it — but those fields' own
  `Type('bool')` constraint rejects the same value anyway, so the observable
  result is an extra message, not a changed verdict. It is a real narrowing only
  for a future consumer that is not a boolean field.
- **The `null` widening is live everywhere.** A missing key reads back as `null`,
  which `IsTrue` passes and `Type('bool')` also passes (Symfony's `TypeValidator`
  short-circuits on `null`). So `accepted()` alone does **not** make a field
  required — `required()` must say so.

The constraint is registered as a plain instance, and a message keys the class it
is: `validationMessages([IsTrue::class => …])` / `[IsFalse::class => …]`. Call
order does not matter — the
[post-pass](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built)
applies messages over the finished constraint list, so a `validationMessages()`
call made *after* `accepted()` / `declined()` still lands. These arms registered a
closure until that post-pass existed, purely to defer the message read.
