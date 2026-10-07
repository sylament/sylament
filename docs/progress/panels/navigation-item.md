---
description: 'The sidebar navigation item: label, icon, url, badge, sort, group, visibility and active state.'
ports:
  - vendor/filament/filament/src/Navigation/NavigationItem.php
owns:
  - packages/panels/src/Navigation/NavigationItem.php
---
# NavigationItem (panels)

`Sylament\Panels\Navigation\NavigationItem` extends support's `Component` and composes its `HasIcon`.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make()`](../../../packages/panels/src/Navigation/NavigationItem.php) / `label()` / `getLabel()` | [`NavigationItem.php`](../../../vendor/filament/filament/src/Navigation/NavigationItem.php)'s constructor, `make()` and label pair | done | — | [semantics](#make-without-a-label-leaves-getlabel-fatal) |
| [`icon()` / `getIcon()`](../../../packages/support/src/Components/Concerns/HasIcon.php) through `HasIcon` | the icon pair | done | — | — |
| [`activeIcon()` / `getActiveIcon()`](../../../packages/panels/src/Navigation/NavigationItem.php) | the active-icon pair | done | — | — |
| [`url()` / `getUrl()`](../../../packages/panels/src/Navigation/NavigationItem.php) / `openUrlInNewTab()` / `shouldOpenUrlInNewTab()` | the url and new-tab pairs | done | — | [diverges](#a-user-derived-navigation-url-must-be-validated-before-it-reaches-href) |
| [`badge()` / `getBadge()`](../../../packages/panels/src/Navigation/NavigationItem.php) | the badge pair | done | — | [diverges](#badge-preserves-a-badge-colour-set-before-it) |
| [`badgeColor()` / `getBadgeColor()`](../../../packages/panels/src/Navigation/NavigationItem.php) | the badge-colour pair | done | — | [diverges](#getbadgecolor-takes-no-badge-argument) |
| [`group()` / `getGroup()`](../../../packages/panels/src/Navigation/NavigationItem.php) | the group pair | done | — | [diverges](#group-and-getgroup-carry-no-unitenum-arm) |
| [`sort()` / `getSort()`](../../../packages/panels/src/Navigation/NavigationItem.php) | the sort pair | done | — | — |
| [`visible()` / `hidden()` / `isVisible()` / `isHidden()`](../../../packages/panels/src/Navigation/NavigationItem.php) | the visibility pairs, hidden winning | done | — | — |
| [`isActiveWhen()` / `isActive()`](../../../packages/panels/src/Navigation/NavigationItem.php) | the memoising active-state pair | done | — | [panel.md](panel.md#navigation-is-assembled-on-the-panel-not-by-a-navigationmanager) |
| [`setEvaluationServices()`](../../../packages/panels/src/Navigation/NavigationItem.php) and its closure seam | `app()->make()` inside Filament's closure evaluation | done | — | [diverges](#the-item-hands-its-own-services-to-evaluated-closures) · [semantics](#the-evaluation-services-are-a-container-build-requirement) |
| `key()` / `getKey()` | the key pair | todo | — | [detail](#the-nesting-arm-waits-on-key) |
| `parentItem()` / `getParentItem()` / `childItems()` / `getChildItems()` / `isChildItemsActive()`, and `getIcon()`'s parent-without-icon refusal | the nesting arm | todo | — | [detail](#the-nesting-arm-waits-on-key) |
| `HasBadgeTooltip` | [`Support/Concerns/HasBadgeTooltip.php`](../../../vendor/filament/support/src/Concerns/HasBadgeTooltip.php) | todo | — | [detail](#the-badge-tooltip-and-extra-attributes-wait-on-a-sidebar-template-that-renders-them) |
| `HasExtraAttributes` | [`Support/Concerns/HasExtraAttributes.php`](../../../vendor/filament/support/src/Concerns/HasExtraAttributes.php) | todo | — | [detail](#the-badge-tooltip-and-extra-attributes-wait-on-a-sidebar-template-that-renders-them) |

## Details

### The nesting arm waits on `key()`

`key()` is the identity a nested item is addressed by, and `getKey()` falls back to the label, so it earns its keep only once items nest.
The nesting arm lands whole with it: the parent and child pairs, `isChildItemsActive()`, and the `LogicException` Filament's `getIcon()` throws for a parent item with no icon.
[The band's `processParentNavigationItems()` pass](has-sub-navigation.md#item-nesting-waits-on-navigationitems-nesting-arm) waits on the same arm.

### The badge tooltip and extra attributes wait on a sidebar template that renders them

Support ships both concerns ([`HasBadgeTooltip`](../../../packages/support/src/Components/Concerns/HasBadgeTooltip.php) and [`HasExtraAttributes`](../../../packages/support/src/Components/Concerns/HasExtraAttributes.php)), and `NavigationItem` composes neither.
[`SidebarItem`](../../../packages/panels/templates/components/SidebarItem/index.html.twig) renders no badge tooltip and no item attribute bag, so composing either would publish a setter with no effect.

## Semantics

### `make()` without a label leaves `getLabel()` fatal

`NavigationItem::$label` is typed `string|Closure` with no default and no constructor assignment, so it is a typed-but-uninitialised property.
`NavigationItem::make()` only calls `label()` when a label is `filled()`, which means `NavigationItem::make()->getLabel()` throws `Error: Typed property … must not be accessed before initialization` rather than returning `null`.
That is Filament's shape exactly — its constructor guards the same `label()` call with `filled()` over the same undefaulted property — and it is deliberate: a navigation item with no label is not a valid item, and the fatal names the missing call.
The reason it reads like a bug is the nullable parameter on `make()`, which exists so a `Closure` label can be passed positionally; nothing else about it says the label is optional.
Anything building items from data must supply the label at `make()` or call `label()` before rendering.

### The evaluation services are a container-build requirement

`setEvaluationServices()` is a plain `#[Required]` setter on an autowired service, so `UrlGeneratorInterface`, `RequestStack` and `IconManager` must all be resolvable when the container compiles.
An application that disables `framework.router`, leaving no `UrlGeneratorInterface` to autowire, fails at container build with an unresolved argument, not at the first navigation render.
The coupling is benign in practice, since a panel exists to mount routes and cannot work without the router anyway, and it is the price of not publishing three public aliases in every consuming application.
It is recorded because the failure names `NavigationItem`, which is not where a reader would look for a router dependency.
A `new`-built item is unaffected, because the setter only fires through the container.

## Divergences

### `badge()` preserves a badge colour set before it

Filament's `badge($badge, $color = null)` assigns `$this->badgeColor = $color` unconditionally, so `->badgeColor('danger')->badge('12')` silently clears the colour back to `null`.
Ours writes the colour only when one is passed, so the earlier `badgeColor()` survives and the two setters compose in either order.
The cost is that `badge()` cannot clear a colour; `badgeColor(null)` does that, which is the explicit spelling anyway.

### `group()` and `getGroup()` carry no `UnitEnum` arm

Filament types the group `string | UnitEnum | Closure | null` and returns `string | UnitEnum | null`, so an enum case can name a group and `NavigationGroup::fromEnum()` reads its label and icon off the case.
Ours is string-only.
The enum arm lands with [`fromEnum()`](navigation-group.md#fromenum-waits-on-the-collapsible-enum-contract), which waits on the `Collapsible` enum contract (support ports `HasLabel` and `HasIcon`) — deferring it here keeps the two halves from disagreeing about what a group key is.

### `getBadgeColor()` takes no `$badge` argument

Filament's `getBadgeColor(?string $badge = null)` injects `['badge' => $badge]` into the closure evaluation, so a colour closure can branch on the badge value it is colouring (`fn ($badge) => $badge > 10 ? 'danger' : 'gray'`).
Nothing here calls it with an argument — `SidebarItem` reads `item.getBadgeColor` — so the parameter would be dead weight that still has to be threaded through every caller.
It returns with the first surface that passes a badge in.

### A user-derived navigation URL must be validated before it reaches `href`

Filament's `url()` carries a `// Security:` comment saying that a URL derived from user input must be validated, or a `javascript:` URL becomes XSS when rendered into an `href`.
The comment is not ported — a rationale comment whose home is this trail — so the invariant lives here, and it holds for us just as hard: `SidebarItem` renders `item.getUrl` straight into `href` through `attrs()`, which escapes the attribute *value* but does not police its **scheme**, and nothing in `NavigationItem` inspects the URL.
So an application feeding user-supplied strings into `url()` owns the validation.
Sylament-generated URLs (a route path, a resource URL) are safe by construction; anything read from a database, a config file a user can edit, or a request parameter is not.

### The item hands its own services to evaluated closures

Filament resolves an unbound class hint in an evaluated closure through `app()->make()`, so any service is reachable from any closure with no wiring at all.
Symfony's container cannot do that: the [`Invoker`](../../../packages/support/src/Evaluation/Invoker.php) reads through `ContainerInterface::has()`/`get()`, and every alias worth hinting here — FrameworkBundle's `RequestStack::class` and `UrlGeneratorInterface::class`, support's `IconManager::class` — is private, so the lookup answers nothing and the closure dies at render time on *"[$icons] was unresolvable"*, with nothing at container-build time to catch it.
Publishing those aliases would fix the symptom by making three services public in every consuming application, for the benefit of closures **we** ship.

So the item carries them instead.
`NavigationItem` takes `UrlGeneratorInterface`, `RequestStack` and `IconManager` through one `#[Required]` setter — it is a `sylament.component`-tagged, container-built, non-shared service, so the setter fires on every `make()` — and hands them to closures from `resolveDefaultClosureDependencyForEvaluationByType()`, the [`EvaluatesClosures`](../../../packages/support/src/Concerns/EvaluatesClosures.php) seam the `Invoker` consults **before** the container arm.
Those are exactly the three types sylament's own closures hint ([`Page::getNavigationItems()`](page.md) builds the url and active-state ones, [`Dashboard`](dashboard.md) the icon one), so the shipped navigation works against a stock container.

Two things the design deliberately leaves alone.
A `new`-built item keeps its three properties `null`, the hook returns `null` and the container arm is consulted, so nothing that works outside the container depends on the setter.
And `NavigationGroup` gets **no** override: its closures have no shipped service-hinting examples, and leaving it bare is what keeps the container arm under test ([`SylamentPanelsBundleTest`](../../../packages/panels/tests/Integration/SylamentPanelsBundleTest.php) evaluates a `RequestStack` closure on a group, which passes only because the panels test kernel republishes that one alias).
A consumer hinting a fourth service type still republishes its alias, and that is the case the group test stands in for.
