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
| [`assertSchemaStateSet()`](../../../packages/schemas/src/Testing/TestsSchemas.php) | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | done | — | [semantics](#a-key-names-a-component-or-else-a-path-under-the-schemas-state-path) |
| `assertSchemaComponentExists()` / `assertSchemaComponentDoesNotExist()` / `assertSchemaComponentVisible()` / `assertSchemaComponentHidden()` | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | todo | — | — |
| `assertSchemaComponentStateSet()` / `assertSchemaComponentStateNotSet()` | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | todo | — | — |
| `assertWizardStepExists()` / `assertWizardCurrentStep()` / `goToWizardStep()` / `goToNextWizardStep()` / `goToPreviousWizardStep()` | [`Testing/TestsSchemas.php`](../../../vendor/filament/schemas/src/Testing/TestsSchemas.php) | todo | — | — |

## Semantics

### A key names a component, or else a path under the schema's state path

`assertSchemaStateSet()` compares each key's value with the state of the component that key names in the schema, hidden ones included, and a key naming no component with the host's value at that path under the schema's state path, both with `assertEquals()`, as Filament's does.
A closure receives the schema's raw state and returns the array to compare.
With no schema named, the helpers test the mounted action's schema when an action is mounted, and otherwise the first schema the host's `schemas()` declares: Filament's host answers its first cached schema, which is the same one whenever no action is mounted, the only case the default is read.
