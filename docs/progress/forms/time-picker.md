---
description: 'The time-only field: a `DateTimePicker` whose `hasDate()` is a constant `false`.'
ports:
  - vendor/filament/forms/src/Components/TimePicker.php
owns:
  - packages/forms/src/Components/TimePicker.php
---
# TimePicker (forms)

`Sylament\Forms\Components\TimePicker` is a [`DateTimePicker`](date-time-picker.md) whose `hasDate()` is a constant `false`, so it renders `<input type="time">` and formats state as `H:i:s`, or `H:i` under `seconds(false)`.

Everything else is the parent's and is tracked in [date-time-picker.md](date-time-picker.md).
It validates as a date too, where [Filament leaves a time-only picker unvalidated](date-time-picker.md#every-variant-validates-as-a-date-including-the-time-only-one).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`hasDate()`](../../../packages/forms/src/Components/TimePicker.php), constant `false` | [`TimePicker::hasDate()`](../../../vendor/filament/forms/src/Components/TimePicker.php) | done | — | [diverges](date-time-picker.md#every-variant-validates-as-a-date-including-the-time-only-one) |
