---
description: The `native()` / `isNative()` switch between a field's native browser control and its JavaScript one. (draft)
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeNative.php
  - vendor/filament/forms/src/Components/Select.php
  - vendor/filament/forms/src/Components/DateTimePicker.php
owns:
  - packages/forms/src/Components/Concerns/CanBeNative.php
---
# CanBeNative (forms)

`Sylament\Forms\Components\Concerns\CanBeNative` — the `native()` / `isNative()` switch between a field's native browser control and its JavaScript one.
Two families compose it: the [date pickers](date-time-picker.md), where the JS calendar is not ported and `native(false)` throws, and [`Select`](select.md), where `native(false)` is one of the four things that put the field on the JS control — so it is promoted out of the picker's fold to a file of its own per [one row per file](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature).
Filament composes it on the same two families, unchanged between them.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`native()` / `isNative()`](../../../packages/forms/src/Components/Concerns/CanBeNative.php) | [`Concerns/CanBeNative.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeNative.php) | done | — | — |
| `Select` — `native(false)` renders the JS control | [`Components/Select.php`](../../../vendor/filament/forms/src/Components/Select.php) | done | — | [semantics](#a-select-is-native-only-when-nothing-forces-the-js-control) |
| Date pickers — `native(false)` renders the JS calendar | [`Components/DateTimePicker.php`](../../../vendor/filament/forms/src/Components/DateTimePicker.php) | blocked | the JS calendar vertical | [diverges](#nativefalse-throws-rather-than-falling-back-to-the-native-control) |

Coverage: the default, the literal and closure arms, and every picker composing it by [`CanBeNativeTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeNativeTest.php); the picker's throw by [`DateTimePickerTest`](../../../packages/forms/tests/Unit/Components/DateTimePickerTest.php); the select's effective-nativeness table — `native(false)`, `searchable()`, `multiple()` and `allowHtml()` each forcing the JS branch, `native(true)` alone not restoring it — by [`SelectTest`](../../../packages/forms/tests/Integration/Components/SelectTest.php).

## Semantics

### A select is native only when nothing forces the JS control

`isNative()` is a declaration, and on `Select` it is not the last word: the template renders the native `<select>` only when `! (isSearchable() || isMultiple() || isHtmlAllowed()) && isNative()` — Filament's `toEmbeddedHtml()` preamble verbatim.
Searching needs a control that can ask the server, chips need one that can hold several values with a remove affordance, and a raw HTML label has no `<option>` to live in, so each of the three forces the JS control whatever `native()` said; `native(false)` alone is enough to get it on a plain select.
The rule is computed once per render, in [`getExtraTemplateData()`](select.md#getextratemplatedata-makes-the-one-getoptions-read-both-branches-share), and both template branches read it from there.
Two visible consequences: every `multiple()` select renders the chips control, and — because [`Select::isSearchable()` defaults to `isMultiple()`](can-be-searchable.md#select-widens-searchable-to-a-column-array-and-defaults-it-to-multiple) — a multiple relationship select enumerates [no options until the user types](select.md#a-relationship-map-is-full-or-an-exception-except-where-search-makes-it-partial-by-design) unless it declares `preload()`.

## Divergences

### `native(false)` throws rather than falling back to the native control

On the [date pickers](date-time-picker.md) the trait is ported verbatim — the flag, the setter, the reader — and Filament's default (`true`) is the port's only working value.
`getType()` throws a `LogicException` naming the picker file when the flag is false.

The alternative was to ignore the flag and render the native control anyway.
That is worse in the way silent fallbacks are always worse: `native(false)` is the one call whose *entire meaning* is "do not render the native control", so honouring the signature while ignoring the intent hands the user a form that looks configured and is not.
The throw is reached the moment the field renders, names the flag and points at the row above it, so the failure is a five-second read rather than a hunt.

`getType()` is the throw site because it is the one member of the native branch that has no meaning at all for the JS control — the panel renders a button and a hidden model, not a typed input.
Reverses when the [calendar lands](date-time-picker.md#the-js-calendar-family-waits-on-a-js-vertical-of-its-own): the guard becomes the branch that picks the other template, exactly as [`Select`'s does today](#a-select-is-native-only-when-nothing-forces-the-js-control).
