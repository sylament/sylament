---
description: The testing helpers that fill and assert a live host's form, on the tester a test drives.
ports:
  - vendor/filament/forms/src/Testing/TestsForms.php
owns:
  - packages/forms/src/Testing/TestsForms.php
---
# TestsForms (forms)

[`Sylament\Forms\Testing\TestsForms`](../../../packages/forms/src/Testing/TestsForms.php) is a trait on actions' [`Testable`](../actions/testable.md) tester, where Filament's is a Livewire `Testable` mixin.
`fillForm()` writes through live requests, as a user's typing does, and `assertFormSet()` reads the re-hydrated host through [`assertSchemaStateSet()`](../schemas/tests-schemas.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`fillForm()`](../../../packages/forms/src/Testing/TestsForms.php) | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | done | — | [diverges](#fillform-sends-one-live-request-per-key) |
| `fillForm()`'s uploaded-file values | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — | — |
| [`assertFormSet()`](../../../packages/forms/src/Testing/TestsForms.php) | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | done | — | — |
| `assertHasFormErrors()` / `assertHasNoFormErrors()` | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — | — |
| `assertFormExists()` / `assertFormComponentExists()` / `assertFormComponentDoesNotExist()` | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — | — |
| `assertFormFieldExists()` / `assertFormFieldDoesNotExist()` / `assertFormFieldDisabled()` / `assertFormFieldEnabled()` / `assertFormFieldReadOnly()` / `assertFormFieldHidden()` / `assertFormFieldVisible()` | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | todo | — | — |
| `assertFormFieldIsDisabled()` / `…IsEnabled()` / `…IsReadOnly()` / `…IsHidden()` / `…IsVisible()` | [`Testing/TestsForms.php`](../../../vendor/filament/forms/src/Testing/TestsForms.php) | skipped | — | [deprecated for `assertFormFieldDisabled()` and its siblings](../README.md#statuses) |

## Semantics

### `fillForm()` with no state empties the form

`fillForm()` undots the given keys under the schema's state path, as Laravel's `Arr::undot()` does, so a key given twice keeps its later value, and with no state it writes an empty array over the state path, as Filament's does.

## Divergences

### `fillForm()` sends one live request per key

Filament writes each key in process and runs that key's `afterStateUpdated` hooks before the next (`fillFormDataForTesting()`), so the hooks run in the order the keys are given and a filled value wins over what an earlier field's hook wrote.
Here every write is a live request, `set()` on the key's path, one per key in the given order, which keeps both: the host runs a field's hooks after the request that changed it.
One request carrying every key would not: the host runs every changed field's hooks after all the writes, in schema order, so an earlier field's hook overwrote a later filled value, and UX's multi-key form submission merged a list into the old one by index and dropped an empty list.
A non-empty associative array is written key by key, so the keys it leaves out keep their state; a list or an empty array is one write that replaces what it lands on; and a numeric key the filled state leaves out is removed afterwards by writing back the array that held it, as Filament's `unsetMissingNumericArrayKeys()` removes it.
