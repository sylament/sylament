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

## Divergences

### `disabledOn()` is not ported

Filament's component-level `disabledOn()` gates `disabled()` on an operation
string/instanceof, mirroring `CanBeHidden`'s `hiddenOn()` /
[`visibleOn()`](can-be-hidden.md#operation-scoped-and-state-path-visibility-are-not-ported).
Schemas has no `Operation` concept yet, so it is **blocked** on the same
prerequisite, for the same reason — wanted for parity, revisit once an
operation concept lands.
