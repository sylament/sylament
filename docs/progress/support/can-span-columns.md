# CanSpanColumns (support)

`Sylament\Support\Components\Concerns\CanSpanColumns` — the shared trait that lets
a component declare how many grid columns it spans (`columnSpan`) and which column
it starts on (`columnStart`), per responsive breakpoint. Values are literals or
closures, resolved through `EvaluatesClosures`; getters fold the accumulated
per-breakpoint entries into a `default`/`sm`/`md`/`lg`/`xl`/`2xl` map (or a single
value when a breakpoint is named). It ports Filament's identically-named base
trait.

**Status:** done — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`support/src/Concerns/CanSpanColumns.php`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php)
  — the trait, setters, and getters ported here

## Surface

Every point of this trait. _Blocked by_ names what has to land first. _Detail_
carries a **diverges** link to the permanent note in [Divergences](#divergences),
or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`columnSpan()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`columnSpan()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | — |
| [`columnSpanFull()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`columnSpanFull()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | — |
| [`columnStart()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`columnStart()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | — |
| [`getColumnSpan()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`getColumnSpan()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | [diverges](#empty-breakpoint-keys-are-dropped-by-strict-equality-not-blank) |
| [`getColumnStart()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`getColumnStart()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | [diverges](#empty-breakpoint-keys-are-dropped-by-strict-equality-not-blank) |

## Details

Every row above is **done** — the setters, `columnSpanFull()`, and both getters
are ported faithfully and covered directly by
[`CanSpanColumnsTest.php`](../../../packages/support/tests/Unit/Components/Concerns/CanSpanColumnsTest.php)
(defaults, scalar→`lg` mapping and the `columnStart` asymmetry, `columnSpanFull`,
successive-call merge, appended scalar/array-returning/keyed closures, empty-key
drop, single-breakpoint lookup) — so none has a write-up here.

## Divergences

### Empty breakpoint keys are dropped by strict equality, not `blank()`

Filament seeds `$span` from the raw property (`$this->columnSpan ?? [defaults]`)
and mutates it in place, `unset()`-ing each consumed key and testing empties with
Laravel's `blank($columnSpanBreakpoint)`. Sylament rebuilds the map fresh from
`[]` and drops an empty key with `$columnSpanBreakpoint === ''` (same for
`columnStart`). The rebuild is a structural cleanup with no behavioral effect —
the resulting map is identical for every realistic input, and the direct test
pins that. The one behavioral edge is the empty-key test itself: `blank()` also
treats a whitespace-only string key (`'  '`) as empty, so Filament drops it while
Sylament keeps it. Array keys are `string|int`, so the only inputs where the two
disagree are whitespace-only string breakpoints — never produced by the real
breakpoint set (`default`/`sm`/`md`/`lg`/`xl`/`2xl`). Reverse it by switching the
guard to the ported [`blank()`](../../../packages/support/src/helpers.php) helper
if a consumer ever needs whitespace-key parity.
