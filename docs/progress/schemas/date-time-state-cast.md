# DateTimeStateCast (schemas)

`Sylament\Schemas\State\Casts\DateTimeStateCast` — the state cast behind a field
whose value is a moment in time. `get()` turns the wire string an
`<input type="date">`/`time`/`datetime-local` submits into whatever the record
wants at that path — a `DateTimeImmutable`, a `DateTime`, or a string in the
record's own format; `set()` writes any of those back as the string the input
speaks.

`DateTimePicker::getDefaultStateCasts()` builds it (forms), passing
`format` (the record-side spelling), `internalFormat` (the wire spelling the
native control emits), `timezone` (the field's own zone) and `objectClass` —
the target type, read off
[`RecordTypeReader::describe()`](record-type-reader.md) so a record with a
`?DateTimeImmutable` property is written an instance while a card with no record
at all keeps a wire-safe string.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/StateCasts/DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php)

## Surface

_Detail_ links a permanent note in [Semantics](#semantics) or
[Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `$format` / `$internalFormat` / `$timezone` constructor | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | — |
| `$objectClass` constructor argument | — | done | — | [diverges](#the-cast-produces-the-records-object-because-nothing-downstream-will) |
| Blank handling, both directions | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | — |
| `get()` string parse | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [diverges](#carbon-is-replaced-by-a-strict-then-lenient-php-parse-and-get-gains-one) |
| `get()` timezone conversion | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [semantics](#the-timezone-conversion-is-asymmetric-on-purpose) · [diverges](#getdatetimeinterface-keeps-the-objects-wall-time-where-filament-reinterprets-it) |
| `get()` typed output | — | done | — | [diverges](#the-cast-produces-the-records-object-because-nothing-downstream-will) |
| `set()` string parse | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [diverges](#carbon-is-replaced-by-a-strict-then-lenient-php-parse-and-get-gains-one) |
| `set()` timezone conversion | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [semantics](#the-timezone-conversion-is-asymmetric-on-purpose) |
| Missing-field fill | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [diverges](#missing-format-fields-pin-to-midnight-and-a-date-less-format-to-today) |
| Non-parseable / wrong-typed fail-closed guard | — | done | — | [diverges](#a-value-no-parse-accepts-fails-closed-in-both-directions) |

Coverage: both blank arms, the object and string arms of each direction, the
timezone conversion in each direction, all three `objectClass` outcomes
(`DateTimeImmutable`, `DateTime`, the format string), the midnight fill, the
time-only format and the today anchor behind it, the two lenient-parse variants
a browser produces, and the fail-closed arms for an unparseable string and a
non-string scalar — by
[`DateTimeStateCastTest`](../../../packages/schemas/tests/Unit/State/Casts/DateTimeStateCastTest.php),
whose helper defaults `timezone` to `date_default_timezone_get()` so only the
zone-sensitive cases name a zone and the rest read as pure format work. The
anchor case is written season-proof — it compares against a `today 14:30` built
in the same zone rather than against a literal instant, so it does not flip
between EST and EDT twice a year.

## Semantics

### The timezone conversion is asymmetric on purpose

Both directions convert a string they parse, and only one converts an object
they are handed. The string arms are symmetric under one rule — parse in the
zone the value came from, convert to the zone it is going to: `get()` parses in
the field's zone and converts to the default one, `set()` parses in the default
zone and converts to the field's. `set(DateTimeInterface)` converts for the same
reason its string arm does — the value came off the record, which speaks the
application's default zone, and the wire speaks the field's.

`get(DateTimeInterface)` is the one arm that converts nothing, and that half is
a deliberate deviation from Filament rather than a consequence of the rule
above; it is recorded
[under Divergences](#getdatetimeinterface-keeps-the-objects-wall-time-where-filament-reinterprets-it).

### The internal format is a spelling, not a grammar

`internalFormat` is what the field *renders*, and a native control does not
promise to hand it back verbatim: browsers normalise a `datetime-local` value to
the `T` separator, and one with a seconds step emits seconds a `Y-m-d H:i`
internal format never asked for. Both are legal values of the same control, so
the strict `createFromFormat()` pass is followed by a lenient
`new DateTimeImmutable()` one, which accepts every ISO-ish variant of the same
moment.

Trailing data is an *error* to `createFromFormat()` rather than a warning, so
the seconds variant fails the strict pass and reaches the lenient one with its
seconds intact — a value more precise than the field's format keeps its
precision instead of being silently truncated.

## Divergences

Deliberate deviations from Filament, and the reason each holds.

### The cast produces the record's object because nothing downstream will

Filament's cast is string-to-string in both directions: `get()` returns
`$state->format($this->format)`, and Eloquent's own model casts turn that string
into a date object when the attribute is assigned. We have no such layer —
[`BelongsToRecord::getData()`](belongs-to-record.md) writes `get()`'s output
straight onto the property through `PropertyAccessor::setOnRecord()`, so a
string reaching a `?DateTimeImmutable` property is a `TypeError`, not a
conversion.

So `get()` returns the object itself, and the constructor grows a fourth
argument — `objectClass`, Sylament-only, appended last so the three Filament
arguments keep their positions. It takes `DateTimeImmutable::class`,
`DateTime::class` or `null`, and `null` is the Filament-parity arm: the cast
formats to `$format` exactly as the original does, which is what a schema with
no record behind it (a showcase card, a filter bag) needs, since the state bag
is a writable `array` LiveProp and the hydrator refuses an object inside one.

The field chooses that argument from the record's own type rather than from
configuration — see [`RecordTypeReader`](record-type-reader.md) — which is what
makes the same `DateTimePicker::make('publishedAt')` correct against an
immutable property, a mutable one and a string one without being told which it
faces.

### Carbon is replaced by a strict-then-lenient PHP parse, and `get()` gains one

The two-stage shape is **`set()`'s**. Filament's `set()` parses with
`Carbon::createFromFormat()` and falls back to `Carbon::parse()`, catching
`InvalidFormatException` on each; that ports directly onto PHP's own API —
`DateTimeImmutable::createFromFormat()` returning `false` is the first failure,
`new DateTimeImmutable()` throwing is the second — so the port keeps the
structure and drops the dependency. Both stages are given the same zone, which
is the parse-time half of the conversion rule above.

Filament's **`get()`** has no such shape to port: it is a single lenient
`Carbon::parse($state)`, with no format pass at all. The port routes both
directions through the same helper, so `get()` *gains* a strict `internalFormat`
pass Filament has no counterpart for. What each `get()` is handed is the reason.
Filament's only ever sees a record-side value — Eloquent hands it a `Carbon`,
and the string branch exists for values a model cast already shaped — while ours
reads the string the browser put on the wire, whose spelling is the one thing
the field knows in advance. Parsing it strictly first is what makes
`internalFormat` a parse contract rather than a render-only setting, and the
lenient arm behind it is what absorbs the `T` and the seconds the browser adds
([above](#the-internal-format-is-a-spelling-not-a-grammar)).

### `get(DateTimeInterface)` keeps the object's wall time where Filament reinterprets it

Filament's `get()` converts **both** arms: the parse is guarded by
`if (! $state instanceof CarbonInterface)`, but the two conversion lines —
`shiftTimezone($this->timezone)` then `setTimezone(config('app.timezone'))` —
run unconditionally after that block. `shiftTimezone()` keeps the clock reading
and swaps the zone behind it
(`vendor/nesbot/carbon/src/Carbon/Traits/Date.php:1889`), so an object handed
straight in is re-read as though its wall time had always been the field's.

The port converts the string arm only, and normalises the object arm's PHP type
without touching its zone. A `DateTimeInterface` reaching `get()` came off the
record, so its wall time is already the application's: shifting it does not
correct a zone, it moves the moment. An entity holding 14:30 UTC under a field
declaring `timezone('Europe/Bratislava')` would come back as 12:30 UTC — two
hours off a value that was never on the wire — which is what
[`DateTimeStateCastTest`](../../../packages/schemas/tests/Unit/State/Casts/DateTimeStateCastTest.php)
pins as "normalizes an already-typed value without reinterpreting its wall
time".

Filament can afford the symmetry because that arm is defensive over there: its
cast is string-to-string, so a `Carbon` only reaches `get()` when the caller
hands one in. Here the object arm is the mainstream path —
[`objectClass`](#the-cast-produces-the-records-object-because-nothing-downstream-will)
exists precisely so record-side objects flow through both directions — which is
what turns a defensive branch's behaviour into a deviation worth recording.

### Missing format fields pin to midnight, and a date-less format to today

The strict parse prefixes the format with `!`, which resets every field the
format does not mention. Filament's `set()` passes the format unprefixed (its
`get()` has no format pass at all), so PHP fills the unmentioned fields from the
current time: a `Y-m-d` field parsed at 23:30 in one zone carries a time that
lands on the *next* day once converted to another, and the same input parsed
twice within a request can disagree.

Pinning them makes the cast a pure function of its input, which is what every
format naming a date wants. It costs something in the one case where the format
names none: `!` pins the date to **1970-01-01**, and an offset is a property of
a date — `14:30` parsed in `America/New_York` is read at EST (−05:00) all year
round, so a summer value names an instant an hour off. So a format naming no
date field at all — a `TimePicker`'s `H:i:s` — has its date set to **today in
the parse zone** after the strict pass, which restores the offset in force now
while keeping the time-of-day the input carried. That gives back exactly as much
purity as it has to: a date-less parse becomes a function of its input *and* the
current day, and the day is the only part that moves. Formats naming a date are
untouched, and the detection strips escaped characters first, so `\Y` counts as
a literal letter rather than as a year. The lenient arm needs no anchor of its
own: `new DateTimeImmutable('14:30:00', $zone)` already lands on today.

What the anchor does not do is give a time-only value a meaningful date. A
Doctrine `TIME` column discards the date, and a `DATE` column the time, so
nothing the format omitted survives the column either way — but the conversion
runs *before* the column, and it is the offset the anchor is there to get right.

### A value no parse accepts fails closed in both directions

Filament's `get()` hands whatever survives `blank()` to `Carbon::parse()`, which
throws out of the cast for a tampered payload; its `set()` casts the state to
`string` first, so an array raises before the parse is even attempted.

Both directions return `null` here instead — the
[`EnumStateCast`](enum-state-cast.md#a-non-scalar-state-fails-closed-rather-than-fataling)
precedent. A `TypeError` escaping a state cast is a 500 on a request the
application was equipped to refuse, and the refusal has a home: the field ships
a `ValidDate` constraint over the raw state, so the payload is reported as
invalid before `getData()` ever reads the `null`.

Note this makes `set()` a guard rather than the passthrough
[`EnumStateCast::set()`](enum-state-cast.md#set-is-a-passthrough-not-a-guard)
is. The two casts differ in what they can honestly say about a value they do not
recognise: an enum cast only knows how to unwrap an enum and leaves anything
else for the next cast in the chain, whereas this one owns the whole conversion
to a date and has no answer but `null` for a value that is not one.
