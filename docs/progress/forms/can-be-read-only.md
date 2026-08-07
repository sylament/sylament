# CanBeReadOnly (forms)

`Sylament\Forms\Components\Concerns\CanBeReadOnly` — the shared concern behind
the native `readonly` attribute: the `readOnly()` setter, the `readOnlyOn()`
operation gate, and the `isReadOnly()` reader the field templates emit from. The
port's two text fields compose it, `TextInput` and `Textarea`, so it keeps a row
of its own rather than folding into either. Filament composes it on two more —
`DateTimePicker` and `OneTimeCodeInput` — neither of them ported; each picks the
concern up unchanged when it lands.

**Status:** done — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Components/Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php)
  — the trait
- [`schemas/src/Components/Concerns/CanBeDisabled.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php)
  — `disabledOn()`, the body `readOnlyOn()` mirrors

## Surface

_Blocked by_ names what has to land first. _Detail_ links a permanent note in
[Divergences](#divergences) or [Semantics](#semantics), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`readOnly()` / `isReadOnly()`](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`Concerns/CanBeReadOnly.php`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [semantics](#read-only-is-not-disabled--the-state-still-dehydrates) |
| [`readOnlyOn()`](../../../packages/forms/src/Components/Concerns/CanBeReadOnly.php) | [`readOnlyOn()`](../../../vendor/filament/forms/src/Components/Concerns/CanBeReadOnly.php) | done | — | [diverges](#readonlyon-accepts-an-operation-case-where-filaments-takes-strings-only) |
| The `readonly` attribute on each control | [`TextInput::toEmbeddedHtml()`](../../../vendor/filament/forms/src/Components/TextInput.php) | done | — | — |

Coverage: the `false` default, the literal and closure conditions, the operation
match in both the string and enum spellings, a non-matching list, and the host
`instanceof` arm by
[`CanBeReadOnlyTest`](../../../packages/forms/tests/Unit/Components/Concerns/CanBeReadOnlyTest.php),
which also pins that both text fields compose it. That last case sets the
schema's operation and matches against an **interface** the host implements, so
the `===` arm beside it cannot be what satisfies the match — a candidate equal to
the operation string short-circuits the `||` and leaves `instanceof` unexercised,
which is exactly what a class-string candidate does under `getOperation()`'s
host-class fallback. The rendered attribute is
asserted present-and-absent per field by the `renders the readonly attribute only
when read-only` case in
[`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php)
and
[`TextareaTest`](../../../packages/forms/tests/Integration/Components/TextareaTest.php).

Neither template guards the attribute with an `{% if %}`:
[`ComponentAttributeBag`](../../../packages/support/src/Twig/ComponentAttributeBag.php)
drops a `false` value and renders a bare attribute for `true`, so
`readonly: component.isReadOnly` already *is* the condition — the same shape
`disabled` and `autofocus` beside it use.

## Semantics

### Read-only is not disabled — the state still dehydrates

`readOnly()` is the sibling of schemas'
[`disabled()`](../schemas/can-be-disabled.md) and the two look interchangeable at
the call site, but only one touches state. `disabled()` couples to `saved()`, so
a disabled field drops out of `getState()` and never reaches the record;
`readOnly()` sets a flag the template reads and nothing else, so a read-only
field is submitted, validated and mapped exactly like a writable one. That is
Filament's behaviour too, and it is the useful half of the pair: a field the user
must not edit but whose value still has to round-trip wants `readOnly()`, not
`disabled()`.

Neither attribute is authorization. Filament's
[security note on `disabled()`](../schemas/can-be-disabled.md) applies here word
for word: a client can strip either one, so the server decides what may be
written.

## Divergences

Deliberate deviations from Filament, and the reason each holds. Provisional ones
say what would reverse them.

### `readOnlyOn()` accepts an `Operation` case where Filament's takes strings only

Filament types this one `readOnlyOn(string | array<string> $operations)` and
matches each candidate as a raw string. Its own
[`disabledOn()`](../schemas/can-be-disabled.md) and
[`hiddenOn()`/`visibleOn()`](../schemas/can-be-hidden.md) take
`string | Operation | array<string | Operation>` and unwrap an
[`Operation`](../schemas/has-operation.md) case to its `->value` inside the loop
— so the omission here is Filament's inconsistency, not its design. It fails two
ways there: `readOnlyOn(Operation::Edit)` raises a `TypeError` at the signature,
while `readOnlyOn([Operation::Edit])` is accepted, compares an enum object
against a string, matches nothing, and reports no error at all.

The port takes the widened signature for all three, so `readOnlyOn()` runs
`disabledOn()`'s loop over `readOnly()` — the same matching, without the
`saved()` coupling that [separates the two](#read-only-is-not-disabled--the-state-still-dehydrates).
Its one translation — `Arr::wrap` mapping to
support's [`wrap()`](../support/helpers.md) helper and never to the shorter
`(array)` cast, which would dump an enum's properties instead of wrapping it —
is written up once, in
[can-be-hidden.md](../schemas/can-be-hidden.md#arrwrap-maps-to-the-wrap-helper-never-an-array-cast).
The `HasSchemas $livewire` → `InteractsWithSchemas $liveComponent` parameter is
the [interface swap and the Livewire → UX LiveComponent vocabulary](../renames.md),
not a divergence.
