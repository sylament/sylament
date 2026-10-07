---
description: The $set state writer handed to closures, the write-side twin of Get.
ports:
  - vendor/filament/schemas/src/Components/Utilities/Set.php
owns:
  - packages/schemas/src/State/Set.php
---
# Set (schemas)

[`Sylament\Schemas\State\Set`](../../../packages/schemas/src/State/Set.php) is the `$set` writer handed to closures during evaluation, the write-side twin of [`Get`](get.md).
An invokable bound to one [`Component`](component.md), its `__invoke($path, $state, $isAbsolute, $shouldCallUpdatedHooks)` resolves the relative or absolute state path to a component and closure-evaluates `$state`.
It then writes through the owning component's `state()`, optionally firing its after-update hooks, or, when no component owns the path, writes the path on the live component through the component's property accessor.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`__invoke` component-branch write: resolve path → `state()` with target cast](../../../packages/schemas/src/State/Set.php) | [`Set::__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | done | — | — |
| [Closure-valued state evaluated before writing; evaluated value returned](../../../packages/schemas/src/State/Set.php) | [`Set::__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | done | — | — |
| [`shouldCallUpdatedHooks` gating of after-update hooks](../../../packages/schemas/src/State/Set.php) | [`Set::__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | done | — | [diverges](#shouldcallupdatedhooks-fires-the-non-bubbling-hook-set-dropping-filaments-parent-bubbling) |
| [Raw-bag else-branch](../../../packages/schemas/src/State/Set.php) | [`Set::__invoke()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | in progress | — | [detail](#the-raw-bag-write-is-untested-and-skips-the-roots-child-schema-cache-clear) |
| [`skipComponentsChildContainersWhileSearching(bool)` toggle](../../../packages/schemas/src/State/Set.php) | [`Set::skipComponentsChildContainersWhileSearching()`](../../../vendor/filament/schemas/src/Components/Utilities/Set.php) | done | — | — |

## Details

### The raw-bag write is untested and skips the root's child-schema cache clear

The else-branch writes a path no component owns with `$this->component->getPropertyAccessor()->set($liveComponent, $path, $state)`.
No test runs it: every `SetTest` write resolves to a component.
Filament follows the bag write with `getRootContainer()->clearCachedChildSchemas()`, so child schemas that depend on the written value rebuild.
The port omits that call, because the Schema-level `clearCachedChildSchemas()` it needs is not ported ([schema.md](schema.md#the-schema-level-clearcachedchildschemas-has-every-prerequisite)); the component-level one and its cache exist ([component.md](component.md#haschildcomponents-only-the-materialization-core-is-ported)).
The row is done once a `SetTest` case writes an undeclared path and the call follows the bag write.

## Divergences

### `shouldCallUpdatedHooks` fires the non-bubbling hook set, dropping Filament's parent bubbling

Filament's `Set` calls `$component->callAfterStateUpdated()`, which runs the component's hooks, applies its partial-render and skip-render settings, and bubbles the notification to the parent component.
Sylament's `Set` calls [`callAfterStateUpdatedHooks()`](../../../packages/schemas/src/Components/Concerns/HasState.php), Filament's protected hook runner made public, which runs only this component's own hooks.
The port has no `callAfterStateUpdated()` orchestrator to call, because firing and de-duplication belong to the LiveComponent host ([component.md](component.md#afterstateupdated-firing-de-dup-and-bubbling-move-out-of-the-trait-to-the-livecomponent-host)).
Porting parent bubbling into `HasState` and repointing `Set` at it would reverse this.
