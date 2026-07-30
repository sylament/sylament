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

Coverage: the empty default, both constraint builds, the false-condition skip and
the message hand-off by
[`CanBeAcceptedTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeAcceptedTest.php)
against the
[`FixtureBooleanField`](../../../packages/forms/tests/Fixtures/Components/FixtureBooleanField.php)
fixture.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### The accepted and declined rules become IsTrue and IsFalse

Filament writes `rule('accepted', $condition)` / `rule('declined', $condition)`.
Laravel's `accepted` rule passes for `"yes"`, `"on"`, `1`, `"1"` and `true`, and
`declined` for their negative counterparts; Symfony's
[`IsTrue`](https://symfony.com/doc/current/reference/constraints/IsTrue.html) and
`IsFalse` are strict identity checks against `true` / `false` (each also passing
on `null`). This is the
[rule → constraint vocabulary](../renames.md#laravel-rule-vocabulary-becomes-symfony-constraint-vocabulary),
not a new mechanism.

The narrowing is harmless for both consumers here, because `Checkbox` and
`Toggle` cast their state through `BooleanStateCast(isNullable: false)` before
validation runs, so the validated value is always a real `bool`. It is a real
narrowing for any future consumer that does not cast — such a field must cast, or
accept strict semantics.

The constraint is registered as a **closure** rather than an instance, so
`getValidationMessages()` is read when the constraint list is built rather than
when `accepted()` is called: a `validationMessages(['accepted' => …])` call made
*after* the `accepted()` call still lands. The message keys are `accepted` and
`declined`.
