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
the message hand-off, and — pinning the closure registration below — a message
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

Both halves reach the consumers here, because validation reads the **raw,
uncast** state rather than the cast one
([checkbox.md](checkbox.md#validation-reads-the-raw-state-so-the-boolean-type-constraint-is-live)):
`BooleanStateCast(isNullable: false)` normalizes what `state()` writes and what
`getState()` reads back, not what the validator sees. The normal path never
exercises the delta — a live checkbox binds `element.checked`, a real boolean,
and `fill()` seeds a real `false` — but a state bag written any other way does. A
raw `"yes"` fails `IsTrue` here where Laravel's `accepted` passes it (on
`Checkbox` and `Toggle` the `Type('bool')` constraint rejects it as well), and a
**missing key** reads back as `null` and passes both, so `accepted()` alone does
not make a field required — `required()` must say so.

The constraint is registered as a **closure** rather than an instance, so
`getValidationMessages()` is read when the constraint list is built rather than
when `accepted()` is called: a `validationMessages(['accepted' => …])` call made
*after* the `accepted()` call still lands. The message keys are `accepted` and
`declined`.
