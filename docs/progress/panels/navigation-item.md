---
description: 'The sidebar navigation item: label, icon, url, badge, sort, group, visibility and active state.'
ports:
  - vendor/filament/filament/src/Navigation/NavigationItem.php
owns:
  - packages/panels/src/Navigation/NavigationItem.php
---
# NavigationItem (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Navigation\NavigationItem`](../../../packages/panels/src/Navigation/NavigationItem.php) | [`Navigation/NavigationItem.php`](../../../vendor/filament/filament/src/Navigation/NavigationItem.php) | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### The item's own surface is ported and its nesting arm is deferred

The item's own surface is ported — label, icon and active icon, url with the new-tab flag, badge and badge colour, sort, group, the visible/hidden pair with hidden winning, and the memoising `isActiveWhen()`/`isActive()`.

Deferred, all todo:

- `key()` / `getKey()` — the identity a nested item is addressed by; it only earns its keep once items nest.
- `parentItem()` / `getParentItem()` and `childItems()` / `getChildItems()` / `isChildItemsActive()` — the nesting arm, deferred whole with `key`, which is what makes [the band's `processParentNavigationItems()` pass](has-sub-navigation.md#three-v1-skips-item-nesting-collapsible-groups-and-badge-tooltips) wait too.
- The badge tooltip (`HasBadgeTooltip`) — not ported in `support` yet.
- Extra attributes.
  `Sylament\Support\Components\Concerns\HasExtraAttributes` is ported, but `NavigationItem` does not compose it: nothing in the sidebar templates renders an item attribute bag yet, so composing it would publish a setter with no effect.

A container-built item also carries an injected service set — `UrlGeneratorInterface`, `RequestStack`, `IconManager` — that it hands to its own evaluated closures ahead of the `Invoker`'s container arm, which is what lets sylament's shipped url, active-state and icon closures run against a stock container: [the item hands its own services to evaluated closures](#the-item-hands-its-own-services-to-evaluated-closures).

That set is a **build-time** requirement, not only a render-time one, and it is consumer-visible.
`setEvaluationServices()` is a plain `#[Required]` setter on an autowired service, so all three types must be resolvable when the container *compiles*: an application that disables `framework.router` — leaving no `UrlGeneratorInterface` to autowire — fails at container build with an unresolved argument, not at the first navigation render.
The coupling is benign in practice, since a panel exists to mount routes and cannot work without the router anyway, and it is the price of not publishing three public aliases in every consuming application.
It is recorded because the failure names `NavigationItem`, which is not where a reader would look for a router dependency.
A `new`-built item is unaffected — the setter only fires through the container.

Four deltas against Filament's item, each with its own divergence: [`badge()` keeps a colour set earlier](#badge-preserves-a-badge-colour-set-before-it), [`group()`/`getGroup()` carry no `UnitEnum` arm](#group-and-getgroup-carry-no-unitenum-arm), [`getBadgeColor()` takes no `$badge` argument](#getbadgecolor-takes-no-badge-argument), and [the security invariant Filament states in a comment lives here](#a-user-derived-navigation-url-must-be-validated-before-it-reaches-href).
The uninitialised `$label` property is a [semantics note](#make-without-a-label-leaves-getlabel-fatal).

## Semantics

### `make()` without a label leaves `getLabel()` fatal

`NavigationItem::$label` is typed `string|Closure` with **no** default and no constructor assignment, so it is a typed-but-uninitialised property.
`NavigationItem::make()` only calls `label()` when a label is `filled()`, which means `NavigationItem::make()->getLabel()` throws `Error: Typed property … must not be accessed before initialization` rather than returning `null`.
That is Filament's shape exactly — its constructor guards the same `label()` call with `filled()` over the same undefaulted property — and it is deliberate: a navigation item with no label is not a valid item, and the fatal names the missing call.
The reason it reads like a bug is the nullable parameter on `make()`, which exists so a `Closure` label can be passed positionally; nothing else about it says the label is optional.
Anything building items from data must supply the label at `make()` or call `label()` before rendering.

## Divergences

### `badge()` preserves a badge colour set before it

Filament's `badge($badge, $color = null)` assigns `$this->badgeColor = $color` unconditionally, so `->badgeColor('danger')->badge('12')` silently clears the colour back to `null`.
Ours writes the colour only when one is passed, so the earlier `badgeColor()` survives and the two setters compose in either order.
The cost is that `badge()` can no longer *clear* a colour — pass `badgeColor(null)` for that, which is the explicit spelling anyway.

### `group()` and `getGroup()` carry no `UnitEnum` arm

Filament types the group `string | UnitEnum | Closure | null` and returns `string | UnitEnum | null`, so an enum case can name a group and `NavigationGroup::fromEnum()` reads its label and icon off the case.
Ours is string-only.
The enum arm lands with `fromEnum()` and the `HasLabel` / `HasIcon` / `Collapsible` enum contracts it needs, none of which are ported — deferring it here keeps the two halves from disagreeing about what a group key is.

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
A `new`-built item behaves exactly as before — the properties stay `null`, the hook returns `null`, and the container arm is consulted — so nothing that worked outside the container stops working.
And `NavigationGroup` gets **no** override: its closures have no shipped service-hinting examples, and leaving it bare is what keeps the container arm under test ([`SylamentPanelsBundleTest`](../../../packages/panels/tests/Integration/SylamentPanelsBundleTest.php) evaluates a `RequestStack` closure on a group, which passes only because the panels test kernel republishes that one alias).
A consumer hinting a fourth service type still republishes its alias, and that is the case the group test stands in for.
