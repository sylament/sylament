# HasAlignment (support)

`Sylament\Support\Components\Concerns\HasAlignment` — the shared concern that gives a component a configurable horizontal alignment.
It ports Filament's `HasAlignment`: an `Alignment | string | Closure | null` property, a fluent `alignment()` setter, seven `align*()` shortcut helpers each taking a `bool | Closure` condition, a `getAlignment()` reader that normalizes a known string to its [`Alignment`](../../../packages/support/src/Enums/Alignment.php) case, and a `hasDynamicAlignment()` probe.
Sylament adds one method with no Filament counterpart — `getAlignmentClass()` — so this port carries a divergence.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Concerns/HasAlignment.php`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) — the trait ported here, method for method
- [`support/src/Enums/Alignment.php`](../../../vendor/filament/support/src/Enums/Alignment.php) — the enum the property and helpers resolve to

## Surface

Every point of this concern.
_Blocked by_ names what has to land first.
_Detail_ carries either or both: **detail** → the write-up in [Details](#details), deleted once the row is **done**; **diverges** → the permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`alignment()` setter + property](../../../packages/support/src/Components/Concerns/HasAlignment.php) | [`HasAlignment::alignment()`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | — |
| [`align*()` helpers](../../../packages/support/src/Components/Concerns/HasAlignment.php) | [`HasAlignment::alignStart()` …](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | — |
| [`getAlignment()` normalization](../../../packages/support/src/Components/Concerns/HasAlignment.php) | [`HasAlignment::getAlignment()`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | — |
| [`hasDynamicAlignment()`](../../../packages/support/src/Components/Concerns/HasAlignment.php) | [`HasAlignment::hasDynamicAlignment()`](../../../vendor/filament/support/src/Concerns/HasAlignment.php) | done | — | — |
| [`getAlignmentClass()`](../../../packages/support/src/Components/Concerns/HasAlignment.php) | — (no Filament equivalent) | done | — | [diverges](#getalignmentclass-centralizes-the-alignment-to-class-mapping) |

## Details

Every row above is **done** — the port is faithful and every method, including the added `getAlignmentClass()`, is exercised by [`HasAlignmentTest`](../../../packages/support/tests/Unit/Components/Concerns/HasAlignmentTest.php).
No row has a write-up here.

## Divergences

### `getAlignmentClass()` centralizes the alignment-to-class mapping

Filament's `HasAlignment` stops at `getAlignment()`; each Blade template that renders an aligned component then maps the `Alignment` case to a CSS class with its own inline `match ($alignment) { … }`.
Sylament folds that mapping into the trait: `getAlignmentClass()` returns `sy-align-<value>` for an enum case, a custom string as-is, and `null` when unaligned — so Twig templates emit `component.getAlignmentClass` without type-sniffing the enum-vs-string union that `getAlignment()` returns.
It is additive (nothing in Filament is removed or renamed) and covered by the class-building cases in [`HasAlignmentTest`](../../../packages/support/tests/Unit/Components/Concerns/HasAlignmentTest.php).
