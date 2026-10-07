---
description: The `gridDirection()` declaration deciding whether Radio, CheckboxList and ToggleButtons options flow across the grid's columns or down them.
ports:
  - vendor/filament/forms/src/Components/Concerns/HasGridDirection.php
owns:
  - packages/forms/src/Components/Concerns/HasGridDirection.php
---
# HasGridDirection (forms)

`Sylament\Forms\Components\Concerns\HasGridDirection` holds one declaration, `gridDirection()`, deciding whether a field's options flow across the grid's columns or down them.
It is storage and coercion only: the direction it answers is a [`Sylament\Support\Enums\GridDirection`](../support/enums.md), and what that direction *does* is support's [`grid()`](../../../packages/support/src/Twig/Extension/GridExtension.php) Twig function, which emits the `sy-grid-direction-col` class and the bare `--cols-*` custom properties its stylesheet reads, from the column map schemas' [`HasColumns`](../schemas/has-columns.md) supplies.

[`Radio`](radio.md), [`ToggleButtons`](toggle-buttons.md) and [`CheckboxList`](checkbox-list.md) compose it, as in Filament, and each template passes the direction to `grid()`.
`Radio` and `ToggleButtons` skip the grid under `inline()` (and `ToggleButtons` under `grouped()`, whose template lays out no grid), while `CheckboxList` has no `inline()` and always grids.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`gridDirection()`](../../../packages/forms/src/Components/Concerns/HasGridDirection.php), the enum, string and closure arms | [`Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | done | — | — |
| [`getGridDirection()`](../../../packages/forms/src/Components/Concerns/HasGridDirection.php), evaluation, string coercion and the unset answer | [`Concerns/HasGridDirection.php`](../../../vendor/filament/forms/src/Components/Concerns/HasGridDirection.php) | done | — | [semantics](#the-column-default-belongs-to-the-field-not-to-this-getter) · [diverges](#an-empty-direction-string-answers-null-where-filaments-getter-fatals) |
## Semantics

### The `Column` default belongs to the field, not to this getter

`getGridDirection()` answers `null` for a field nobody called `gridDirection()` on, and every one of Filament's three composers opens its render with `$this->getGridDirection() ?? GridDirection::Column` — so the default an option field actually has is **Column**, stated once per field rather than once in the getter.
Reading the trait alone suggests options default to no direction at all, or to the `Row` that support's `grid()` helper defaults to; neither is what a [`Radio`](radio.md) or [`ToggleButtons`](toggle-buttons.md) renders — each template spells the default as `component.getGridDirection ?? constant('…GridDirection::Column')` at the one place the grid is built.

Keeping the null is what makes that possible: a getter defaulting to `Column` could not tell an author's explicit `Column` from silence, so nothing downstream could ever choose a different default for the field it renders.
The port keeps the split, and each field names its own default at its render site.

A string naming no direction — `gridDirection('diagonal')` — coerces to `null` through `GridDirection::tryFrom()` and therefore lands on that same per-field default rather than throwing.
Filament does the same, and the failure is deliberately soft because the value only ever picks a CSS class.

## Divergences

### An empty direction string answers null where Filament's getter fatals

Filament gates the coercion on `filled($direction) && ! ($direction instanceof GridDirection)`, so the **empty** string skips `tryFrom()` and is returned straight out of a `?GridDirection` signature — a `TypeError` naming the getter rather than the `gridDirection('')` that caused it.
The port gates on `is_string()` instead, so `''` takes the same `tryFrom()` path as any other unrecognised string, answers `null`, and lands on the field's default.

One branch instead of two, every string coerced the same way, and the only input whose behaviour differs is one Filament could never return.
