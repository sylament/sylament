---
description: 'The sidebar navigation group: label, icon, items, the collapsible pair and the active state.'
ports:
  - vendor/filament/filament/src/Navigation/NavigationGroup.php
  - vendor/filament/filament/src/Navigation/Concerns/HasExtraSidebarAttributes.php
  - vendor/filament/filament/src/Navigation/Concerns/HasExtraTopbarAttributes.php
owns:
  - packages/panels/src/Navigation/NavigationGroup.php
---
# NavigationGroup (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()` / `label()` / `getLabel()`](../../../packages/panels/src/Navigation/NavigationGroup.php) | [`NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | done | — | — |
| `icon()` / `getIcon()`, through support's [`HasIcon`](../support/has-icon.md) | [`NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | done | — | — |
| [`items()` / `getItems()`](../../../packages/panels/src/Navigation/NavigationGroup.php) | [`NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | done | — | [diverges](#getitems-evaluates-a-closure-where-filament-accepts-an-arrayable) |
| [`collapsed()` / `isCollapsed()`](../../../packages/panels/src/Navigation/NavigationGroup.php) | [`NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | done | — | [diverges](#collapsed-does-not-also-make-the-group-collapsible) |
| [`collapsible()` / `isCollapsible()`](../../../packages/panels/src/Navigation/NavigationGroup.php), with `collapsibleFallback()` | [`NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | done | — | [diverges](#the-panel-collapsible-default-is-stamped-on-the-group-not-read-from-a-facade) |
| [`isActive()`](../../../packages/panels/src/Navigation/NavigationGroup.php) | [`NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | done | — | [diverges](#isactive-skips-hidden-items) |
| `fromEnum()` | [`NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | todo | — | [detail](#fromenum-waits-on-the-collapsible-enum-contract) |
| `extraSidebarAttributes()` / `getExtraSidebarAttributes()` / `getExtraSidebarAttributeBag()` | [`HasExtraSidebarAttributes.php`](../../../vendor/filament/filament/src/Navigation/Concerns/HasExtraSidebarAttributes.php) | todo | — | [detail](#the-per-surface-attribute-traits-wait-on-a-template-that-renders-a-group-attribute-bag) |
| `extraTopbarAttributes()` / `getExtraTopbarAttributes()` / `getExtraTopbarAttributeBag()` | [`HasExtraTopbarAttributes.php`](../../../vendor/filament/filament/src/Navigation/Concerns/HasExtraTopbarAttributes.php) | todo | — | [detail](#the-per-surface-attribute-traits-wait-on-a-template-that-renders-a-group-attribute-bag) |

## Details

### `fromEnum()` waits on the `Collapsible` enum contract

Filament's `fromEnum()` reads a group's label, icon and collapsible pair off an enum case through the `HasLabel`, `HasIcon` and `Collapsible` contracts.
Support ports `HasLabel` and `HasIcon` under `Sylament\Support\Contracts`, but not `Collapsible`.
The `UnitEnum` group arm that [`NavigationItem::group()`](navigation-item.md#group-and-getgroup-carry-no-unitenum-arm) defers lands with this method, so the two halves agree about what a group key is.

### The per-surface attribute traits wait on a template that renders a group attribute bag

Filament composes `HasExtraSidebarAttributes` and `HasExtraTopbarAttributes` into the group only.
Neither is ported, because no panels template renders a group attribute bag, so composing them would publish setters with no effect; [`NavigationItem`](navigation-item.md) leaves `HasExtraAttributes` out for the same reason.

## Divergences

### `collapsed()` does not also make the group collapsible

Filament's `collapsed()` calls `collapsible()` as a side effect, so `collapsed(false)` — the natural way to say *start expanded* — silently turns the group non-collapsible and the user loses the toggle.
Ours only records the state, and the gate lives in the reader instead: `isCollapsed()` returns `false` whenever `isCollapsible()` is false, which is the behaviour the coupling was reaching for without the surprise on the `false` arm.

### `isActive()` skips hidden items

Filament's group `isActive()` returns true for any item whose `isActive()` is true, hidden or not, so a hidden-but-active item lights up a group that shows no active child — a group highlighted for a reason the user cannot see.
Ours skips items that report `isHidden()`, matching what the sidebar actually renders (`SidebarGroup` filters on `item.isVisible`).

### `getItems()` evaluates a `Closure` where Filament accepts an `Arrayable`

Filament types `items(array | Arrayable $items)` and returns the value untouched from `getItems(): array | Arrayable`, leaving callers to iterate either shape.
`Arrayable` is a Laravel contract with no Symfony counterpart, so the arm is translated rather than ported: `items()` takes `array<NavigationItem> | Closure` and `getItems()` evaluates it through the `Invoker`, which additionally makes the item list lazy and able to reach container services — the deferred-collection use Filament's `Arrayable` arm serves, plus request-derived items.
An `Arrayable` object passed here is simply an unsupported type.

### The panel collapsible default is stamped on the group, not read from a facade

Filament's `NavigationGroup::isCollapsible()` falls back to `filament()->hasCollapsibleNavigationGroups()` — a facade reaching the current panel from inside the group.
We have no such global, and passing the panel into every group would make a group unusable without one.
So the direction is inverted: `collapsibleFallback(bool)` is a plain setter that [`Panel::getNavigation()`](panel.md) stamps on each group it assembles, and the group reads its own property.
A group built by hand keeps the `true` default, which is the answer Filament's fallback gives for a panel that never changed the setting.

The setter is deliberately not `bool | Closure`: it carries a value the panel has already evaluated, so a closure arm would only add a second place for the same decision to be made.
`collapsibleFallback()` has no Filament counterpart at all — it is a new method rather than a rename, so it is not a [renames.md](../renames.md) row; a migrating user never types it, because `collapsible()` is still the setter they know.
