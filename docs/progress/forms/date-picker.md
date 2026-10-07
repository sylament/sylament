---
description: 'The date-only field: a `DateTimePicker` whose `hasTime()` is a constant `false`.'
ports:
  - vendor/filament/forms/src/Components/DatePicker.php
owns:
  - packages/forms/src/Components/DatePicker.php
---
# DatePicker (forms)

`Sylament\Forms\Components\DatePicker` is a [`DateTimePicker`](date-time-picker.md) whose `hasTime()` is a constant `false`, so it renders `<input type="date">`, formats state as `Y-m-d`, emits no default `step`, and [truncates a bound carrying a time](date-time-picker.md#a-date-only-control-truncates-its-bounds-for-the-attribute-but-not-for-the-constraint) for its native `min` / `max` attributes.

Everything else is the parent's and is tracked in [date-time-picker.md](date-time-picker.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`hasTime()`](../../../packages/forms/src/Components/DatePicker.php), constant `false` | [`DatePicker::hasTime()`](../../../vendor/filament/forms/src/Components/DatePicker.php) | done | — | [semantics](date-time-picker.md#a-date-only-control-truncates-its-bounds-for-the-attribute-but-not-for-the-constraint) |
