---
description: 'Several relation managers behind one tab: the group supplies the tab''s label and chrome, and the page mounts every manager it holds inside that tab. (draft)'
ports:
  - vendor/filament/filament/src/Resources/RelationManagers/RelationGroup.php
  - vendor/filament/filament/src/Resources/RelationManagers/RelationManagerConfiguration.php
owns:
  - packages/panels/src/Resources/RelationManagers/RelationGroup.php
  - packages/panels/src/Resources/RelationManagers/RelationManagerConfiguration.php
---
# RelationGroup (panels)

[`Sylament\Panels\Resources\RelationManagers\RelationGroup`](../../../packages/panels/src/Resources/RelationManagers/RelationGroup.php) — several [relation managers](relation-manager.md) behind one tab: the group supplies the tab's label and chrome, and the page mounts every manager it holds inside that tab.
It ports [`Resources/RelationManagers/RelationGroup.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationGroup.php).

[`RelationManagerConfiguration`](../../../packages/panels/src/Resources/RelationManagers/RelationManagerConfiguration.php) is folded in here: it is the value object `RelationManager::make()` returns — a manager class-string plus the mount props that one registration adds — and it appears in a `getRelations()` list wherever a bare class-string can, groups included.
It ports [`Resources/RelationManagers/RelationManagerConfiguration.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManagerConfiguration.php).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(string\|Closure $label, array\|Closure $managers = [])`](../../../packages/panels/src/Resources/RelationManagers/RelationGroup.php) | `make()` / `__construct()` | done | — | [diverges](#the-two-constructor-arguments-arrive-through-setters) |
| [`getLabel()`](../../../packages/panels/src/Resources/RelationManagers/RelationGroup.php) | `getLabel()` | done | — | — |
| [`getManagers()`](../../../packages/panels/src/Resources/RelationManagers/RelationGroup.php) | `getManagers()` | done | — | [diverges](#the-closure-manager-list-is-evaluated-filaments-is-returned-raw) |
| [`ownerRecord()` / `getOwnerRecord()` / `pageClass()` / `getPageClass()`](../../../packages/panels/src/Resources/RelationManagers/RelationGroup.php) | same four | done | — | — |
| [`badge()` / `badgeColor()` / `badgeTooltip()` / `icon()` / `iconPosition()`](../../../packages/panels/src/Resources/RelationManagers/RelationGroup.php) — support's `HasBadge`, `HasBadgeTooltip`, `HasIcon`, `HasIconPosition` | the same four Filament concerns plus the group's own `badge`/`badgeColor` | done | — | — |
| [`tab()` / `getTabComponent()`](../../../packages/panels/src/Resources/RelationManagers/RelationGroup.php) | `tab()` / `getTabComponent()` | done | — | [diverges](#the-badge-is-resolved-before-the-colour-and-tooltip-closures-read-it) |
| [`resolveDefaultClosureDependencyForEvaluationBy{Name,Type}()`](../../../packages/panels/src/Resources/RelationManagers/RelationGroup.php) — `$ownerRecord` / `$pageClass` injection | the same two overrides | done | — | — |
| [`RelationManagerConfiguration`](../../../packages/panels/src/Resources/RelationManagers/RelationManagerConfiguration.php) — `$relationManager` + `getProperties()` | [`RelationManagerConfiguration.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManagerConfiguration.php) | done | — | — |
| `deferBadge()` / `isBadgeDeferred()` | `RelationGroup.php` | skipped | — | [rides the manager's divergence](relation-manager.md#deferred-tab-badges-do-not-port) |
| _rest not audited yet_ | — | — | — | — |

[`Unit/Resources/RelationManagers/RelationGroupTest`](../../../packages/panels/tests/Unit/Resources/RelationManagers/RelationGroupTest.php) covers the literal and closure arms of both `make()` arguments, the unfiltered list before the owner and page are known, the filter dropping a manager that declines the record in both its class-string and configuration forms, the owner/page closure injection, the badge ordering, and the `tab()` callback.

## Divergences

### The two constructor arguments arrive through setters

Filament's `RelationGroup` takes `$label` and `$managers` as constructor arguments and builds through `app(static::class, [...])`.
Every Sylament component is built by `Component::newInstance()` through the container's component locator, which cannot pass constructor arguments, so `make()` follows the shape schemas' [`LiveComponent`](../schemas/live-component.md#the-two-constructor-arguments-arrive-through-setters) and [`Flex`](../schemas/flex.md) already use: `newInstance()`, the two setters, then `configure()`.
The two setters are additions the public `make()` signature hides; `$label` stays **required**, exactly as Filament has it, and `$managers` takes an empty default because a group with none is a well-formed (if useless) object here where Filament would fatal.

Reaching that locator at all takes a service definition, and the group gets an explicit one in [`SylamentPanelsBundle::loadExtension()`](../../../packages/panels/src/SylamentPanelsBundle.php) — `->set(RelationGroup::class)->autowire()->autoconfigure()` — rather than a glob, because it is the only `Component` under `Resources/RelationManagers/` (the other two classes there are a live component and a value object).
Without it `newInstance()` silently falls back to `new`, `EvaluatesClosures::getInvoker()` builds an `Invoker` over an empty `Container`, and every closure the group evaluates — `label()`, `managers()`, the four badge/icon setters, `tab()` — resolves no service at all.
The panels bundle Integration suite pins the invoker identity, the same pin tables' suite carries for `BaseFilter`/`Indicator`.

### The closure manager list is evaluated; Filament's is returned raw

Filament types `$managers` as `array | Closure`, but the early return in `getManagers()` — taken whenever the owner or the page is missing — hands back `$this->managers` unevaluated, so a closure list leaks a `Closure` where the caller expects an array.
(The filtered path never evaluates it either; it passes the raw value to `array_filter()`.)
Ours evaluates once at the top of the method and filters the result, so both paths answer an array.

### The badge is resolved before the colour and tooltip closures read it

Filament's `getTabComponent()` assigns `$badge` inside the *false* arm of a ternary — `->badge($isTabBadgeDeferred ? fn () => … : ($badge = $this->getBadge()))` — and then passes `$badge ?? null` to `badgeColor()` and `badgeTooltip()`.
With a deferred badge the assignment never runs, so a colour closure taking `$badge` receives `null`.
Ours resolves the badge into a variable first and passes it to both, which is what the argument is for.
(The deferred arm does not port here at all — see the [manager's divergence](relation-manager.md#deferred-tab-badges-do-not-port).)
