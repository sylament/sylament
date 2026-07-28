# CanBeDisabled (schemas)

Two identically-named traits, one per layer — Filament ships both under the
same class name in different namespaces, so Sylament does too:
[`Sylament\Schemas\Concerns\CanBeDisabled`](../../../packages/schemas/src/Concerns/CanBeDisabled.php)
on `Schema` and
[`Sylament\Schemas\Components\Concerns\CanBeDisabled`](../../../packages/schemas/src/Components/Concerns/CanBeDisabled.php)
on the base `Component`. The component-level trait also couples `disabled()`
to `saved()` ({@see HasState}) so a disabled field drops out of `getState()`.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Concerns/CanBeDisabled.php`](../../../vendor/filament/schemas/src/Concerns/CanBeDisabled.php)
  — the schema-level core, fully ported
- [`schemas/src/Components/Concerns/CanBeDisabled.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php)
  — the component-level core plus `disabledOn()`

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Schema::disabled()` / `isDisabled()` / `isEnabled()`](../../../packages/schemas/src/Concerns/CanBeDisabled.php) | [`Concerns/CanBeDisabled.php`](../../../vendor/filament/schemas/src/Concerns/CanBeDisabled.php) | done | — | — |
| [`Component::disabled()` / `isDisabled()` / `isEnabled()`](../../../packages/schemas/src/Components/Concerns/CanBeDisabled.php) | [`Components/Concerns/CanBeDisabled.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php) | done | — | — |
| `Component::disabledOn()` | [`disabledOn()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php) | blocked | operation concept | [diverges](#disabledon-is-not-ported) |

Both **done** rows are covered by
[`Unit/Concerns/CanBeDisabledTest`](../../../packages/schemas/tests/Unit/Concerns/CanBeDisabledTest.php)
and
[`Unit/Components/Concerns/CanBeDisabledTest`](../../../packages/schemas/tests/Unit/Components/Concerns/CanBeDisabledTest.php)
— defaults, literal and closure conditions, inheritance from the parent
component / container, and the `saved()` coupling that keeps a disabled field out
of `getState()`. The **rendering** half lives in forms, not schemas: both shipped
field templates emit the native attribute from `component.isDisabled`
([`TextInput.html.twig:15`](../../../packages/forms/templates/components/TextInput.html.twig),
[`Textarea.html.twig:14`](../../../packages/forms/templates/components/Textarea.html.twig)),
asserted present-and-absent by
[`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php)
and
[`TextareaTest`](../../../packages/forms/tests/Integration/Components/TextareaTest.php).
Filament's security note on `disabled()` carries over unchanged: disabling
prevents saving, but a client can bypass the attribute — authorization belongs on
the server.

## Divergences

### `disabledOn()` is not ported

Filament's component-level `disabledOn()` gates `disabled()` on an operation
string/instanceof, mirroring `CanBeHidden`'s `hiddenOn()` /
[`visibleOn()`](can-be-hidden.md#operation-scoped-and-state-path-visibility-are-not-ported).
Schemas has no `Operation` concept yet, so it is **blocked** on the same
prerequisite, for the same reason — wanted for parity, revisit once an
operation concept lands.
