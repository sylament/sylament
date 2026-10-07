---
description: 'The concern behind the native `<datalist>`: the `datalist()` setter and the `getDatalistOptions()` reader the field templates loop over to render… (draft)'
ports:
  - vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php
owns:
  - packages/forms/src/Components/Concerns/HasDatalistOptions.php
---
# HasDatalistOptions (forms)

`Sylament\Forms\Components\Concerns\HasDatalistOptions` — the concern behind the native `<datalist>`: the `datalist()` setter and the `getDatalistOptions()` reader the field templates loop over to render browser-side suggestions.
Two fields compose it, [`TextInput`](text-input.md) and [`DateTimePicker`](date-time-picker.md), so it is promoted out of the former's fold and keeps a row of its own.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`datalist()` / `getDatalistOptions()`](../../../packages/forms/src/Components/Concerns/HasDatalistOptions.php) | [`Concerns/HasDatalistOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php) | done | — | [diverges](#the-datalist-drops-the-arrayable-arm) |
| The `<datalist>` element and the input's `list` attribute | [`TextInput::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |

Coverage: the absent default, a rendered list the input points at, and closure options resolved through `evaluate()` by [`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php); the same three on a date control by [`Integration/DateTimePickerTest`](../../../packages/forms/tests/Integration/Components/DateTimePickerTest.php).

Both templates key the element `<field id>-list` and gate the element and the `list` attribute on one truthiness check, so a field with no options renders neither — Filament's own shape.

## Divergences

### The datalist drops the `Arrayable` arm

Filament types `datalist()` as `array<string> | Arrayable | Closure | null` and `getDatalistOptions()` unwraps an `Illuminate\Contracts\Support\Arrayable` with `->toArray()`.
That interface is Laravel's, with no Symfony counterpart and no port here, so the type is `array<string>|Closure|null` and the getter has no `instanceof` branch.
A caller holding a collection-like object passes `$collection->toArray()` — or a closure returning it — which is what the `Closure` arm is for.
`HasOptions` [drops the same arm](has-options.md#the-arrayable-arm-is-dropped) for the same reason.
