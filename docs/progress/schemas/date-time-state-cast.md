# DateTimeStateCast (schemas)

`Sylament\Schemas\State\Casts\DateTimeStateCast` — the state cast behind a field
whose value is a moment in time. `get()` turns the wire string an
`<input type="date">`/`time`/`datetime-local` submits into whatever the record
wants at that path — a `DateTimeImmutable`, a `DateTime`, or a string in the
record's own format; `set()` writes any of those back as the string the input
speaks.

`DateTimePicker::getDefaultStateCasts()` builds it (forms, M3·2), passing
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
| `get()` string parse | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [diverges](#carbon-is-replaced-by-a-strict-then-lenient-php-parse) |
| `get()` timezone conversion | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [semantics](#the-timezone-conversion-is-asymmetric-on-purpose) |
| `get()` typed output | — | done | — | [diverges](#the-cast-produces-the-records-object-because-nothing-downstream-will) |
| `set()` string parse | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [diverges](#carbon-is-replaced-by-a-strict-then-lenient-php-parse) |
| `set()` timezone conversion | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [semantics](#the-timezone-conversion-is-asymmetric-on-purpose) |
| Missing-field fill | [`DateTimeStateCast.php`](../../../vendor/filament/schemas/src/Components/StateCasts/DateTimeStateCast.php) | done | — | [diverges](#missing-format-fields-pin-to-midnight-rather-than-to-now) |
| Non-parseable / wrong-typed fail-closed guard | — | done | — | [diverges](#a-value-no-parse-accepts-fails-closed-in-both-directions) |

Coverage: both blank arms, the object and string arms of each direction, the
timezone conversion in each direction, all three `objectClass` outcomes
(`DateTimeImmutable`, `DateTime`, the format string), the midnight fill, the
time-only format, the two lenient-parse variants a browser produces, and the
fail-closed arms for an unparseable string and a non-string scalar — by
[`DateTimeStateCastTest`](../../../packages/schemas/tests/Unit/State/Casts/DateTimeStateCastTest.php),
whose helper defaults `timezone` to `date_default_timezone_get()` so only the
two timezone cases name a zone and the rest read as pure format work.

## Semantics

### The timezone conversion is asymmetric on purpose

Both directions convert a string they parse, and only one converts an object
they are handed:

- `set(DateTimeInterface)` **converts** — the value came off the record, which
  speaks the application's default zone, and the wire speaks the field's zone.
- `get(DateTimeInterface)` **does not** — the value is already record-side, so
  the only thing left to do is normalise its PHP type. Re-reading its wall time
  as field-local would shift a moment that was never on the wire.

The string arms follow from the same rule: `get()` parses in the field's zone
and then converts to the default one, `set()` parses in the default zone and
converts to the field's.

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

### Carbon is replaced by a strict-then-lenient PHP parse

Filament parses with `Carbon::createFromFormat()` and falls back to
`Carbon::parse()`, catching `InvalidFormatException` on each. The shape ports
directly onto PHP's own API — `DateTimeImmutable::createFromFormat()` returning
`false` is the first failure, `new DateTimeImmutable()` throwing is the second —
so the port keeps the two-stage structure and drops the dependency. Both stages
are given the same zone, which is the parse-time half of the conversion rule
above.

### Missing format fields pin to midnight rather than to now

The strict parse prefixes the format with `!`, which resets every field the
format does not mention. Filament passes the format unprefixed, so PHP fills the
unmentioned fields from the current time: a `Y-m-d` field parsed at 23:30 in one
zone carries a time that lands on the *next* day once converted to another, and
the same input parsed twice within a request can disagree.

Pinning them makes the cast a pure function of its input. Nothing is lost
through a Doctrine `DATE` or `TIME` column, which discards the fields the format
omitted anyway, and a field that wants a time asks for one in its format.

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
