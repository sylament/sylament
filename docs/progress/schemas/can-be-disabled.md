---
description: The two CanBeDisabled traits, one on Schema and one on the base Component, and how a disabled component leaves the saved state.
ports:
  - vendor/filament/schemas/src/Concerns/CanBeDisabled.php
  - vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php
owns:
  - packages/schemas/src/Components/Concerns/CanBeDisabled.php
  - packages/schemas/src/Concerns/CanBeDisabled.php
---
# CanBeDisabled (schemas)

Filament ships two traits under the same class name in different namespaces, and Sylament does too: [`Sylament\Schemas\Concerns\CanBeDisabled`](../../../packages/schemas/src/Concerns/CanBeDisabled.php) on `Schema` and [`Sylament\Schemas\Components\Concerns\CanBeDisabled`](../../../packages/schemas/src/Components/Concerns/CanBeDisabled.php) on the base `Component`.
The component-level trait couples `disabled()` to `saved()` ([`HasState`](component.md)), so a disabled field drops out of `getState()`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Schema::disabled()` / `isDisabled()` / `isEnabled()`](../../../packages/schemas/src/Concerns/CanBeDisabled.php) | [`Concerns/CanBeDisabled.php`](../../../vendor/filament/schemas/src/Concerns/CanBeDisabled.php) | done | — | — |
| [`Component::disabled()` / `isDisabled()` / `isEnabled()`](../../../packages/schemas/src/Components/Concerns/CanBeDisabled.php) | [`Components/Concerns/CanBeDisabled.php`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php) | done | — | [semantics](#disabling-keeps-a-field-out-of-the-saved-state-not-out-of-the-request) |
| [`Component::disabledOn()`](../../../packages/schemas/src/Components/Concerns/CanBeDisabled.php) | [`disabledOn()`](../../../vendor/filament/schemas/src/Components/Concerns/CanBeDisabled.php) | done | — | [semantics](can-be-hidden.md#arrwrap-maps-to-the-wrap-helper-never-an-array-cast) |

## Semantics

### Disabling keeps a field out of the saved state, not out of the request

`disabled()` sets `saved(false)` on the component, so its value is left out of `getState()`, and the template emits the native `disabled` attribute.
Neither stops a client from posting a value for that path: the attribute is a browser convention, and the live component's state still accepts the write.
Filament's `disabled()` carries the same warning in a comment, and it holds here unchanged: authorization for a sensitive field belongs on the server.
