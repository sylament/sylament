# CanBeDisabled (schemas)

Two identically-named traits, one per layer — Filament ships both under the
same class name in different namespaces, so Sylament does too:
[`Sylament\Schemas\Concerns\CanBeDisabled`](../../../packages/schemas/src/Concerns/CanBeDisabled.php)
on `Schema` and
[`Sylament\Schemas\Components\Concerns\CanBeDisabled`](../../../packages/schemas/src/Components/Concerns/CanBeDisabled.php)
on the base `Component`. The component-level trait also couples `disabled()`
to `saved()` ({@see HasState}) so a disabled field drops out of `getState()`.

**Status:** done — see the
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
| [`Component::disabledOn()`](../../../packages/schemas/src/Components/Concerns/CanBeDisabled.php) | [`disabledOn()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php) | done | — | [diverges](can-be-hidden.md#arrwrap-maps-to-the-wrap-helper-never-an-array-cast) |

All three rows are covered by
[`Unit/Concerns/CanBeDisabledTest`](../../../packages/schemas/tests/Unit/Concerns/CanBeDisabledTest.php)
and
[`Unit/Components/Concerns/CanBeDisabledTest`](../../../packages/schemas/tests/Unit/Components/Concerns/CanBeDisabledTest.php)
— defaults, literal and closure conditions, inheritance from the parent
component / container, the `saved()` coupling that keeps a disabled field out
of `getState()`, and `disabledOn('edit')` against the container's
[operation](has-operation.md). The **rendering** half lives in forms, not schemas: both shipped
field templates emit the native attribute from `component.isDisabled`
([`TextInput.html.twig:24`](../../../packages/forms/templates/partials/TextInput/index.html.twig),
[`Textarea.html.twig:16`](../../../packages/forms/templates/partials/Textarea/index.html.twig)),
asserted present-and-absent by
[`TextInputTest`](../../../packages/forms/tests/Integration/Components/TextInputTest.php)
and
[`TextareaTest`](../../../packages/forms/tests/Integration/Components/TextareaTest.php).
Filament's security note on `disabled()` carries over unchanged: disabling
prevents saving, but a client can bypass the attribute — authorization belongs on
the server.

`disabledOn()` is `CanBeHidden`'s `hiddenOn()`/`visibleOn()` body over
`disabled()`, so its one translation — the `Arr::wrap` normalization that must
not be an `(array)` cast — is written up once, in
[can-be-hidden.md](can-be-hidden.md#arrwrap-maps-to-the-wrap-helper-never-an-array-cast),
and linked from the row here.
