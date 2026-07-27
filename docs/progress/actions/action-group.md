# ActionGroup (actions)

`Sylament\Actions\ActionGroup` — groups a list of `Action`/`ActionGroup` children
behind a single trigger, so a table row or a toolbar can collapse many actions
into one control. It composes `Sylament\Support\Components\ViewComponent`
(Twig-backed rendering, the same base `Action` extends) and folds in its own
actions-package concern, `BelongsToGroup` — shared with `Action` (both now
compose it), written up here rather than split into a third detail file, since
its whole surface is a two-method back-reference pair. It also reuses two
already-folded `Action` concerns unchanged — `HasLabel` and `HasSize`, tracked
in [`action.md`](action.md) — and five support-level traits (`HasColor`,
`HasExtraAttributes`, `HasIcon`, `HasIconSize`, `HasTooltip`) that stay tracked
in the [`support`](../support/README.md) package and appear here only as
reference rows. This task ports the model core only: the children list, the
eager name-keyed flat map, and the group back-reference wiring. Everything
that makes the class an actual UI control — the dropdown trigger config,
visibility composition, propagation to children, the grouped-item template,
the trigger machinery, and the render pipeline itself — lands in later tasks
of the same epic (see _Blocked by_ below).

**Status:** in progress — see the [status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`actions/src/ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php)
  — the base class and the ~24 traits (plus the `Arrayable`/`HasEmbeddedView`
  interfaces) it composes
- [`actions/src/Concerns/BelongsToGroup.php`](../../../vendor/filament/actions/src/Concerns/BelongsToGroup.php)
  — the back-reference concern audited below
- [`actions/src/Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php)
  — the trigger-config concern, `todo` (Task 5)

## Surface

Every point of this component and its own folded concern. _Blocked by_ names
a later task in this same epic or an unbuilt package. _Detail_ links the
write-up in [Details](#details) (deleted once the row is **done**) and/or the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| Construction, `make()` & `actions()`/`getActions()`/`getFlatActions()` | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) | done | — | [diverges](#make-drops-di-construction) |
| [`BelongsToGroup`](../../../packages/actions/src/Concerns/BelongsToGroup.php) | [`Concerns/BelongsToGroup.php`](../../../vendor/filament/actions/src/Concerns/BelongsToGroup.php) | done | — | [diverges](#getrootgroup-is-not-ported) |
| `getLabel()` default | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getLabel()`) | done | — | [diverges](#getlabel-falls-back-to-a-plain-string-not-a-translated-label) |
| [`HasLabel`](../../../packages/actions/src/Concerns/HasLabel.php) (shared with `Action`) | [`Concerns/HasLabel.php`](../../../vendor/filament/actions/src/Concerns/HasLabel.php) | done | — | - |
| [`HasSize`](../../../packages/actions/src/Concerns/HasSize.php) (shared with `Action`) | [`Concerns/HasSize.php`](../../../vendor/filament/actions/src/Concerns/HasSize.php) | done | — | - |
| [`HasColor`](../../../packages/support/src/Components/Concerns/HasColor.php) (support) | [`Concerns/HasColor.php`](../../../vendor/filament/support/src/Concerns/HasColor.php) | done | — | - |
| [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php) (support) | [`Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | done | — | - |
| [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php) (support) | [`Concerns/HasIcon.php`](../../../vendor/filament/support/src/Concerns/HasIcon.php) | done | — | - |
| [`HasIconSize`](../../../packages/support/src/Components/Concerns/HasIconSize.php) (support) | [`Concerns/HasIconSize.php`](../../../vendor/filament/support/src/Concerns/HasIconSize.php) | done | — | - |
| [`HasTooltip`](../../../packages/support/src/Components/Concerns/HasTooltip.php) (support) | [`Concerns/HasTooltip.php`](../../../vendor/filament/support/src/Concerns/HasTooltip.php) | done | — | - |
| `HasDropdown` | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) | todo | Task 5 | - |
| Visibility composition (`isHidden()` group-iteration) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`isHidden()`/`resolveIsHidden()`) | todo | Task 6 | - |
| Propagation to children (dropdown placement, trigger-view defaults) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`actions()`/`getActions()`) | todo | Task 6 | - |
| Grouped item template | [`components/dropdown/list/item.blade.php`](../../../vendor/filament/support/resources/views/components/dropdown/list/item.blade.php) | todo | Task 7 | - |
| Trigger machinery | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`getTriggerView()`/`toTriggerHtml()`) | todo | Task 8 | - |
| Rendering (`toEmbeddedHtml()`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toEmbeddedHtml()`) | todo | Task 9 | - |
| Badge/button-group/grouped/link triggers | [`View/Concerns/CanGenerate*Html.php`](../../../vendor/filament/support/src/View/Concerns) | todo | — | - |
| Submenu rendering (nested non-dropdown groups) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toEmbeddedHtml()` action-list branch) | todo | — | - |
| Dropdown teleport & auto-placement | [`Concerns/HasDropdown.php`](../../../vendor/filament/actions/src/Concerns/HasDropdown.php) (`dropdownTeleport()`/`dropdownAutoPlacement()`) | todo | — | - |
| `toArray()`/`fromArray()` (`Arrayable`) | [`ActionGroup.php`](../../../vendor/filament/actions/src/ActionGroup.php) (`toArray()`/`fromArray()`) | todo | — | - |
| Table and bulk concerns | [`Concerns/BelongsToTable.php`](../../../vendor/filament/actions/src/Concerns/BelongsToTable.php), `hasNonBulkAction()` | blocked | tables package, BulkAction | [detail](#table-and-bulk-concerns-are-blocked-on-the-tables-and-bulkaction-ports) |

## Details

### Table and bulk concerns are blocked on the tables and BulkAction ports

Filament's `ActionGroup` composes `BelongsToTable` and `BelongsToSchemaComponent`,
and exposes `hasNonBulkAction()` (iterates `getFlatActions()` calling
`$action->isBulk()`). None of this has a Sylament target yet: there is no
`tables`-package integration point (`BelongsToTable`'s host) and no `BulkAction`
class (`isBulk()`'s only caller) in the port. **Blocked** on both landing; add
`BelongsToTable`/`BelongsToSchemaComponent` (or their Sylament equivalents) and
`hasNonBulkAction()` in the same change that ports whichever comes first.

## Divergences

### `make()` drops DI construction

Filament's `make(array $actions): static` resolves through
`app(static::class, ['actions' => $actions])` — container construction, so an
`ActionGroup` subclass can declare extra constructor-injected dependencies
beyond `$actions`. The port's
[`make()`](../../../packages/actions/src/ActionGroup.php) is
`new static($actions); $group->configure(); return $group;` — plain
instantiation, the same `app()->make()` → Symfony DI translation `Action::make()`
already made (see
[action.md](action.md#make-drops-getdefaultname-and-di-construction)), because
`ActionGroup`'s constructor needs no injected services. Reversible the moment a
subclass needs one, by switching to `Invoker`/container construction.

### `getRootGroup()` is not ported

Filament's `Concerns\BelongsToGroup` carries a third method,
`getRootGroup(): ?ActionGroup`, that walks `getGroup()` up through nested
parents and returns the outermost one. The port's
[`BelongsToGroup`](../../../packages/actions/src/Concerns/BelongsToGroup.php)
has only `group()`/`getGroup()` — no root-walking helper, and no test exercises
one because none exists. The port's `group(?ActionGroup $group = null): static`
also gives its parameter a default of `null` (Filament's is required); harmless
since `null` is the effective value every caller passes today, and it lets
`->group()` clear the association without spelling out the `null` argument.
Reversible: port `getRootGroup()` the moment a consumer needs to walk to the
top of a nested-group chain — Task 6's propagation work (pushing dropdown
placement/attributes across nested groups) looks like the first candidate.

### `getLabel()` falls back to a plain string, not a translated label

Filament's `getLabel()` is
`$this->evaluate($this->label) ?? __('filament-actions::group.trigger.label')`,
then runs a string result through `__()` again when `$shouldTranslateLabel` is
set — the same translation layer `Action`'s own `HasLabel` already drops (no
translator wired into the repo; see
[action.md](action.md#label-translation-and-the-deprecated-disablelabel-alias-are-not-ported)).
The port's [`getLabel()`](../../../packages/actions/src/ActionGroup.php) is
`$this->evaluate($this->label) ?? 'Actions'` — a plain literal. The override
also means `ActionGroup` never runs the `HasLabel` trait's own `getLabel()`
(which derives a name-based default via `getName()`) — PHP resolves the
class-defined method over the trait's, no aliasing needed, and a group has no
name for that default to derive from anyway. Reverses if/when a translation
layer lands, same as the linked `Action` divergence.
