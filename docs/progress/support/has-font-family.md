# HasFontFamily (support)

`Sylament\Support\Components\Concerns\HasFontFamily` — the shared component trait
that ports Filament's `HasFontFamily`. It holds a closure-evaluable
`FontFamily | string | null` value and normalizes a string that names a known
enum case back to the `FontFamily` enum, so text-rendering components (schemas'
`Text`, and the future table/infolist text surfaces) can carry a font-family
override.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Concerns/HasFontFamily.php`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php)
  — the trait ported here
- [`tables/src/Columns/TextColumn.php`](../../../vendor/filament/tables/src/Columns/TextColumn.php)
  and [`infolists/src/Components/TextEntry.php`](../../../vendor/filament/infolists/src/Components/TextEntry.php)
  — Filament's call sites that inline the `fi-font-{value}` class ternary this
  trait centralizes

## Surface

Every point of this trait. _Blocked by_ names what has to land first. _Detail_
carries only two links, either or both: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`fontFamily()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | [`HasFontFamily::fontFamily()`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | done | — | — |
| [`getFontFamily()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | [`HasFontFamily::getFontFamily()`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | done | — | [diverges](#getfontfamily-drops-the-state-parameter) |
| [`getFontFamilyClass()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | _(no Filament equivalent — inlined at each call site)_ | done | — | [diverges](#getfontfamilyclass-centralizes-the-class-ternary) |
| [`hasFontFamily()`](../../../packages/support/src/Components/Concerns/HasFontFamily.php) | [`HasFontFamily::hasFontFamily()`](../../../vendor/filament/support/src/Concerns/HasFontFamily.php) | done | — | — |

## Details

Every row above is **done** — covered by schemas' `Text` tests, which exercise
this trait directly through its only current consumer:
[`Text` unit test](../../../packages/schemas/tests/Unit/Components/TextTest.php)
asserts the string→enum normalization of `getFontFamily()` and the
`sy-font-serif` / `null` outputs of `getFontFamilyClass()`, and the
[render test](../../../packages/schemas/tests/Integration/Components/TextRenderTest.php)
asserts the class reaches the emitted markup. No row has an open write-up.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### `getFontFamily()` drops the `$state` parameter

Filament's `getFontFamily(mixed $state = null)` threads `'state' => $state` into
`evaluate()` so a `fn ($state) => …` font-family closure can branch on the
column/entry's per-row state. Sylament's `getFontFamily()` takes no argument and
evaluates with no injected state, because nothing here passes one yet. The base
[`Component`](component.md#surface)'s arity probe is no longer part of that
reason: it shipped with the column catalog (M4 · 4) as
`EvaluatesClosures::evaluationValueIsFunctionAndHasParameter()`, the predicate
`Column::hasStateBasedUrls()` asks. Re-add the parameter in the same change as
the first state-carrying text consumer, where it finally has state to pass and
behavior to test.

### `getFontFamilyClass()` centralizes the class ternary

Filament has no `getFontFamilyClass()`: every consumer inlines the same
`($fontFamily instanceof FontFamily) ? "fi-font-{$fontFamily->value}" : (is_string($fontFamily) ? $fontFamily : '')`
ternary at its call site (`TextColumn`, `TextEntry`, the link Blade). Sylament
adds one method on the trait that returns `sy-font-<value>` for an enum case, a
custom string as-is, or `null` when unset, so Twig templates never type-sniff
the enum-vs-string union — they read a single `component.getFontFamilyClass`.
This mirrors the sibling `getSizeClass()` / `getWeightClass()` helpers and keeps
the `fi-*`→`sy-*` prefix rewrite (a systematic translation) in PHP rather than
scattered across templates.

_See also: the global [rename table](../renames.md) — the `fi-*` → `sy-*` class
prefix and the `Support\Concerns\` → `Support\Components\Concerns\` trait split
are systematic translations recorded there, not per-trait divergences._
