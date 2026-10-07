# HasActions (schemas)

[`Sylament\Schemas\Components\Concerns\HasActions`](../../../packages/schemas/src/Components/Concerns/HasActions.php) — the component-level action-registration trait: `registerActions()` / `action()` append to the list and set the "main" action, `getActions()` (memoized via `cacheActions()`) merges `getDefaultActions()`, the affix actions of a component implementing [`HasAffixActions`](../../../packages/schemas/src/Components/Contracts/HasAffixActions.php), and the registered list — later entries win by name — stamping every own `Action` with `prepareAction()` (`$action->schemaComponent($this)`, from the actions package's [`BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php)).
It ports Filament's identically-named concern verbatim in name: the [rename table](../renames.md#interface-and-trait-names-are-swapped) only swaps the *host-level* `HasActions` / `InteractsWithActions` pair (tracked in [`actions/has-actions.md`](../actions/has-actions.md)), not this component-level trait.
Composed onto the base [`Component`](component.md), so every schemas component gets it.

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Concerns/HasActions.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) — properties, method bodies, and the `cacheActions()` merge order

## Surface

Every point of this concern.
_Blocked by_ names what has to land first.
_Detail_ carries a **diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`registerActions()` / `action()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`registerActions()` / `action()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getAction()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) — incl. dotted/array modal-action descent | [`getAction()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getActions()` / `cacheActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`getActions()` / `cacheActions()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getDefaultActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`getDefaultActions()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`prepareAction()` / `prepareActionGroup()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`prepareAction()` / `prepareActionGroup()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`hasAction()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`hasAction()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [Affix action merging in `cacheActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) — incl. the [`HasAffixActions`](../../../packages/schemas/src/Components/Contracts/HasAffixActions.php) contract | [`cacheActions()` `HasAffixActions` branch](../../../vendor/filament/schemas/src/Components/Contracts/HasAffixActions.php) | done | — | [diverges](#the-affix-contract-is-typed-as-a-name-keyed-map) |
| [Extra-item action merging in `cacheActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) — incl. the [`HasExtraItemActions`](../../../packages/schemas/src/Components/Contracts/HasExtraItemActions.php) contract | [`cacheActions()` `HasExtraItemActions` branch](../../../vendor/filament/schemas/src/Components/Contracts/HasExtraItemActions.php) | done | — | [diverges](#the-extra-item-contract-is-typed-as-a-name-keyed-map-too) |
| `actionSchemaModel()` / `getActionSchemaModel()` | [`actionSchemaModel()` / `getActionSchemaModel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | blocked | the action→schema record hop | [diverges](#actionschemamodel-is-not-ported) |

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The affix contract is typed as a name-keyed map

Filament types both [`HasAffixActions`](../../../vendor/filament/schemas/src/Components/Contracts/HasAffixActions.php) getters `@return array<Action>`, which reads as a list; the port declares `@return array<string, Action>`.
The splice is a spread (`[...$this->cachedActions, ...$this->getPrefixActions(), ...]`) and PHP renumbers integer keys on spread, so a list-returning implementation lands the actions under `0`, `1`, … and `getAction('createOption')` misses — silently, since the cache is still populated and `hasAction()` merely reports false.
Filament's own implementations return name-keyed maps, so the stricter type describes what already has to be true rather than diverging in behaviour; it just makes an implementer's mistake a static-analysis failure instead of a runtime miss.
The `forms` affix concern is the first implementer.

The splice also carries an obligation the signature cannot: it spreads the two maps in as-is, without running `prepareAction()` over them, exactly as Filament does — so an implementer's getters own the `schemaComponent()` stamping (and any affix-specific defaults) themselves, and returning raw `Action`s leaves them unstamped.

### The extra-item contract is typed as a name-keyed map too

Filament types [`HasExtraItemActions::getExtraItemActions()`](../../../vendor/filament/schemas/src/Components/Contracts/HasExtraItemActions.php) `@return array<Action>`; the port declares `@return array<string, Action>`, for exactly the reason the affix contract does — the splice is a spread, PHP renumbers integer keys on spread, and a list-returning implementation would land its actions under `0`, `1`, … where `getAction('reorder')` misses silently.
The branch sits between the affix splice and the `registerActions()` loop, matching Filament's order, so a registered action of the same name still wins.

Like the affix splice it spreads the map in as-is, without running `prepareAction()` over it: the implementer's getter owns the `schemaComponent()` stamping.

### `actionSchemaModel()` is not ported

Filament's `actionSchemaModel(Model | string | Closure | null $model)` / `getActionSchemaModel()` fall back to `$this->getRecord() ?? $this->getModel()`, and both of those now exist on the schemas `Component` — [`BelongsToRecord`](belongs-to-record.md) supplies `getRecord()` and `getRecordClass()`.
Neither the property, the setter, nor the getter exist on the port.
The consumer now does: the action-side hop that seeds a mounted action's schema with a record ([has-actions.md](../actions/has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation)) walks to the owning component — and reads `getRecord()` and `getRecordClass()` straight off it, which is what these two would have wrapped, so the seed is complete without them.
They stay unported until a component wants to answer the mounted schema something *other* than its own record; the naming would then follow the record-only vocabulary the concern already speaks, recorded in [`renames.md`](../renames.md) with the rest of the set.
