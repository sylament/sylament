---
description: The schemas component trait that registers actions, caches them by name with the affix and extra-item contracts' actions, and stamps each with its component.
ports:
  - vendor/filament/schemas/src/Components/Concerns/HasActions.php
  - vendor/filament/schemas/src/Components/Contracts/HasAffixActions.php
  - vendor/filament/schemas/src/Components/Contracts/HasExtraItemActions.php
owns:
  - packages/schemas/src/Components/Concerns/HasActions.php
  - packages/schemas/src/Components/Contracts/HasAffixActions.php
  - packages/schemas/src/Components/Contracts/HasExtraItemActions.php
---
# HasActions (schemas)

[`Sylament\Schemas\Components\Concerns\HasActions`](../../../packages/schemas/src/Components/Concerns/HasActions.php) — the component-level action-registration trait: `registerActions()` / `action()` append to the list and set the "main" action, `getActions()` (memoized via `cacheActions()`) merges `getDefaultActions()`, the affix actions of a component implementing [`HasAffixActions`](../../../packages/schemas/src/Components/Contracts/HasAffixActions.php), and the registered list — later entries win by name — stamping every own `Action` with `prepareAction()` (`$action->schemaComponent($this)`, from the actions package's [`BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php)).
It keeps Filament's name: the [rename table](../renames.md#interface-and-trait-names-are-swapped) swaps only the *host-level* `HasActions` / `InteractsWithActions` pair ([`actions/has-actions.md`](../actions/has-actions.md)), not this component-level trait.
Composed onto the base [`Component`](component.md), so every schemas component gets it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`registerActions()` / `action()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`registerActions()` / `action()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getAction()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) — incl. dotted/array modal-action descent | [`getAction()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getActions()` / `cacheActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`getActions()` / `cacheActions()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getDefaultActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`getDefaultActions()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`prepareAction()` / `prepareActionGroup()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`prepareAction()` / `prepareActionGroup()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`hasAction()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`hasAction()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [Affix action merging](../../../packages/schemas/src/Components/Concerns/HasActions.php) and [`HasAffixActions`](../../../packages/schemas/src/Components/Contracts/HasAffixActions.php) | [`cacheActions()` `HasAffixActions` branch](../../../vendor/filament/schemas/src/Components/Contracts/HasAffixActions.php) | done | — | [diverges](#the-affix-contract-is-typed-as-a-name-keyed-map) |
| [Extra-item action merging](../../../packages/schemas/src/Components/Concerns/HasActions.php) and [`HasExtraItemActions`](../../../packages/schemas/src/Components/Contracts/HasExtraItemActions.php) | [`cacheActions()` `HasExtraItemActions` branch](../../../vendor/filament/schemas/src/Components/Contracts/HasExtraItemActions.php) | done | — | [diverges](#the-extra-item-contract-is-typed-as-a-name-keyed-map-too) |
| `actionSchemaModel()` / `getActionSchemaModel()` | [`actionSchemaModel()` / `getActionSchemaModel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | skipped | — | [diverges](#actionschemamodel-is-not-ported) |

## Divergences

### The affix contract is typed as a name-keyed map

Filament types both [`HasAffixActions`](../../../vendor/filament/schemas/src/Components/Contracts/HasAffixActions.php) getters `@return array<Action>`, which reads as a list; the port declares `@return array<string, Action>`.
The splice is a spread (`[...$this->cachedActions, ...$this->getPrefixActions(), ...]`) and PHP renumbers integer keys on spread, so a list-returning implementation lands the actions under `0`, `1`, … and `getAction()` by the action's name misses silently, because the cache is still populated and `hasAction()` merely reports false.
Filament's own implementations return name-keyed maps, so the stricter type describes what already has to be true rather than diverging in behaviour; it makes an implementer's mistake a static-analysis failure instead of a runtime miss.
The forms [`HasAffixes`](../../../packages/forms/src/Components/Contracts/HasAffixes.php) contract extends it.

The splice also carries an obligation the signature cannot: it spreads the two maps in as-is, without running `prepareAction()` over them, exactly as Filament does — so an implementer's getters own the `schemaComponent()` stamping (and any affix-specific defaults) themselves, and returning raw `Action`s leaves them unstamped.

### The extra-item contract is typed as a name-keyed map too

Filament types [`HasExtraItemActions::getExtraItemActions()`](../../../vendor/filament/schemas/src/Components/Contracts/HasExtraItemActions.php) `@return array<Action>`; the port declares `@return array<string, Action>`, for exactly the reason the affix contract does — the splice is a spread, PHP renumbers integer keys on spread, and a list-returning implementation would land its actions under `0`, `1`, … where `getAction()` by name misses silently.
The branch sits between the affix splice and the `registerActions()` loop, matching Filament's order, so a registered action of the same name still wins.

Like the affix splice it spreads the map in as-is, without running `prepareAction()` over it: the implementer's getter owns the `schemaComponent()` stamping.

### `actionSchemaModel()` is not ported

Filament's `actionSchemaModel(Model | string | Closure | null $model)` / `getActionSchemaModel()` answer a mounted action's schema with a model that falls back to `$this->getRecord() ?? $this->getModel()`.
The port has neither the property, the setter nor the getter.
The action-side hop that seeds a mounted action's schema with a record ([has-actions.md](../actions/has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation)) walks to the owning component and reads `getRecord()` and `getRecordClass()` ([`BelongsToRecord`](belongs-to-record.md)) straight off it, which is what these two would wrap.
They return when a component wants to answer the mounted schema something *other* than its own record, named in the [record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record) the concern speaks.
