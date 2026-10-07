# HasGridDirection (forms)

`Sylament\Forms\Components\Concerns\HasGridDirection` — one declaration, `gridDirection()`, deciding whether a field's options flow across the grid's columns or down them.
It is storage and coercion only: the direction it answers is a [`Sylament\Support\Enums\GridDirection`](../../../packages/support/src/Enums/GridDirection.php), and everything that direction *does* was already ported with schemas' grid — support's [`grid()`](../../../packages/support/src/Twig/Extension/GridExtension.php) Twig function emits the `sy-grid-direction-col` class and the bare `--cols-*` custom properties its stylesheet reads, from the column map schemas' [`HasColumns`](../schemas/has-columns.md) supplies.

Three fields compose it — `Radio`, `CheckboxList` and `ToggleButtons` — which is why it is filed here rather than folded into one of them.
[`Radio`](radio.md) is the first built, and the first template to pass the direction to `grid()`; [`ToggleButtons`](toggle-buttons.md) is the second, spelling the same `Column` default at the one of its two templates that lays out a grid at all; and [`CheckboxList`](checkbox-list.md) is the third, the only one with no `inline()` escape from the grid at all.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) — the trait
- [`support/src/Enums/GridDirection.php`](../../../vendor/filament/support/src/Enums/GridDirection.php) — the enum, [ported](../support/README.md) with support
- [`Components/Radio.php`](../../../vendor/filament/forms/src/Components/Radio.php), [`CheckboxList.php`](../../../vendor/filament/forms/src/Components/CheckboxList.php), [`ToggleButtons.php`](../../../vendor/filament/forms/src/Components/ToggleButtons.php) — the three composers, each naming the default this getter does not

## Surface

_Blocked by_ names what has to land first.
_Detail_ links a permanent note in [Semantics](#semantics) or [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| `gridDirection()` — the enum, string and closure arms | [`Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | done | — | — |
| `getGridDirection()` — evaluation, string coercion, and the unset answer | [`Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | done | — | [semantics](#the-column-default-belongs-to-the-field-not-to-this-getter) · [diverges](#an-empty-direction-string-answers-null-where-filaments-getter-fatals) |

Coverage: the null default, a `GridDirection` kept as given, `'row'` and `'column'` coerced, a closure evaluated, an unrecognised string and the empty one both answering null, and a direction unset again — by [`HasGridDirectionTest`](../../../packages/forms/tests/Unit/Components/Concerns/HasGridDirectionTest.php).
What the direction then emits is covered a package down, by [`GridExtensionTest`](../../../packages/support/tests/Unit/Twig/Extension/GridExtensionTest.php), whose first case pins the column arm's class and styles, and end to end — the default column grid, an explicit `'row'`, and `inline()` suppressing the call altogether — by [`RadioTest`](../../../packages/forms/tests/Integration/Components/RadioTest.php), with the column default and the `inline()` suppression re-run against the second composer — plus `grouped()`, which suppresses the grid a second way — by [`ToggleButtonsTest`](../../../packages/forms/tests/Integration/Components/ToggleButtonsTest.php), and the column default re-run once more against a field that always grids, by [`CheckboxListTest`](../../../packages/forms/tests/Integration/Components/CheckboxListTest.php).

## Semantics

### The `Column` default belongs to the field, not to this getter

`getGridDirection()` answers `null` for a field nobody called `gridDirection()` on, and every one of Filament's three composers opens its render with `$this->getGridDirection() ?? GridDirection::Column` — so the default an option field actually has is **Column**, stated once per field rather than once in the getter.
Reading the trait alone suggests options default to no direction at all, or to the `Row` that support's `grid()` helper defaults to; neither is what a [`Radio`](radio.md) or [`ToggleButtons`](toggle-buttons.md) renders — each template spells the default as `component.getGridDirection ?? constant('…GridDirection::Column')` at the one place the grid is built.

Keeping the null is what makes that possible: a getter defaulting to `Column` could not tell an author's explicit `Column` from silence, so nothing downstream could ever choose a different default for the field it renders.
The port keeps the split, and each field names its own default at its render site.

A string naming no direction — `gridDirection('diagonal')` — coerces to `null` through `GridDirection::tryFrom()` and therefore lands on that same per-field default rather than throwing.
Filament does the same, and the failure is deliberately soft because the value only ever picks a CSS class.

## Divergences

Deliberate deviations from Filament, and the reason each holds.
Provisional ones say what would reverse them.

### An empty direction string answers null where Filament's getter fatals

Filament gates the coercion on `filled($direction) && ! ($direction instanceof GridDirection)`, so the **empty** string skips `tryFrom()` and is returned straight out of a `?GridDirection` signature — a `TypeError` naming the getter rather than the `gridDirection('')` that caused it.
The port gates on `is_string()` instead, so `''` takes the same `tryFrom()` path as any other unrecognised string, answers `null`, and lands on the field's default.

One branch instead of two, every string coerced the same way, and the only input whose behaviour differs is one Filament could never return.
