---
description: 'The date-only field: a `DateTimePicker` whose `hasTime()` is a constant `false`, so it renders `<input type="date">`, formats state as `Y-m-d`, emits… (draft)'
ports:
  - vendor/filament/forms/src/Components/DatePicker.php
owns:
  - packages/forms/src/Components/DatePicker.php
---
# DatePicker (forms)

`Sylament\Forms\Components\DatePicker` — the date-only field: a [`DateTimePicker`](date-time-picker.md) whose `hasTime()` is a constant `false`, so it renders `<input type="date">`, formats state as `Y-m-d`, emits no `step`, and [truncates a bound carrying a time](date-time-picker.md#a-date-only-control-truncates-its-bounds-for-the-attribute-but-not-for-the-constraint) for its native `min` / `max` attributes.

Everything else — the formats, the bounds, the timezone, the state cast, the validity constraint, the template, and the `native(false)` gap — is the parent's and is tracked in [date-time-picker.md](date-time-picker.md).

## Surface

_Detail_ links the parent's file, where every inherited row lives.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`hasTime()`](../../../packages/forms/src/Components/DatePicker.php) — constant `false` | [`DatePicker::hasTime()`](../../../vendor/filament/forms/src/Components/DatePicker.php) | done | — | [detail](date-time-picker.md) |
| Everything inherited | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | in progress | — | [detail](date-time-picker.md) |

Coverage: the type, both formats, the absent step, the truncated bound and the always-on validity constraint by [`DateTimePickerTest`](../../../packages/forms/tests/Unit/Components/DateTimePickerTest.php), which asserts each variant beside the others rather than in a file of its own — the class is one overridden method, and the behaviour worth pinning is the difference from its parent.
