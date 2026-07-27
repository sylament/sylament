# HasActions (schemas)

[`Sylament\Schemas\Components\Concerns\HasActions`](../../../packages/schemas/src/Components/Concerns/HasActions.php)
— the component-level action-registration trait: `registerActions()` /
`action()` append to the list and set the "main" action, `getActions()`
(memoized via `cacheActions()`) merges `getDefaultActions()` with the
registered list — later entries win by name — stamping every resolved
`Action` with `prepareAction()` (`$action->schemaComponent($this)`, from the
actions package's
[`BelongsToSchemaComponent`](../../../packages/actions/src/Concerns/BelongsToSchemaComponent.php)).
It ports Filament's identically-named concern verbatim in name: the
[rename table](../renames.md#interface-and-trait-names-are-swapped) only
swaps the *host-level* `HasActions` / `InteractsWithActions` pair (tracked in
[`actions/has-actions.md`](../actions/has-actions.md)), not this
component-level trait. Composed onto the base [`Component`](component.md), so
every schemas component gets it.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`schemas/src/Components/Concerns/HasActions.php`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php)
  — properties, method bodies, and the `cacheActions()` merge order

## Surface

Every point of this concern. _Blocked by_ names what has to land first. _Detail_
carries a **diverges** link to [Divergences](#divergences), or `—`.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`registerActions()` / `action()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`registerActions()` / `action()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getAction()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`getAction()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | [diverges](#getaction-does-not-support-dotted-modal-action-descent) |
| [`getActions()` / `cacheActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`getActions()` / `cacheActions()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getDefaultActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`getDefaultActions()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`prepareAction()` / `prepareActionGroup()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`prepareAction()` / `prepareActionGroup()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`hasAction()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`hasAction()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| Affix/extra-item action merging in `cacheActions()` | `cacheActions()` `HasAffixActions`/`HasExtraItemActions` branches | blocked | `HasAffixActions`/`HasExtraItemActions` contracts (forms affix/extra-item actions) | [diverges](#affix-and-extra-item-action-merging-is-not-ported) |
| `actionSchemaModel()` / `getActionSchemaModel()` | [`actionSchemaModel()` / `getActionSchemaModel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | blocked | `BelongsToModel` (schemas component-level concern) | [diverges](#actionschemamodel-is-not-ported) |

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### `getAction()` does not support dotted modal-action descent

Filament's `getAction(string | array | null $name = null)` accepts a dotted
string (`'primary.modal-action'`) or an array `[actionName, ...modalActionNames]`,
exploding it and walking each subsequent segment through
`$action->getModalAction($modalActionName)` to reach an action nested inside
another action's modal footer. The port's `getAction(?string $name = null)`
only resolves `blank($name)` to the main action, or a direct
`getActions()[$name] ?? null` lookup — no dotted/array form, because
`CanOpenModal::getModalAction()` does not exist on the `actions` package yet.
Reverses once `CanOpenModal` grows modal-action nesting.

### Affix and extra-item action merging is not ported

Filament's `cacheActions()` additionally merges `$this->getPrefixActions()` /
`getSuffixActions()` when `$this instanceof HasAffixActions`, and
`getExtraItemActions()` when `$this instanceof HasExtraItemActions` — both
forms-only contracts
([`Components/Contracts/HasAffixActions.php`](../../../vendor/filament/schemas/src/Components/Contracts/HasAffixActions.php),
[`HasExtraItemActions.php`](../../../vendor/filament/schemas/src/Components/Contracts/HasExtraItemActions.php)),
both still `todo` in the
[package README](README.md#supporting-surface). The port's `cacheActions()`
merges only `getDefaultActions()` and the registered list; no `instanceof`
branch exists for either contract because no Sylament class implements them
yet. Reverses in the same change that ports the `forms` affix/extra-item-action
concerns.

### `actionSchemaModel()` is not ported

Filament's `actionSchemaModel(Model | string | Closure | null $model)` /
`getActionSchemaModel()` fall back to `$this->getRecord() ?? $this->getModel()`
— both supplied by the schemas component-level `BelongsToModel` concern, still
`todo` in the
[package README](README.md#component-level-concerns). Neither the property,
the setter, nor the getter exist on the port. Reverses once `BelongsToModel`
lands on the schemas `Component`.
