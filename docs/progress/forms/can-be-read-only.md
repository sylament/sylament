---
description: The `readOnly()` / `readOnlyOn()` / `isReadOnly()` concern behind the native `readonly` attribute of the text fields and date pickers.
ports:
  - vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php
owns:
  - packages/forms/src/Components/Concerns/CanBeReadOnly.php
---
# CanBeReadOnly (forms)

`Sylament\Forms\Components\Concerns\CanBeReadOnly` is the shared concern behind the native `readonly` attribute: the `readOnly()` setter, the `readOnlyOn()` operation gate, and the `isReadOnly()` reader the field templates emit from.
The port's two text fields compose it, `TextInput` and `Textarea`, as does [`DateTimePicker`](date-time-picker.md) and through it `DatePicker` and `TimePicker`.
Filament composes it on one more, `OneTimeCodeInput`, which has no port.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`readOnly()` / `isReadOnly()`](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [semantics](#read-only-is-not-disabled--the-state-still-dehydrates) |
| [`readOnlyOn()`](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`readOnlyOn()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [diverges](#readonlyon-accepts-an-operation-case-where-filaments-takes-strings-only) |
| The `readonly` attribute on each control | [`TextInput::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | [semantics](#the-readonly-attribute-renders-unguarded-because-the-bag-drops-a-false) |

## Semantics

### Read-only is not disabled — the state still dehydrates

`readOnly()` is the sibling of schemas' [`disabled()`](../schemas/can-be-disabled.md) and the two look interchangeable at the call site, but only one touches state.
`disabled()` couples to `saved()`, so a disabled field drops out of `getState()` and never reaches the record; `readOnly()` sets a flag the template reads and nothing else, so a read-only field is submitted, validated and mapped exactly like a writable one.
That is Filament's behaviour too, and it is the useful half of the pair: a field the user must not edit but whose value still has to round-trip wants `readOnly()`, not `disabled()`.

Neither attribute is authorization.
Filament's [security note on `disabled()`](../schemas/can-be-disabled.md) applies here word for word: a client can strip either one, so the server decides what may be written.

### The `readonly` attribute renders unguarded, because the bag drops a `false`

No template guards the attribute with an `{% if %}`: [`ComponentAttributeBag`](../../../packages/support/src/Twig/ComponentAttributeBag.php) drops a `false` value and renders a bare attribute for `true`, so `readonly: component.isReadOnly` already *is* the condition, the same shape `disabled` and `autofocus` beside it use.

## Divergences

### `readOnlyOn()` accepts an `Operation` case where Filament's takes strings only

Filament types this one `readOnlyOn(string | array<string> $operations)` and matches each candidate as a raw string.
Its own [`disabledOn()`](../schemas/can-be-disabled.md) and [`hiddenOn()`/`visibleOn()`](../schemas/can-be-hidden.md) take `string | Operation | array<string | Operation>` and unwrap an [`Operation`](../schemas/has-operation.md) case to its `->value` inside the loop — so the omission here is Filament's inconsistency, not its design.
It fails two ways there: `readOnlyOn(Operation::Edit)` raises a `TypeError` at the signature, while `readOnlyOn([Operation::Edit])` is accepted, compares an enum object against a string, matches nothing, and reports no error at all.

The port takes the widened signature for all three, so `readOnlyOn()` runs `disabledOn()`'s loop over `readOnly()` — the same matching, without the `saved()` coupling that [separates the two](#read-only-is-not-disabled--the-state-still-dehydrates).
Its one translation — `Arr::wrap` mapping to support's [`wrap()`](../support/helpers.md) helper and never to the shorter `(array)` cast, which would dump an enum's properties instead of wrapping it — is written up once, in [can-be-hidden.md](../schemas/can-be-hidden.md#arrwrap-maps-to-the-wrap-helper-never-an-array-cast).
The `HasSchemas $livewire` → `InteractsWithSchemas $liveComponent` parameter is the [interface swap](../renames.md#interface-and-trait-names-are-swapped) and the Livewire → UX LiveComponent vocabulary, not a divergence.
