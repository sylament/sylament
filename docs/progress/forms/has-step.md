# HasStep (forms)

`Sylament\Forms\Components\Concerns\HasStep` — the concern behind the native
`step` attribute: the `step()` setter and the `getStep()` reader the field
templates emit from. Two fields compose it, [`TextInput`](text-input.md) and
[`DateTimePicker`](date-time-picker.md), so it is promoted out of the former's
fold and keeps a row of its own. Filament composes it on two more —
`Slider` and tables' `TextInputColumn` — neither of them ported; each picks the
concern up unchanged when it lands.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/Concerns/HasStep.php`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php)
  — the trait
- [`forms/src/Components/DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php)
  — `getStep()`, the one override in the port's two consumers

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Semantics](#semantics), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`step()` / `getStep()`](../../../packages/forms/src/Components/Concerns/HasStep.php) | [`Concerns/HasStep.php`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php) | done | — | [semantics](#the-step-is-a-declaration-a-consumer-may-derive-past) |
| The `step` attribute on each control | [`TextInput::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |

Coverage: the interval on a text input — seeded by
[`numeric()` and `integer()`](text-input.md#numeric-seeds-the-step-through-isnumeric-not-the-captured-condition),
set explicitly, and absent by default — by
[`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php);
the picker's composed interval, in all six of its arms, by
[`DateTimePickerTest`](../../../packages/forms/tests/Unit/Components/DateTimePickerTest.php).

The templates guard nothing:
[`ComponentAttributeBag`](../../../packages/support/src/Twig/ComponentAttributeBag.php)
drops a `null` value, so `step: component.getStep` already *is* the condition.

## Semantics

### The step is a declaration a consumer may derive past

The trait holds one nullable property and evaluates it, which reads like the
whole feature. It is not: both consumers put a *derivation* in front of it, and
in opposite directions.

[`TextInput`](text-input.md) **writes** the property from another setter —
`numeric()` seeds `'any'` and `integer()` seeds `1`, each through a closure that
reads its flag back — so `getStep()` stays the trait's, and the value it reads
was filled in by a sibling call.

[`DateTimePicker`](date-time-picker.md#the-time-steps-compose-into-one-native-step-in-seconds)
**overrides the getter** instead, composing its three time steps into one
interval in seconds, and consults `$this->step` first so an explicit `step()`
still wins. The property is never written behind the user's back there.

Both shapes are Filament's own. What they have in common is the invariant worth
stating: `step()` is the caller's last word in either field, and a field that
derives an interval must check the declared one first.
