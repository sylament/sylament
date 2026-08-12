# Panels — port progress

Ports [`filament/filament`](../../../vendor/filament/filament) into
[`packages/panels`](../../../packages/panels) — Filament ships its panel builder as
the `filament/filament` package. The admin shell: panel config, routing, auth,
resources, pages, navigation.

**Status:** in progress — see the [status vocabulary](../README.md#statuses) and
[what goes where](../README.md#one-row-per-file-one-file-per-thing).

The tables below are structure plus the first ported rows; nothing has been
audited end to end yet.
[`support/component.md`](../support/component.md) is the shape each detail file
follows.

## Carried decisions

Settled while porting other packages, recorded here because they constrain how
this one gets built and would otherwise be re-litigated when the panel epics
start.

### `Resource` is a service, not a static class-string registry

Filament's `Resource` is entirely static — `OrderResource::form($schema)`,
`::table($table)`, `::getUrl()` — which works because Laravel's `app()` global
lets a static method reach any service. Symfony has no such global, so a static
method cannot reach a dependency at all.

The consequence is not cosmetic. If `Resource` stays static, its `form()` and
`table()` cannot hold a dependency, so neither can the `OrderForm`/`OrdersTable`
aggregator classes they delegate to, and the **only** remaining place a
dependency can live is a per-field or per-column leaf class. That would force
every user to split everything, which is the opposite of the intent: users who
want one class per form should be able to have one.

So `Resource` is registered as a service and collected by tag — the shape
`AsController`/`AsEventListener` use — and `form()`/`table()`/`infolist()`
become instance methods.

**What survives unchanged.** The user-facing API stays a class-string:
`protected static string $resource = OrderResource::class;` on pages, and
`OrderResource::class` wherever a resource is named. Pages resolve it through a
`ServiceLocator`, exactly as `$this->createForm(OrderType::class)` resolves a
form type through Symfony Form's `FormRegistry`. That precedent is the reason
this reads as Symfony idiom rather than divergence for its own sake.

**What it costs.** `static::getResource()::form($schema)` and its siblings
become a locator lookup — 66 call sites across 10 files in
`vendor/filament/filament/src`, all framework code, none of it user code.
`getUrl()` and the `can*()` authorization statics cannot stay static either
(they need `UrlGeneratorInterface` and an authorization checker); expect
`getUrl()` to gain a Twig function and `can*()` to delegate to voters.

**Decide before writing any of it.** Whether a resource is identified by
class-string (recommended — preserves Filament's API and inherits the
`FormRegistry` precedent) or by name/slug. Pages, routes, and navigation all
hang off that choice, and it is expensive to reverse once they exist.

**Why it is safe to defer until now.** Nothing below `Resource` depends on it:
component classes get their dependencies from the container the moment they are
registered, whether a static or an instance aggregator created them.

## Components

The panel surface —
[`src/Panel.php`](../../../vendor/filament/filament/src/Panel.php),
[`src/Resources`](../../../vendor/filament/filament/src/Resources),
[`src/Pages`](../../../vendor/filament/filament/src/Pages),
[`src/Navigation`](../../../vendor/filament/filament/src/Navigation),
[`src/Auth`](../../../vendor/filament/filament/src/Auth),
[`src/Clusters`](../../../vendor/filament/filament/src/Clusters),
[`src/GlobalSearch`](../../../vendor/filament/filament/src/GlobalSearch),
[`src/Widgets`](../../../vendor/filament/filament/src/Widgets).

| Component | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`Navigation\NavigationItem`](../../../packages/panels/src/Navigation/NavigationItem.php) | [`Navigation/NavigationItem.php`](../../../vendor/filament/filament/src/Navigation/NavigationItem.php) | in progress | — |
| [`Navigation\NavigationGroup`](../../../packages/panels/src/Navigation/NavigationGroup.php) | [`Navigation/NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | in progress | — |
| [`Pages\Page`](../../../packages/panels/src/Pages/Page.php) | [`Pages/Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | in progress | — |
| _rest not audited yet_ | | | |

### NavigationItem

The item's own surface is ported — label, icon and active icon, url with the
new-tab flag, badge and badge colour, sort, group, the visible/hidden pair with
hidden winning, and the memoising `isActiveWhen()`/`isActive()`.

Deferred, all todo:

- `key()` / `getKey()` — the identity a nested item is addressed by; it only
  earns its keep once items nest.
- `parentItem()` / `getParentItem()` and `childItems()` / `getChildItems()` /
  `isChildItemsActive()` — the nesting arm, deferred whole with `key`.
- The badge tooltip (`HasBadgeTooltip`) and extra attributes
  (`HasExtraAttributes`) — neither trait is ported in `support` yet.
- `getBadgeColor()` takes no `?string $badge` argument here, and `group()` takes
  no `UnitEnum` arm — both land with the surface that needs them.

### NavigationGroup

Label, icon, items, the collapsible/collapsed pair and `isActive()` are ported.
`items()` takes `array<NavigationItem> | Closure` where Filament takes
`array | Arrayable` — the closure arm is the idiom translation of Laravel's
`Arrayable`, and `getItems()` evaluates it.

Deferred, all todo:

- `HasExtraSidebarAttributes` / `HasExtraTopbarAttributes` — the extra-attribute
  trait they build on is not ported in `support` yet, same gap `NavigationItem`
  records for `HasExtraAttributes`.
- `fromEnum()` — needs the `HasLabel` / `HasIcon` / `Collapsible` enum contracts,
  and the `UnitEnum` group arm `NavigationItem::group()` also defers.
- The panel-level collapsible default. Filament falls back to
  `filament()->hasCollapsibleNavigationGroups()`; there is no panel to ask yet,
  so `isCollapsible()` is pinned to `true` until M2 · 2 introduces the setting.

Two deliberate deltas beyond that. `collapsed()` does **not** also call
`collapsible()` the way Filament's does — that hidden coupling makes
`collapsed(false)` silently turn the group non-collapsible — so the gate lives in
the reader instead: `isCollapsed()` returns `false` whenever the group is not
collapsible. And `isActive()` skips hidden items, so a group is active only for
an item the user can actually see.

### Page

The declarative surface only: the `title` static with `getTitle()`, the
`heading`/`subheading` pair with `getHeading()` falling back to the title, and
the four navigation statics (`navigationIcon`, `navigationLabel`,
`navigationSort`, `navigationGroup`) with their static getters. That is enough
for navigation to describe a page before anything can render one.

Deferred, all todo:

- Live-component wiring and routing — a page is a Livewire component in Filament;
  here it becomes a UX LiveComponent plus a route, owned by M2 · 2.
- The `BasePage` split — Filament separates `BasePage` (the render/lifecycle
  half) from `Page` (the panel-aware half); the split lands with the half that
  needs it.
- Breadcrumbs and header actions — both need the panel shell around them.
- Page discovery. How a page is registered is decided in M2 · 2, alongside the
  `Resource`-as-a-service decision above; nothing here presumes it.

## Features

Cross-cutting concerns and traits — this package has no top-level `Concerns/`;
they sit per subsystem:
[`src/Panel/Concerns`](../../../vendor/filament/filament/src/Panel/Concerns),
[`src/Pages/Concerns`](../../../vendor/filament/filament/src/Pages/Concerns),
[`src/Resources/Concerns`](../../../vendor/filament/filament/src/Resources/Concerns),
[`src/Navigation/Concerns`](../../../vendor/filament/filament/src/Navigation/Concerns).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| _not audited yet_ | | | |

## Supporting surface

Enums, contracts, HTTP layer, events, templates —
[`src/Enums`](../../../vendor/filament/filament/src/Enums),
[`src/Contracts`](../../../vendor/filament/filament/src/Contracts),
[`src/Http`](../../../vendor/filament/filament/src/Http),
[`src/Events`](../../../vendor/filament/filament/src/Events),
[`src/View`](../../../vendor/filament/filament/src/View).

| Item | Ports | Status | Detail |
| --- | --- | --- | --- |
| [Layout templates](../../../packages/panels/templates/components) | [`resources/views/components`](../../../vendor/filament/filament/resources/views/components) | in progress | — |
| [Assets (JS/CSS)](../../../packages/panels/assets) | [`resources/js/`](../../../vendor/filament/filament/resources/js), [`resources/css/`](../../../vendor/filament/filament/resources/css) | in progress | the `@sylament/panels` workspace ships one Stimulus controller (`sidebar`) and the `sy-pa-*` rules, which compile into support's stylesheet — [Assets](#assets) |
| _rest not audited yet_ | | | |

### Layout templates

`Sidebar`, `SidebarGroup` and `SidebarItem` render the navigation model as
static Twig components: the `<aside id="sy-pa-sidebar">` shell, one `<li>` per
group with its label button carrying `aria-expanded`/`aria-controls` and the
`sylament--panels--sidebar#toggleGroup` action, and one `<li>` per visible item
with its icon (the active icon when the item is active), label and badge.
Hidden items never render, and collapse state is markup only — the
`sy-collapsed` class plus `data-group-label`, which the Stimulus controller
reads and writes.

What Filament's sidebar has and this does not, all todo:

- Desktop collapse (the narrow icon-only rail) and the collapsed-state tooltips
  on items — both need the panel-level sidebar settings M2 · 2 introduces.
- Sub-navigation: Filament renders `childItems` nested under an active item, and
  the nesting arm of `NavigationItem` is deferred whole (see
  [NavigationItem](#navigationitem)).
- The rest of `resources/views/components` — topbar, logo, avatar, tenant menu,
  theme switcher, page and header — none of it ported.

The Livewire-shell → static-component divergence these templates sit on is
recorded with the layout that composes them.

### Assets

`packages/panels/assets` is the `@sylament/panels` pnpm workspace, JS-only in the
same shape as `@sylament/notifications`: one Stimulus controller builds to its own
`dist`, while `src/styles/index.css` is imported by support's stylesheet and
compiles into support's single built CSS. The built `dist` is mapped into
AssetMapper as `@sylament/panels`. The four style files (`layout`, `sidebar`,
`topbar`, `header`) carry the `sy-pa-*` rules mobile-first — off-canvas drawer
below `lg`, two-column grid at `lg` and up — and take their active-item colour
from the `--sy-text` slot rather than a hardcoded shade.

The `sidebar` controller is declared `fetch: eager`, matching notifications: the
shell must apply stored collapse state on the first paint rather than after lazy
loading catches up. It owns the drawer open/close pair (`toggle`, `close`,
`closeOnEscape`), the per-group collapse toggle, and persistence under the
localStorage key `sylament.panels.sidebar.collapsedGroups` (a JSON array of group
labels read from `data-group-label`).

Two behaviours worth knowing. Every storage read and write is guarded, and an
absent key, unavailable storage or an unparseable value all collapse to the same
"no usable state" signal — in which case the server-rendered classes stand and
the key is seeded from them, so a user who has never touched a group gets the
markup's defaults instead of everything force-expanded. And the controller keeps
`aria-expanded` on each group button in sync with the class it toggles, since the
markup is static and nothing else updates it.

What Filament's panel assets have and this does not, all todo:

- Desktop collapse (the icon-only rail) and its width/tooltip state — the
  controller tracks the mobile drawer only, and the settings behind it arrive
  with M2 · 2.
- `scroll-sidebar.js` — restoring sidebar scroll position across navigations.
- `dark-mode.js`, `unsaved-changes-alert.js`, `error-notifications.js`,
  `echo.js`, and the resource/page scripts — none of the surfaces they serve
  exist yet.
