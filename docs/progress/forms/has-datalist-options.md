---
description: The concern behind the native `<datalist>`, the `datalist()` setter and the `getDatalistOptions()` reader the field templates render suggestions from.
ports:
  - vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php
owns:
  - packages/forms/src/Components/Concerns/HasDatalistOptions.php
---
# HasDatalistOptions (forms)

`Sylament\Forms\Components\Concerns\HasDatalistOptions` is composed by [`TextInput`](text-input.md) and [`DateTimePicker`](date-time-picker.md), whose templates render the browser-side suggestions it holds.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`datalist()` / `getDatalistOptions()`](../../../packages/forms/src/Components/Concerns/HasDatalistOptions.php) | [`Concerns/HasDatalistOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php) | done | — | [diverges](#the-datalist-drops-the-arrayable-arm) |
| [The `<datalist>` element and the input's `list` attribute](../../../packages/forms/templates/partials/TextInput/index.html.twig) — and `DateTimePicker`'s | [`TextInput::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |

## Divergences

### The datalist drops the `Arrayable` arm

Filament types `datalist()` as `array<string> | Arrayable | Closure | null` and `getDatalistOptions()` unwraps an `Illuminate\Contracts\Support\Arrayable` with `->toArray()`.
That interface is Laravel's, with no Symfony counterpart and no port here, so the type is `array<string>|Closure|null` and the getter has no `instanceof` branch.
A caller holding a collection-like object passes `$collection->toArray()`, or a closure returning it, which is what the `Closure` arm is for.
`HasOptions` [drops the same arm](has-options.md#the-arrayable-arm-is-dropped) for the same reason.
