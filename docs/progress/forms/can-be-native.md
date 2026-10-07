---
description: The `native()` / `isNative()` switch between a field's native browser control and its JavaScript one, on Select and the date pickers.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeNative.php
  - vendor/filament/forms/src/Components/Select.php
  - vendor/filament/forms/src/Components/DateTimePicker.php
owns:
  - packages/forms/src/Components/Concerns/CanBeNative.php
---
# CanBeNative (forms)

`Sylament\Forms\Components\Concerns\CanBeNative` is the `native()` / `isNative()` switch between a field's native browser control and its JavaScript one.
Two families compose it: the [date pickers](date-time-picker.md), where the JS calendar is not ported and `native(false)` throws, and [`Select`](select.md), where `native(false)` is one of the four things that put the field on the JS control.
Filament composes it on the same two families plus `MorphToSelect`, which has no port, unchanged between them.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`native()` / `isNative()`](../../../packages/forms/src/Components/Concerns/CanBeNative.php) | [`Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php) | done | — | — |
| `Select` — `native(false)` renders the JS control | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#a-select-is-native-only-when-nothing-forces-the-js-control) |
| Date pickers — `native(false)` renders the JS calendar | [`Components/DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [diverges](#nativefalse-throws-rather-than-falling-back-to-the-native-control) |

## Semantics

### A select is native only when nothing forces the JS control

`isNative()` is a declaration, and on `Select` it is not the last word: the template renders the native `<select>` only when `! (isSearchable() || isMultiple() || isHtmlAllowed()) && isNative()` — Filament's `toEmbeddedHtml()` preamble verbatim.
Searching needs a control that can ask the server, chips need one that can hold several values with a remove affordance, and a raw HTML label has no `<option>` to live in, so each of the three forces the JS control whatever `native()` said; `native(false)` alone is enough to get it on a plain select.
The rule is computed once per render, in [`getExtraTemplateData()`](select.md#getextratemplatedata-makes-the-one-getoptions-read-both-branches-share), and both template branches read it from there.
Two visible consequences: every `multiple()` select renders the chips control, and — because [`Select::isSearchable()` defaults to `isMultiple()`](can-be-searchable.md#select-widens-searchable-to-a-column-array-and-defaults-it-to-multiple) — a multiple relationship select enumerates [no options until the user types](has-relationship.md#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design) unless it declares `preload()`.

## Divergences

### `native(false)` throws rather than falling back to the native control

On the [date pickers](date-time-picker.md) the trait is ported verbatim — the flag, the setter, the reader — and Filament's default (`true`) is the port's only working value.
`getType()` throws a `LogicException` naming the field and pointing at `docs/progress/forms/date-time-picker.md` when the flag is false.

The alternative was to ignore the flag and render the native control anyway.
That is worse in the way silent fallbacks are always worse: `native(false)` is the one call whose *entire meaning* is "do not render the native control", so honouring the signature while ignoring the intent hands the user a form that looks configured and is not.
The throw is reached the moment the field renders and names `native(false)`, so the failure explains itself.

`getType()` is the throw site because it is the one member of the native branch that has no meaning at all for the JS control — the panel renders a button and a hidden model, not a typed input.
Reverses when the [calendar lands](date-time-picker.md#the-js-calendar-family-waits-on-a-js-vertical-of-its-own): the guard becomes the branch that picks the other template, as [`Select`'s does](#a-select-is-native-only-when-nothing-forces-the-js-control).
