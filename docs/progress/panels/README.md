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

**Decided — implemented.** The base, its registry and the container wiring ship;
see [Resources](#resources) for what the class actually holds.

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

**Answered: class-string.** `->resources([ArticleResource::class])` on the panel,
`ArticleResource::class` wherever one is named, and
[`ResourceRegistry`](#resources) resolving that class-string to the
container-built instance through a `ServiceLocator` a compiler pass fills — the
recommended arm, taken for the reasons above. The slug stays what it always was
in Filament, a URL segment derived from the class name and overridable per
resource; it identifies a resource in a *URL*, never in code.

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
| [`Resources\Resource`](../../../packages/panels/src/Resources/Resource.php) | [`Resources/Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php) and [`Resources/Resource/Concerns`](../../../vendor/filament/filament/src/Resources/Resource/Concerns) — `HasLabels`, `HasPages`, `HasRoutes`, `HasNavigation`, `CanGenerateUrls` | in progress | [Resources](#resources) |
| [`Resources\ResourceRegistry`](../../../packages/panels/src/Resources/ResourceRegistry.php) | no Filament counterpart — a Filament resource is reached by calling its statics on the class-string | in progress | [Resources](#resources) |
| [`Resources\Pages\PageRegistration`](../../../packages/panels/src/Resources/Pages/PageRegistration.php) | [`Resources/Pages/PageRegistration.php`](../../../vendor/filament/filament/src/Resources/Pages/PageRegistration.php) | in progress | [Resources](#resources) |
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
only), `HasComponents` ([`pages()` and `resources()`, each accumulating,
de-duplicated and
re-indexed](#getpages-re-indexes-where-filament-keeps-array_uniques-gapped-keys)),
`HasNavigation` (`navigationGroups()`/`navigationItems()` registration plus
`getNavigation()`), `HasSidebar` (`collapsibleNavigationGroups()` alone — the
panel-level default [`NavigationGroup::isCollapsible()`](#navigationgroup) falls
back to) and `HasBrandName` — with `default()`/`isDefault()` on the class itself,
matching Filament.

`getNavigation()` is the assembly step: it clones every item each registered page
contributes, then every item each registered resource contributes, then the
panel's own `navigationItems()`, drops the hidden ones, sorts by `getSort()`, and
buckets what is left by `getGroup()`. The ungrouped
bucket becomes the leading label-less group; each registered group then claims
its bucket by key **or** label (a clone, so the registration survives repeated
calls); buckets no registration matched follow in encounter order. Every group
that comes out is stamped with `collapsibleFallback($this->hasCollapsibleNavigationGroups())`.
A group with no items is never built, so a registration nothing matched simply
does not render. Cloning at both ends is what makes the method idempotent —
`items()` mutates, and the registered objects are the panel's, not the render's.
All three sources land in one list before the filter/sort/bucket pass, so a
resource item sorts and groups against the page items rather than trailing them;
source order decides ties alone, `usort` being stable.
`HasComponents` types its page list `class-string<Page>` for this: the assembly
calls `Page`'s static navigation methods on each entry.

The resource arm is the one source that needs a service. A registered resource is
a class-string, and turning it into the instance whose
`getNavigationItems($this)` answers means going through
[`ResourceRegistry`](#resources) — so `HasNavigation` takes one through a
`#[Required] setResourceRegistry()` setter, exactly as
[`NavigationItem`](#the-item-hands-its-own-services-to-evaluated-closures) takes
its three. `Panel` is a `sylament.component`-tagged, container-built, non-shared
service, so the setter fires on every `Panel::make()`. A panel built with `new`
holds no registry, and `getNavigation()` then throws *"Resource navigation needs
the container-built panel"* the moment it reaches a registered resource — the
same [fail-fast](#a-new-built-resource-fails-fast-rather-than-degrading) shape
`Resource`'s own service set uses, rather than silently dropping half the
navigation. A `new`-built panel that registers no resource still assembles
normally, because the loop body is what needs the registry.
`shouldRegisterNavigation()` is consulted here, in the loop, rather than inside
`getNavigationItems()` — Filament's placement exactly, and the [pulled rather
than pushed](#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource)
entry covers why the loop exists at all. A resource's items are built fresh on
every call, so cloning them buys nothing today and is done anyway: it keeps the
three loops identical to read, and it means a later memoisation inside
`getNavigationItems()` cannot leak one render's
[destructive `isActive()`](#navigation-is-assembled-on-the-panel-not-by-a-navigationmanager)
into the next.

`PanelProvider` is the abstract `panel(Panel $panel): Panel` contract; every
subclass an application registers as a service is tagged
`sylament.panels.panel_provider` by autoconfiguration. `PanelRegistry` consumes
that tagged iterator, builds each provider's panel once (memoised), keys them by
id, rejects a duplicate id, and answers `getDefault()` or throws
`NoDefaultPanelSetException`. `CurrentPanel` reads the `_sylament_panel` request
attribute off the `RequestStack` and resolves it through the registry. Both are
ordinary private services: [`PanelRouteLoader`](#routing) injects the registry
and [`PageController`](#routing) injects the current-panel lookup.

`resources()` / `getResources()` is the same shape as `pages()`, typed
`class-string<Resource>` and equally unvalidated at registration time — the
route loader is where a non-`Resource` entry is caught. Both readers are live:
[`PanelRouteLoader`](#routing) mounts every page a registered resource declares,
and `getNavigation()` above merges the item that resource contributes.

Deferred, all todo: the other 28 `Panel/Concerns` traits (auth, tenancy, colors,
assets, render hooks, middleware, plugins, …); `discoverResources()` /
`discoverPages()`, Filament's filesystem-scanning registration arms, which the
[tag-collected](#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem)
shape replaces for services and which would only return as a convenience over
`services.yaml`; the widget registration arm;
`navigation()` / `NavigationBuilder` — the hand-built navigation escape hatch,
which needs nothing from the assembly above and lands when a consumer wants it;
the parent-item nesting pass and the `UnitEnum` group arm, both waiting on
[`NavigationItem`'s deferred `key()`](#navigationitem); and the widget
contribution to the item list, which arrives with that subsystem.

Nine deltas, each with its own divergence:
[panels are collected from tagged providers rather than registered by a service
provider](#panels-are-collected-from-tagged-providers-not-registered-imperatively),
[the current panel is derived from the request, never stored](#the-current-panel-is-derived-from-the-request-not-stored-on-a-manager),
[`PanelRegistry::get()` has no fuzzy arm](#panelregistryget-has-no-fuzzy-arm),
[navigation is assembled on the panel rather than by a
`NavigationManager`](#navigation-is-assembled-on-the-panel-not-by-a-navigationmanager),
[resource navigation is pulled through the registry rather than pushed by the
resource](#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource),
[the panel has no `register()` or `boot()`](#the-panel-has-no-register-or-boot),
[`make()` configures before the id is set](#make-configures-the-panel-before-its-id-is-set),
[`getBrandName()` returns `null` rather than an application
name](#getbrandname-returns-null-where-filament-falls-back-to-the-app-name),
and [`getPages()` re-indexes](#getpages-re-indexes-where-filament-keeps-array_uniques-gapped-keys).

### Resources

`Resource` is the abstract base a user extends once per record type. It is a
**service**, not a static class-string registry — the carried decision above,
now implemented, with the identity question answered the way that decision
recommended: a resource is identified by its **class-string** everywhere a user
names one (`->resources([ArticleResource::class])`,
`protected static string $resource = ArticleResource::class` on the pages to
come), and `ResourceRegistry` turns that class-string into the container-built
instance. `ResourcePass` fills the registry's `ServiceLocator` from the
`sylament.panels.resource` tag `SylamentPanelsBundle::build()` autoconfigures
onto every `Resource` subclass an application registers, skipping abstract
definitions and anything tagged that is not a `Resource`. Both throws name the
fix: an unregistered class-string says so, and a locator answering with a
non-resource says that.

Because it is a service, the whole surface is instance methods. `form(Schema)`
and `table(Table)` are Filament's identity defaults verbatim; the five services
the rest needs — `EntityManagerInterface`, `UrlGeneratorInterface`,
`CurrentPanel`, `PanelRegistry` and schemas' `PropertyAccessor` — arrive through
one `#[Required] setResourceServices()` setter, so a subclass declaring its own
constructor dependencies never has to repeat them. A resource built with `new`
answers everything that is pure derivation (slugs, labels, pages) and
[throws on the first method that needs a service](#a-new-built-resource-fails-fast-rather-than-degrading).

What is ported, by cluster:

- **Record class and labels.** `$recordClass` plus `getRecordClass()`;
  `$recordLabel` / `$pluralRecordLabel` and the four getters, deriving from the
  record class basename through Filament's exact pipeline (`BlogPost` →
  `blog post` → `blog posts` → `Blog Post` / `Blog Posts`). Title case is
  `u($label)->title(allWords: true)`, the house `Str::ucwords` equivalent
  [`Page`](#page) already uses.
- **Slug.** `$slug`, else the class basename minus a trailing `Resource`,
  pluralized and kebab-cased — `ArticleResource` → `articles`,
  `BlogPostResource` → `blog-posts`. The suffix is stripped only when the name
  actually ends with it, so a class merely *containing* `Resource` keeps its
  whole basename rather than being emptied by `beforeLast()`.
- **Pages.** `getPages(): array<string, PageRegistration>` (empty by default) and
  `hasPage()`. The keys `'index'` / `'create'` / `'edit'` are load-bearing API,
  exactly as in Filament: they gate navigation and URL generation and become the
  route-name suffix. `PageRegistration` is a `readonly` pair of the page
  class-string and its path, built directly rather than through Filament's
  `Page::route()` closure factory — the route is built by
  [`PanelRouteLoader`](#routing), which needs the path as data.
- **Query and record binding.** `getRecordQuery()` is the
  [`getEloquentQuery()`](../renames.md#renames) port and the single overridable
  base query. `resolveRecordRouteBinding()` resolves a URL segment
  [through that query rather than `EntityManager::find()`](#a-record-is-resolved-through-the-resources-own-query),
  reading the root alias off the builder so an overridden query still works, and
  `getRecordRouteKey()` is the generation side of the same key —
  `$recordRouteKeyName` when set, else the single identifier value off
  `ClassMetadata`. A composite identifier throws on both sides.
- **URLs.** `getUrl()` / `getIndexUrl()` over
  `UrlGeneratorInterface::generate()`, with Filament's `blank($name)` → index
  fallback and its verbatim "does not have an [index] page" message.
  `getRouteBaseName(Panel)` composes `sylament.<panel>.resources.<slug>` in
  [exactly one place](#the-route-base-name-is-composed-in-one-place).
- **Navigation.** `getNavigationItems(Panel)` contributes at most one item, only
  when an `index` page exists, matching Filament — including the
  `$activeNavigationIcon ?? $navigationIcon` fallback and the
  `getNavigationBadge()` / `getNavigationBadgeColor()` override hooks that return
  `null`. Active state is a [route-name prefix match](#active-state-is-a-route-name-prefix-match),
  which is what keeps the item lit on the resource's create and edit pages.
  [`Panel::getNavigation()`](#panel) is what calls it — the resource never pushes
  its item anywhere, which is the
  [pull model](#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource).

Deferred, all todo: authorization (`canAccess()` and the whole `HasAuthorization`
cluster), global search, widgets, breadcrumbs, sub-navigation, clusters, tenancy,
nested/parent resources, resource configurations, `getRelations()`, and
`configureTable()`'s seeding of table-level labels. `NavigationItem`'s `key()`,
`parentItem()` and `badgeTooltip()` are skipped here because
[they are not ported on the item yet](#navigationitem); `shouldRegisterNavigation()`
is ported and, as in Filament, is consulted by the panel-side loop rather than by
`getNavigationItems()` itself.

Divergences, each with its own entry:
[the record class is declared, never derived](#the-record-class-is-declared-never-derived-from-an-application-namespace),
[the slug has no namespace-path arm](#the-slug-has-no-namespace-path-arm),
[pluralization takes the inflector's last candidate](#pluralization-takes-the-inflectors-last-candidate),
[there is no locale gate on pluralization](#there-is-no-locale-gate-on-pluralization),
[the route base name is composed in one place](#the-route-base-name-is-composed-in-one-place),
[nothing is memoised in a static](#nothing-is-memoised-in-a-static),
[resources are collected from a tag, not discovered on the filesystem](#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem),
[a record is resolved through the resource's own query](#a-record-is-resolved-through-the-resources-own-query),
[`getRecordTitle()` returns a plain string](#getrecordtitle-returns-a-plain-string),
[`getUrl()` drops three parameters and widens a fourth](#geturl-drops-three-parameters-and-widens-a-fourth),
[active state is a route-name prefix match](#active-state-is-a-route-name-prefix-match),
and [a `new`-built resource fails fast](#a-new-built-resource-fails-fast-rather-than-degrading).

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
| [`DependencyInjection\Compiler\ResourcePass`](../../../packages/panels/src/DependencyInjection/Compiler/ResourcePass.php) | no Filament counterpart — resource discovery is [`Panel/Concerns/HasComponents.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasComponents.php) `discoverResources()`, a filesystem scan | in progress | every `Resource` service is autoconfigured `sylament.panels.resource` and the pass collects the non-abstract ones into the class-keyed `ServiceLocator` `ResourceRegistry` reads — [Resources](#resources) |
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
| [`Routing\PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php) | [`routes/web.php`](../../../vendor/filament/filament/routes/web.php) — the per-panel route group and its `$page::registerRoutes($panel)` / `$resource::registerRoutes($panel)` loops, [`Pages/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasRoutes.php) `routes()`, [`Resources/Resource/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) `registerRoutes()` | in progress | [Routing](#routing) |
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

Every resource a panel registers mounts a second family in the same pass, one
`GET` route per [`getPages()`](#resources) entry. The name is
`Resource::getRouteBaseName($panel)` plus the registration **key** —
`sylament.<panel>.resources.<slug>.index`, `….create`, `….edit` — and the path
joins three pieces through the same trim-and-re-prefix, so an `index` registered
at `/` collapses onto the resource root (`/admin/articles`) while
`/{record}/edit` keeps its placeholder (`/admin/articles/{record}/edit`). The
defaults are the page family's three verbatim: `_sylament_page` names the **page**
class, never the resource, which is what lets one `PageController` serve both
families — a resource page finds its resource through its own `$resource` static,
not off the route. Building these routes resolves each resource **out of the
container**, the loader holding [`ResourceRegistry`](#resources) beside the panel
one, because a slug and a page map are instance methods here where Filament's are
statics: route loading is therefore the first thing in a request that
instantiates a resource service, and `getPages()` runs once at route-build time
rather than per request.

Two things beyond mounting. Every page class file, every resource class file and
every provider class file is added to the collection as a `FileResource`: the
routes are derived from method bodies (`getSlug()`, `$routePath`, `getPages()`,
`panel()`) that Symfony's `ObjectLoader` cannot see — it tracks only the loader
class and its parents — so without them a dev-mode route cache would survive a
renamed slug. And an entry that is not the subclass it claims to be throws a
`LogicException` naming the class, at route-build time rather than at the request
that finally hits it: `pages()` entries against `Page`, `resources()` entries
against `Resource`, and a resource's own `getPages()` entries against `Page`.

Nothing is silently overwritten, either. `RouteCollection::add()` unsets a name
before assigning it and checks nothing, so two mounts deriving one route name
used to leave the later one standing and the earlier one unreachable — with no
error at route-build time, at request time or in `debug:router` — and resources
multiply the ways to collide (two pages sharing a `$slug`, two resources sharing
a slug, a resource page landing on a page's name). Every mount now goes through
one `addRoute()` that records the name's owner and throws a `LogicException`
naming both mounts when a second claims it. The compile-time
[component-name check](#pages-register-as-live-components-through-a-compiler-pass-not-per-class-attributes)
does not cover this and cannot: it keys on the FQCN, which is exactly what
differs when two classes derive one slug.

[`PageController`](../../../packages/panels/src/Controllers/PageController.php)
is the single action every panel route points at. It resolves the panel through
`CurrentPanel`, resolves the page **service** from the class-keyed
`ServiceLocator`
[`PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php)
builds, and renders the Document template with `panel`, `pageTitle`,
`componentName` and `componentProps`. Each failure mode is its own
`LogicException` naming the missing piece — a page that is not a registered
service names the registration to add, since a page reached through `new` would
have no dependencies injected.

`componentProps` is the route → component bridge, and the whole rule is one
line: every matched route parameter (`_route_params`) whose name does not start
with `_`. That drops the two `_sylament_*` defaults the loader writes and keeps
the user's own placeholders, so `/admin/articles/{record}/edit` hands the page
`{record: '12'}`. The document spreads it into `component()`, where
ux-twig-component matches each entry against the page's `mount()` parameters **by
name**, then against writable properties, and turns whatever is left into an HTML
attribute on the page's root element — so a placeholder no page mounts paints
itself into the markup rather than erroring, which is the one shape to watch for.
A prop must be a `#[LiveProp]` to survive past the first render: only live props
are dehydrated into `data-live-props-value`, and a plain public property is gone
by the time the component posts back.

A page can 404 from its `mount()`, and the controller is what lets that reach the
kernel. The mount runs **inside** `Twig::render()` — the document is what invokes
the component — and Twig wraps any non-`Twig\Error` thrown during a render in a
`RuntimeError`, so a `NotFoundHttpException` would arrive at the kernel as a
template error and be answered 500. The controller catches `RuntimeError` and
rethrows its previous exception when that is an `HttpExceptionInterface`,
[unwrapping the wrapper and nothing else](#a-mount-time-http-exception-is-unwrapped-from-twigs-render-error).

The two templates split the document from the page. `Document/index.html.twig`
is the `<html>` shell — title (the page title, then the brand name when the panel
has one), `sylamentStyles()`, the importmap, the `Layout` component with the
page's live component in its content block (invoked by the tag name
`Page::getComponentName()` returns, and given `componentProps`), and the
notifications outlet. That outlet is
`<twig:SylamentNotifications:Notifications />` as a direct `<body>` child outside
the layout — the position Filament's
`@livewire(Filament\Livewire\Notifications::class)` holds in `base.blade.php` —
and it is the document's job rather than the application's now that panels
declares `sylament/notifications`. The **actions** outlet deliberately is not
here, and Filament agrees: `<x-filament-actions::modals />` sits in its *page*
view, not its layout. Ours has a second, harder reason —
[`Modals`](../actions/has-actions.md) resolves its host live component by walking
ux-twig-component's `ComponentStack`, so it only works rendered inside the
component whose actions it shows, which a document-level outlet is not.
`Page/index.html.twig` is what
`Page::getTemplate()` falls back to: the `Header` (rendered only when the page
has a heading or subheading) above an empty `sy-pa-page-content` container that
stays empty until pages carry schemas. Both are `partials/`, not `components/` —
they are rendered from PHP (a controller and a `template_from_method` getter),
never as a `<twig:>` tag.

Four divergences are theirs:
[a page is rendered by a controller rather than routed to directly](#a-panel-page-is-rendered-by-a-controller-not-routed-to-directly),
[the document calls `importmap()` unguarded](#the-document-calls-importmap-unguarded),
[the route import carries no prefix](#the-route-import-carries-no-prefix-because-the-panel-path-is-authoritative),
and [a mount-time HTTP exception is unwrapped from Twig's render error](#a-mount-time-http-exception-is-unwrapped-from-twigs-render-error).

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

That discipline covers `getNavigation()` and nothing else. `getNavigationItems()`
and `getNavigationGroups()` hand back the registered objects themselves, and
`PanelRegistry` builds each panel once and memoises it — so under a long-running
worker those are the same instances for every request the process serves. Read
against them, [`NavigationItem::isActive()`](#navigationitem) is destructive: it
overwrites its `isActiveWhen()` closure with the boolean the first evaluation
produced, so a raw item asked once whether it is active answers that way for the
life of the process, on every request, whatever the route. Navigation is read
through `getNavigation()`, whose clones absorb exactly that; the raw getters are
registration-time composition surface — what a provider reads to add to what an
earlier call registered — and are not a render path.

The escape hatch Filament layers on top — `navigation(Closure | bool)` with
`NavigationBuilder`, plus `hasNavigation()` / `hasNavigationBuilder()` — is
deferred rather than rejected: it replaces the derived navigation with a
hand-built one and can land whole, unchanged in shape, whenever a consumer wants
it.

### Resource navigation is pulled through the registry, not pushed by the resource

Filament's `Resource::registerNavigationItems()` is a `void` static that reaches
`Filament::getCurrentOrDefaultPanel()` and **pushes** its items onto the panel
with `->navigationItems(...)`; `NavigationManager::mountNavigation()` calls it
once per registered page and resource, then reads the accumulated list back off
itself. Both halves of that are forced by the same thing: a Filament resource is
a static class with no panel in hand, so it needs the facade to find one, and
pushing onto the real panel would accumulate items across requests, so it needs
the manager's per-request clone to push *into*.

Ours pulls. `getNavigation()` resolves each registered class-string through
[`ResourceRegistry`](#resources) and calls `getNavigationItems($this)` on the
instance — the panel is handed over, not looked up, the same inversion
[`Page::getNavigationItems(Panel)`](#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade)
already uses — and merges the returned array locally. Nothing mutates the panel,
so there is no accumulation to clone away and no scratch object to own the
result. The one thing the push model buys that this does not is a third party
queueing items into a build already under way; that is the plugin seam, deferred
with the rest of plugins.

So `registerNavigationItems()` has no port, and its four early returns are
distributed rather than dropped: `shouldRegisterNavigation()` is ported and
consulted by the panel-side loop, in Filament's own position; `getCluster()` and
`getParentResourceRegistration()` guard unported subsystems; `canAccess()` waits
on authorization. Each of those lands in the loop when its subsystem does, not
back inside the resource — a resource that answers `getNavigationItems()` with an
array cannot also decide it should not have been asked.

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

### `make()` configures the panel before its id is set

Filament's `Panel::make()` is `app(static::class)` and configures nothing;
`configure()` is called from exactly one place, `HasId::id()`, immediately after
the assignment. So every `Panel::configureUsing()` closure Filament runs sees a
panel that already knows its id, and a closure keyed on it —
`fn (Panel $panel) => $panel->getId() === 'admin' ? … : …` — works.

Ours follows the house `make()` shape instead: `Panel::make()` calls
`newInstance()` then `configure()`, the same two lines every other Sylament
component's factory has, and `id()` is a plain write-once setter. The ordering
inverts — configuration runs first, the id arrives after — and the cost is
narrow but real: a `Panel::configureUsing()` closure that reads `getId()` does
not see a different id, it **fatals**, because
[`HasId::getId()` throws when the property is unset](#panel). Nothing in-repo
does that (the panels suite registers no panel-level `configureUsing()`), so the
divergence is recorded rather than fixed. Adopting Filament's placement would
mean `id()` calling `configure()`, which double-configures every panel built
through `make()` unless `make()` stops doing it — a change to the one factory
shape the whole toolkit shares, for a closure nobody has written yet. Revisit the
moment a consumer hits it.

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

What the pass tags is every `Page` service the application registers, not every
page a panel lists — `pages()` is read by [`PanelRouteLoader`](#routing), never by
the pass. So a page class that no panel attaches still becomes a live component
mounted at `ux_live_component` over POST and still becomes a public controller
service through `controller.service_arguments`; it has no panel GET route and is
reachable anyway. It could not be otherwise: the pass runs at container compile
time and a panel is assembled at runtime, by `PanelRegistry` calling each
provider's `panel()`, so no `pages()` list exists to filter by when the tags are
written. Accepted, then, but it fixes the shape of the auth work: **the
authorization gate belongs on the component mount, not on the panel routes**. A check written against the route defaults (`_sylament_panel`,
`_sylament_page`) secures exactly the half of the surface the pass did not create,
and leaves the POST endpoint open under a name derived from the page's own FQCN.

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
the keys, so there is nothing to lose by dropping them. `getResources()` is built
the same way, for the same reason.

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

### A mount-time HTTP exception is unwrapped from Twig's render error

An edit page whose `{record}` matches nothing 404s from `mount()`, and in
Filament that needs no machinery: the page **is** the route action, so the
exception its mount throws is already the one the framework answers. Here it is
not. [A page is rendered by a controller](#a-panel-page-is-rendered-by-a-controller-not-routed-to-directly),
the controller renders the document, and the document is what invokes the page's
component — so the mount runs inside `Twig::render()`, and `Twig\Template` wraps
every non-`Twig\Error` thrown during a render in a `RuntimeError` carrying the
original as its previous. Left alone, every mount-time `NotFoundHttpException`
is answered **500**, with the real exception two frames down in a stack trace.

So [`PageController`](../../../packages/panels/src/Controllers/PageController.php)
catches `RuntimeError`, and rethrows `getPrevious()` when it is an
`HttpExceptionInterface` — otherwise it rethrows the `RuntimeError` untouched, so
a genuine template bug still reads as one. One level is exactly enough and no
loop is wanted: Twig re-throws a `Twig\Error` from an enclosing template or block
as-is (only a *non*-`Error` gets wrapped), so however deep the component sits,
there is one wrapper. The unwrap is deliberately narrow — an `HttpExceptionInterface`
is a value the kernel knows how to answer, and nothing else qualifies.

The general shape is worth naming, because it is not only about 404s: anything a
page's `mount()` throws to the *framework* rather than to the user has to survive
this trip, and a redirect-by-exception or an `AccessDeniedException` will want the
same unwrap when authorization lands.

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

### The record class is declared, never derived from an application namespace

Filament's `getModel()` falls back to
`class_basename(static::class)->beforeLast('Resource')->prepend(app()->getNamespace() . 'Models\\')`,
so `ArticleResource` finds `App\Models\Article` with nothing declared.
`app()->getNamespace()` reads the application's PSR-4 root out of its
`composer.json` — a Laravel-container concept with no Symfony equivalent, and one
that also assumes every entity lives in a single flat namespace, which Doctrine
applications routinely do not. So the arm is dropped rather than approximated:
`$recordClass` must be declared, and `getRecordClass()` throws a `LogicException`
naming the resource and the property when it is not. The cost is one line per
resource; the gain is that the entity a resource speaks for is stated rather than
guessed from a naming convention the port cannot enforce.

### The slug has no namespace-path arm

Filament's `resolveDefaultSlug()` has a second branch: when the resource's parent
namespace segment equals the pluralized basename (`App\Filament\Resources\Articles\ArticleResource`),
the slug is built from the namespace *path* after `\Resources\`, so nested
directories become nested URL segments. Ours only ever derives from the class
basename. The convention it keys on is Filament's own generator layout, which no
Sylament application has yet, and the nested-slug feature it enables (nested
resources, clusters) is deferred whole. `str_replace('/', '.')` is still applied
when composing the route base name, so a `$slug` containing a slash already works
and the arm can return without disturbing anything downstream.

### Pluralization takes the inflector's last candidate

Laravel's `Str::plural` returns one string; Symfony's
`EnglishInflector::pluralize()` returns *candidates*, ascending in specificity —
`pluralize('status')` is `['status', 'statuses']` and `pluralize('person')` is
`['persons', 'people']`. Taking `[0]`, the obvious reading, would leave
`StatusResource` un-pluralized and give `persons` where Laravel gives `people`,
so the port takes the **last** candidate. That matches Laravel on every word
tested, but the two inflectors are different engines and their candidate sets
diverge for exotic nouns; `$slug` and `$pluralRecordLabel` are the overrides when
one does. One wart is inherited from the engine rather than chosen: Symfony's
inflector pluralizes an already-plural word (`Articles` → `Articless`), so a
resource class whose basename is already plural and does not end with `Resource`
needs an explicit `$slug`.

### There is no locale gate on pluralization

Filament wraps `Str::plural` in `locale_has_pluralization()`, which asks
Symfony's own `MessageSelector` whether the active locale has more than one
plural form and skips pluralizing when it does not. The gate needs a locale, and
the port has no i18n layer at all — no catalogues, no `trans` calls, no
user-facing string carrying a message id ([the dashboard title](#the-dashboard-title-is-a-literal-not-a-translation-key)
records the same absence). Labels are English-derived literals here exactly as
they are in Filament, which never translates them either, so the gate would
guard nothing. It returns with i18n.

### The route base name is composed in one place

Filament prepends the literal `resources.` in `getRouteBaseName()` *and*
independently in `registerRoutes()`'s `Route::name('resources.')`, so the name a
URL is generated from and the name a route is registered under are built by two
pieces of code that must agree. Ours composes
`sprintf('sylament.%s.resources.%s', …)` once, in `getRouteBaseName(Panel)`, and
the route loader reads it — one derivation, no lockstep to maintain. The
`Panel|string|null $panel` parameter is explicit rather than defaulted from a
facade, matching [`Page::getRouteName()`](#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade).

### Nothing is memoised in a static

Filament caches two pure derivations in `protected static` arrays keyed by
class-string — `HasRoutes::$cachedDefaultSlugs` and
`Resources\Pages\Page::$cachedResourcePageNames`. Under PHP-FPM that is a
per-request cache; under a long-lived worker (FrankenPHP, Swoole, RoadRunner) it
lives for the worker's whole life, which is safe only because the derivations are
deterministic and the classes immutable. The port keeps no such state: every
derivation recomputes, which is a handful of string operations per call and keeps
the [worker-safe](#the-shell-is-turbo-indifferent-and-worker-safe) constraint the
shell is built under. The same reasoning rules out Filament's mutating static
setters (`navigationLabel()`, `navigationSort()`, `navigationGroup()`,
`navigationIcon()`, …), which write process-global state from panel
configuration; a resource is a service, so panel-level customisation of a
third-party resource will be configuration on the `Panel`, not a static write.

### A record is resolved through the resource's own query

`resolveRecordRouteBinding()` clones `getRecordQuery()` and adds the identifier
constraint, rather than calling `EntityManager::find()`. The clone is there for
the override: the shipped `getRecordQuery()` mints a fresh builder every call, but
a subclass is free to return a memoised one, and adding a `WHERE` to a builder
the list page also reads would poison it. This is the security
property Filament's `getEloquentQuery()` comment describes: a resource that
scopes its base query to the current user's rows must yield a **404** for a row
outside that scope, not the row. Going through `find()` would bypass the scope
entirely and leak by primary key, and the leak would be invisible — the list page
would look correctly filtered while the edit URL served anything. The root alias
is read off the builder (`getRootAliases()[0]`) so an overridden query that names
its own alias still works, and a composite identifier throws rather than guessing
which column the URL segment means. `getRecordRouteKey()` is the generation side
of the same pair, replacing what Laravel got free from `UrlRoutable`; both sides
read `$recordRouteKeyName`, so they cannot disagree.

### `getRecordTitle()` returns a plain string

Filament types it `string | Htmlable | null`, which under the project's escaping
contract would be `string|Markup|null`. Ours returns `string`. The null arm is
already unreachable in Filament — every path falls back to `getModelLabel()` —
and the `Markup` arm buys nothing here because record titles compose into
sentences (`sprintf('Edit %s', $title)`, a breadcrumb, a page heading), where a
raw-HTML title would either be escaped by the surrounding template anyway or
smuggle markup into a heading. A resource that genuinely wants markup in a title
overrides the getter and widens its own return type. Note the fallback follows
Filament exactly and is the *un*-title-cased `getRecordLabel()`, not
`getTitleCaseRecordLabel()`.

The read itself needs a `catch` Filament does not: `$record?->getAttribute($attr)`
answers `null` for an attribute an Eloquent model does not have, while schemas'
`PropertyAccessor::getFromRecord()` propagates Symfony's `AccessException` for a
property with no accessor or an uninitialized one. Catching it and falling back to
the label is what reproduces Filament's behaviour — a mis-declared
`$recordTitleAttribute` degrades to the label rather than fataling a whole list
page. It is the only swallowed exception on the class; `getRecordRouteKey()`
deliberately lets the same failure through, because a route key that cannot be
read is a broken URL, not a cosmetic default.

### `getUrl()` drops three parameters and widens a fourth

Filament's signature carries seven parameters; ours carries four. `$tenant` and
`$configuration` belong to deferred features (tenancy, resource configurations)
and `$shouldGuessMissingParameters` fills parent-resource route parameters from
the original request — the nested-resource arm, deferred with it, and the one
place Filament reaches `original_request()` inside URL generation. What survives
is widened: Filament takes `?string $panel` (a panel id) where ours takes
`Panel|string|null`, because the caller that already holds the panel object —
`getNavigationItems()`, the route loader, a page — should not have to round-trip
through the registry by id. A `null` still resolves the current panel, then the
default one, which is Filament's `getCurrentOrDefaultPanel()` behaviour with the
facade replaced by the two injected services.

### Active state is a route-name prefix match

Filament matches with `original_request()->routeIs($routeBaseName . '.*')`, a
route-name glob, so the `Articles` navigation item stays lit on the resource's
create and edit pages. Symfony's router has no `routeIs()`, so the glob becomes
`str_starts_with($request->attributes->get('_route'), $routeBaseName . '.')`. The
trailing dot matters: without it `…resources.articles` would also match
`…resources.articles-archive`. This is the wildcard arm
[`Page::getNavigationItems()`](#page) deliberately did not need — a page matches
its one route by equality — and it is why a resource's `isActiveWhen()` closure
takes `RequestStack` from [the item's own injected service
set](#the-item-hands-its-own-services-to-evaluated-closures) rather than a
matcher helper, of which there is still none.

### A `new`-built resource fails fast rather than degrading

The five services arrive through a `#[Required]` setter, so a resource built with
`new` — a unit test, a user constructing one by hand — holds none of them. Every
method that needs one throws a `LogicException` naming the resource and saying it
was built outside the container, rather than returning null or an empty result.
The alternative, degrading to a container-less fallback the way
[`EvaluatesClosures`](../support/component.md) does for its `Invoker`, is wrong
here: there is no meaningful entity manager or URL generator to fall back to, and
a silently empty query is exactly the failure a reader will not think to
question. Pure derivations — slugs, labels, `getPages()`, `hasPage()`,
`getRouteBaseName()` — need no service and keep working, which is what makes the
class cheap to unit-test.

### Resources are collected from a tag, not discovered on the filesystem

Filament finds resources two ways, both imperative: `->resources([X::class])`
listed on the panel, and `->discoverResources(in: …, for: …)`, which globs a
directory and reflects every class it finds. The port keeps the first and
replaces the second with the tag — `SylamentPanelsBundle::build()` autoconfigures
`sylament.panels.resource` onto every `Resource` service, and
[`ResourcePass`](../../../packages/panels/src/DependencyInjection/Compiler/ResourcePass.php)
collects the non-abstract ones into a class-keyed `ServiceLocator` on
`ResourceRegistry`. This is the same move [pages
made](#pages-register-as-live-components-through-a-compiler-pass-not-per-class-attributes),
and for a related reason: a resource must be a **service** to hold dependencies,
and a Symfony application already declares its services with a PSR-4 glob in
`services.yaml`. A filesystem scan on top of that would be a second discovery
mechanism disagreeing with the first — a class the glob excluded would still be
reflected, and a resource registered by hand with constructor arguments would be
found twice.

Two consequences worth stating. The locator has **no stable service id** —
`ServiceLocatorTagPass::register()` mints one, and the only way in is
`ResourceRegistry`, which is what keeps the collection an implementation detail.
And what the pass collects is every registered `Resource` **service**, not the
resources a panel lists in `resources()`: the registry can resolve a class-string
the panel never mentioned, exactly as `PageComponentPass` tags pages no panel
routes. The panel list stays authoritative for routing and navigation; the
registry is only the class-string → instance lookup.

`discoverResources()` itself is deferred rather than rejected. If it ever
returns it is a convenience over `services.yaml` — a helper that registers the
services — not a parallel registry.
