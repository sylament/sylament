---
description: The trait that sets how many grid columns a component spans and which column it starts on, per responsive breakpoint.
ports:
  - vendor/filament/support/src/Concerns/CanSpanColumns.php
owns:
  - packages/support/src/Components/Concerns/CanSpanColumns.php
---
# CanSpanColumns (support)

`Sylament\Support\Components\Concerns\CanSpanColumns` ports Filament's trait of the same name.
Values are literals or closures, resolved through `EvaluatesClosures`; the getters fold the accumulated per-breakpoint entries into a `default`/`sm`/`md`/`lg`/`xl`/`2xl` map, or return one value when a breakpoint is named.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`columnSpan()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`columnSpan()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | — |
| [`columnSpanFull()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`columnSpanFull()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | — |
| [`columnStart()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`columnStart()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | — |
| [`getColumnSpan()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`getColumnSpan()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | [diverges](#empty-breakpoint-keys-are-dropped-by-strict-equality-not-blank) |
| [`getColumnStart()`](../../../packages/support/src/Components/Concerns/CanSpanColumns.php) | [`getColumnStart()`](../../../vendor/filament/support/src/Concerns/CanSpanColumns.php) | done | — | [diverges](#empty-breakpoint-keys-are-dropped-by-strict-equality-not-blank) |

## Divergences

### Empty breakpoint keys are dropped by strict equality, not `blank()`

Filament seeds `$span` from the raw property (`$this->columnSpan ?? [defaults]`) and mutates it in place, `unset()`-ing each consumed key and testing empties with Laravel's `blank($columnSpanBreakpoint)`.
Sylament rebuilds the map from `[]` and drops an empty key with `$columnSpanBreakpoint === ''` (the same for `columnStart`).
[`CanOrderColumns::getColumnOrder()`](../../../packages/support/src/Components/Concerns/CanOrderColumns.php) rebuilds its map the same way and drops an empty key with `$columnOrderBreakpoint === ''`, where Filament tests `blank()`.

The two shapes disagree on two inputs.
`blank()` also treats a whitespace-only string key (`'  '`) as empty, so Filament drops it while Sylament keeps it; the real breakpoint set (`default`/`sm`/`md`/`lg`/`xl`/`2xl`) never produces one.
Two appended closures that each return a breakpoint map leave the second closure in Filament's result under key `0`, because the spread renumbers the integer keys before `unset()` reaches them; the rebuild never carries a closure over.
Reverse the empty-key guard by switching it to the ported [`blank()`](../../../packages/support/src/helpers.php) helper if a consumer needs whitespace-key parity.
