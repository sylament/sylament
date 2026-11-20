---
description: The testing helpers that assert a live host's schemas and their state, on the tester a test drives.
ports:
  - vendor/filament/schemas/src/Testing/TestsSchemas.php
owns:
  - packages/schemas/src/Testing/TestsSchemas.php
---
# TestsSchemas (schemas)

[`Sylament\Schemas\Testing\TestsSchemas`](../../../packages/schemas/src/Testing/TestsSchemas.php) is a trait on actions' [`Testable`](../actions/testable.md) tester, where Filament's is a Livewire `Testable` mixin.
Its assertions read the re-hydrated host, the state after the last response, and reach a schema through the host's `resolveSchemaByName()`, where Filament reads a Livewire property of the schema's name.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`assertSchemaExists()`](../../../packages/schemas/src/Testing/TestsSchemas.php) | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | done | — | — |
| [`assertSchemaStateSet()`](../../../packages/schemas/src/Testing/TestsSchemas.php) | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | done | — | [semantics](#a-key-names-a-component-or-else-a-path-under-the-schemas-state-path) · [semantics](#with-no-schema-named-the-helpers-test-the-mounted-actions-schema-else-the-hosts-default) |
| `assertSchemaComponentExists()` / `assertSchemaComponentDoesNotExist()` / `assertSchemaComponentVisible()` / `assertSchemaComponentHidden()` | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | todo | — | — |
| `assertSchemaComponentStateSet()` / `assertSchemaComponentStateNotSet()` | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | todo | — | — |
| `assertWizardStepExists()` / `assertWizardCurrentStep()` / `goToWizardStep()` / `goToNextWizardStep()` / `goToPreviousWizardStep()` | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | todo | — | — |

## Semantics

### A key names a component, or else a path under the schema's state path

`assertSchemaStateSet()` compares each key's value with the state of the component that key names in the schema, hidden ones included, and a key naming no component with the host's value at that path under the schema's state path, both with `assertEquals()`, as Filament's does.
A closure receives the schema's raw state and returns the array to compare.
The fallback reads a path the host does not hold as `null`, as Filament's `assertSet()` does through `data_get()`, so `assertFormSet(['typo' => null])` passes.

### With no schema named, the helpers test the mounted action's schema, else the host's default

A helper called without a schema tests the innermost mounted action's schema while an action is mounted, and otherwise the one the host's `getDefaultTestingSchemaName()` names, as Filament's do.
So a bare form helper after a completed action tests the host's default, never the closed modal's schema.
A host whose default is `null` throws "declares no schema to test"; which hosts answer `null` is in [has-schemas.md](has-schemas.md#the-default-testing-schema-is-the-first-one-schemas-declares).
