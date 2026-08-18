# HasActions (schemas)

[`Sylament\Schemas\Components\Concerns\HasActions`](../../../packages/schemas/src/Components/Concerns/HasActions.php)
— the component-level action-registration trait: `registerActions()` /
`action()` append to the list and set the "main" action, `getActions()`
(memoized via `cacheActions()`) merges `getDefaultActions()`, the affix actions
of a component implementing
[`HasAffixActions`](../../../packages/schemas/src/Components/Contracts/HasAffixActions.php),
and the registered list — later entries win by name — stamping every own
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
| [`getAction()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) — incl. dotted/array modal-action descent | [`getAction()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getActions()` / `cacheActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`getActions()` / `cacheActions()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`getDefaultActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`getDefaultActions()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`prepareAction()` / `prepareActionGroup()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`prepareAction()` / `prepareActionGroup()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [`hasAction()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) | [`hasAction()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | done | — | — |
| [Affix action merging in `cacheActions()`](../../../packages/schemas/src/Components/Concerns/HasActions.php) — incl. the [`HasAffixActions`](../../../packages/schemas/src/Components/Contracts/HasAffixActions.php) contract | [`cacheActions()` `HasAffixActions` branch](../../../vendor/filament/schemas/src/Components/Contracts/HasAffixActions.php) | done | — | [diverges](#the-affix-contract-is-typed-as-a-name-keyed-map) |
| Extra-item action merging in `cacheActions()` | `cacheActions()` `HasExtraItemActions` branch | blocked | `HasExtraItemActions` contract (forms extra-item actions) | [diverges](#extra-item-action-merging-is-not-ported) |
| `actionSchemaModel()` / `getActionSchemaModel()` | [`actionSchemaModel()` / `getActionSchemaModel()`](../../../vendor/filament/schemas/src/Components/Concerns/HasActions.php) | blocked | the action→schema record hop | [diverges](#actionschemamodel-is-not-ported) |

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The affix contract is typed as a name-keyed map

Filament types both
[`HasAffixActions`](../../../vendor/filament/schemas/src/Components/Contracts/HasAffixActions.php)
getters `@return array<Action>`, which reads as a list; the port declares
`@return array<string, Action>`. The splice is a spread
(`[...$this->cachedActions, ...$this->getPrefixActions(), ...]`) and PHP
renumbers integer keys on spread, so a list-returning implementation lands the
actions under `0`, `1`, … and `getAction('createOption')` misses — silently, since
the cache is still populated and `hasAction()` merely reports false. Filament's
own implementations return name-keyed maps, so the stricter type describes what
already has to be true rather than diverging in behaviour; it just makes an
implementer's mistake a static-analysis failure instead of a runtime miss. The
`forms` affix concern is the first implementer.

The splice also carries an obligation the signature cannot: it spreads the two
maps in as-is, without running `prepareAction()` over them, exactly as Filament
does — so an implementer's getters own the `schemaComponent()` stamping (and any
affix-specific defaults) themselves, and returning raw `Action`s leaves them
unstamped.

### Extra-item action merging is not ported

Filament's `cacheActions()` additionally merges `getExtraItemActions()` when
`$this instanceof HasExtraItemActions`, a forms-only contract
([`HasExtraItemActions.php`](../../../vendor/filament/schemas/src/Components/Contracts/HasExtraItemActions.php))
still `todo` in the [package README](README.md#supporting-surface). No
`instanceof` branch exists for it because no Sylament class implements it yet.
Reverses in the same change that ports the `forms` extra-item-action concern.

### `actionSchemaModel()` is not ported

Filament's `actionSchemaModel(Model | string | Closure | null $model)` /
`getActionSchemaModel()` fall back to `$this->getRecord() ?? $this->getModel()`,
and both of those now exist on the schemas `Component` —
[`BelongsToRecord`](belongs-to-record.md) supplies `getRecord()` and
`getRecordClass()`. Neither the property, the setter, nor the getter exist on the
port, and the consumer does not either: what would read them is the action-side
hop that seeds a mounted action's schema with a record, which is still unbuilt
and carried by no epic — M1 · 6 bound a record to the *action* and left the
action→schema hop alone
([has-actions.md](../actions/has-actions.md#the-per-mount-schema-is-not-seeded-with-a-record-or-an-operation)).
It lands with that hop, and its naming follows the record-only vocabulary the concern
already speaks — recorded in [`renames.md`](../renames.md) with the rest of the
set if the port drops `model` from these two names too.
