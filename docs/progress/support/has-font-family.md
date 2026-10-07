---
description: The concern that gives a component a font family and the class a template emits for it.
ports:
  - vendor/filament/support/src/Concerns/HasFontFamily.php
owns:
  - packages/support/src/Components/Concerns/HasFontFamily.php
---
# HasFontFamily (support)

`Sylament\Support\Components\Concerns\HasFontFamily` ports Filament's trait of the same name; `getFontFamily()` normalizes a known string to its [`FontFamily`](../../../packages/support/src/Enums/FontFamily.php) case and passes any other string through.
Schemas' [`Text`](../schemas/text.md) and tables' [`TextColumn`](../tables/text-column.md) compose it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`fontFamily()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | [`HasFontFamily::fontFamily()`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | done | — | — |
| [`getFontFamily()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | [`HasFontFamily::getFontFamily()`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | done | — | [diverges](#getfontfamily-drops-the-state-parameter) |
| [`getFontFamilyClass()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | — (no Filament equivalent) | done | — | [diverges](#getfontfamilyclass-centralizes-the-class-ternary) |
| [`hasFontFamily()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | [`HasFontFamily::hasFontFamily()`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | done | — | — |

## Divergences

### `getFontFamily()` drops the `$state` parameter

Filament's `getFontFamily(mixed $state = null)` threads `'state' => $state` into `evaluate()`, so a `fn ($state) => …` closure can branch on one item of a text column's or entry's state.
Sylament's `getFontFamily()` takes no argument and evaluates with no injected state, for the reason and with the consequence [`getWeight()`'s entry](has-weight.md#getweight-drops-the-state-argument) gives.

### `getFontFamilyClass()` centralizes the class ternary

Filament has no `getFontFamilyClass()`: `TextColumn`, `TextEntry` and schemas' `Text` each inline the same `($fontFamily instanceof FontFamily) ? "fi-font-{$fontFamily->value}" : …` ternary.
Sylament's trait returns `sy-font-<value>` for an enum case, a custom string as-is, or `null` when unset, so a Twig template reads one `component.getFontFamilyClass` instead of type-sniffing the enum-or-string union.
It is one member of the [`get*Class()` family](../translations.md#the-getclass-family-is-one-pattern-and-filaments-getclass-means-a-class-string).
