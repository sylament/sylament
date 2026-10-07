---
description: 'The sidebar navigation group: label, icon, items, collapsible state and active state.'
ports:
  - vendor/filament/filament/src/Navigation/NavigationGroup.php
owns:
  - packages/panels/src/Navigation/NavigationGroup.php
---
# NavigationGroup (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Navigation\NavigationGroup`](../../../packages/panels/src/Navigation/NavigationGroup.php) | [`Navigation/NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The group ports its label, icon, items, collapsible pair and active state

Label, icon, items, the collapsible/collapsed pair and `isActive()` are ported.

Deferred, all todo:

- `HasExtraSidebarAttributes` / `HasExtraTopbarAttributes` — Filament's two per-surface attribute traits (`Filament\Navigation\Concerns`), neither ported; same reason `NavigationItem` records for extra attributes, since no template renders a group attribute bag.
- `fromEnum()` — needs the `HasLabel` / `HasIcon` / `Collapsible` enum contracts, and the `UnitEnum` group arm `NavigationItem::group()` also defers.

The panel-level collapsible default is wired: `isCollapsible()` falls back to `collapsibleFallback()`, a plain `bool` setter [`Panel::getNavigation()`](panel.md) stamps onto every group it assembles from `hasCollapsibleNavigationGroups()`.
A group built outside a panel keeps the `true` the property initialises to.

Four deliberate deltas beyond that, each with its own divergence: [`collapsed()` no longer forces the group collapsible](#collapsed-does-not-also-make-the-group-collapsible), [`isActive()` skips hidden items](#isactive-skips-hidden-items), [`getItems()` evaluates a `Closure` instead of accepting an `Arrayable`](#getitems-evaluates-a-closure-where-filament-accepts-an-arrayable), and [the panel collapsible default is stamped on the group rather than read from a facade](#the-panel-collapsible-default-is-stamped-on-the-group-not-read-from-a-facade).

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
