# HasDefaultDataFormattingSettings (support)

`Sylament\Support\Concerns\HasDefaultDataFormattingSettings` — the eight
toolkit-wide formatting defaults a date, money or number formatter falls back to
when the component that renders it names none. It ports Filament's trait method
for method: four `string|Closure` display formats, three `iso*` twins and a
nullable `defaultNumberLocale`, each with a fluent setter and an `evaluate()`ing
getter.

It sits in support's **root** `Concerns/`, not under `Components/Concerns/`,
because nothing that composes it is a nested component: Filament puts it on
`Table` and `Schema`, and this port composes it on
[`Tables\Table`](../tables/table.md) alone — `Schema` follows when infolists
ship `TextEntry`, which needs the same defaults.

Six of the eight settings gained their first reader with
[`TextColumn`](../tables/text-column.md)'s date, money and numeric families, and
grouping and summaries add two more readers of the same two date formats off the same table:
a [`date()` group's title](../tables/grouping.md#a-date-group-is-a-half-open-day-range-bound-with-the-fields-own-type)
formats through `getDefaultDateDisplayFormat()`, and
[`Range::minimalDateTimeDifference()`](../tables/summarizer.md) picks between that
and `getDefaultDateTimeDisplayFormat()` by how far apart its two ends fall. The
three `iso*` ones are carried unread: their moment.js tokens (`L`, `LLL`, `LT`)
only ICU locale presets can answer, which belongs to the
[deferred i18n pass](../../internals/i18n.md), so
[`text-column.md`](../tables/text-column.md) blocks the `isoDate()` family on it
and this trait holds the defaults those setters will read.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Concerns/HasDefaultDataFormattingSettings.php`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php)
  — the trait ported here, member for member and default for default
- [`tables/src/Table.php`](../../../vendor/filament/tables/src/Table.php) — one of
  the two classes that compose it upstream

## Surface

Every point of this concern. _Blocked by_ names what has to land first. _Detail_
carries either or both: **detail** → the write-up in [Details](#details), deleted
once the row is **done**; **semantics** → the permanent note in
[Semantics](#semantics).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`defaultCurrency()` / `getDefaultCurrency()`](../../../packages/support/src/Concerns/HasDefaultDataFormattingSettings.php) | [`HasDefaultDataFormattingSettings`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | done | — | [semantics](#the-currency-default-is-a-lowercase-code-and-stays-one) |
| [`defaultDateDisplayFormat()` / `defaultDateTimeDisplayFormat()` / `defaultTimeDisplayFormat()` and their getters](../../../packages/support/src/Concerns/HasDefaultDataFormattingSettings.php) | [`HasDefaultDataFormattingSettings`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | done | — | [semantics](#the-three-display-formats-are-php-format-strings-because-the-reader-is-datetimeimmutableformat) |
| [`defaultNumberLocale()` / `getDefaultNumberLocale()`](../../../packages/support/src/Concerns/HasDefaultDataFormattingSettings.php) | [`HasDefaultDataFormattingSettings`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | done | — | [semantics](#a-null-number-locale-means-ask-the-process-not-no-locale) |
| [`defaultIsoDateDisplayFormat()` / `defaultIsoDateTimeDisplayFormat()` / `defaultIsoTimeDisplayFormat()` and their getters](../../../packages/support/src/Concerns/HasDefaultDataFormattingSettings.php) | [`HasDefaultDataFormattingSettings`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | done | — | [semantics](#the-three-iso-defaults-ship-with-no-reader) |
| Composition on `Schema` | [`Schemas\Schema`](../../../vendor/filament/schemas/src/Schema.php) | todo | infolists' `TextEntry` | — |

Every member is exercised by
[`HasDefaultDataFormattingSettingsTest`](../../../packages/support/tests/Unit/Concerns/HasDefaultDataFormattingSettingsTest.php)
— the eight defaults, a literal and a closure through every setter, and the
nullable locale taken back to `null` — over a fixture that composes the trait
with [`EvaluatesClosures`](component.md#surface), which is the trait's only
requirement of its host.

## Details

Every landed row is **done**; the one open row names the consumer that will need it
rather than a write-up.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### The currency default is a lowercase code, and stays one

`'usd'`, exactly as Filament ships it — not `'USD'`. ICU's
`NumberFormatter::formatCurrency()` accepts either case, so the lowercase default
is harmless where it is read, and changing it would silently break a consumer
comparing `getDefaultCurrency()` against Filament's documented value.

### The three display formats are PHP format strings, because the reader is `DateTimeImmutable::format()`

`'M j, Y'`, `'M j, Y H:i:s'` and `'H:i:s'` are byte-identical to Filament's,
which is not a coincidence: Carbon's `translatedFormat()` takes PHP `date()`
tokens too, and only differs from `format()` by translating month and day names.
The port reads them through `DateTimeImmutable::format()`, so English output
matches Carbon's exactly and every other locale waits on the
[deferred i18n pass](../../internals/i18n.md) — recorded at
[`text-column.md`](../tables/text-column.md).

### A null number locale means "ask the process", not "no locale"

`getDefaultNumberLocale()` is the only nullable setting, and `null` is the
shipped default. Its reader — `TextColumn`'s `money()` and `numeric()` — chains
`$locale ?? $table->getDefaultNumberLocale() ?? \Locale::getDefault()`, and
Symfony's `Request::setLocale()` keeps `\Locale::getDefault()` in step with the
request locale, so an unset default follows the request rather than pinning
`en`. Filament's tail is `config('app.locale')`, which is the same intent one
framework over.

### The three `iso*` defaults ship with no reader

`'L'`, `'LLL'` and `'LT'` are moment.js locale presets, not PHP format strings —
handing one to `DateTimeImmutable::format()` would emit literal text. They are
carried because the setters are part of Filament's public surface and a
migrating consumer's `defaultIsoDateDisplayFormat()` call must not fatal; the
`isoDate()` family that reads them is blocked on i18n.
