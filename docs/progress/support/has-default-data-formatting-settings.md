---
description: The eight toolkit-wide formatting defaults a date, money or number formatter falls back to when the component that renders it names none.
ports:
  - vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php
owns:
  - packages/support/src/Concerns/HasDefaultDataFormattingSettings.php
---
# HasDefaultDataFormattingSettings (support)

`Sylament\Support\Concerns\HasDefaultDataFormattingSettings` ports Filament's trait method for method: four `string|Closure` display formats, three `iso*` twins and a nullable `defaultNumberLocale`, each with a fluent setter and an `evaluate()`ing getter.
It sits in support's root `Concerns/`, not under `Components/Concerns/`, because what composes it is a root object rather than a nested component: Filament puts it on `Table` and `Schema`, and the port composes it on [`Tables\Table`](../tables/table.md).
`TextColumn`'s date, money and numeric families, the summarizers' money and numeric formatting, a [`date()` group's title](../tables/grouping.md#a-date-group-is-a-half-open-day-range-bound-with-the-fields-own-type) and [`Range::minimalDateTimeDifference()`](../tables/summarizer.md) read the five settings that are not `iso*` off the table.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`defaultCurrency()` / `getDefaultCurrency()`](../../../packages/support/src/Concerns/HasDefaultDataFormattingSettings.php) | [`HasDefaultDataFormattingSettings`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | done | — | [semantics](#the-currency-default-is-a-lowercase-code-and-stays-one) |
| [`defaultDateDisplayFormat()` / `defaultDateTimeDisplayFormat()` / `defaultTimeDisplayFormat()` and their getters](../../../packages/support/src/Concerns/HasDefaultDataFormattingSettings.php) | [`HasDefaultDataFormattingSettings`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | done | — | [semantics](#the-three-display-formats-are-php-format-strings-because-the-reader-is-datetimeimmutableformat) |
| [`defaultNumberLocale()` / `getDefaultNumberLocale()`](../../../packages/support/src/Concerns/HasDefaultDataFormattingSettings.php) | [`HasDefaultDataFormattingSettings`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | done | — | [semantics](#a-null-number-locale-means-ask-the-process-not-no-locale) |
| [`defaultIsoDateDisplayFormat()` / `defaultIsoDateTimeDisplayFormat()` / `defaultIsoTimeDisplayFormat()` and their getters](../../../packages/support/src/Concerns/HasDefaultDataFormattingSettings.php) | [`HasDefaultDataFormattingSettings`](../../../vendor/filament/support/src/Concerns/HasDefaultDataFormattingSettings.php) | done | — | [semantics](#the-three-iso-defaults-ship-with-no-reader) |
| Composition on `Schema` | [`Schemas\Schema`](../../../vendor/filament/schemas/src/Schema.php) | blocked | infolists' `TextEntry` | [detail](#schema-composes-the-trait-once-infolists-textentry-reads-it) |

## Details

### `Schema` composes the trait once infolists' `TextEntry` reads it

Filament's `Schema` composes the trait beside `Table`, because an infolist's `TextEntry` formats dates and numbers with the same defaults.
The port has no `TextEntry`, so nothing on a schema would read the settings; the composition lands with it.

## Semantics

### The currency default is a lowercase code, and stays one

`'usd'`, exactly as Filament ships it — not `'USD'`.
ICU's `NumberFormatter::formatCurrency()` accepts either case, so the lowercase default is harmless where it is read, and changing it would silently break a consumer comparing `getDefaultCurrency()` against Filament's documented value.

### The three display formats are PHP format strings, because the reader is `DateTimeImmutable::format()`

`'M j, Y'`, `'M j, Y H:i:s'` and `'H:i:s'` are byte-identical to Filament's, which is not a coincidence: Carbon's `translatedFormat()` takes PHP `date()` tokens too, and only differs from `format()` by translating month and day names.
The port reads them through `DateTimeImmutable::format()`, so English output matches Carbon's exactly and every other locale waits on the [deferred i18n pass](../../internals/i18n.md), recorded at [`text-column.md`](../tables/text-column.md).

### A null number locale means "ask the process", not "no locale"

`getDefaultNumberLocale()` is the only nullable setting, and `null` is the shipped default.
Its readers, `TextColumn`'s `money()` and `numeric()` and the summarizers' formatting, chain `$locale ?? $table->getDefaultNumberLocale() ?? \Locale::getDefault()`, and Symfony's `Request::setLocale()` keeps `\Locale::getDefault()` in step with the request locale, so an unset default follows the request rather than pinning `en`.
Filament's tail is `config('app.locale')`, which is the same intent one framework over.

### The three `iso*` defaults ship with no reader

`'L'`, `'LLL'` and `'LT'` are moment.js locale presets, not PHP format strings: handed to `DateTimeImmutable::format()`, `L` is the leap-year flag and `T` the timezone abbreviation, so `'LT'` renders `1UTC` for a date in 2024.
They are carried because the setters are part of Filament's public surface and a migrating consumer's `defaultIsoDateDisplayFormat()` call must not fatal; the `isoDate()` family that reads them is [blocked on i18n](../tables/text-column.md).
