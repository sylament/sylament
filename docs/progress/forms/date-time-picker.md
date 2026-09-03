# DateTimePicker (forms)

`Sylament\Forms\Components\DateTimePicker` — the date-and-time field, extending
[`Field`](field.md). It ships Filament's **native** arm only: the control is the
browser's `datetime-local` input, the wire carries the string that input speaks,
and a [`DateTimeStateCast`](../schemas/date-time-state-cast.md) turns that string
into whatever the bound record's write-side type declares. Its two thin
subclasses drop one half each — [`DatePicker`](date-picker.md) has no time,
[`TimePicker`](time-picker.md) no date — and all three share this file's surface
and one template.

The JS calendar behind `native(false)` — the Alpine panel, `displayFormat()`,
`disabledDates()`, `firstDayOfWeek()` and the rest of its family — is not ported;
it is a JS vertical of its own, [recorded as this epic's one
gap](../roadmap.md#m3--forms-widening). Declaring `native(false)` is therefore a
[fail-fast throw](can-be-native.md#nativefalse-throws-rather-than-falling-back-to-the-native-control)
rather than a silent fallback.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`forms/src/Components/DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php)
  — the field and every trait it composes
- [`forms/src/Components/Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php)
  — the native/JS switch
- [`schemas/src/Components/StateCasts/DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php)
  — the Carbon cast this field appends, ported in
  [date-time-state-cast.md](../schemas/date-time-state-cast.md)
- [`forms/resources/css/components/date-time-picker.css`](../../../vendor/filament/forms/resources/css/components/date-time-picker.css)
  — the stylesheet, of which only the WebKit rule applies to a native control

## Surface

Every point this field adds over `Field`. `Field`'s own surface (label, hint,
helper text, placeholder, required, slots, rendering chrome) is tracked in
[`field.md`](field.md), not here. _Blocked by_ names what has to land first.
_Detail_ links a permanent note in [Semantics](#semantics) or
[Divergences](#divergences), a **detail** write-up in [Details](#details), or
another file.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`format()` / `getFormat()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [semantics](#format-is-the-record-side-shape-and-getinternalformat-the-wire-side-one) |
| [`getInternalFormat()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [semantics](#format-is-the-record-side-shape-and-getinternalformat-the-wire-side-one) · [semantics](#the-wire-format-uses-a-space-and-the-browser-answers-with-a-t) |
| [`date()` / `hasDate()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | — |
| [`time()` / `hasTime()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | — |
| [`seconds()` / `hasSeconds()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | — |
| [`getType()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [diverges](can-be-native.md#nativefalse-throws-rather-than-falling-back-to-the-native-control) |
| [`minDate()` / `getMinDate()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [diverges](#the-bounds-are-date-comparison-constraints-not-laravel-rule-strings) · [semantics](#a-bound-no-date-parse-accepts-throws-where-a-sibling-reference-is-skipped) |
| [`maxDate()` / `getMaxDate()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [diverges](#the-bounds-are-date-comparison-constraints-not-laravel-rule-strings) · [semantics](#a-bound-no-date-parse-accepts-throws-where-a-sibling-reference-is-skipped) |
| The native `min` / `max` attributes | [`DateTimePicker::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [semantics](#a-date-only-control-truncates-its-bounds-for-the-attribute-but-not-for-the-constraint) · [semantics](#a-bound-no-date-parse-accepts-throws-where-a-sibling-reference-is-skipped) |
| [`timezone()` / `getTimezone()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [diverges](#the-timezone-default-is-phps-own-there-is-no-filamenttimezone-facade) |
| [`hoursStep()` / `minutesStep()` / `secondsStep()` + getters](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [semantics](#the-time-steps-compose-into-one-native-step-in-seconds) |
| [`getStep()`](../../../packages/forms/src/Components/DateTimePicker.php) — the [`HasStep`](has-step.md) override | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [semantics](#the-time-steps-compose-into-one-native-step-in-seconds) |
| [`getDefaultStateCasts()`](../../../packages/forms/src/Components/DateTimePicker.php) | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [diverges](#the-cast-writes-an-object-when-the-record-declares-one) |
| [`getDateTimeObjectClass()`](../../../packages/forms/src/Components/DateTimePicker.php) — no Filament counterpart | — | done | — | [diverges](#the-cast-writes-an-object-when-the-record-declares-one) |
| Date validity (`setUp()`'s always-on [`ValidDate`](can-be-validated.md#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings)) | [`DateTimePicker::setUp()`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) — `rule('date', hasDate())` | done | — | [diverges](#every-variant-validates-as-a-date-including-the-time-only-one) |
| [`CanBeNative`](can-be-native.md) | [`Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php) | done | — | [detail](can-be-native.md) |
| `native(false)` — the JS calendar control | [`DateTimePicker::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical ([roadmap](../roadmap.md#m3--forms-widening)) | [diverges](can-be-native.md#nativefalse-throws-rather-than-falling-back-to-the-native-control) |
| [`CanBeReadOnly`](can-be-read-only.md) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [detail](can-be-read-only.md) |
| [`HasStep`](has-step.md) | [`Concerns/HasStep.php`](../../../vendor/filament/forms/src/Components/Concerns/HasStep.php) | done | — | [detail](has-step.md) |
| [`HasDatalistOptions`](has-datalist-options.md) | [`Concerns/HasDatalistOptions.php`](../../../vendor/filament/forms/src/Components/Concerns/HasDatalistOptions.php) | done | — | [detail](has-datalist-options.md) |
| [`HasExtraInputAttributes`](has-extra-input-attributes.md) | [`Concerns/HasExtraInputAttributes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasExtraInputAttributes.php) | done | — | [detail](has-extra-input-attributes.md) |
| [`HasPlaceholder`](field.md) — already on `Field` here | [`Concerns/HasPlaceholder.php`](../../../vendor/filament/forms/src/Components/Concerns/HasPlaceholder.php) | done | — | [detail](field.md) |
| [`HasAffixes`](../../../packages/forms/src/Components/Concerns/HasAffixes.php) (+ its contract) | [`Concerns/HasAffixes.php`](../../../vendor/filament/forms/src/Components/Concerns/HasAffixes.php) | done | — | [detail](has-affixes.md) |
| `HasExtraAlpineAttributes` | [`support/Concerns/HasExtraAlpineAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAlpineAttributes.php) | skipped | — | [diverges](#the-alpine-and-published-view-surface-has-no-counterpart), [diverges](../support/component-attribute-bag.md#extra-alpine-attributes-are-not-ported) |
| `$publishedViewOverrideCheckPath` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | skipped | — | [diverges](#the-alpine-and-published-view-surface-has-no-counterpart) |
| `displayFormat()` / `getDisplayFormat()` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [detail](#the-js-calendar-family-waits-on-a-js-vertical-of-its-own) |
| The five `default*DisplayFormat()` pairs | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [detail](#the-js-calendar-family-waits-on-a-js-vertical-of-its-own) |
| `firstDayOfWeek()` / `getFirstDayOfWeek()` / `resetFirstDayOfWeek()` / `weekStartsOnMonday()` / `weekStartsOnSunday()` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [detail](#the-js-calendar-family-waits-on-a-js-vertical-of-its-own) |
| `locale()` / `getLocale()` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [detail](#the-js-calendar-family-waits-on-a-js-vertical-of-its-own) |
| `disabledDates()` / `getDisabledDates()` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [detail](#the-js-calendar-family-waits-on-a-js-vertical-of-its-own) |
| `defaultFocusedDate()` / `getDefaultFocusedDate()` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [detail](#the-js-calendar-family-waits-on-a-js-vertical-of-its-own) |
| `closeOnDateSelection()` / `shouldCloseOnDateSelection()` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [detail](#the-js-calendar-family-waits-on-a-js-vertical-of-its-own) |
| `extraTriggerAttributes()` / `getExtraTriggerAttributes()` / `getExtraTriggerAttributeBag()` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [detail](#the-js-calendar-family-waits-on-a-js-vertical-of-its-own) |
| `icon()` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | skipped | — | [diverges](#the-deprecated-surface-is-not-ported) |
| `withoutDate()` / `withoutSeconds()` / `withoutTime()` | [`DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | skipped | — | [diverges](#the-deprecated-surface-is-not-ported) |
| [The template](../../../packages/forms/templates/partials/DateTimePicker/index.html.twig) | [`DateTimePicker::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) — the `$isNative` branch | done | — | [semantics](#the-value-attribute-reads-the-raw-state-not-the-cast-one) |
| [The WebKit height rule](../../../packages/forms/assets/src/styles/components/input.css) | [`date-time-picker.css`](../../../vendor/filament/forms/resources/css/components/date-time-picker.css) | done | — | [detail](styles.md#the-date-time-picker-stylesheet-is-one-native-rule-and-a-calendar-panel) |
| [`required` drops when concealed](../../../packages/forms/templates/partials/DateTimePicker/index.html.twig) | [`toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | done | — | [can-be-concealed.md](../schemas/can-be-concealed.md) |

Coverage: the field's own logic — the three variants' `getType()`, the type
derived from `date(false)` / `time(false)`, the `native(false)` throw, both
formats across every variant plus an explicit `format()`, the step composition
(seconds, minutes, hours, `seconds(false)`, date-only, an explicit `step()`), the
timezone default and override, both bounds in their string and
`DateTimeInterface` spellings, the appended state cast and the formats it carries,
the always-on `ValidDate` on all three variants, the bounds as `DateComparison`
constraints, the truncated-versus-verbatim `min` attribute and the throw an
unparseable bound raises — by
[`DateTimePickerTest`](../../../packages/forms/tests/Unit/Components/DateTimePickerTest.php),
with the `native()` switch itself by
[`CanBeNativeTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeNativeTest.php).
The rendered control, the validation run through a live host and the record
round-trip against `DateTimeImmutable`, `DateTime`, `DateTimeInterface` and
`string` properties are covered by
[`Integration/DateTimePickerTest`](../../../packages/forms/tests/Integration/Components/DateTimePickerTest.php);
the frame the write-side class is resolved in — a picker inside a subtree bound
to another record reading that record's types, one outside it still reading the
root, and a memoized path escaping the frame resolving nothing — by
[`DateTimePickerRecordFrameTest`](../../../packages/forms/tests/Integration/Components/DateTimePickerRecordFrameTest.php).

## Details

Specifics for rows that are not finished — what is in, what is left, the next
step. Delete a subsection when its row flips to **done**.

### The JS calendar family waits on a JS vertical of its own

Ten members exist only to configure the Alpine calendar panel Filament renders
when `native(false)`: the display format (and its five `default*DisplayFormat`
fallbacks), the first day of the week, the locale, the disabled dates, the
initially focused date, the close-on-selection flag, and the extra attributes for
the panel's trigger button. None of them changes a native control — the browser
owns the panel, its formatting and its locale — so porting the setters ahead of
the panel would ship API that silently does nothing, which is
[what the throw exists to prevent](can-be-native.md#nativefalse-throws-rather-than-falling-back-to-the-native-control).

**Next step:** the calendar itself — a Stimulus controller and a panel template
under `packages/forms/assets/` plus `templates/partials/DateTimePicker/`, with
`isNative()` choosing between the two markup branches exactly as Filament's
`toEmbeddedHtml()` does. Everything the panel needs from PHP is already
reachable: the flags, the formats, the bounds and the steps all ship. The
`extraTriggerAttributes()` pair additionally wants an attribute-bag getter of the
[`HasExtraInputAttributes`](has-extra-input-attributes.md) shape, and
`displayFormat()` wants a PHP → JS date-format translation
(Filament's `convert_date_format()` helper), neither of which exists yet.

## Semantics

Faithful-in-spirit behaviour a reader would plausibly misread from the source.
Permanent — these outlive the detail sections above.

### `format()` is the record-side shape and `getInternalFormat()` the wire-side one

Two format methods sit next to each other and it is easy to read them as a
setter/getter pair. They are not. `getInternalFormat()` is the shape the
**control** speaks — what a `datetime-local` input puts on the wire and what the
`value` attribute must carry back — and it is derived, never set. `getFormat()`
is the shape the **record** wants when it holds a string rather than a date
object, and it is the only one `format()` sets.

The pair is what the [state cast](../schemas/date-time-state-cast.md) is
constructed from, in that order: `set()` formats the record value with
`internalFormat` on the way to the input, `get()` parses the input with
`internalFormat` and, for a string-typed target, formats with `format`. So
`format('d.m.Y')` changes what a `?string $publishedAt` property receives and
changes nothing about the input, which keeps speaking `Y-m-d`. Both default to
the same string for every variant, which is why the distinction is invisible
until someone calls `format()`.

### The wire format uses a space and the browser answers with a `T`

`getInternalFormat()` returns `'Y-m-d H:i:s'` — a space — while the browser
normalises a `datetime-local` value to `2026-08-18T14:30`, with a `T` and often
without the seconds. So the string the server renders into `value` and the string
the client sends back are legally different spellings of the same instant, on
every request. This is Filament's format verbatim, and it works for the same
reason it works there: the cast's parse is
[strict first and lenient second](../schemas/date-time-state-cast.md), so the
`T` form and the dropped seconds both land on the lenient arm rather than on the
`null` a failed parse would produce. Do not "fix" the format to `Y-m-d\TH:i` — the
round trip is designed around the lenient arm, and a stricter format would only
move which of the two spellings needs it.

### The time steps compose into one native `step` in seconds

Filament's three step setters read like three independent attributes; the native
control has exactly one, measured in seconds. `getStep()` composes them,
first-match-wins in the order seconds → minutes → hours, each arm only counting
when it was set above its default of `1`: `secondsStep(30)` renders `step="30"`,
`minutesStep(5)` renders `step="300"`, `hoursStep(2)` renders `step="7200"`. An
explicit [`step()`](has-step.md) beats all three, a control with no time part
renders no `step` at all, and a control with time but no seconds renders none
either — the browser's own default for `datetime-local` is minute granularity,
which is exactly what `seconds(false)` means. Only when seconds are on and no
step was named does the field emit `step="1"`, which is what makes the seconds
field appear in the browser's control.

### A date-only control truncates its bounds for the attribute but not for the constraint

`minDate(new DateTimeImmutable('2026-01-01 08:00:00'))` on a
[`DatePicker`](date-picker.md) renders `min="2026-01-01"` — a `date` input rejects
a bound carrying a time — while the same call on a `DateTimePicker` renders the
bound whole. That is Filament's inline Blade preamble, ported into
`getExtraTemplateData()` because a Twig template has no place to hold it.

The **constraint** is not truncated. `minDate()` registers a
[`DateComparison`](can-be-validated.md#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings)
against `getMinDate()`, which keeps the time, so a date-only control whose bound
carries `08:00:00` accepts `2026-01-01` in the browser and rejects it on the
server. Filament has the identical split (its rule reads `getMinDate()` too), and
the resolution is the same there and here: give a date-only control a date-only
bound.

### A bound no date parse accepts throws where a sibling reference is skipped

`minDate('2026-13-45')` on a date-only control raises a `LogicException` naming
the field and the bound, out of `getBoundaryAttribute()` — the same parse that
truncates the bound above. Filament raises there too: its native branch reads
`Carbon::parse($minDate)->toDateString()` with nothing catching it. The
alternative is to swallow the parse failure to a `null`, and it is worse than it
looks — that renders no `min` attribute, and the constraint half is silent too
(below), so a typo in a bound produces a picker with no bound, no error and no
log entry, invisible until someone compares the rendered form against the code.

Two limits on the throw's reach, both deliberate. It fires only on a control
**without a time part**, because only that branch parses — a `datetime-local`
bound is passed to the attribute verbatim and the browser ignores what it cannot
read. And the constraint half stays silent whatever the bound: `minDate()`
registers an `afterOrEqual()` boundary, whose builder tests the string with
`strtotime()` and routes anything it cannot read to
[the field-name arm](can-be-validated.md#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings),
which resolves nothing and
[drops the constraint](can-be-validated.md#a-blank-sibling-skips-the-comparison-where-laravel-fails-the-field).
That skip is the designed behaviour for a sibling reference and is not narrowed
here; the attribute path is where a bad literal now surfaces.

### The `value` attribute reads the raw state, not the cast one

Every other field template renders `component.getState`; this one renders
`component.getRawState`. With a cast in play the two differ by definition —
`getState()` is the record-side value (a `DateTimeImmutable`, or a string in
[`getFormat()`](#format-is-the-record-side-shape-and-getinternalformat-the-wire-side-one)),
and only the raw state is the string the control speaks. Rendering `getState()`
would put an object through `attrs()` on every request. The `?? ''` beside it is
the idiomorph rule every input template follows: an unset field must render
`value=""` rather than dropping the attribute.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them. The `native(false)` throw is recorded with the
concern it belongs to, in
[can-be-native.md](can-be-native.md#nativefalse-throws-rather-than-falling-back-to-the-native-control),
since `Select` composes the same trait and takes the other branch.

### The cast writes an object when the record declares one

Filament's `DateTimeStateCast` is string-to-string in both directions — Eloquent's
own model casts turn the dehydrated string into a `Carbon` at the model layer, so
the schema never has to. There is no such layer here:
[`getData()`](../schemas/belongs-to-record.md) writes the cast's output straight
onto the entity property, so a string would reach a `?DateTimeImmutable` setter
and fatal.

So the port's cast takes a fourth constructor argument, `objectClass`, and this
field fills it from the record's own write-side type. `getDateTimeObjectClass()`
walks the same guard chain
[`isRequiredByRecord()`](can-be-validated.md#requirement-is-derived-from-the-records-write-side-types)
walks — [nearest record frame](../schemas/belongs-to-record.md#the-nearest-record-frame-is-the-first-node-above-a-component-that-binds-one)
→ that frame's record class → the field's path relative to it →
[type reader](../schemas/record-type-reader.md) — and returns the target's class
only when it is `DateTime` or `DateTimeImmutable`. Class and path come off one
walk **so the two agree**, which is what lets a picker inside a subtree bound to
a different record resolve against that record instead of asking the root about a
path it does not own. Anything else, including no record at all and a path that
escapes the frame, leaves it null and the cast writes Filament's string in
[`getFormat()`](#format-is-the-record-side-shape-and-getinternalformat-the-wire-side-one).
This is the first cast in the port whose *shape* is chosen from a record type
rather than declared by the caller.

A property typed with the **interface** `DateTimeInterface` is covered: the
reader [carves it out](../schemas/record-type-reader.md#objectclass-is-gated-on-instantiability-so-it-is-a-promise-not-a-hint)
and reports `DateTimeImmutable`, so that spelling writes an object like the
concrete one. One limit rides along and is accepted: a field that is never wired
into a schema — a bare `make()` in a test, or a showcase card with no record —
has no chain to walk, which is deliberate: the value on the wire of a
record-less form is the string its `$data` bag can hold.

### Every variant validates as a date, including the time-only one

Filament's `setUp()` registers `rule('date', fn ($component) => $component->hasDate())`,
so a `TimePicker` carries no validity rule at all. The port registers an
unconditional [`ValidDate`](can-be-validated.md#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings).

The condition is what makes Filament's version safe over there and unsafe here: a
raw value that no parse accepts makes the cast return `null`, and that null is
what `getData()` writes onto the record. A tampered `publishedAt` would silently
clear the column instead of failing validation — the same hole the
[option fields closed with an always-on `Choice`](select.md). `ValidDate` is
lenient in exactly the way the cast's fallback is (`new DateTimeImmutable($value)`),
so it rejects only what the cast would have nulled, and the
[presence gate](../schemas/presence-gate.md) still lets a cleared field through
as blank.

### The bounds are date comparison constraints, not Laravel rule strings

`minDate()` / `maxDate()` push `"after_or_equal:{$date}"` / `"before_or_equal:{$date}"`
rule strings in Filament. Here each registers a
[`DateComparison`](can-be-validated.md#the-date-comparison-family-builds-constraints-where-filament-builds-rule-strings)
through the same `afterOrEqual()` / `beforeOrEqual()` the user calls directly, so
the bound is validated by the one constraint pair the port ships rather than by a
second mechanism. Filament's laziness is kept exactly — the bound is a closure
reading `getMinDate()` back off the component, re-evaluated on every validation
run, and the pair's condition drops the constraint when the bound resolves blank.

One consequence is worth stating: because both bounds and the user-facing
`after()` family produce the same constraint class, a `validationMessages()` entry
keyed `DateComparison::class` reaches all of them at once. That is
[class-keyed messages](can-be-validated.md#validation-messages-apply-after-the-constraints-are-built)
working as designed, and it is the one place this field is less granular than
Filament's rule-name keys.

### The timezone default is PHP's own; there is no `FilamentTimezone` facade

`getTimezone()` is `evaluate($this->timezone) ?? date_default_timezone_get()`.
Filament's is `?? ($this->hasTime() ? FilamentTimezone::get() : config('app.timezone'))`
— a global, app-wide display timezone with a documented split: a control carrying
a time shows the *user's* timezone, a date-only control stays in the app's, since
shifting a bare date across zones moves the day.

Support ships no `TimezoneManager` yet, so there is one source and no split.
`date_default_timezone_get()` is the app timezone in a Symfony application, which
makes the port equal to Filament's **date-only** arm and equal to its time arm
too wherever no display timezone was configured — the common case. Reverses when
support ports `TimezoneManager`: the split returns as written above, and the
`timezone()` setter, which already wins over both, is unaffected either way.

### The deprecated surface is not ported

`icon()` (an alias for `suffixIcon(Heroicon::Calendar)`), `withoutDate()`,
`withoutSeconds()` and `withoutTime()` (each the inverse of the setter beside it)
are all marked `@deprecated` in Filament. No deprecated surface ships here —
a port that starts with the previous generation's aliases starts with a
migration debt it never earned. `date(false)` / `seconds(false)` / `time(false)`
are the spellings, and the icon is spelled `suffixIcon()` through
[`HasAffixes`](has-affixes.md).

### The Alpine and published-view surface has no counterpart

`HasExtraAlpineAttributes` adds `x-*` attributes to the control, and
`$publishedViewOverrideCheckPath` lets Filament warn when a user has published and
edited the Blade view this component would otherwise render. Neither has a target
here: Alpine translates to [Stimulus](../renames.md#idiom) rather than to an
attribute bag, and the port has no view-publishing mechanism to check against.
**Skipped**, both permanently.
