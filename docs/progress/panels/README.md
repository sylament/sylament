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

### The shell is Turbo-indifferent and worker-safe

Two constraints the shell is built under. Both are cheap to hold while the shell
is six templates and one controller, and expensive to retrofit once pages,
panels and widgets hang off them.

**Turbo-indifferent.** Every behaviour hangs off Stimulus `connect()` — never
`DOMContentLoaded`, never `turbo:load`, and never an inline `<script>`. A handler
bound to first paint is dead in a Turbo-driven app from the second navigation on,
and an inline script is both a CSP liability and unreachable from a re-rendered
fragment. The landmark ids the shell emits are stable — `sy-pa-sidebar`,
`sy-pa-topbar`, `sy-pa-main` — so a swapped body reconnects to the same targets
and the skip link keeps working. Sidebar collapse state is
restored from `localStorage` on `connect()` rather than preserved with
`data-turbo-permanent`: the markup can be replaced wholesale and the state still
survives, which `data-turbo-permanent` would only manage for the exact subtree it
guards.

**Worker-safe.** No mutable static per-request state anywhere in the shell. A
long-running worker (FrankenPHP, RoadRunner, Swoole) reuses the PHP process
across requests, so anything static that remembers *this* request leaks into the
next one — a class of bug that never shows up under php-fpm and is close to
undebuggable in production. Request awareness reaches the navigation model
through evaluated closures instead: `isActiveWhen(fn (RequestStack $requests) =>
…)`, where the `Invoker` resolves the parameter from the container per call —
`RequestStack` is a shared service, but everything it hands back belongs to the
request being served, so nothing has to be remembered. One wiring caveat comes
with it, and `NavigationItem` absorbs the part of it sylament's own closures hit:
the `Invoker`'s container arm reads through `ContainerInterface::has()`/`get()`,
which no **private** service answers — and FrameworkBundle's `RequestStack::class`
and `UrlGeneratorInterface::class` aliases and support's `IconManager::class`
alias are all private. Those three are exactly the types sylament's shipped
closures hint ([`Page::getNavigationItems()`](#page) builds the url and
active-state ones, [`Dashboard`](#dashboard) the icon one), so a container-built
item carries them itself
([the injected set](#the-item-hands-its-own-services-to-evaluated-closures)) and
no application publishes an alias for them. A consumer hinting any **other**
service type in a navigation closure still lands on the container arm and still
has to republish that alias public. The panels test kernel republishes
`RequestStack` for precisely that path, and
[`SylamentPanelsBundleTest`](../../../packages/panels/tests/Integration/SylamentPanelsBundleTest.php)
pins the arm through a `NavigationGroup`, which carries no override.
M2 · 2's current-panel lookup must be derived the same way,
from the request, and never held in a stateful singleton.

### The shell is not live

Filament v5's sidebar and topbar are Livewire components. Ours are anonymous Twig
components rendering a plain PHP navigation model, because nothing in M2 needs a
server round-trip from the shell: navigation is a set of links, collapse state is
client-side, and the only interactive parts (the mobile drawer, per-group
collapse) are markup plus one Stimulus controller. A live shell would buy
re-rendering on the server and cost a component round-trip on every page.

Revisit when something in the shell must change without a navigation — the
database-backed notifications trigger is the first candidate, since an unread
count in the topbar is a server fact that changes between page loads. Until then
the divergence is a simplification, not a gap.

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
| [`Navigation\NavigationItem`](../../../packages/panels/src/Navigation/NavigationItem.php) | [`Navigation/NavigationItem.php`](../../../vendor/filament/filament/src/Navigation/NavigationItem.php) | in progress | [NavigationItem](#navigationitem) |
| [`Navigation\NavigationGroup`](../../../packages/panels/src/Navigation/NavigationGroup.php) | [`Navigation/NavigationGroup.php`](../../../vendor/filament/filament/src/Navigation/NavigationGroup.php) | in progress | [NavigationGroup](#navigationgroup) |
| [`Pages\Page`](../../../packages/panels/src/Pages/Page.php) | [`Pages/Page.php`](../../../vendor/filament/filament/src/Pages/Page.php) | in progress | [Page](#page) |
| [`Pages\Dashboard`](../../../packages/panels/src/Pages/Dashboard.php) | [`Pages/Dashboard.php`](../../../vendor/filament/filament/src/Pages/Dashboard.php) | in progress | [Dashboard](#dashboard) |
| [`Panel`](../../../packages/panels/src/Panel.php) | [`Panel.php`](../../../vendor/filament/filament/src/Panel.php) | in progress | [Panel](#panel) |
| [`PanelProvider`](../../../packages/panels/src/PanelProvider.php) | [`PanelProvider.php`](../../../vendor/filament/filament/src/PanelProvider.php) | in progress | [Panel](#panel) |
| [`PanelRegistry`](../../../packages/panels/src/PanelRegistry.php) | [`PanelRegistry.php`](../../../vendor/filament/filament/src/PanelRegistry.php) | in progress | [Panel](#panel) |
| [`CurrentPanel`](../../../packages/panels/src/CurrentPanel.php) | [`FilamentManager.php`](../../../vendor/filament/filament/src/FilamentManager.php) current-panel arm, [`Http/Middleware/SetUpPanel.php`](../../../vendor/filament/filament/src/Http/Middleware/SetUpPanel.php) | in progress | [Panel](#panel) |
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
- The badge tooltip (`HasBadgeTooltip`) — not ported in `support` yet.
- Extra attributes. `Sylament\Support\Components\Concerns\HasExtraAttributes` is
  ported, but `NavigationItem` does not compose it: nothing in the sidebar
  templates renders an item attribute bag yet, so composing it would publish a
  setter with no effect.

A container-built item also carries an injected service set —
`UrlGeneratorInterface`, `RequestStack`, `IconManager` — that it hands to its own
evaluated closures ahead of the `Invoker`'s container arm, which is what lets
sylament's shipped url, active-state and icon closures run against a stock
container:
[the item hands its own services to evaluated closures](#the-item-hands-its-own-services-to-evaluated-closures).

That set is a **build-time** requirement, not only a render-time one, and it is
consumer-visible. `setEvaluationServices()` is a plain `#[Required]` setter on an
autowired service, so all three types must be resolvable when the container
*compiles*: an application that disables `framework.router` — leaving no
`UrlGeneratorInterface` to autowire — fails at container build with an unresolved
argument, not at the first navigation render. The coupling is benign in practice,
since a panel exists to mount routes and cannot work without the router anyway,
and it is the price of not publishing three public aliases in every consuming
application. It is recorded because the failure names `NavigationItem`, which is
not where a reader would look for a router dependency. A `new`-built item is
unaffected — the setter only fires through the container.

Four deltas against Filament's item, each with its own divergence:
[`badge()` keeps a colour set earlier](#badge-preserves-a-badge-colour-set-before-it),
[`group()`/`getGroup()` carry no `UnitEnum` arm](#group-and-getgroup-carry-no-unitenum-arm),
[`getBadgeColor()` takes no `$badge` argument](#getbadgecolor-takes-no-badge-argument),
and [the security invariant Filament states in a comment lives
here](#a-user-derived-navigation-url-must-be-validated-before-it-reaches-href).
The uninitialised `$label` property is a
[semantics note](#make-without-a-label-leaves-getlabel-fatal).

### NavigationGroup

Label, icon, items, the collapsible/collapsed pair and `isActive()` are ported.

Deferred, all todo:

- `HasExtraSidebarAttributes` / `HasExtraTopbarAttributes` — Filament's two
  per-surface attribute traits (`Filament\Navigation\Concerns`), neither ported;
  same reason `NavigationItem` records for extra attributes, since no template
  renders a group attribute bag.
- `fromEnum()` — needs the `HasLabel` / `HasIcon` / `Collapsible` enum contracts,
  and the `UnitEnum` group arm `NavigationItem::group()` also defers.

The panel-level collapsible default is wired: `isCollapsible()` falls back to
`collapsibleFallback()`, a plain `bool` setter
[`Panel::getNavigation()`](#panel) stamps onto every group it assembles from
`hasCollapsibleNavigationGroups()`. A group built outside a panel keeps the
`true` the property initialises to.

Four deliberate deltas beyond that, each with its own divergence:
[`collapsed()` no longer forces the group
collapsible](#collapsed-does-not-also-make-the-group-collapsible),
[`isActive()` skips hidden items](#isactive-skips-hidden-items),
[`getItems()` evaluates a `Closure` instead of accepting an
`Arrayable`](#getitems-evaluates-a-closure-where-filament-accepts-an-arrayable),
and [the panel collapsible default is stamped on the group rather than read from
a facade](#the-panel-collapsible-default-is-stamped-on-the-group-not-read-from-a-facade).

### Page

The declarative surface plus the routing and live-component half. The `title`
static with `getTitle()` (falling back to the kebab-cased, title-cased class
basename), the `heading`/`subheading` pair with `getHeading()` falling back to
the title, the navigation statics (`navigationIcon`, `activeNavigationIcon`,
`navigationLabel`, `navigationSort`, `navigationGroup`) with their static
getters, and the `navigationBadge`/`navigationBadgeColor` hooks a subclass
overrides. `getNavigationLabel()` carries Filament's full fallback chain —
`$navigationLabel`, then `$title`, then the humanized class basename — so a page
that sets neither static still names itself in the sidebar.

Routing and rendering: `getSlug()` (the `$slug` static, else the kebab-cased
class basename), `getRoutePath()`, `getRouteName(Panel $panel)` — the
`sylament.<panel id>.pages.<slug>` convention — `getComponentName()` (the FQCN
with `\` replaced by `:`, the ux-twig-component tag name), and `getTemplate()` (the
`$template` property, else `@SylamentPanels/partials/Page/index.html.twig`) as the
`template_from_method` target. `DefaultActionTrait` makes the page a live
component. `getNavigationItems(Panel $panel)` builds the one `NavigationItem` a
page contributes, its `url()` and `isActiveWhen()` closures resolving
`UrlGeneratorInterface` and `RequestStack` through the `Invoker` at render time
rather than capturing them.

Deferred, all todo:

- The `BasePage` split — Filament separates `BasePage` (the render/lifecycle
  half) from `Page` (the panel-aware half); the split lands with the half that
  needs it.
- Breadcrumbs and header actions — both need the panel shell around them.
- A writer for the `heading`/`subheading` pair. Filament ships none either — its
  `BasePage` declares both as protected properties a subclass assigns or a
  subclass getter overrides — so today the pair stays subclass-only.

Three deltas against Filament's page:
[`$navigationGroup` is string-only](#navigationgroup-is-string-only-on-page-too),
[`getNavigationItems()` and `getRouteName()` take the panel](#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade),
and [a page registers as a live component through a compiler pass rather than an
attribute](#pages-register-as-live-components-through-a-compiler-pass-not-per-class-attributes).

### Dashboard

The one concrete page the package ships: `$routePath = '/'` (overriding
`getRoutePath()`, so the page owns the panel root rather than `/dashboard`),
`$navigationSort = -2` to keep it first, and `$title = 'Dashboard'`, which
`Page::getTitle()` and `getNavigationLabel()` both read —
[a literal where Filament translates](#the-dashboard-title-is-a-literal-not-a-translation-key).
`getNavigationIcon()` returns a `Closure` resolving
[`PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM`](../../../packages/panels/src/Twig/PanelsIconAlias.php)
through the injected `IconManager`, falling back to `heroicons:home`; the closure
defers the lookup to render time, which is what lets an override registered after
the page class loads still win.

Deferred, all todo: the widgets half (`getWidgets()`, `getColumns()`,
`content()`, the widget schema components) — none of it can land before
`sylament/widgets` exists. Filament's icon fallback also picks between two
heroicon cases on `hasTopNavigation()`; ours resolves the single
`heroicons:home` name, until the top-navigation layout arrives.

### Panel

The definition surface a panel provider configures, plus the two services that
collect and locate panels. `Panel` composes six of Filament's `Panel/Concerns`
traits — `HasId` (write-once id, fatal when read unset), `HasRoutes` (the path
only), `HasComponents` ([`pages()`, accumulating, de-duplicated and
re-indexed](#getpages-re-indexes-where-filament-keeps-array_uniques-gapped-keys)),
`HasNavigation` (`navigationGroups()`/`navigationItems()` registration plus
`getNavigation()`), `HasSidebar` (`collapsibleNavigationGroups()` alone — the
panel-level default [`NavigationGroup::isCollapsible()`](#navigationgroup) falls
back to) and `HasBrandName` — with `default()`/`isDefault()` on the class itself,
matching Filament.

`getNavigation()` is the assembly step: it clones every item each registered page
contributes plus the panel's own `navigationItems()`, drops the hidden ones,
sorts by `getSort()`, and buckets what is left by `getGroup()`. The ungrouped
bucket becomes the leading label-less group; each registered group then claims
its bucket by key **or** label (a clone, so the registration survives repeated
calls); buckets no registration matched follow in encounter order. Every group
that comes out is stamped with `collapsibleFallback($this->hasCollapsibleNavigationGroups())`.
A group with no items is never built, so a registration nothing matched simply
does not render. Cloning at both ends is what makes the method idempotent —
`items()` mutates, and the registered objects are the panel's, not the render's.
`HasComponents` types its page list `class-string<Page>` for this: the assembly
calls `Page`'s static navigation methods on each entry.

`PanelProvider` is the abstract `panel(Panel $panel): Panel` contract; every
subclass an application registers as a service is tagged
`sylament.panels.panel_provider` by autoconfiguration. `PanelRegistry` consumes
that tagged iterator, builds each provider's panel once (memoised), keys them by
id, rejects a duplicate id, and answers `getDefault()` or throws
`NoDefaultPanelSetException`. `CurrentPanel` reads the `_sylament_panel` request
attribute off the `RequestStack` and resolves it through the registry. Both are
ordinary private services: [`PanelRouteLoader`](#routing) injects the registry
and [`PageController`](#routing) injects the current-panel lookup.

Deferred, all todo: the other 28 `Panel/Concerns` traits (auth, tenancy, colors,
assets, render hooks, middleware, plugins, …); the resource and widget
registration arms; `navigation()` / `NavigationBuilder` — the
hand-built navigation escape hatch, which needs nothing from the assembly above
and lands when a consumer wants it; the parent-item nesting pass and the
`UnitEnum` group arm, both waiting on
[`NavigationItem`'s deferred `key()`](#navigationitem); and the resource and
widget contributions to the item list, which arrive with those subsystems.

Seven deltas, each with its own divergence:
[panels are collected from tagged providers rather than registered by a service
provider](#panels-are-collected-from-tagged-providers-not-registered-imperatively),
[the current panel is derived from the request, never stored](#the-current-panel-is-derived-from-the-request-not-stored-on-a-manager),
[`PanelRegistry::get()` has no fuzzy arm](#panelregistryget-has-no-fuzzy-arm),
[navigation is assembled on the panel rather than by a
`NavigationManager`](#navigation-is-assembled-on-the-panel-not-by-a-navigationmanager),
[the panel has no `register()` or `boot()`](#the-panel-has-no-register-or-boot),
[`getBrandName()` returns `null` rather than an application
name](#getbrandname-returns-null-where-filament-falls-back-to-the-app-name),
and [`getPages()` re-indexes](#getpages-re-indexes-where-filament-keeps-array_uniques-gapped-keys).

## Features

Cross-cutting concerns and traits — this package has no top-level `Concerns/`;
they sit per subsystem:
[`src/Panel/Concerns`](../../../vendor/filament/filament/src/Panel/Concerns),
[`src/Pages/Concerns`](../../../vendor/filament/filament/src/Pages/Concerns),
[`src/Resources/Concerns`](../../../vendor/filament/filament/src/Resources/Concerns),
[`src/Navigation/Concerns`](../../../vendor/filament/filament/src/Navigation/Concerns).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`DependencyInjection\Compiler\PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php) | [`Panel/Concerns/HasComponents.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasComponents.php) — `registerLivewireComponents()` / `queueLivewireComponentForRegistration()` | in progress | every `Page` service is autoconfigured `sylament.panels.page` and the pass tags it as a live `twig.component` keyed by `Page::getComponentName()`, rendering through `template_from_method: getTemplate`; it also builds the class-keyed `ServiceLocator` `PageController` reads pages from — [why a pass rather than an attribute](#pages-register-as-live-components-through-a-compiler-pass-not-per-class-attributes) |
| _rest not audited yet_ | | | |

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
| [`Twig\PanelsIconAlias`](../../../packages/panels/src/Twig/PanelsIconAlias.php) | [`View/PanelsIconAlias.php`](../../../vendor/filament/filament/src/View/PanelsIconAlias.php) | in progress | three aliases: the shell's two ([Layout templates](#layout-templates)) and the dashboard's navigation icon ([Dashboard](#dashboard)) |
| [Assets (JS/CSS)](../../../packages/panels/assets) | [`resources/js/`](../../../vendor/filament/filament/resources/js), [`resources/css/`](../../../vendor/filament/filament/resources/css) | in progress | the `@sylament/panels` workspace ships one Stimulus controller (`sidebar`) and the `sy-pa-*` rules, which compile into support's stylesheet — [Assets](#assets) |
| [`Routing\PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php) | [`routes/web.php`](../../../vendor/filament/filament/routes/web.php) — the per-panel route group and its `$page::registerRoutes($panel)` loop, [`Pages/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasRoutes.php) `routes()` | in progress | [Routing](#routing) |
| [`Controllers\PageController`](../../../packages/panels/src/Controllers/PageController.php) | no Filament counterpart — a Filament page **is** its own route action | in progress | [Routing](#routing) |
| [Document template](../../../packages/panels/templates/partials/Document/index.html.twig) | [`components/layout/base.blade.php`](../../../vendor/filament/filament/resources/views/components/layout/base.blade.php) | in progress | [Routing](#routing) |
| [Page template](../../../packages/panels/templates/partials/Page/index.html.twig) | [`components/page/index.blade.php`](../../../vendor/filament/filament/resources/views/components/page/index.blade.php) | in progress | [Routing](#routing) |
| _rest not audited yet_ | | | |

### Layout templates

`Sidebar`, `SidebarGroup` and `SidebarItem` render the navigation model as
static Twig components: the `<aside id="sy-pa-sidebar">` shell, one `<li>` per
group, and one `<li>` per visible item with its icon (the active icon when the
item is active), label and badge — the active item's anchor also carrying
`aria-current="page"`.

A group's label element is a disclosure **only when the group is collapsible**.
`SidebarGroup` picks the tag (`{% set labelTag = collapsible ? 'button' : 'div' %}`)
and nulls `type`, `aria-expanded`, `aria-controls` and the
`sylament--panels--sidebar#toggleGroup` action on the non-collapsible arm, so a
static group renders a plain labelled `<div>` with no disclosure semantics and
no control to operate; only the collapsible arm gets the button, the
`aria-expanded`/`aria-controls` pair pointing at
`sy-pa-sidebar-group-items-<index>`, and the chevron icon. Hidden items never
render, and collapse state is markup only — the `sy-collapsed` class plus
`data-group-label`, which the Stimulus controller reads and writes.

The shell's two default icons are overridable defaults, not hardcoded names: the
group chevron and the topbar hamburger read
[`PanelsIconAlias::SIDEBAR_GROUP_COLLAPSE_BUTTON` and
`TOPBAR_OPEN_SIDEBAR_BUTTON`](../../../packages/panels/src/Twig/PanelsIconAlias.php)
through `constant()` the way
[Modal's close button](../../../packages/support/templates/components/Modal/index.html.twig)
does, so an app swaps either one under [`sylament.icons`](../support/icon-manager.md)
without touching a template. Both values drop Filament's `panels::` prefix
([rename](../renames.md)); the constant names are Filament's.

What Filament's sidebar has and this does not, all todo:

- Desktop collapse (the narrow icon-only rail) and the collapsed-state tooltips
  on items — both need the panel-level sidebar settings M2 · 2 introduces.
- Sub-navigation: Filament renders `childItems` nested under an active item, and
  the nesting arm of `NavigationItem` is deferred whole (see
  [NavigationItem](#navigationitem)).
- The rest of `resources/views/components` — logo, avatar, tenant menu, theme
  switcher and page — none of it ported. (`Topbar` and `Header` are, as the
  shell's own two; `Layout` composes them.)

The Livewire-shell → static-component divergence these templates sit on is
[The shell is not live](#the-shell-is-not-live) above. Two more are theirs:
[sidebar group ids are positional](#sidebar-group-ids-are-positional-not-derived-from-the-label)
and [the render tests sit under
`tests/Integration/Twig/`](#the-panels-render-tests-live-under-testsintegrationtwig).

### Assets

`packages/panels/assets` is the `@sylament/panels` pnpm workspace, JS-only in the
same shape as `@sylament/notifications`: one Stimulus controller builds to its own
`dist`, while `src/styles/index.css` is imported by support's stylesheet and
compiles into support's single built CSS. The built `dist` is mapped into
AssetMapper as `@sylament/panels`. The four style files (`layout`, `sidebar`,
`topbar`, `header`) carry the `sy-pa-*` rules mobile-first — off-canvas drawer
below `lg`, two-column grid at `lg` and up — and paint the active sidebar item
from the [fixed `primary`
palette](#the-active-sidebar-item-paints-a-fixed-primary-shade).

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
`aria-expanded` in sync with the class it toggles, since the markup is static and
nothing else updates it — and the whole restore/persist pass, not just the aria
sync, walks only groups matching `.sy-pa-sidebar-group-btn[aria-expanded]`, so a
non-collapsible group (same label class, no disclosure attributes — see
[Layout templates](#layout-templates)) is deliberately skipped everywhere: a
stale localStorage entry can never collapse a group that has no control to
reopen it.

What Filament's panel assets have and this does not, all todo:

- Desktop collapse (the icon-only rail) and its width/tooltip state — the
  controller tracks the mobile drawer only, and the settings behind it arrive
  with M2 · 2.
- `scroll-sidebar.js` — restoring sidebar scroll position across navigations.
- `dark-mode.js`, `unsaved-changes-alert.js`, `error-notifications.js`,
  `echo.js`, and the resource/page scripts — none of the surfaces they serve
  exist yet.

### Routing

[`PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php)
is a service route loader — tagged `routing.route_loader`, imported with the
`service` type — so the whole panel surface reaches the router in one pass over
`PanelRegistry::all()`. For every page a panel registers it mounts one `GET`
route at the panel path plus the page's route path, named
[`Page::getRouteName($panel)`](#page), carrying three defaults: `_controller`
(always `PageController`), `_sylament_panel` (the id
[`CurrentPanel`](#the-current-panel-is-derived-from-the-request-not-stored-on-a-manager)
reads back) and `_sylament_page` (the page class the controller resolves).
The two halves are joined by trimming the concatenation on both sides and
re-prefixing a single `/`, which is what makes the two degenerate cases work:
`Dashboard`'s `/` route path collapses onto the panel root rather than leaving a
trailing slash (`path('admin')` puts the dashboard at `/admin`), and a panel left
at the default empty path mounts at the site root — `''` joined with
`/settings-page` is `/settings-page`, not `//settings-page`, and its dashboard is
`/`. Trimming only the trailing slash would leave that leading pair in place, and
a `//`-pathed route matches nothing.

Two things beyond mounting. Every page class file and every provider class file
is added to the collection as a `FileResource`: the routes are derived from
method bodies (`getSlug()`, `$routePath`, `panel()`) that Symfony's
`ObjectLoader` cannot see — it tracks only the loader class and its parents — so
without them a dev-mode route cache would survive a renamed slug. And a
`pages()` entry that is not a `Page` subclass throws a `LogicException` naming
the class, at route-build time rather than at the request that finally hits it.

[`PageController`](../../../packages/panels/src/Controllers/PageController.php)
is the single action every panel route points at. It resolves the panel through
`CurrentPanel`, resolves the page **service** from the class-keyed
`ServiceLocator`
[`PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php)
builds, and renders the Document template with `panel`, `pageTitle` and
`componentName`. Each failure mode is its own `LogicException` naming the missing
piece — a page that is not a registered service names the registration to add,
since a page reached through `new` would have no dependencies injected.

The two templates split the document from the page. `Document/index.html.twig`
is the `<html>` shell — title (the page title, then the brand name when the panel
has one), `sylamentStyles()`, the importmap, and the `Layout` component with the
page's live component in its content block, invoked by the tag name
`Page::getComponentName()` returns. `Page/index.html.twig` is what
`Page::getTemplate()` falls back to: the `Header` (rendered only when the page
has a heading or subheading) above an empty `sy-pa-page-content` container that
stays empty until pages carry schemas. Both are `partials/`, not `components/` —
they are rendered from PHP (a controller and a `template_from_method` getter),
never as a `<twig:>` tag.

Four divergences are theirs:
[a page is rendered by a controller rather than routed to directly](#a-panel-page-is-rendered-by-a-controller-not-routed-to-directly),
[the document calls `importmap()` unguarded](#the-document-calls-importmap-unguarded),
[the route import carries no prefix](#the-route-import-carries-no-prefix-because-the-panel-path-is-authoritative),
and [the document renders no notifications outlet](#the-document-renders-no-notifications-outlet).

## Semantics

Faithful ports subtle enough that re-deriving them from the source would
plausibly get them wrong.

### `make()` without a label leaves `getLabel()` fatal

`NavigationItem::$label` is typed `string|Closure` with **no** default and no
constructor assignment, so it is a typed-but-uninitialised property.
`NavigationItem::make()` only calls `label()` when a label is `filled()`, which
means `NavigationItem::make()->getLabel()` throws
`Error: Typed property … must not be accessed before initialization` rather than
returning `null`. That is Filament's shape exactly — its constructor guards the
same `label()` call with `filled()` over the same undefaulted property — and it
is deliberate: a navigation item with no label is not a valid
item, and the fatal names the missing call. The reason it reads like a bug is the
nullable parameter on `make()`, which exists so a `Closure` label can be passed
positionally; nothing else about it says the label is optional. Anything building
items from data must supply the label at `make()` or call `label()` before
rendering.

## Divergences

### `badge()` preserves a badge colour set before it

Filament's `badge($badge, $color = null)` assigns `$this->badgeColor = $color`
unconditionally, so `->badgeColor('danger')->badge('12')` silently clears the
colour back to `null`. Ours writes the colour only when one is passed, so the
earlier `badgeColor()` survives and the two setters compose in either order. The
cost is that `badge()` can no longer *clear* a colour — pass `badgeColor(null)`
for that, which is the explicit spelling anyway.

### `group()` and `getGroup()` carry no `UnitEnum` arm

Filament types the group `string | UnitEnum | Closure | null` and returns
`string | UnitEnum | null`, so an enum case can name a group and
`NavigationGroup::fromEnum()` reads its label and icon off the case. Ours is
string-only. The enum arm lands with `fromEnum()` and the `HasLabel` / `HasIcon`
/ `Collapsible` enum contracts it needs, none of which are ported — deferring it
here keeps the two halves from disagreeing about what a group key is.

### `getBadgeColor()` takes no `$badge` argument

Filament's `getBadgeColor(?string $badge = null)` injects `['badge' => $badge]`
into the closure evaluation, so a colour closure can branch on the badge value it
is colouring (`fn ($badge) => $badge > 10 ? 'danger' : 'gray'`). Nothing here
calls it with an argument — `SidebarItem` reads `item.getBadgeColor` — so the
parameter would be dead weight that still has to be threaded through every
caller. It returns with the first surface that passes a badge in.

### A user-derived navigation URL must be validated before it reaches `href`

Filament's `url()` carries a `// Security:` comment saying that a URL derived
from user input must be validated, or a `javascript:` URL becomes XSS when
rendered into an `href`. The comment is not ported — a rationale comment whose
home is this trail — so the invariant lives here, and it holds for us just as
hard: `SidebarItem` renders `item.getUrl` straight into `href` through `attrs()`,
which escapes the attribute *value* but does not police its **scheme**, and
nothing in `NavigationItem` inspects the URL. So an application feeding
user-supplied strings into `url()` owns the validation. Sylament-generated URLs
(a route path, a resource URL) are safe by construction; anything read from a
database, a config file a user can edit, or a request parameter is not.

### `collapsed()` does not also make the group collapsible

Filament's `collapsed()` calls `collapsible()` as a side effect, so
`collapsed(false)` — the natural way to say *start expanded* — silently turns the
group non-collapsible and the user loses the toggle. Ours only records the state,
and the gate lives in the reader instead: `isCollapsed()` returns `false` whenever
`isCollapsible()` is false, which is the behaviour the coupling was reaching for
without the surprise on the `false` arm.

### `isActive()` skips hidden items

Filament's group `isActive()` returns true for any item whose `isActive()` is
true, hidden or not, so a hidden-but-active item lights up a group that shows no
active child — a group highlighted for a reason the user cannot see. Ours skips
items that report `isHidden()`, matching what the sidebar actually renders
(`SidebarGroup` filters on `item.isVisible`).

### `getItems()` evaluates a `Closure` where Filament accepts an `Arrayable`

Filament types `items(array | Arrayable $items)` and returns the value untouched
from `getItems(): array | Arrayable`, leaving callers to iterate either shape.
`Arrayable` is a Laravel contract with no Symfony counterpart, so the arm is
translated rather than ported: `items()` takes
`array<NavigationItem> | Closure` and `getItems()` evaluates it through the
`Invoker`, which additionally makes the item list lazy and able to reach
container services — the deferred-collection use Filament's `Arrayable` arm
serves, plus request-derived items. An `Arrayable` object passed here is simply
an unsupported type.

### `$navigationGroup` is string-only on `Page` too

Filament types the static `string | UnitEnum | null` so a page can name its group
with an enum case, matching `NavigationItem::group()`. Ours is `?string`, for the
same reason and on the same schedule as
[the item's missing arm](#group-and-getgroup-carry-no-unitenum-arm) — the two
have to agree about what a group key is, so they move together.

M2 · 2 looked at it and deferred it again, deliberately. The panel's navigation
assembly buckets items by `getGroup()` and matches a registered group by key or
label, all of it string-keyed; widening the page static alone would produce a
group key nothing matches, and widening the whole path means answering how an
enum case *names* a group and where its label and icon come from — which is
`NavigationGroup::fromEnum()` and the `HasLabel` / `HasIcon` / `Collapsible`
contracts behind it, none of them ported. So the three move as one piece:
`fromEnum()`, `NavigationItem::group()`'s enum arm, and this static.

### Sidebar group ids are positional, not derived from the label

Filament's `group.blade.php` builds `fi-sidebar-group-items-<slug>-<md5 prefix>`
from the group label, so the `aria-controls` target is stable per label and
unique across everything on the page. Ours is
`sy-pa-sidebar-group-items-<loop index>`, assigned by `Sidebar` from the render
loop. It is stable per render and needs no label at all (a group may have none),
and it rests on the shell's **single-sidebar-per-page** assumption — the same
assumption the singleton ids `sy-pa-sidebar`, `sy-pa-topbar` and `sy-pa-main`
already encode. Revisit the moment two sidebars can render on one page: groups at
the same index would then collide, pointing one group's button at another's item
list. The label-hash approach is the fix if that day comes — noting that collapse
*persistence* is keyed the other way, on `data-group-label`, so two groups sharing
a label share their stored collapse state and a renamed group loses what it had
stored, which is inherent to keying on the label and a property Filament's
label-slug ids carry too.

### The panels render tests live under `tests/Integration/Twig/`

The repo convention is to mirror `src/` inside each test bucket, but these
templates have no `src/` counterpart — they live in `packages/panels/templates/`
and are rendered by name. The other packages settled it differently from each
other: `actions` puts component render tests in `tests/Integration/Components/`,
`notifications` in `tests/Integration/Twig/Components/`. Panels uses
`tests/Integration/Twig/` — the templates are the whole Twig surface of this
package, and there is no `Twig/Extension/` or class-backed component to sit
beside them. Panels-local convention, settled here so the next template test does
not have to re-decide; it moves under a `Components/` subdirectory if panels ever
grows a class-backed component.

### A panel page is rendered by a controller, not routed to directly

Filament mounts a page with `Route::get($routePath, static::class)` — the
Livewire component class *is* the route action, and Livewire's own request
handling turns it into a full page render. `symfony/ux-live-component` has no
such arm: its single route (`ux_live_component`) serves component **actions**
against an already-rendered component, never a page load. So the route action is
[`PageController`](#routing) for every page, the page class travels as the
`_sylament_page` route default, and the page is reached as a service through the
class-keyed locator rather than instantiated by the router. What a user writes is
unchanged — a `Page` subclass listed in `pages()` — and the page is still a live
component once rendered, so its actions round-trip through `ux_live_component`
exactly as Filament's do through Livewire's.

One wiring consequence falls out of it, and it is invisible until a panel is
mounted near the root: `ux_live_component`'s path is
`/{_live_component}/{_live_action}`, so an application importing that route
without the recipe's `/_components` prefix has it match `/admin` first and every
panel page 404s with *Component "admin" not found*. The panels test kernel mounts
it under the prefix for that reason.

### The document calls `importmap()` unguarded

`Document/index.html.twig` calls `importmap('app')` with no check that
AssetMapper is installed, so a consumer without `symfony/asset-mapper` gets an
`Unknown "importmap" function` error on the first panel request. That is the
intended behaviour, not an oversight. A guard cannot be written cheaply anyway —
Twig resolves a function at **compile** time, so a call inside a dead `{% if %}`
branch still has to exist — and, more decisively, the shell's JS (the sidebar
Stimulus controller, live components) has no supported delivery path outside
AssetMapper today. Rendering a silently script-less shell would hand the user a
drawer that never opens and a page whose actions do nothing, with nothing in the
output saying why; a hard error naming the missing function is the better
failure. `asset-mapper` therefore stays out of the package's composer `require`
(it is the application's dependency, and the bundle's own
[`prependExtension()` hook](../../../packages/panels/src/SylamentPanelsBundle.php)
is guarded on it), and the entrypoint name is the Symfony-conventional `app`
until panels grow asset settings of their own.

### The active sidebar item paints a fixed `primary` shade

`sidebar.css` writes `text-primary-600 dark:text-primary-400` for
`.sy-pa-sidebar-item.sy-active` rather than reading a `--sy-text` slot.
[styling.md](../../styling.md)'s *never hardcode a shade for a colour a user can
set* rule (rule 6) governs the **per-instance** `color` palette, which is chosen
by a [contrast policy](../support/color-policies.md) because the palette is not
known until render. The sidebar has no per-instance colour API — neither
`NavigationItem` nor `NavigationGroup` composes `HasColor`, and there is nothing
to build a colour map from — so there is no policy to ask and no slot to read;
`primary` resolves through the `--sy-primary-*` runtime tokens, so a consumer
retheming the palette still gets their colour. It becomes a rule-6 case the day
the sidebar takes a colour, at which point the shade moves into a policy slot and
the two rules (`sy-pa-sidebar-item-btn` and the icon/label pair) read the slot
variables instead. The known residual risk until then: a consumer registering an
unusually light or dark `primary` gets a fixed 600/400 against the fixed
`gray-100` / `white/5` active background, which no policy is measuring.

### Panels are collected from tagged providers, not registered imperatively

Filament's `PanelProvider` extends Laravel's `ServiceProvider` and its
`register()` calls `Filament::registerPanel(fn () => $this->panel(Panel::make()))`,
so a panel exists because a provider imperatively pushed it into the manager.
Symfony has no service-provider tier to hook: ours is a plain abstract class
carrying `panel()` alone, autoconfigured with `sylament.panels.panel_provider`,
and `PanelRegistry` receives the tagged iterator and builds each panel lazily on
first `all()`. The user-facing shape is unchanged — write a `PanelProvider`
subclass with a `panel()` method — while registration becomes container wiring,
the same trade `AsController`/`AsEventListener` make.

The tag buys one behaviour Filament's registry has no place for: two providers
claiming the same id throw naming both classes, where Filament's
`$this->panels[$panel->getId()] = $panel` silently overwrites the first.

### The current panel is derived from the request, not stored on a manager

Filament holds `?Panel $currentPanel` on the `FilamentManager` singleton and
`Http\Middleware\SetUpPanel` assigns it per request. That is exactly the mutable
static per-request state [the shell is worker-safe](#the-shell-is-turbo-indifferent-and-worker-safe)
forbids: a long-running worker would leak one request's panel into the next.
`CurrentPanel` stores nothing — it reads the `_sylament_panel` request attribute
through `RequestStack` on every call, so the answer belongs to the request being
served and a worker has nothing to reset. An id the registry does not know is a
`LogicException` rather than a silent null, since only our own routing writes
that attribute.

What statelessness buys is visible in Filament's own code. `FilamentManager`
carries a second piece of remembered state beside the panel —
`protected bool $isCurrentPanelBooted = false`, guarding `bootCurrentPanel()`
([`FilamentManager.php:65-74`](../../../vendor/filament/filament/src/FilamentManager.php))
— and nothing ever sets it back to `false`. Under php-fpm that is invisible,
because the manager dies with the request. Under a long-running worker the second
request onwards finds the flag already true and skips booting its panel
entirely, so the colours, icons and render hooks of whichever panel happened to
be served first are the ones every later request gets. The port has no flag to
forget to reset because it has nothing to boot
([the panel has no `register()` or `boot()`](#the-panel-has-no-register-or-boot))
and nothing to remember.

Filament's `getCurrentOrDefaultPanel()` fallback is not ported here: `get()`
returns `null` outside a panel, and a caller that wants the default asks
`PanelRegistry::getDefault()` for it.

### `PanelRegistry::get()` has no fuzzy arm

Filament's `get(?string $id, bool $isStrict = true)` can lower-case and strip
dashes/underscores from every registered id to match a sloppy one. Ours takes the
id alone and matches exactly. Nothing calls the fuzzy arm with `$isStrict: false`
except Filament's own artisan commands resolving a panel from console input,
which has no counterpart here; a normalising lookup that silently answers for a
panel the caller did not name is worth having only once something needs it.

### Navigation is assembled on the panel, not by a `NavigationManager`

Filament's `Panel::getNavigation()` resolves a `NavigationManager` out of the
container, hands itself to it via a `$navigationManager` property the
registration setters then redirect to, and reads the result back — a round trip
that buys a per-request scratch object (it clones the panel's groups and items in
its constructor) which `navigationGroups()`/`navigationItems()` can still be
called on *while* the navigation is being built, the seam plugins register
through. Ours does the assembly in `HasNavigation::getNavigation()` directly. The
panel already holds every input (its pages, groups and items), the method needs
no service, and the
manager's mid-build registration arm has no caller here until plugins land. The
piece worth keeping from it is the cloning discipline, which ours has: groups and
items are cloned on the way out, so repeated calls neither accumulate items on a
registered group nor let one render's mutation reach the next.

The escape hatch Filament layers on top — `navigation(Closure | bool)` with
`NavigationBuilder`, plus `hasNavigation()` / `hasNavigationBuilder()` — is
deferred rather than rejected: it replaces the derived navigation with a
hand-built one and can land whole, unchanged in shape, whenever a consumer wants
it.

### The panel collapsible default is stamped on the group, not read from a facade

Filament's `NavigationGroup::isCollapsible()` falls back to
`filament()->hasCollapsibleNavigationGroups()` — a facade reaching the current
panel from inside the group. We have no such global, and passing the panel into
every group would make a group unusable without one. So the direction is
inverted: `collapsibleFallback(bool)` is a plain setter that
[`Panel::getNavigation()`](#panel) stamps on each group it assembles, and the
group reads its own property. A group built by hand keeps the `true` default,
which is the answer Filament's fallback gives for a panel that never changed the
setting.

The setter is deliberately not `bool | Closure`: it carries a value the panel has
already evaluated, so a closure arm would only add a second place for the same
decision to be made. `collapsibleFallback()` has no Filament counterpart at all —
it is a new method rather than a rename, so it is not a
[renames.md](../renames.md) row; a migrating user never types it, because
`collapsible()` is still the setter they know.

### The item hands its own services to evaluated closures

Filament resolves an unbound class hint in an evaluated closure through
`app()->make()`, so any service is reachable from any closure with no wiring at
all. Symfony's container cannot do that: the
[`Invoker`](../../../packages/support/src/Evaluation/Invoker.php) reads through
`ContainerInterface::has()`/`get()`, and every alias worth hinting here —
FrameworkBundle's `RequestStack::class` and `UrlGeneratorInterface::class`,
support's `IconManager::class` — is private, so the lookup answers nothing and
the closure dies at render time on *"[$icons] was unresolvable"*, with nothing at
container-build time to catch it. Publishing those aliases would fix the symptom
by making three services public in every consuming application, for the benefit
of closures **we** ship.

So the item carries them instead. `NavigationItem` takes
`UrlGeneratorInterface`, `RequestStack` and `IconManager` through one
`#[Required]` setter — it is a `sylament.component`-tagged, container-built,
non-shared service, so the setter fires on every `make()` — and hands them to
closures from
`resolveDefaultClosureDependencyForEvaluationByType()`, the
[`EvaluatesClosures`](../../../packages/support/src/Concerns/EvaluatesClosures.php)
seam the `Invoker` consults **before** the container arm. Those are exactly the
three types sylament's own closures hint ([`Page::getNavigationItems()`](#page)
builds the url and active-state ones, [`Dashboard`](#dashboard) the icon one), so
the shipped navigation works against a stock container.

Two things the design deliberately leaves alone. A `new`-built item behaves
exactly as before — the properties stay `null`, the hook returns `null`, and the
container arm is consulted — so nothing that worked outside the container stops
working. And `NavigationGroup` gets **no** override: its closures have no shipped
service-hinting examples, and leaving it bare is what keeps the container arm
under test
([`SylamentPanelsBundleTest`](../../../packages/panels/tests/Integration/SylamentPanelsBundleTest.php)
evaluates a `RequestStack` closure on a group, which passes only because the
panels test kernel republishes that one alias). A consumer hinting a fourth
service type still republishes its alias, and that is the case the group test
stands in for.

### The panel has no `register()` or `boot()`

Filament's `Panel` carries both lifecycle methods and neither is ported.
`register()` queues the panel's Livewire components, registers persistent
middleware, sets a tenant route pattern and registers assets when running in
console; `boot()` installs per-request global state — `FilamentColor::register()`,
`FilamentIcon::register()`, `FilamentView::spa()`, the render hooks, and an
`Action::configureUsing()` for database transactions.

Neither has a job here. Component registration is a **container** concern, done
once at compile time by
[`PageComponentPass`](#pages-register-as-live-components-through-a-compiler-pass-not-per-class-attributes)
rather than per request by the panel. And `boot()`'s remaining work is exactly
the mutable global state
[the shell is worker-safe](#the-shell-is-turbo-indifferent-and-worker-safe)
forbids: pushing this request's panel colours and icons into process-wide
registries is undebuggable the moment two panels are served by one worker. Ours
are container configuration instead —
[`sylament.colors` and `sylament.icons`](../support/icon-manager.md) are read
into `ColorManager`/`IconManager` at container build and never rewritten — so
there is nothing for a panel to install and nothing to reset. Render hooks,
middleware and assets are all unported surface that will have to answer the same
question when it lands; the answer this establishes is *configure at build, do
not install at boot*.

### Pages register as live components through a compiler pass, not per-class attributes

Filament registers a panel's pages as Livewire components imperatively, from
`Panel::register()` through `registerLivewireComponents()` and
`queueLivewireComponentForRegistration()`. The literal Symfony translation is
`#[AsLiveComponent]` on every concrete `Page` subclass, and it was staged as an
open decision for this epic. It is not the one taken.

Class attributes do not inherit: `registerAttributeForAutoconfiguration()` reads
the attribute off the class being registered, so putting `#[AsLiveComponent]` on
the abstract `Page` base does nothing for its subclasses — every page a consumer
writes has to repeat it. Forgetting it fails badly. The page is still a service,
so the route still mounts and the request still reaches
[`PageController`](#routing); the failure surfaces at the point where the
document renders `{{ component(componentName) }}` and ux-twig-component has no
component under that name — a render-time error about a component the user never
typed, at a name derived from the FQCN, several layers from the missing
attribute.

So the tag is applied for them.
[`PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php)
walks everything autoconfigured `sylament.panels.page` and replicates what the
attribute would have produced: the **same two tags** the LiveComponent extension
adds — `twig.component` carrying `AsLiveComponent::serviceConfig()`'s payload
(`key`, `live`, `route`, `method`, `url_reference_type`, `expose_public_props`,
`attributes_var` and `template_from_method: 'getTemplate'`, the two nullable
entries the extension filters away being simply not written) plus
`controller.service_arguments`. `template_from_method` is the payload the
attribute's `new FromMethod('getTemplate')` form produces, and it is the arm that
matters: a literal `template:` string is fixed at compile time, where
`getTemplate()` lets the base class supply the default and a subclass override
`$template`.

The pass also owns two things an attribute could not. Two pages deriving the same
component name throw at compile time naming both services, where a duplicate
attribute key would silently leave one registration standing. And the pass builds
the class-keyed `ServiceLocator` `PageController` resolves pages through, so the
same tag that makes a page renderable makes it reachable.

The cost is the ordinary one for compile-time magic: nothing in a page class says
it is a live component, so the fact lives here and in the bundle's `build()`.

### `getNavigationItems()` takes the panel rather than reaching a facade

Filament's `Page::getNavigationItems()` is parameterless and reaches the current
panel through the `Filament` facade — `getRouteName(?Panel $panel = null)`
defaults to `Filament::getCurrentOrDefaultPanel()` when the argument is omitted.
A **static** method cannot reach a service in Symfony, which is the same
constraint the [`Resource` carried decision](#resource-is-a-service-not-a-static-class-string-registry)
opens with, so the panel travels as a required argument:
`getNavigationItems(Panel $panel)` and `getRouteName(Panel $panel)`. Both callers
already hold one — [`Panel::getNavigation()`](#panel) passes `$this`,
[`PanelRouteLoader`](#routing) passes the panel it is mounting — so nothing has
to find a panel it did not already have.

The argument moves the other way where nothing reads it. Filament's
`getRoutePath(Panel $panel)` and `getSlug(?Panel $panel = null)` take a panel
solely to look up a per-panel `PageConfiguration` (the same page registered twice
in one panel under different keys); that surface is unported, so both are
parameterless here and gain the argument if and when configurations land. The
policy is the same in both directions: the panel is an argument exactly where the
method reads it, never as a uniform prefix.

### `getBrandName()` returns null where Filament falls back to the app name

Filament's `getBrandName()` ends `?? config('app.name')`, so an unconfigured
panel still prints *Laravel* in its `<title>` and brand slot. Symfony has no
framework-owned application name to fall back to — there is no `framework.name`,
and `kernel.project_dir`'s basename is a directory, not a product — so ours
returns `null` and the [document template](#routing) drops the title suffix
entirely rather than printing a placeholder. A consumer who wants one calls
`brandName()`, which is the call a Filament user makes anyway the first time they
see the default.

### `getPages()` re-indexes where Filament keeps `array_unique()`'s gapped keys

Filament returns `array_unique($this->pages)` directly, which preserves the
original keys and therefore leaves gaps wherever a duplicate was removed. Ours
wraps it in `array_values()`. Order is unchanged and the surviving entry is still
the first occurrence — the only difference is that the result is a list. It
matters because the return is a collection every caller iterates or serialises,
and a gapped integer-keyed array is the shape that silently becomes a JSON object
instead of an array the first time something encodes it. Nothing here depends on
the keys, so there is nothing to lose by dropping them.

### The route import carries no prefix because the panel path is authoritative

Filament mounts each panel inside a `Route::prefix($panel->getPath())` group, so
the path is applied by the router. Ours cannot be: one
[`routing.route_loader`](#routing) pass covers **every** registered panel, and a
single `import()` prefix cannot express a different path per panel. So
`PanelRouteLoader` joins the panel path itself and an application imports the
loader bare:

```php
$routes->import('sylament_panels.route_loader', 'service');
```

Adding `->prefix('/admin')` there double-prefixes — a panel declaring
`path('admin')` mounts at `/admin/admin` — and it fails as a 404 on every panel
URL rather than as an error, because the routes exist and are simply somewhere
else. `Panel::path()` is the one place a panel's mount point is written.

### The document renders no notifications outlet

Filament's `base.blade.php` emits `@livewire(Filament\Livewire\Notifications::class)`
in every panel document, so a notification sent from any page has a stack to land
in. Ours does not, and the reason is the dependency rule rather than a view
decision: `sylament/panels` does not require `sylament/notifications`, and a
template that unconditionally renders `<twig:SylamentNotifications:Notifications />`
is an unconditional dependency on a package the manifest does not declare —
precisely the shape that breaks a split repo. Until panels either declares the
dependency or gates the outlet on the bundle being registered, an application
that wants panel-wide notifications renders the tag from its own page template.
[M2 · 4](../roadmap.md) — the demo panel assembly, the first place a real panel
is mounted and notified from — is where the gap gets felt and the choice gets
made.

### The dashboard title is a literal, not a translation key

Filament's `Dashboard` resolves its label through
`__('filament-panels::pages/dashboard.title')`; ours sets
`protected static ?string $title = 'Dashboard'` and both `getTitle()` and
`getNavigationLabel()` read the literal. There is no translation layer anywhere
in the port yet — no bundle ships a message catalogue, no template calls `trans`,
and no user-facing string carries a message id — so a key here would be the only one
in the codebase and would have to invent the catalogue conventions (domain
naming, where catalogues live, how a consumer overrides one) as a side effect of
shipping a dashboard. The literal is a placeholder with a known replacement: when
i18n lands it becomes a translated string with `'Dashboard'` as its English
default, and a consumer overriding `$title` today is unaffected either way.
