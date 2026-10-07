# TimePicker (forms)

`Sylament\Forms\Components\TimePicker` — the time-only field: a [`DateTimePicker`](date-time-picker.md) whose `hasDate()` is a constant `false`, so it renders `<input type="time">` and formats state as `H:i:s` — `H:i` under `seconds(false)`.

Everything else — the formats, the bounds, the timezone, the state cast, the validity constraint, the template, and the `native(false)` gap — is the parent's and is tracked in [date-time-picker.md](date-time-picker.md).
One of those rows is not inherited quietly: this variant validates as a date too, where [Filament leaves a time-only picker unvalidated](date-time-picker.md#every-variant-validates-as-a-date-including-the-time-only-one).

**Status:** in progress — see the [status vocabulary](../README.md#statuses); the parent's unfinished rows are inherited whole.

Reference — the Filament source this is diffed against:

- [`forms/src/Components/TimePicker.php`](../../../vendor/filament/forms/src/Components/TimePicker.php) — the subclass, which is this one method and nothing else

## Surface

_Detail_ links the parent's file, where every inherited row lives.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`hasDate()`](../../../packages/forms/src/Components/TimePicker.php) — constant `false` | [`TimePicker::hasDate()`](../../../vendor/filament/forms/src/Components/TimePicker.php) | done | — | [detail](date-time-picker.md) |
| Everything inherited | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | in progress | — | [detail](date-time-picker.md) |

Coverage: the type, both formats in their seconds and secondless spellings, and the always-on validity constraint by [`DateTimePickerTest`](../../../packages/forms/tests/Unit/Components/DateTimePickerTest.php), which asserts each variant beside the others rather than in a file of its own — the class is one overridden method, and the behaviour worth pinning is the difference from its parent.
