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
follows. The package's docs are in a deliberate mixed state: most detail lives as
in-file anchors in this README (a recorded divergence from the tree rule), while
M6 · 1 started the first per-component detail files and every surface since has
followed that shape, fourteen so far — the relation-manager tier
([relation-manager.md](relation-manager.md),
[relation-group.md](relation-group.md),
[has-relation-managers.md](has-relation-managers.md),
[interacts-with-relationship-table.md](interacts-with-relationship-table.md),
[manage-related-records.md](manage-related-records.md)), the record and
navigation concerns ([interacts-with-record.md](interacts-with-record.md),
[has-sub-navigation.md](has-sub-navigation.md),
[nested-resources.md](nested-resources.md)), the page tier
([custom-pages.md](custom-pages.md), [form-actions.md](form-actions.md),
[simple-page.md](simple-page.md),
[unsaved-changes-alert.md](unsaved-changes-alert.md)),
[global-search.md](global-search.md), and M4 · 3's
[has-tabs.md](has-tabs.md). The inline sections stay until each is migrated.

## Carried decisions

Settled while porting other packages, recorded here because they constrain how
this one gets built and would otherwise be re-litigated once the panel epics
reached them. All three have now been reached: the `Resource` decision was spent
by M2 · 3 and is marked implemented, and the two shell constraints held through
M2 · 1 to M2 · 3.

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
M2 · 2's current-panel lookup had to be derived the same way, from the request
and never held in a stateful singleton, and
[it is](#the-current-panel-is-derived-from-the-request-not-stored-on-a-manager).

### The shell is not live

Filament v5's sidebar and topbar are Livewire components. Ours are anonymous Twig
components rendering a plain PHP navigation model, because nothing in M2 needs a
server round-trip from the shell: navigation is a set of links, collapse state is
client-side, and the only interactive parts (the mobile drawer, per-group
collapse) are markup plus one Stimulus controller. A live shell would buy
re-rendering on the server and cost a component round-trip on every page.

One thing in the shell has since needed exactly that round trip, and it is not
the one this entry predicted. M6 · 4's [global search](global-search.md) asks
the server between navigations — a query in, matching records out — so the
topbar now carries a live root. It is a **sibling** of the static shell rather
than a wrapper around it: `Topbar` stays anonymous and renders
`<twig:SylamentPanels:GlobalSearch />` inside `sy-pa-topbar-end`, which is where
the live boundary begins and ends. So the round trip is paid by the one element
that needs it, and everything else in the bar still renders once per page. That
component is also the toolkit's **first** `#[AsLiveComponent]` in `packages/*/src`
— panels' pages are live too, but they are registered by
[a compiler pass](#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute)
rather than by the attribute.

Revisit the rest when something else in the shell must change without a
navigation — the database-backed notifications trigger is the next candidate,
since an unread count in the topbar is a server fact that changes between page
loads. Until then the divergence is a simplification, not a gap.

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
| [`Pages\Page`](../../../packages/panels/src/Pages/Page.php) | [`Pages/Page.php`](../../../vendor/filament/filament/src/Pages/Page.php), [`Pages/Concerns/InteractsWithHeaderActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php), [`InteractsWithFormActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithFormActions.php), [`HasMaxWidth.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasMaxWidth.php), [`HasTopbar.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasTopbar.php), [`HasUnsavedDataChangesAlert.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) | in progress | [Page](#page), [custom-pages.md](custom-pages.md) |
| [`Pages\SimplePage`](../../../packages/panels/src/Pages/SimplePage.php) | [`Pages/SimplePage.php`](../../../vendor/filament/filament/src/Pages/SimplePage.php) | in progress | [simple-page.md](simple-page.md) |
| [`Pages\Dashboard`](../../../packages/panels/src/Pages/Dashboard.php) | [`Pages/Dashboard.php`](../../../vendor/filament/filament/src/Pages/Dashboard.php) | in progress | [Dashboard](#dashboard) |
| [`Panel`](../../../packages/panels/src/Panel.php) | [`Panel.php`](../../../vendor/filament/filament/src/Panel.php) | in progress | [Panel](#panel) |
| [`PanelProvider`](../../../packages/panels/src/PanelProvider.php) | [`PanelProvider.php`](../../../vendor/filament/filament/src/PanelProvider.php) | in progress | [Panel](#panel) |
| [`PanelRegistry`](../../../packages/panels/src/PanelRegistry.php) | [`PanelRegistry.php`](../../../vendor/filament/filament/src/PanelRegistry.php) | in progress | [Panel](#panel) |
| [`CurrentPanel`](../../../packages/panels/src/CurrentPanel.php) | [`FilamentManager.php`](../../../vendor/filament/filament/src/FilamentManager.php) current-panel arm, [`Http/Middleware/SetUpPanel.php`](../../../vendor/filament/filament/src/Http/Middleware/SetUpPanel.php) | in progress | [Panel](#panel) |
| [`Resources\Resource`](../../../packages/panels/src/Resources/Resource.php) | [`Resources/Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php) and [`Resources/Resource/Concerns`](../../../vendor/filament/filament/src/Resources/Resource/Concerns) — `HasLabels`, `HasPages`, `HasRoutes`, `HasNavigation`, `CanGenerateUrls` | in progress | [Resources](#resources) |
| [`Resources\ResourceRegistry`](../../../packages/panels/src/Resources/ResourceRegistry.php) | no Filament counterpart — a Filament resource is reached by calling its statics on the class-string | in progress | [Resources](#resources) |
| [`Resources\Pages\PageRegistration`](../../../packages/panels/src/Resources/Pages/PageRegistration.php) | [`Resources/Pages/PageRegistration.php`](../../../vendor/filament/filament/src/Resources/Pages/PageRegistration.php) | in progress | [Resources](#resources) |
| [`Resources\Pages\Page`](../../../packages/panels/src/Resources/Pages/Page.php) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | in progress | [Resource pages](#resource-pages) |
| [`Resources\Pages\ListRecords`](../../../packages/panels/src/Resources/Pages/ListRecords.php) | [`Resources/Pages/ListRecords.php`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) | in progress | [ListRecords](#listrecords) |
| [`Resources\Pages\CreateRecord`](../../../packages/panels/src/Resources/Pages/CreateRecord.php) | [`Resources/Pages/CreateRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) | in progress | [CreateRecord](#createrecord) |
| [`Resources\Pages\EditRecord`](../../../packages/panels/src/Resources/Pages/EditRecord.php) | [`Resources/Pages/EditRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/EditRecord.php) | in progress | [EditRecord](#editrecord) |
| [`Resources\Pages\ManageRelatedRecords`](../../../packages/panels/src/Resources/Pages/ManageRelatedRecords.php) | [`Resources/Pages/ManageRelatedRecords.php`](../../../vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php) | in progress | [ManageRelatedRecords](manage-related-records.md) |
| [`GlobalSearch\GlobalSearchResult`](../../../packages/panels/src/GlobalSearch/GlobalSearchResult.php), [`GlobalSearchResults`](../../../packages/panels/src/GlobalSearch/GlobalSearchResults.php) | [`GlobalSearch/GlobalSearchResult.php`](../../../vendor/filament/filament/src/GlobalSearch/GlobalSearchResult.php), [`GlobalSearchResults.php`](../../../vendor/filament/filament/src/GlobalSearch/GlobalSearchResults.php) | in progress | [global-search.md](global-search.md) |
| [`GlobalSearch\Providers\DefaultGlobalSearchProvider`](../../../packages/panels/src/GlobalSearch/Providers/DefaultGlobalSearchProvider.php) and its [contract](../../../packages/panels/src/GlobalSearch/Providers/Contracts/GlobalSearchProvider.php) | [`GlobalSearch/Providers`](../../../vendor/filament/filament/src/GlobalSearch/Providers) | in progress | [global-search.md](global-search.md) |
| [`GlobalSearch\GlobalSearchProviderRegistry`](../../../packages/panels/src/GlobalSearch/GlobalSearchProviderRegistry.php) | no Filament counterpart — a provider is resolved with `app($panel->getGlobalSearchProvider())` | in progress | [global-search.md](global-search.md#the-provider-is-resolved-through-a-tagged-registry-rather-than-app) |
| [`Twig\Components\GlobalSearch`](../../../packages/panels/src/Twig/Components/GlobalSearch.php) | [`Livewire/GlobalSearch.php`](../../../vendor/filament/filament/src/Livewire/GlobalSearch.php) | in progress | [global-search.md](global-search.md), and [the shell's first live root](#the-shell-is-not-live) |
| [`Resources\RelationManagers\RelationManager`](../../../packages/panels/src/Resources/RelationManagers/RelationManager.php) | [`Resources/RelationManagers/RelationManager.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php), [`Resources/Concerns/InteractsWithRelationshipTable.php`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php) | in progress | [RelationManager](relation-manager.md) |
| [`Resources\RelationManagers\RelationGroup`](../../../packages/panels/src/Resources/RelationManagers/RelationGroup.php) (+ `RelationManagerConfiguration`) | [`Resources/RelationManagers/RelationGroup.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationGroup.php), [`Resources/RelationManagers/RelationManagerConfiguration.php`](../../../vendor/filament/filament/src/Resources/RelationManagers/RelationManagerConfiguration.php) | in progress | [RelationGroup](relation-group.md) |
| _rest not audited yet_ | | | |

### NavigationItem

The item's own surface is ported — label, icon and active icon, url with the
new-tab flag, badge and badge colour, sort, group, the visible/hidden pair with
hidden winning, and the memoising `isActiveWhen()`/`isActive()`.

Deferred, all todo:

- `key()` / `getKey()` — the identity a nested item is addressed by; it only
  earns its keep once items nest.
- `parentItem()` / `getParentItem()` and `childItems()` / `getChildItems()` /
  `isChildItemsActive()` — the nesting arm, deferred whole with `key`, which is
  what makes
  [the band's `processParentNavigationItems()` pass](has-sub-navigation.md#three-v1-skips-item-nesting-collapsible-groups-and-badge-tooltips)
  wait too.
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

`getBreadcrumbs(): array` is here too, returning `[]`, and its contract is the
map [support's `Breadcrumbs`](../support/breadcrumbs.md) consumes: a **string**
key is a URL and its crumb links, an **int** key is plain text. The base returns
nothing, so a plain panel page — the [`Dashboard`](#dashboard) included — heads
no trail, and [`Page/index.html.twig`](#routing) renders no `Header` at all
unless something else fills it.

Two more members arrived with M6 · 3. `shouldRegisterNavigation()` and its
`protected static bool $shouldRegisterNavigation = true` are Filament's, and the
flag gates both surfaces: the [sub-navigation band](has-sub-navigation.md)
filters on it, and `Panel::getNavigation()`'s page loop `continue`s on it in the
same shape the resource half of that loop already used — which is the position
Filament's `Pages\Page::registerNavigationItems()` early-returns from. So a page
opting out is absent from a band and from the **sidebar** alike, pinned by an
`OptedOutNavigationPage` fixture registered and routed like its siblings, which
leaves the gate the only thing keeping it out. The other two arms of that method
stay unported on the terms the
[resource loop](#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource)
already states: `getCluster()` guards an unported subsystem, and `canAccess()`
waits on authorization.

The base also composes [`Pages\Concerns\HasSubNavigation`](has-sub-navigation.md), so
**every** panel page can draw a band: `getSubNavigation()` returns `[]` by
default, `getCachedSubNavigation()` normalizes whatever a page answers with, and
`generateNavigationItems()` builds one item per sibling page. The page's own
`$subNavigationPosition` static is the first rung of the position ladder.

**M6 · 2 turned the base into a page a user writes**, and the whole of that tier
is [custom-pages.md](custom-pages.md). In one paragraph: the class now
`implements InteractsWithActions` and `InteractsWithSchemas` and composes
`HasActions` (aliased) and `HasSchemas`, so **every** panel page hosts actions
and schemas — with `schemas()` narrowed to `[]` and an identity
`content(Schema): Schema` a page overrides for its body; five
`Pages\Concerns` traits arrive beside it
([`InteractsWithHeaderActions`](custom-pages.md),
[`InteractsWithFormActions`](form-actions.md),
[`HasMaxWidth` and `HasTopbar`](simple-page.md),
[`HasUnsavedDataChangesAlert`](unsaved-changes-alert.md)); the header-action
walk, `callHook()`, `halt()`, the `panelId` prop and its
[priority-20 stamp](custom-pages.md#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism)
moved up from the [resource base](#resource-pages); and `getPanel()`,
`getPageClasses()`, `getExtraBodyAttributes()`, `getLayout()`/`getLayoutData()`,
`getMaxContentWidth()`, `hasUnsavedDataChangesAlert(): false` and a
`#[LiveListener('refresh-page')] refresh()` are new. The partial it renders
through is no longer a page with an empty body but
[the chrome every other page template extends](#routing), with `header`,
`content` and `footer` blocks.

[`Pages\SimplePage`](simple-page.md) is the one subclass the package ships
beside [`Dashboard`](#dashboard): an abstract page that picks the centred-card
shell through `getLayout()`, composes `HasMaxWidth` and `HasTopbar`, renders its
own partial, and defaults `$shouldRegisterNavigation` to `false` so it stays out
of a sidebar it does not draw. Filament's cannot be routed through `pages()` at
all — [a strictly larger surface here](simple-page.md#simplepage-extends-page-so-it-is-routable-and-hides-itself-from-navigation).

Deferred, all todo:

- The `BasePage` split — Filament separates `BasePage` (the render/lifecycle
  half) from `Page` (the panel-aware half); it waits on the auth epic, which is
  what first needs a page that knows no panel.
- The panel-level breadcrumb toggle. Filament's `Panel::breadcrumbs(bool)` /
  `hasBreadcrumbs()` switches the trail off for a whole panel and its
  `hasResourceBreadcrumbs()` does the same for resource pages alone; neither is
  ported, so a page that returns crumbs always renders them.
- A writer for the `heading`/`subheading` pair. Filament ships none either — its
  `BasePage` declares both as protected properties a subclass assigns or a
  subclass getter overrides — so today the pair stays subclass-only.

Three deltas against Filament's page:
[`$navigationGroup` is string-only](#navigationgroup-is-string-only-on-page-too),
[`getNavigationItems()` and `getRouteName()` take the panel](#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade),
and [a concrete page carries a bare `#[AsLiveComponent]` that a compiler pass
registers over](#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute).

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
heroicon cases on `hasTopNavigation()` — `Heroicon::Home` for top navigation and
`Heroicon::OutlinedHome` otherwise; ours resolves the single `heroicons:home`
name, which *is* the outline arm, since the port has no top-navigation layout to
pick the mini one for.

### Panel

The definition surface a panel provider configures, plus the two services that
collect and locate panels. `Panel` composes eleven of Filament's `Panel/Concerns`
traits — `HasId` (write-once id, fatal when read unset), `HasRoutes` (the path
only), `HasComponents` ([`pages()` and `resources()`, each accumulating,
de-duplicated and
re-indexed](#getpages-re-indexes-where-filament-keeps-array_uniques-gapped-keys)),
`HasNavigation` (`navigationGroups()`/`navigationItems()` registration plus
`getNavigation()`, whose resource loop also
[leaves a nested resource out](nested-resources.md#the-suppressions-sit-where-the-trail-already-put-them)),
`HasSubNavigation` (`subNavigationPosition()` alone — the panel-wide default the
[band's position ladder](has-sub-navigation.md#the-position-is-one-instance-method-with-no-static-twin)
ends at), `HasSidebar` (`collapsibleNavigationGroups()` alone — the
panel-level default [`NavigationGroup::isCollapsible()`](#navigationgroup) falls
back to), `HasBrandName`, `HasBrandLogo` (`brandLogo()`/`darkModeBrandLogo()`,
each `string|Markup|Closure|null` under the standing `Htmlable` → `Markup`
translation, plus `brandLogoHeight()` — consumed by the [`Logo`
template](#layout-templates)) and
[`HasGlobalSearch`](global-search.md) (the provider switch plus the field's
debounce, key bindings and suffix), and the two M6 · 2 brought forward,
[`HasMaxContentWidth`](simple-page.md#the-width-resolution-is-one-ladder-in-the-controller)
(`maxContentWidth()` and `simplePageMaxContentWidth()`, the two ends of the
width ladder) and
[`HasUnsavedChangesAlerts`](unsaved-changes-alert.md) (`unsavedChangesAlerts()`,
off by default, so shipping it changed nothing until a panel opted in) — with
`default()`/`isDefault()` on the class itself, matching Filament.

`HasGlobalSearch` carries one member Filament files elsewhere:
`isGlobalSearchEnabled()` is a `FilamentManager` method over there, and there is
no manager here — a panel is reached through `PanelRegistry`/`CurrentPanel` — so
it sits on the panel under its **unchanged** Filament name. A relocation, not a
rename, which is why it earns no row in
[the rename table](../renames.md) and is recorded
[here and in global-search.md](global-search.md#isglobalsearchenabled-moved-off-the-manager-onto-the-panel)
instead.

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

Deferred, all todo: the other 23 `Panel/Concerns` traits (auth, tenancy, colors,
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

Because it is a service, the whole surface is instance methods. `form(Schema)`,
`infolist(Schema)` and `table(Table)` are Filament's identity defaults verbatim —
`infolist()` ships ahead of infolists themselves because it is what a
[`ViewAction`](../actions/view-action.md) mounted on a resource page resolves its
modal schema through, and [M5 · 2](../roadmap.md#m5--view-page--infolists) fills
it in; the five services
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
  `blog post` → `blog posts` → `Blog Post` / `Blog Posts`). The singular step is
  support's [`get_record_label()`](../support/helpers.md) rather than a second
  copy of that derivation — actions' record family reads the same label off a
  bare class-string, so the pipeline lives in one place and the resource
  delegates to it. Title case is
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
  class-string and its path, which
  [`Resources\Pages\Page::route()`](#resource-pages) mints —
  `ListArticles::route('/')`, Filament's call shape, returning
  [a value rather than registering a route](#route-returns-a-registration-rather-than-registering-a-route).
  The route itself is built by [`PanelRouteLoader`](#routing), which needs the
  path as data.
- **Query and record binding.** `getRecordQuery()` is the
  [`getEloquentQuery()`](../renames.md#renames) port and the single overridable
  base query. `resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null)`
  resolves a URL segment
  [through that query rather than `EntityManager::find()`](#a-record-is-resolved-through-the-resources-own-query),
  reading the root alias off the builder so an overridden query still works — the
  second parameter is Filament's, restored with the
  [nested resources](nested-resources.md) it belongs to, and it may answer with a
  **different** builder, which is how a parent scope replaces the query rather
  than adding a predicate to it — and
  `getRecordRouteKey()` is the generation side of the same key —
  `$recordRouteKeyName` when set, else the key
  [`RecordKey::of()`](../schemas/record-key.md#the-api) mints for an identifier
  of **any** width. The resolution side splits the same way: with no
  `$recordRouteKeyName` it decodes the segment strictly and narrows through
  `IdentifierConstraint::equals()`, so a key no record of the class could mint
  answers no record **without running a statement**; with one, it binds that
  one field,
  [untyped and uncodec'd](#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it).
- **URLs.** `getUrl()` / `getIndexUrl()` over
  `UrlGeneratorInterface::generate()`, with Filament's `blank($name)` → index
  fallback and its verbatim "does not have an [index] page" message.
  `getRouteBaseName(Panel)` composes `sylament.<panel>.resources.<slug>` in
  [exactly one place](#the-route-base-name-is-composed-in-one-place). All three
  grew a nested arm with M6 · 3: the base name nests under the parent's plus the
  kebab-cased relationship, `getUrl()` keys ancestor **objects** and takes
  `shouldGuessMissingParameters`, and `getIndexUrl()` answers from the parent's
  pages — [nested-resources.md](nested-resources.md) carries all three.
- **Navigation.** `getNavigationItems(Panel)` contributes at most one item, only
  when an `index` page exists, matching Filament — including the
  `$activeNavigationIcon ?? $navigationIcon` fallback and the
  `getNavigationBadge()` / `getNavigationBadgeColor()` override hooks that return
  `null`. Active state is a [route-name prefix match](#active-state-is-a-route-name-prefix-match),
  which is what keeps the item lit on the resource's create and edit pages.
  [`Panel::getNavigation()`](#panel) is what calls it — the resource never pushes
  its item anywhere, which is the
  [pull model](#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource).
- **Global search.** The one cluster that is a **trait** rather than a section of
  `Resource` itself, filed exactly where Filament files it:
  `Sylament\Panels\Resources\Resource\Concerns\HasGlobalSearch`, which is what
  created the package's first `Resources/Resource/Concerns/` directory. Its
  surface, what it defers, and the whole feature around it — the two value
  objects, the provider contract and the panel knobs — are
  [global-search.md](global-search.md); nothing about it is repeated here.

- **Relations.** `getRelations()` returns the relation managers a record page of
  this resource mounts, keyed by the identity each one's tab is activated under.
  It is an instance method where Filament's is static, for the reason every other
  hook here is — [HasRelationManagers](has-relation-managers.md#getrelations-is-an-instance-method-like-every-other-resource-hook).

Deferred, all todo: authorization (`canAccess()` and the whole `HasAuthorization`
cluster), widgets, clusters, tenancy, and resource configurations. Two of that
list came off it with M6 · 3: **nested/parent resources** ship as
[`Resource\Concerns\BelongsToParent`](nested-resources.md) — the `$parentResource`
static, the registration, the derived relationship name and route parameter, the
parent-scoped query and the attach — and **sub-navigation** as
`getRecordSubNavigation(Page)` plus `getSubNavigationPosition()`, the resource
rungs of [the band](has-sub-navigation.md).
`configureTable()`'s seeding of table-level labels landed with the tables label
surface — on [`ListRecords::table()`](#the-list-pages-table-is-stamped-query-first-and-resource-last),
the instance seam that stands in for Filament's static one. The title-case **toggle** is
unported with them — Filament's `$hasTitleCaseModelLabel` (default `true`) with
`hasTitleCaseModelLabel()` and `titleCaseModelLabel(bool)` — so
`getTitleCaseRecordLabel()` title-cases unconditionally and a subclass declaring
the static silently no-ops; the two getters themselves are ported.
`NavigationItem`'s `key()`, `parentItem()` and `badgeTooltip()` are skipped here
because [they are not ported on the item yet](#navigationitem);
`shouldRegisterNavigation()` is ported and, as in Filament, is consulted by the
panel-side loop rather than by `getNavigationItems()` itself.
`Resource\Concerns\HasBreadcrumbs` is ported too, and it closes what this section
used to record as a missing API: `protected static ?string $breadcrumb` with
`getBreadcrumb(): string` answering `static::$breadcrumb ??
getTitleCasePluralRecordLabel()`, which is Filament's default verbatim. Every
crumb the [page base](#resource-pages) builds for a resource — this one's and
each ancestor's — reads it, so a resource can rename itself in the trail without
touching a page.

One consequence of the breadcrumb trail lands here rather than on the page: a
resource with no `index` page **throws** the moment any of its pages renders,
where before the trail existed it merely contributed no navigation item. The
trail's first crumb is `getIndexUrl()`, and that is Filament's verbatim *"does
not have an [index] page. Define [getIndexUrl()] for alternative routing"*
`LogicException`
([`CanGenerateUrls.php:122-123`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php)) —
not a `RouteNotFoundException`, which is what Filament's own unconditional
`getResourceUrl()` call would raise a layer further down. Parity, and the
message names the fix.

M6 · 3 narrows which resources can reach it. A **nested** resource's index is its
parent's page, so `getIndexUrl()` answers from
[four parent arms](nested-resources.md#the-parent-arms-carry-no-relation-parameter)
before it ever tests `hasPage('index')` — which is the shape a generated nested
resource has, `create` and `edit` and nothing else. What still throws is a
resource that names no parent and registers no index page, which is the case the
message was always about.

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
[`getUrl()` drops two parameters and widens a third](#geturl-drops-two-parameters-and-widens-a-third),
[active state is a route-name prefix match](#active-state-is-a-route-name-prefix-match),
and [a `new`-built resource fails fast](#a-new-built-resource-fails-fast-rather-than-degrading).

**One structural decision is deferred, not settled.** Filament splits its
`Resource` across twelve traits in
[`Resources/Resource/Concerns`](../../../vendor/filament/filament/src/Resources/Resource/Concerns),
five of which cover what shipped first — `HasLabels`, `HasPages`, `HasRoutes`,
`HasNavigation`, `CanGenerateUrls` — and ours holds all five in one file.
`HasGlobalSearch` is the exception and does not reopen the question: it arrived
whole, as one self-contained cluster with nothing of the original five entangled
in it, so there was no guess to make about where its members belong. That is the
bar the rest of the split has to clear too. The one file is deliberate for now
rather than a miss: the class is the
largest new file the resource epic added, but the deferred list above is longer
than what shipped, so splitting now would file each trait against a guess at
where the unported members land — and a trait whose contents are guessed is
harder to move later than a method is. The names are Filament's and stay
available; revisit when the authorization and pages clusters land, since those
are what would push the file past one screenful per concern, and the categories
can then be read off the finished set instead of predicted.

### Resource pages

`Resources\Pages\Page` is the base every page a resource registers extends —
Filament's `Resources\Pages\Page`, which likewise extends the panel
[`Page`](#page) rather than standing beside it, so a resource page keeps the
whole page surface (title, heading, template, live-component registration) and
adds the resource hop.

The **hosting** moved up with M6 · 2: `implements InteractsWithActions`, the
aliased `use HasActions`, `implements InteractsWithSchemas` and `use HasSchemas`
all sit on [`Pages\Page`](#page) now, so a resource page composes only what it
adds (tables' `HasTable` on the list page, `InteractsWithRecord` and
`HasRelationManagers` on the record ones) and this base keeps its `getDefault*`
overrides in the class body, where PHP's precedence keeps them winning. That is
not a tidying choice: PHP inserts a trait's members *over* an inherited class's,
so a `getDefaultActionUrl()` here would be silently shadowed by `HasActions`'
null-returning default in any subclass that re-`use`d the trait —
[the actions host sits on the base because a trait outranks an inherited method](#the-actions-host-sits-on-the-base-because-a-trait-outranks-an-inherited-method).
The header-action walk itself, `callHook()`, `halt()` and the `panelId` prop
moved up in the same change, into
[`Pages\Concerns\InteractsWithHeaderActions`](custom-pages.md) and the page
base; what stayed is everything that needs a **resource**.

Eleven members, plus what M6 · 3 added — the parent-record chain, the breadcrumb
walk, `getDefaultActionRelationship()`, the sub-navigation position and item seam
— and what M6 · 2 added, `getResourceUrl()`.
[`getPageClasses()`](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class)
is **not** one of them: it arrives one tier up, on
[`Pages\Page`](#page), and each concrete page overrides it.
`protected static string $resource` is the class-string a subclass
declares and `getResource()` reads — untyped-default exactly as in Filament, so a
page that forgets it fails on the first read rather than resolving something
plausible. `route(string $path): PageRegistration` is the registration factory a
resource's `getPages()` calls. `getResourceInstance(): Resource` is the hop
itself: an `#[Required] setResourceRegistry()` setter takes
[`ResourceRegistry`](#resources) and the getter resolves `static::getResource()`
through it, throwing a `LogicException` naming container registration when the
setter never fired — the same fail-fast shape
[a `new`-built resource](#a-new-built-resource-fails-fast-rather-than-degrading)
has, for the same reason.
[`getResourceUrl()`](custom-pages.md#getresourceurl-fills-the-record-and-threads-the-parent-chain)
is the instance-side URL helper a custom record page reaches for, filling the
record from the page and threading the parent chain.
`getHeaderActions(): array` returns `[]`, the override
hook a page fills — it lives on
[`Pages\Concerns\InteractsWithHeaderActions`](custom-pages.md) since M6 · 2 and
is **public** where Filament's is `protected`, because the page template reads
the bound list and Twig only reaches public members. That widening is
self-enforcing: PHP refuses to narrow visibility in an override, so a user
copying Filament's `protected function getHeaderActions()` gets a fatal at class
load rather than a silently empty header. `getDefaultActionUrl(Action): ?string`
is the fifth — actions' [url fallthrough hook](../actions/interacts-with-actions.md),
answering for exactly the three prebuilt actions a resource can have a page for:

```php
public function getDefaultActionUrl(Action $action): ?string
{
    $resource = $this->getResourceInstance();
    $recordClass = $action->getRecordClass();
    $ownsRecordClass = $recordClass === null || $recordClass === $resource->getRecordClass();

    if ($action instanceof CreateAction && $resource->hasPage('create') && $ownsRecordClass) {
        return $resource->getUrl('create');
    }

    if ($action instanceof EditAction && $resource->hasPage('edit') && ! $this instanceof EditRecord && $ownsRecordClass && $action->getRecord() !== null) {
        return $resource->getUrl('edit', ['record' => $action->getRecord()]);
    }

    if ($action instanceof ViewAction && $resource->hasPage('view') && $ownsRecordClass && $action->getRecord() !== null) {
        return $resource->getUrl('view', ['record' => $action->getRecord()]);
    }

    return null;
}
```

Every clause earns its place. The `instanceof` discrimination is what keeps a
`DeleteAction` mounting its confirmation instead of becoming a link that opens
nothing — the hook is asked for **every** url-less action the page renders, and
any non-null answer wins, which is why Filament writes the same chain of guards.
`! $this instanceof EditRecord` stops an edit page linking a record-bound
`EditAction` at the page already showing that record; the `ViewAction` arm below
it carries no `! $this instanceof ViewRecord` twin only because there is no view
page class to name yet — that clause lands with
[M5 · 2](../roadmap.md#m5--view-page--infolists), and its absence changes nothing
until it does. `getRecord() !== null` guards the two record arms, because a URL
for a record the action does not carry would be generated with a null parameter.

`$ownsRecordClass` is Filament's `blank($actionModel) || $actionModel === …::getModel()`
pair under [the record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record),
hoisted out of the three arms because it asks one question for all of them: an
action bound to a record class this resource does not own must not be linked at
this resource's pages — a `CreateAction::make()->recordClass(Comment::class)` on
an articles page mounts its modal instead. It is almost always `true`, and
deliberately so: `Action::getRecordClass()` walks its chain down to
`getDefaultActionRecordClass()`, which on a resource page is the resource's own
class, so the two sides of the comparison agree unless the action was told
otherwise.

The `=== null` half is Filament's `blank($actionModel)` kept verbatim, and on a
resource page it is very nearly unreachable. The hook is only ever entered
through `CanOpenUrl::getUrl()`'s `getLiveComponentOrNull()?->getDefaultActionUrl($this)`,
so the action always has a host by the time it is asked; that host is this page,
whose `getDefaultActionRecordClass()` is the last hop of the chain and never
answers null (the record pair below says why). It is kept because it is half of a
ported pair and because a subclass is free to override that hook with null, not
because a resource page can reach it.

The record pair is sixth and seventh. `getDefaultActionRecordClass(Action)` and
`getDefaultActionRecordLabel(Action)` answer the last hop of actions'
[record chain](../actions/action.md#the-record-chain-reaches-the-schema-the-component-and-the-host)
off the resource — its record class and its singular label — so every prebuilt
record action mounted on a resource page knows what it is acting on, and a
`DeleteAction`'s "Delete article" heading comes out right with nothing declared
on the action. The contract types the class hook `?string`, but **it can never
answer null from a resource page**: `Resource::getRecordClass()`
[throws for a resource that declares none](#the-record-class-is-declared-never-derived-from-an-application-namespace)
rather than returning null, which is the right shape — a resource without a
record class is broken, not unopinionated — and the null the interface allows is
for hosts with no resource behind them.

`resolveMountedActionRecord(array $descriptor)` is the eighth: the class-body
override that resolves a mounted frame's record key through the resource's own
scoped query rather than a bare lookup — and, since M6 · 3, through
[the page's parent scope](nested-resources.md#one-scope-serves-the-route-key-and-the-unchecksummed-action-key)
as well, because that key is client-supplied and unchecksummed where the route
key is not. It keeps both of the trait's skip guards
and replaces its third —
[the whole shape is a semantics note](#a-mounted-frames-record-key-resolves-through-the-resources-own-query).
It is `protected`, and it is on this base for the same reason the URL hook is —
[a trait outranks an inherited method](#the-actions-host-sits-on-the-base-because-a-trait-outranks-an-inherited-method).
`callHook(string $hook)` was the ninth and now sits on
[`Pages\Page`](#page) instead, where every page can dispatch its own lifecycle
hooks: [a hook is a method name, not a registration](#a-page-lifecycle-hook-is-a-method-name-not-a-registration).

Filament's own three-branch method is `static::getResource()::hasPage(…)` all the
way down; ours goes through `getResourceInstance()` for the same reason every
other hop does. An action only reaches the hook when it is **stamped with the
live component** — see [header actions](#header-actions-are-bound-lazily-rather-than-at-boot)
for where the page does that, and note that a table record action needs nothing,
since `BelongsToLiveComponent` already falls through the table to its host.

`getResourceInstance()` is **new API**, with no Filament counterpart to preserve:
Filament writes `static::getResource()::getModel()` — a static call on a
class-string — at 32 sites in `Resources/Pages/Page.php` alone and 61 across
`Resources/Pages/`, and a service-backed resource cannot be reached that way. The
name follows schemas'
`getRecordInstance()` precedent (class-string in, instance out) rather than
inventing a third convention. `getResource()` keeps Filament's name and return
type, so a migrating user's `static::getResource()` still answers the
class-string.

The last two are the breadcrumb pair, and they are what puts a trail above every
resource page. `protected static ?string $breadcrumb` with
`getBreadcrumb(): string|Markup` is Filament's default verbatim
(`static::$breadcrumb ?? $this->getTitle()`), and `getBreadcrumbs()` overrides
the [base page's](#page) empty map with the trail Filament builds. The three
concrete pages narrow `getBreadcrumb()` to `string` and answer
`List` / `Create` / `Edit` — v5's `list-records.breadcrumb`,
`create-record.breadcrumb` and `edit-record.breadcrumb` lang values as literals,
the same call
[the notification titles](#the-create-and-save-notification-titles-are-literals-not-translation-keys)
made.

The return type is **widened** to `string|Markup` where Filament types the same
method `?string`, and the widening is a fix rather than a preference:
`getTitle()` already returns `string|Markup`, so Filament's own default chain
can hand a `Htmlable` title into a `?string` return — which only survives there
because its code is not under `strict_types`, and which would stringify the
markup and then let the template escape it, breaking the
`Htmlable` → `Markup` escaping contract this port holds throughout. The
narrowed overrides on the three concrete pages keep the common case a plain
`string`.

M6 · 3 filled that trail in. `getBreadcrumbs()` is now
`[...getResourceBreadcrumbs(), $this->getBreadcrumb()]`, and
`getResourceBreadcrumbs()` — with `hasResourceBreadcrumbs()` beside it, Filament's
pair — walks the page's **already-resolved** ancestor chain root-first,
contributing each ancestor's own listing and then the record itself, and ending
with this resource's listing. It costs no queries beyond the chain resolution the
page did anyway. A resource's listing crumb reads
[`Resource::getBreadcrumb()`](#resources) rather than its plural label directly,
so the resource-level override finally has a reader, and each record crumb links
to that resource's `view` page, else its `edit` page, else nothing —
[Filament's ladder with the authorization arms flattened](interacts-with-record.md#the-crumb-links-through-an-ungated-ladder).
Two helpers carry it: `appendBreadcrumb()`, which emits a crumb whose URL the
trail already spends as an int-keyed (unlinked) entry
[rather than letting a repeated key collapse two crumbs into one](nested-resources.md#a-crumb-whose-url-the-trail-already-spends-renders-unlinked),
and `getRecordBreadcrumbUrl()`, the view → edit → null ladder itself. A **record**
page inserts its own record crumb between the two through
[`InteractsWithRecord`](interacts-with-record.md#the-record-crumb-lands-on-plain-resources-too-and-that-is-new-behaviour),
which is new behaviour for plain resources as well as nested ones: a titled
resource's edit page trails three crumbs where it trailed two.

A plain resource page therefore still trails
`Articles → Create`; a titled record page trails `Articles → Alpha → Edit`; and a
nested one trails the whole chain,
`Articles → Genesis → Sections → In the beginning → Edit`.

One `LiveProp` arrived with M6 · 3 and moved up to
[`Pages\Page`](#page) with M6 · 2: `public ?string $panelId`, minted by its own
getter's dehydration read and stamped back onto the request by a
`#[PostHydrate(priority: 20)]` hook, so a live request — which reaches ux's route
and therefore carries no `_sylament_panel` attribute — resolves the panel the
page was **opened in** rather than the default one. It is the
[`GlobalSearch`](global-search.md#the-component-restores-its-panel-onto-a-live-request)
mechanism ported one tier down, and it closes
[the recorded multi-panel URL defect](#url-generation-inside-a-live-action-resolves-the-default-panel)
for every panel page. Nesting is what forced it: on a non-default panel every
live re-render of a nested page threw `RouteNotFoundException` for the default
panel's route name, so the prop was a prerequisite rather than an improvement —
and [the priority is what makes it beat actions' own hydration hook](custom-pages.md#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism).

Two divergences hang off this base:

- [`route()` returns a registration rather than registering a route](#route-returns-a-registration-rather-than-registering-a-route)
  — the call shape is Filament's, but a Symfony loader must *return* a
  `RouteCollection`, so nothing is registered until the loader reads the pair.
- [A resource page inherits page-route statics that answer for the wrong route](#a-resource-page-inherits-page-route-statics-that-answer-for-the-wrong-route)
  — every static describing a panel-mounted page is inherited and wrong here.

Deferred, all todo: `getResourcePageName()` (Filament's reverse lookup of a
page's own `getPages()` key —
[this epic deliberately routed around it](has-sub-navigation.md#a-resource-page-item-is-built-by-the-band-because-a-page-cannot-name-its-own-route)
rather than forcing the parked decision), the authorization concern, and
`ViewRecord` / `ManageRecords`. Three of that list came off it with M6 · 3:
sub-navigation ([the position override and the band's item seam](has-sub-navigation.md)),
the parent-record concern ([`InteractsWithParentRecord`](nested-resources.md), on
this base exactly as Filament composes its own), and
[`ManageRelatedRecords`](manage-related-records.md).

### ListRecords

The resource's index page: a live component that hosts a table the resource
configures. The whole class is a composition plus nine members: `$template`,
`table()`, `getTitle()` and `schemas()` below, `getBreadcrumb()` — narrowed to
a plain `List` off the [base](#resource-pages) — and the four the modal record
actions add, `form()`, `infolist()`, `getDefaultActionSchemaResolver()` and
`getMountedActionSchemaRecord()`.

```php
abstract class ListRecords extends Page implements InteractsWithTable
{
    use HasTable { HasTable::makeTable as makeBaseTable; }   // Sylament\Tables\Concerns
    use HasTabs;                                             // Sylament\Panels\Resources\Concerns
}
```

One host contract declared here, three composed in total: actions'
`InteractsWithActions`/`HasActions` **and** schemas'
`InteractsWithSchemas`/`HasSchemas` sit on the plain [`Pages\Page`](#page) base
since M6 · 2 and are inherited, and re-`use`ing either here would not merely be
redundant — the actions one would shadow the resource base's
`getDefaultActionUrl()` with the trait's null default, and the schemas one would
shadow the page base's empty `schemas()` with the trait's `['schema']` and
[500 on the first dehydrate](custom-pages.md#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf).
There is no `DefaultActionTrait` either: the panel [`Page`](#page) base already
carries it, and re-`use`ing a trait an ancestor already uses is legal but would
imply the base does not. The `makeTable` alias is the one place this hierarchy
does alias, so the override can call the original. The three host traits share no member name (every `HasTable` member is
`*Table*`-prefixed, actions' are `*Action*`-prefixed, schemas' are
`*Schema*`-prefixed plus `fill()`/`oldState`), so the composition needs no
`insteadof` and no aliasing — the one place Filament does alias
(`makeTable as makeBaseTable`) exists to let its `makeTable()` override call the
original, which our resource delegation does not need. The cost is twelve public
`LiveProp`s on the wire, all inherited: `tableHostId`, `tablePage`,
`tableRecordsPerPage`, `tableSort`, `tableSearch`, `tableFilters`,
`tableDeferredFilters`, `mountedActions`, `mountedActionsData`, `actionsHostId`,
`oldState`, `validatedSchemas`.

`ListRecords` was also the **first shipped class in the repo to use any of those
three host traits** — every other host is a test fixture or a demo component.
That matters beyond bookkeeping: PHPStan skips a trait no analysed class uses
(the globally-ignored `trait.unused`), so all three bodies were unanalysed until
this class existed, and giving them a user turns on both their standalone and
their per-using-class analysis at once. Whatever that surfaces belongs to the
trait's own package, not here.

- `$template` points at
  [`@SylamentPanels/partials/Page/table.html.twig`](../../../packages/panels/templates/partials/Page/table.html.twig)
  and is **required**: `Page::getTemplate()` otherwise falls back to the base
  chrome, whose `content` block renders `renderSchema('content')` — empty on a
  list page — so the page would render a heading with no table and no error
  anywhere. Since M6 · 2 that variant `{% extends %}` the base chrome and
  overrides one block rather than repeating the
  [three-element page skeleton](#routing), so the rhythm rules are inherited
  rather than restated.
- `table(Table $table): Table` stamps the resource's query and its record
  labels and title attribute, then hands the table to the resource —
  [query first, resource last](#the-list-pages-table-is-stamped-query-first-and-resource-last).
  Since M6 · 3 the query has a second arm: a list page hanging under a parent
  record is seeded with
  [that parent's relationship query](nested-resources.md#the-nested-list-page-ships-unexercised-by-any-shipped-page)
  instead, rooted at the same `record` alias, so nothing else in the stamp
  changes. `mount(array $parentRecords = [])` is declared for it — nothing here
  resolves at mount, but a page that scopes its own query should not depend on
  that staying true.
- `getTitle()` is `static::$title ?? $resource->getTitleCasePluralRecordLabel()`,
  Filament verbatim with the static hop replaced by the instance one. It runs
  **before** the component mounts as well: [`PageController`](#routing) reads it
  off the locator instance for the document `<title>`, so it must not depend on
  mounted state — the plural label does not.
- `schemas()` is **no longer declared here**, and the deletion is load-bearing
  rather than tidy. It used to return `[]` on this page because
  `HasSchemas::schemas()` defaults to `['schema']` and `getCachedSchemas()` then
  demands a `schema()` / `schemaSchema()` method the list page does not have, so
  the first `#[PreDehydrate] persistOldState()` threw `RuntimeException` and the
  page 500'd on its very first render. Since M6 · 2 the **base** answers `[]`
  ([why the discovery list is empty there](custom-pages.md#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf)),
  so the override is redundant — and keeping the trait alongside it would have
  been worse than redundant, since a trait outranks an inherited class. The
  schema host is here only so
  [`getTableFiltersForm()`](../tables/has-table.md) has somewhere to live; the
  tables fixture `SchemaTableHost` and the demo's `FiltersTableCard` still carry
  the override for the same reason, being hosts of their own rather than pages.

**Modal record actions.** A resource that registers only an `index` page still
gets every prebuilt record action — that *is* what Filament calls a simple
resource, the absence of registered pages rather than a feature. The URL hook
answers null, the action mounts, and the schema inside the modal comes from
`getDefaultActionSchemaResolver(Action)`, Filament's three arms minus their grid
default: a `CreateAction` or `EditAction` takes `$this->form($schema)`, a
`ViewAction` `$this->infolist($this->form($schema))`, each over the seeded schema
untouched — which leaves the create/edit arm a bare `$this->form(...)` — so
[the resource form's own column count survives into the modal](#a-modals-schema-keeps-the-resource-forms-own-column-count). `form(Schema)` and `infolist(Schema)`
are public delegations to the resource that exist **for that resolver** and are
deliberately not added to `schemas()`, which stays `[]`: that list is the
de/hydration walk, and a modal's schema is built per mount by
[the actions host](../actions/has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation),
never persisted with the page. `getMountedActionSchemaRecord()` answers the
resource's record **class**, which is what a page showing no single record can
seed a mounted schema with — the row actions get their record from the table
frame instead.

`getDefaultActionRecordTitle(Action)` answers a mounted action's title for the
actions the page itself owns. A **row** action no longer reaches it: `table()`
pushes the resource's `recordLabel()`, `pluralRecordLabel()` and
`recordTitleAttribute()` onto the table — Filament's `Resource::configureTable()`
— and `Action::getRecordTitle()` hops through the table before the host hook
([semantics](../actions/action.md#a-table-bound-action-titles-and-labels-through-its-table)),
so a `ViewAction` on a row reads *"View Alpha"* through the table's own title
read. The hook still serves the page's header actions, which carry no table: when
the action holds an object record and the resource declares a
`$recordTitleAttribute` it returns `Resource::getRecordTitle($record)`, otherwise
`null`, handing `Action::getRecordTitle()` back its record-label fallback. The
edit page's override is the sibling shape and reads its *own* record instead,
since a record page has one.

**Header actions.** `getHeaderActions()` returns the actions the template renders
into the `Header` component's `actions` block — the same contract on all three
page types, since the member is the [base's](#resource-pages). Declare them any
way that reads well: a `<name>Action()` method on the page, or an action built
inline in the list. Nothing is bound or registered by hand — the base's
`getCachedHeaderActions()` is what the templates read, and it stamps and
registers the list on the way through, once per request
([how, and why the walk is lazy](#header-actions-are-bound-lazily-rather-than-at-boot)):

```php
public function purgeAction(): Action
{
    return Action::make('purge')->requiresConfirmation()->action(…);
}

public function getHeaderActions(): array
{
    return [CreateAction::make(), $this->purgeAction()];
}
```

Nothing puts a Create button there by default, and that is parity, not a gap:
Filament has no framework-level one either — its `make:filament-resource`
generator writes `CreateAction::make()` into the generated page body. We ship no
generator, so until one exists the button is something a user (or the demo)
writes by hand — and the line above is what that costs.

**The template.** `partials/Page/table.html.twig` is eleven lines: it extends
the base chrome and fills `{% block content %}` with the tabs strip and
`{{ renderTable() }}`. Everything else is the chrome's — the page root, the
`sy-pa-page-header-main-ctn` wrapper holding the `Header` with the header
actions in its `actions` block above `sy-pa-page-main` > `sy-pa-page-content`,
and `<twig:SylamentActions:Modals />` last, outside the wrapper — so the
three-class [page skeleton](#routing) is inherited rather than repeated, and the
root classes come from
[`getPageClasses()`](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class)
(`sy-pa-resource-list-records-page` plus the slug-derived
`sy-pa-resource-<slug>` M6 · 2 added, Filament's `fi-resource-<slug>`). The
variant
[presumes a tabs host and a table host](form-actions.md#the-table-variant-presumes-two-host-traits-and-says-so-only-by-failing).
Two placement rules survive the move, both invisible until they break. `renderTable()` takes no
arguments and reads `this` out of the Twig context, requiring it to be an `InteractsWithTable` —
a page template has `this`, but a `<twig:>` component nested inside one has its
*own*, so a table wrapped in another component must be captured with
`{% set table %}…{% endset %}` where `this` is still the page (the demo's
`TableLiveCard` carries that note) — and a template that `{% extends %}` the
chrome is still the page's own frame, so `this` is the page inside an overridden
block. And the `Modals` outlet must sit inside the page's own template **chain**,
because it resolves its host by walking ux-twig-component's `ComponentStack` —
this is exactly why [the document does not carry one](#routing), and why the base
chrome placing it once means
[a child template must never add a second](../actions/has-actions.md#there-is-no-single-render-guard).
Both files are `partials/`, not `components/`: they are rendered from PHP through
`template_from_method`, and a `<twig:>` invocation would find no `this`.

Three of that list's seven came off it with M4 · 3. **Tabs** ship as
[`HasTabs`](has-tabs.md) — `$activeTab`, `getTabs()`, `getCachedTabs()`,
`getTabsSchema()` and `modifyQueryWithActiveTab()` — the strip rendered above
`renderTable()` from the same `{% if tabs is not empty %}` guard all three
tab-bearing partials carry. **Query-string binding** ships as
[`getTableUrlMapping()`](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page),
overridden here to map six of the seven names Filament binds — `page`
(identifier-prefixed), `sort`, `search`, `filters`, `tab` and, since M4 · 6,
[`grouping`](../tables/grouping.md#a-bookmarked-grouping-beats-a-selectable-default-group).
The one left, `reordering`, is
[deliberately unbound](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page).
Filament's own seven are **six `#[Url]`
attributes plus `page`**, which rides Livewire's pagination binding rather than
that attribute — one hook answers for all six here, which is the whole of the
divergence. And **row-click defaulting** ships as the
`recordAction()` / `recordUrl()` seeding in `makeTable()`, which
[resource pages answer where no action does](#the-row-click-falls-through-to-the-resources-own-view-or-edit-page).

Still deferred, all todo: the deprecated `getTableQuery()` / `modelLabel()`
seeding, `content()` and its `EmbeddedTable`, and the `ManageRecords` subclass.
The parent-record scoping arm came off the list with M6 · 3, and the page partial
gained [the sub-navigation band](has-sub-navigation.md) with it.

Three divergences are the list page's:
[the table is stamped query-first and resource-last](#the-list-pages-table-is-stamped-query-first-and-resource-last),
[header actions are bound lazily](#header-actions-are-bound-lazily-rather-than-at-boot)
and [a modal's schema keeps the resource form's own column count](#a-modals-schema-keeps-the-resource-forms-own-column-count).

### CreateRecord

The resource's create page: a live component hosting the resource's `form()`
schema, bound to the record **class** rather than an instance, that persists and
redirects.

```php
abstract class CreateRecord extends Page
{
    use HasUnsavedDataChangesAlert;   // Sylament\Panels\Pages\Concerns
}
```

Both hosts come from the plain [`Pages\Page`](#page) base since M6 · 2, so the
one trait this page composes of its own is
[the unsaved-changes marker](unsaved-changes-alert.md).
Exactly one `LiveProp` of its own — `$data`, writable, the state bag every field
binds into — and **none** for the record: `protected ?object $record` is a plain
property, set by `create()` and read by `getRedirectUrl()` a few lines later,
which is its whole life. `#[Required] setRecordPersistence(EntityManagerInterface)`
is the persistence hop Filament does not need, because an Eloquent model saves
itself; it is a setter rather than a constructor argument so a subclass declaring
its own constructor never repeats it, and `handleRecordCreation()` opens with the
same container-registration `LogicException`
[`getResourceInstance()`](#resource-pages) throws. `EditRecord` declares that
setter again rather than sharing a trait — two classes, and Filament keeps its
record handlers per page too.

- `mount(array $parentRecords = [])` assigns the ancestor keys first — the
  [chain is a mount argument everywhere](nested-resources.md#the-chain-rides-one-liveprop-of-keys-assigned-inside-mount)
  — then calls `fillForm()`: `callHook('beforeFill')`,
  `$this->getSchema('form')->fill()` and `callHook('afterFill')`. `fillForm()` is
  Filament's own method name, kept as a method of its own — mirroring
  [`EditRecord`'s](#editrecord) — so a page that mutates or hooks on fill has one
  place to reach. The fill takes no argument, so `Schema::fill(null)` hydrates
  with `applyDefaults: true` and every component's `default()` lands; there is no
  fill mutator here, because Filament has none on its create page either.
- `form(Schema)` stamps `statePath('data')`, `record($resource->getRecordClass())`
  — the **class-string** arm of `BelongsToRecord`, which defers `new $class()` to
  `getRecordInstance()` at save time — and `operation(Operation::Create->value)`,
  then hands the schema to `$resource->form()`. The schema is named `form`, so
  `HasSchemas::getSchema('form')` resolves this very method and the template says
  `renderSchema('form')`.
- `schemas()` returns `['form']`, replacing the trait's `['schema']` default: the
  de/hydration hooks walk exactly this list, and naming a schema that has no
  method throws on the first render.
- `getMountedActionSchemaRecord()` answers the resource's record **class**, so a
  header action's modal is seeded with the class rather than the record this page
  has not created yet — redundantly, since the seed chain reaches
  `getDefaultActionRecordClass()` first and never asks
  ([why it is kept](#the-mounted-schemas-record-answers-for-the-top-frame-and-depends-on-the-seeds-laziness)).
- `create()` is the `#[LiveAction]` the form's submit posts.

The lifecycle, ours against Filament's:

| Step | Filament `create()` | Ours |
| --- | --- | --- |
| re-entry guard | `$this->isCreating` | — (no SPA history restore to guard) |
| authorization | `authorizeAccess()` | — deferred with the auth cluster |
| transaction | `beginDatabaseTransaction()` | — deferred |
| validate | `callHook('beforeValidate')` → `$this->form->getState()` → `callHook('afterValidate')` | the same three steps, over `$this->getSchema('form')` |
| mutate | `mutateFormDataBeforeCreate($data)` | same, an identity default a page overrides |
| write | `callHook('beforeCreate')` → `handleRecordCreation($data)` → `saveRelationships()` → `callHook('afterCreate')` | `callHook('beforeCreate')` → `handleRecordCreation($schema->getData($data))` → `callHook('afterCreate')` — `persist()` + `flush()`, no relationship pass |
| events | `RecordCreated`, `RecordSaved` | — no event layer |
| remember | `rememberData()` | same, through [`HasUnsavedDataChangesAlert`](../../../packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) |
| notify | `getCreatedNotification()?->send()` | same |
| create another | the `$another` branch | — deferred |
| redirect | `$this->redirect($this->getRedirectUrl())` | `return new RedirectResponse($this->getRedirectUrl())` |

Every step from `beforeValidate` to the write runs inside a
`try { … } catch (Halt) { return null; }`, so a lifecycle hook calling
[`halt()`](custom-pages.md#halts-rollback-flag-is-carried-and-ignored) stops the
ladder where it threw — no mutator, no write, no notification and no redirect.
Before M6 · 2 a `Halt` from a hook escaped the action and answered 500.

Three things about that table are load-bearing. **Validation is the `getState()`
step and mapping is the `getData($data)` step**, and the split is what makes the
mutator possible: `getState()` opens with `validate()`, so a failing submit
throws `ValidationException` (a 422 `UnprocessableEntityHttpException`) before
the mutator, the write hook or anything else runs — there is no `try`/`catch`,
because ux-live-component's `LiveComponentSubscriber` re-renders the mutated
component for exactly that status and the client morphs the errors in — and
`Schema::getData($data)` then maps that already-validated, already-mutated array
onto a fresh record. Handing `getData()` the array rather than letting it read
state back is [the schemas seam](../schemas/belongs-to-record.md) this epic
added for exactly this. **`handleRecordCreation()` takes the record, not an array** — the Filament
signature is `(array $data): Model` because Eloquent mass-assigns, while our
`getData()` already returns the mapped object, so the hook's job shrinks to
persisting; the name and the override role are kept. And **the notification is
sent before the redirect**, so it lands in the flash bag and the
`SylamentNotifications:Notifications` outlet in the [document](#routing) drains it
on the page the browser lands on.

`getRedirectUrl()` is the `edit` page when the resource has one, else the index —
Filament's chain with the panel-level `getResourceCreatePageRedirect()` setting,
the `view` arm and the two `can*()` guards all absent with their features. Both
arms spread the page's `parentRecords`, so a nested create lands on the nested
edit page rather than raising a missing-parameter exception.

`handleRecordCreation()` gained the parent arm with M6 · 3: with a parent record
in hand it calls
[`Resource::attachRecordToParent()`](nested-resources.md#the-attach-lands-on-the-resource-and-writes-the-owning-side-in-memory)
before the `persist()`/`flush()` pair, so the new record joins the parent's
association in the same flush — an association the mapping refuses to leave empty
therefore fails the form rather than the write. A **modal** create on a nested
page reaches the same place from the other side: the resource-page base answers
`getDefaultActionRelationship()` with the parent relationship for a
`CreateAction`, which is the hook
[`CreateAction`](../actions/create-action.md) already consulted.

**The template.** `$template` points at the shared
[form variant](form-actions.md), `partials/Page/form.html.twig`, which fills the
chrome's `content` block with a `<form class="sy-pa-form">` bound by
`live_action(this.getSubmitFormLiveComponentMethodName ~ ':prevent', event: 'submit')`,
the record-level error outlet, `renderSchema('form')` and the
[form actions row](form-actions.md#the-row-is-never-rendered-standalone-it-hangs-under-a-named-page-schema).
The page root, the [three-class page skeleton](#routing), the `Header` and the
`<twig:SylamentActions:Modals />` outlet are the chrome's, and the root classes
come from `getPageClasses()` (`sy-pa-resource-create-record-page` plus the
slug-derived `sy-pa-resource-<slug>`). The **record-level error outlet is
required, not decorative**: a class-level violation that names no field (a
`UniqueEntity` on the entity) is translated onto the schema's own state path,
which belongs to no input, so without
`this.getErrorsForSchemaStatePath('data')` it renders nowhere and the submit
appears to do nothing. The demo hand-rolled this block for the same reason; this
is the shipped version of it, in one file instead of three, wrapped in a
`form_errors` block a page can replace.

Deferred, all todo: page-level create-another (`$canCreateAnother`,
`createAnother()`, `preserveFormDataWhenCreatingAnother()` — the
[`CreateAction` modal](../actions/create-action.md) has its own, this is the
page's submit-and-stay button), database
transactions (`CanUseDatabaseTransactions`, which
[`halt()`'s carried rollback flag waits on](custom-pages.md#halts-rollback-flag-is-carried-and-ignored)),
`previousUrl`, the record events, the
authorization arms, and `content()` and its `Form`/`EmbeddedSchema`
wrapper. The parent-record association came off that list with M6 · 3, and the
partial gained [the sub-navigation band](has-sub-navigation.md); M6 · 2 took the
partial itself off it, replacing it with the shared
[form variant](form-actions.md) and declaring the Create and Cancel buttons as
real `Action`s — the cancel one
[linking to the listing rather than going back in history](form-actions.md#the-cancel-action-links-to-the-index-where-filament-goes-back-in-history).

Three divergences are shared with the edit page:
[the notification titles are literals](#the-create-and-save-notification-titles-are-literals-not-translation-keys),
[the schema is named `form`, not `schema`](#the-record-pages-schema-is-named-form-and-schemas-is-narrowed-to-it),
and [URL generation inside a live action resolves the default panel](#url-generation-inside-a-live-action-resolves-the-default-panel).

### EditRecord

The resource's edit page: the same form schema bound to a resolved record, saved
in place.

```php
abstract class EditRecord extends Page
{
    use HasRelationManagers;          // Sylament\Panels\Resources\Pages\Concerns
    use HasUnsavedDataChangesAlert;   // Sylament\Panels\Pages\Concerns
    use InteractsWithRecord;          // Sylament\Panels\Resources\Pages\Concerns
}
```

Two `LiveProp`s: `$data` here, and `public ?string $record` —
**the route key, never the record** — on
[`InteractsWithRecord`](interacts-with-record.md), which M6 · 3 extracted out of
this class unchanged so `ManageRelatedRecords` (and `ViewRecord` at M5 · 2) could
compose the same six members. Both halves of the record prop are constrained by
the wire, and each has its own divergence:
[the record prop carries the key and dehydrates through a bridge](#the-record-prop-carries-the-route-key-and-needs-a-dehydration-bridge).
`protected ?object $resolvedRecord` memoises the resolution for the request.
`#[Required] setRecordPersistence()` is the create page's, verbatim.

What moved to the trait, verbatim: the `$record` prop and `dehydrateRecord()`,
`hasRecord()`, `getRecordKey()`, `getRecord()` and `getRecordTitle()`. What the
trait adds beyond them is this page's too — the record's own breadcrumb and the
`getSubNavigation()` / `getSubNavigationParameters()` bridge to
[the band](has-sub-navigation.md). The extraction landed with **zero test
edits**, which is the whole evidence that it preserved behaviour.

- `mount(int|string $record, array $parentRecords = [])` delegates to the trait's
  `mountRecord()` — the parent keys assigned first, then the record key —
  and calls
  `fillForm()`: `callHook('beforeFill')`, a fill from
  `mutateFormDataBeforeFill($schema->getStateFromRecord($record))`, and
  `callHook('afterFill')`. `fillForm()` is Filament's own method name, kept as a
  method of its own so a page that mutates or hooks on fill has one place to
  reach — it had a second caller until the page's own edit modal became
  [the page's own form](#the-edit-pages-own-edit-modal-is-the-page-form-itself).
  The union is on the **mount parameter**, which is Filament's
  signature and is legal there; the property underneath it cannot be one. Route
  parameters arrive as strings ([`componentProps`](#routing)), so the cast is a
  formality for a hand-mounted component.
- `getRecord(): object` resolves through
  `Resource::resolveRecordRouteBinding($key, $this->getParentRecordScope())` and
  memoises, throwing
  `NotFoundHttpException` for a missing key and for a key that matches nothing.
  That is the whole 404 story: `PageController`
  [unwraps an `HttpExceptionInterface` out of Twig's render error](#a-mount-time-http-exception-is-unwrapped-from-twigs-render-error),
  so the throw from `mount()` answers 404 rather than 500. Filament's
  `InteractsWithRecord::getRecord()` does `abort_unless(… instanceof Model, 404)`
  for the same effect.
- `form(Schema)` is the create page's with `record($this->getRecord())` — the
  **instance** arm — and `Operation::Edit`.
- `content(Schema)` holds the one component
  [`HasRelationManagers`](has-relation-managers.md) builds out of the resource's
  `getRelations()`, and `schemas()` is `['form', 'content']`. The template renders
  it with a single `renderSchema('content')` after `</form>`, which is
  [a deliberately narrow reading of Filament's `content()`](has-relation-managers.md#content-ships-in-a-narrow-shape).
  Naming it in `schemas()` also means it is rebuilt on **every** live request of
  the page — [the tab statics are on that path](has-relation-managers.md#the-content-schema-is-built-on-every-live-request).
- `hasRecord()` and `getRecordKey()` answer off the raw `$record` prop, which is
  what an embedded relation manager is mounted with —
  [the record probes read the wire value](has-relation-managers.md#the-record-probes-read-the-wire-value-not-the-resolved-record).
- `save()` is the `#[LiveAction]`.

The lifecycle, ours against Filament's:

| Step | Filament `save()` | Ours |
| --- | --- | --- |
| authorization | `authorizeAccess()` | — deferred |
| transaction | `beginDatabaseTransaction()` | — deferred |
| validate | `callHook('beforeValidate')` → `$this->form->getState(afterValidate: …)` | same, and the callback is Filament's pair: `callHook('afterValidate')` then `callHook('beforeSave')` |
| mutate | `mutateFormDataBeforeSave($data)` | same — **after** `beforeSave`, which fired inside the state read |
| write | `handleRecordUpdate($record, $data)` → `callHook('afterSave')` | `handleRecordUpdate($schema->getData($data))` → `callHook('afterSave')` — `persist()` + `flush()` |
| events | `RecordUpdated`, `RecordSaved` | — no event layer |
| remember | `rememberData()` | same, through [`HasUnsavedDataChangesAlert`](../../../packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) |
| notify | `getSavedNotification()?->send()` when `$shouldSendSavedNotification` | `getSavedNotification()?->send()` |
| redirect | `redirect($url)` when `$shouldRedirect` and `getRedirectUrl()` is filled | `getRedirectUrl()` is `null`, so `save()` returns `null` and the page stays |

The ladder is wrapped in the create page's `catch (Halt)` exactly as described
there, and the `EditHookedArticle` fixture's `$haltsBeforeSave` arm is what pins
it: the hooks that ran are `beforeValidate`, `afterValidate`, `beforeSave`, and
the row is untouched.

`getData($data)` is where the state reaches the record, and it **mutates before
it can refuse**: a record-level violation
(`UniqueEntity`) is raised after the state has been written onto the managed
entity, so the unit of work holds a dirty entity that is never flushed. Nothing
else in the request may flush, which is why `handleRecordUpdate()` is the only
write and why the demo's `testInvalidEditSubmitLeavesTheRowUntouched` is the
carried pin for it.

Staying on the page is expressed as
[a `null` redirect rather than Filament's exception-as-predicate](#staying-on-the-page-after-a-save-is-a-null-redirect-not-a-caught-authorization-exception).
The toast then arrives on the same round trip: notifications'
`LiveDeliverySubscriber` drains the flash bag during the live re-render and
dispatches `sylament:notification` as a browser event, which the document
outlet's controller shows.

`getRecordTitle()` is the trait's, and it is Filament's plus one guard: it
answers the resource's title-case record label when `$this->record` is null,
because `getTitle()` is read off an **un-mounted** page for the document
`<title>`. That guard is not a nicety —
[without it every edit-page GET 404s](#the-document-title-of-an-edit-page-carries-the-record-label-not-the-record-title),
and the visible consequence is that the browser tab says `Edit Article` while the
page heading says `Edit Alpha`.

**The action host hooks.** The edit page is the resource page that answers the
record family, and every answer is guarded on `$this->record` being filled —
`PageController` reads a page's title, and through it these hooks' neighbours,
off an **un-mounted** instance, so an unguarded `getRecord()` would 404 every
edit-page GET.

- `getDefaultActionRecord()` is the page's record for every action **except a
  `CreateAction`**, which gets `null` —
  [a create modal on a record page is anonymous](#a-create-modal-on-a-record-page-is-anonymous).
- `getDefaultActionRecordTitle()` is the already-guarded `getRecordTitle()`, so a
  mounted action's modal heading names the record rather than the record type.
- `getDefaultActionSuccessRedirectUrl()` answers the resource's index URL for a
  `DeleteAction` and `null` for everything else — spreading the page's
  `parentRecords` into it, so a delete on a nested page lands on the parent's
  page rather than on a URL missing an ancestor — the one non-null body of the
  hook anywhere in Filament's panels (it lives on the record-page concern edit
  and view share, and `ManageRelatedRecords` only *narrows* it, returning null
  for a table-context action), and what sends a header delete back to the list
  instead of re-rendering a page whose record is gone. Filament's `match` carries
  a `ForceDeleteAction` arm too, absent here with soft deletes. The redirect ends
  the request before any re-render, which is also why Filament's other use of
  `afterActionCalled()` on that concern — nulling `$this->record` after a delete
  so Livewire does not try to dehydrate a row that no longer exists — is recorded
  rather than ported: there is no re-render left to protect. Its one caveat is inherited
  rather than new: the URL is generated inside a `#[LiveAction]`, so
  [the default panel answers](#url-generation-inside-a-live-action-resolves-the-default-panel)
  — right for every single-panel application, wrong for a resource mounted only
  in a non-default panel, and one more instance against
  [M6 · 6](../roadmap.md#m6--panel-depth)'s multi-panel URL row.
- `getDefaultActionSchemaResolver()` is the list page's create and view arms over
  the **resource's** `form()`, and an edit arm that is not: an `EditAction` on
  this page resolves to `$schema->components([EmbeddedSchema::make('form')])`,
  which is Filament's own body and mounts
  [the page's own form rather than a second tree](#the-edit-pages-own-edit-modal-is-the-page-form-itself).
- `getMountedActionSchemaRecord()` is the resource's record class for a
  `CreateAction` and the guarded record otherwise —
  [it answers for the top frame, depends on the seed being lazy, and is
  unreachable on every page shipped today](#the-mounted-schemas-record-answers-for-the-top-frame-and-depends-on-the-seeds-laziness).
- `afterActionCalled()` is **not** overridden. It re-filled the page form after a
  modal edit while the modal was a second tree over the same record; with one
  tree there is nothing to re-fill and a re-fill would discard what the user just
  typed. Filament's own use of the hook here — nulling the record after a delete —
  stays unported for the reason above: the redirect ends the request first.

**The template.** `partials/EditRecord/index.html.twig` is now seven lines: it
`{% extends %}` the shared [form variant](form-actions.md) and overrides one
block, `after_form`, with `{{ renderSchema('content') }}` — where the relation
managers render. Everything the create page has, it inherits: the same `<form>`
bound through `getSubmitFormLiveComponentMethodName()` (`save` here), the same
required record-level error outlet, the same actions row, the same chrome, the
same `Modals` outlet and the same
[sub-navigation band](has-sub-navigation.md#an-empty-band-never-resolves-a-panel),
with the root class family (`sy-pa-resource-edit-record-page`, the slug and
`sy-pa-resource-record-<key>`) coming from `getPageClasses()`. The managers
render **after** the form element rather than inside it, because a manager hosts
forms of its own and a nested `<form>` is invalid HTML the browser silently
unnests — which is what the `after_form` block exists for.

Deferred, all todo: `refreshFormData()` (it needs a partial fill),
`saveFormComponentOnly()`,
database transactions, `previousUrl`,
the record events, the authorization arms, the combined content tab that
puts the form itself in the relation-manager tab bar
([HasRelationManagers](has-relation-managers.md#the-combined-content-tab-does-not-port)),
and the `getNavigationIcon()` /
`getNavigationLabel()` overrides Filament's edit page carries **for**
sub-navigation — which now have a consumer, so the gap is visible: an edit page
in a band is labelled by
[the inherited chain](#page) (`$navigationLabel`, then `$title`, then the
humanized class basename, i.e. *Edit Article* for an `EditArticle` page) where
Filament labels it *Edit* and gives it a pencil-square glyph. A page declaring
the two statics gets Filament's look today; porting the overrides is what makes
it the default.

Divergences, the three shared with the create page plus six of its own:
[the notification titles are literals](#the-create-and-save-notification-titles-are-literals-not-translation-keys),
[the schema is named `form`](#the-record-pages-schema-is-named-form-and-schemas-is-narrowed-to-it),
[URL generation inside a live action resolves the default panel](#url-generation-inside-a-live-action-resolves-the-default-panel),
[the record prop carries the route key and needs a dehydration bridge](#the-record-prop-carries-the-route-key-and-needs-a-dehydration-bridge),
[staying on the page is a `null` redirect](#staying-on-the-page-after-a-save-is-a-null-redirect-not-a-caught-authorization-exception),
[the document title carries the record label](#the-document-title-of-an-edit-page-carries-the-record-label-not-the-record-title),
[a create modal on it is anonymous](#a-create-modal-on-a-record-page-is-anonymous),
and [a modal's schema keeps the resource form's own column count](#a-modals-schema-keeps-the-resource-forms-own-column-count).
Its own edit modal is no longer one of them —
[it is the page's own form](#the-edit-pages-own-edit-modal-is-the-page-form-itself),
and the relation managers under it are
[HasRelationManagers](has-relation-managers.md)'.

## Features

Cross-cutting concerns and traits — this package has no top-level `Concerns/`;
they sit per subsystem:
[`src/Panel/Concerns`](../../../vendor/filament/filament/src/Panel/Concerns),
[`src/Pages/Concerns`](../../../vendor/filament/filament/src/Pages/Concerns),
[`src/Resources/Concerns`](../../../vendor/filament/filament/src/Resources/Concerns),
[`src/Navigation/Concerns`](../../../vendor/filament/filament/src/Navigation/Concerns).

| Feature | Ports | Status | Detail |
| --- | --- | --- | --- |
| [`DependencyInjection\Compiler\PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php) | [`Panel/Concerns/HasComponents.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasComponents.php) — `registerLivewireComponents()` / `queueLivewireComponentForRegistration()` | in progress | every `Page` service is autoconfigured `sylament.panels.page`; the pass throws for a page class missing the bare `#[AsLiveComponent]` UX reflects for, clears the attribute-derived `twig.component` tag and re-adds a live one keyed by `Page::getComponentName()`, rendering through `template_from_method: getTemplate`; it also builds the class-keyed `ServiceLocator` `PageController` reads pages from — [the attribute and the pass, and why both](#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute) |
| [`DependencyInjection\Compiler\RelationManagerComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/RelationManagerComponentPass.php) | [`Panel/Concerns/HasComponents.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasComponents.php) — the same Livewire component registration, which Filament reaches for relation managers through a resource's `getRelations()` | in progress | every `RelationManager` service is autoconfigured `sylament.panels.relation_manager`, and the pass is `PageComponentPass` minus the controller locator — [the attribute, the tag swap, and why a manager needs both](relation-manager.md#a-manager-must-carry-aslivecomponent-and-the-pass-enforces-it) |
| [`Resources\Pages\Concerns\HasRelationManagers`](../../../packages/panels/src/Resources/Pages/Concerns/HasRelationManagers.php) (+ [`Resource::getRelations()`](../../../packages/panels/src/Resources/Resource.php) and [`EditRecord::content()`](../../../packages/panels/src/Resources/Pages/EditRecord.php)) | [`Resources/Pages/Concerns/HasRelationManagers.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/HasRelationManagers.php), [`Resources/Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php) `getRelations()` | in progress | the registrations a record page mounts, filtered by the record, and the one component that carries them — a tab bar, a bare embed, a group's embeds, or a hidden group — [HasRelationManagers](has-relation-managers.md) |
| [`Resources\Pages\Concerns\InteractsWithRecord`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithRecord.php) | [`Resources/Pages/Concerns/InteractsWithRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithRecord.php) | in progress | the record a page is opened on — the key on the wire, the scoped lazy resolution, the title, the record crumb and the sub-navigation bridge, extracted out of `EditRecord` for the second and third record pages — [InteractsWithRecord](interacts-with-record.md) |
| [`Resource\Concerns\BelongsToParent`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) (+ [`Resources\ParentResourceRegistration`](../../../packages/panels/src/Resources/ParentResourceRegistration.php), [`Pages\Concerns\InteractsWithParentRecord`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php)) | [`Resources/Resource/Concerns/BelongsToParent.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToParent.php), [`ParentResourceRegistration.php`](../../../vendor/filament/filament/src/Resources/ParentResourceRegistration.php), [`Resources/Pages/Concerns/InteractsWithParentRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) | in progress | a resource mounted under its parent's records: the derived relationship, the nested routes, the scoped bindings, the attach and the two suppressions — [nested-resources.md](nested-resources.md) |
| [`Resources\Concerns\InteractsWithRelationshipTable`](../../../packages/panels/src/Resources/Concerns/InteractsWithRelationshipTable.php) | [`Resources/Concerns/InteractsWithRelationshipTable.php`](../../../vendor/filament/filament/src/Resources/Concerns/InteractsWithRelationshipTable.php) | in progress | the tier both relationship-table hosts compose — the association, the title, and the schemas, labels and page URLs a `$relatedResource` answers with — [interacts-with-relationship-table.md](interacts-with-relationship-table.md) |
| [`Pages\Concerns\InteractsWithHeaderActions`](../../../packages/panels/src/Pages/Concerns/InteractsWithHeaderActions.php) | [`Pages/Concerns/InteractsWithHeaderActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php) | in progress | the header list every page template renders, bound and registered by a lazy walk, plus its alignment and the placement default a bare group takes — [custom-pages.md](custom-pages.md), [the walk itself](#header-actions-are-bound-lazily-rather-than-at-boot) |
| [`Pages\Concerns\InteractsWithFormActions`](../../../packages/panels/src/Pages/Concerns/InteractsWithFormActions.php) | [`Pages/Concerns/InteractsWithFormActions.php`](../../../vendor/filament/filament/src/Pages/Concerns/InteractsWithFormActions.php) — the name; the shape comes from [`CreateRecord`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) and [`EditRecord`](../../../vendor/filament/filament/src/Resources/Pages/EditRecord.php) | in progress | the button row below a page's form: the declaration hook, the bound list, the one keyed `Actions` component and the named `formActions` schema it hangs under — [form-actions.md](form-actions.md) |
| [`Pages\Concerns\HasMaxWidth`](../../../packages/panels/src/Pages/Concerns/HasMaxWidth.php) + [`HasTopbar`](../../../packages/panels/src/Pages/Concerns/HasTopbar.php) | [`Pages/Concerns/HasMaxWidth.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasMaxWidth.php), [`HasTopbar.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasTopbar.php) | in progress | the two [`SimplePage`](simple-page.md)-only knobs — the card's own width, and whether the shell draws its top strip; the width trait shares its name with a [component-tier twin](../schemas/README.md) that is still todo |
| [`Pages\Concerns\HasUnsavedDataChangesAlert`](../../../packages/panels/src/Pages/Concerns/HasUnsavedDataChangesAlert.php) (+ [`Panel\Concerns\HasUnsavedChangesAlerts`](../../../packages/panels/src/Panel/Concerns/HasUnsavedChangesAlerts.php)) | [`Pages/Concerns/HasUnsavedDataChangesAlert.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasUnsavedDataChangesAlert.php), [`Panel/Concerns/HasUnsavedChangesAlerts.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasUnsavedChangesAlerts.php) | in progress | the saved-state marker, the panel switch and the fourth Stimulus controller that raises the browser's leave-site prompt off three client signals — [unsaved-changes-alert.md](unsaved-changes-alert.md) |
| [`Panel\Concerns\HasMaxContentWidth`](../../../packages/panels/src/Panel/Concerns/HasMaxContentWidth.php) | [`Panel/Concerns/HasMaxContentWidth.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasMaxContentWidth.php) | in progress | the two panel-wide width defaults the controller's ladder ends at, over support's [`Width`](../support/README.md) enum — [simple-page.md](simple-page.md#the-width-resolution-is-one-ladder-in-the-controller) |
| [`Pages\Concerns\HasSubNavigation`](../../../packages/panels/src/Pages/Concerns/HasSubNavigation.php) (+ [`Pages\Enums\SubNavigationPosition`](../../../packages/panels/src/Pages/Enums/SubNavigationPosition.php), [`Panel\Concerns\HasSubNavigation`](../../../packages/panels/src/Panel/Concerns/HasSubNavigation.php)) | [`Pages/Concerns/HasSubNavigation.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasSubNavigation.php), [`Pages/Enums/SubNavigationPosition.php`](../../../vendor/filament/filament/src/Pages/Enums/SubNavigationPosition.php) | in progress | the band of sibling pages, its normalization, its three positions and the four templates that draw it — [has-sub-navigation.md](has-sub-navigation.md) |
| [`DependencyInjection\Compiler\ResourcePass`](../../../packages/panels/src/DependencyInjection/Compiler/ResourcePass.php) | no Filament counterpart — resource discovery is [`Panel/Concerns/HasComponents.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasComponents.php) `discoverResources()`, a filesystem scan | in progress | every `Resource` service is autoconfigured `sylament.panels.resource` and the pass collects the non-abstract ones into the class-keyed `ServiceLocator` `ResourceRegistry` reads — [Resources](#resources) |
| [`DependencyInjection\Compiler\GlobalSearchProviderPass`](../../../packages/panels/src/DependencyInjection/Compiler/GlobalSearchProviderPass.php) | no Filament counterpart — a provider class-string is handed straight to `app()` | in progress | the same three-piece shape one feature over: every `GlobalSearchProvider` service is autoconfigured `sylament.panels.global_search_provider` and the pass collects the tagged definitions into the class-keyed `ServiceLocator` `GlobalSearchProviderRegistry` reads — [global-search.md](global-search.md#the-provider-is-resolved-through-a-tagged-registry-rather-than-app) |
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
| [Layout templates](../../../packages/panels/templates/components) | [`resources/views/components`](../../../vendor/filament/filament/resources/views/components) | in progress | [Layout templates](#layout-templates) |
| [`Twig\PanelsIconAlias`](../../../packages/panels/src/Twig/PanelsIconAlias.php) | [`View/PanelsIconAlias.php`](../../../vendor/filament/filament/src/View/PanelsIconAlias.php) | in progress | ten constants: nine of Filament's 32 — the sidebar group chevron, the topbar hamburger, the three theme-switcher glyphs ([Layout templates](#layout-templates)), the dashboard's navigation icon ([Dashboard](#dashboard)), the global search field's magnifying glass ([global-search.md](global-search.md)), and the two this epic added, the sub-navigation mobile-menu chevron ([has-sub-navigation.md](has-sub-navigation.md)) and the manage-related-records navigation item ([manage-related-records.md](manage-related-records.md#the-navigation-label-and-icon-drop-their-related-resource-arms)) — plus [the user-menu trigger, which is an invention](#user_menu_trigger-is-an-addition-not-a-rename) |
| [Assets (JS/CSS)](../../../packages/panels/assets) | [`resources/js/`](../../../vendor/filament/filament/resources/js), [`resources/css/`](../../../vendor/filament/filament/resources/css) | in progress | the `@sylament/panels` workspace ships four Stimulus controllers (`sidebar`, `theme`, `global-search`, `unsaved-changes`) and the `sy-pa-*` rules, which compile into support's stylesheet — [Assets](#assets); `sub-navigation.css` and `page.css`'s band modifiers are [has-sub-navigation.md](has-sub-navigation.md#the-top-modifier-is-emitted-and-styled-by-nothing) |
| [`Routing\PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php) | [`routes/web.php`](../../../vendor/filament/filament/routes/web.php) — the per-panel route group and its `$page::registerRoutes($panel)` / `$resource::registerRoutes($panel)` loops, [`Pages/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasRoutes.php) `routes()`, [`Resources/Resource/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) `registerRoutes()` | in progress | [Routing](#routing) |
| [`Controllers\PageController`](../../../packages/panels/src/Controllers/PageController.php) | no Filament counterpart — a Filament page **is** its own route action | in progress | [Routing](#routing) |
| [Document template](../../../packages/panels/templates/partials/Document/index.html.twig) | [`components/layout/base.blade.php`](../../../vendor/filament/filament/resources/views/components/layout/base.blade.php) | in progress | [Routing](#routing) |
| [Page template](../../../packages/panels/templates/partials/Page/index.html.twig) | [`components/page/index.blade.php`](../../../vendor/filament/filament/resources/views/components/page/index.blade.php) | in progress | [Routing](#routing) |
| [Page form variant](../../../packages/panels/templates/partials/Page/form.html.twig) | no Filament counterpart — v5's create and edit pages have no view of their own; [`CreateRecord::content()`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) and [`EditRecord::content()`](../../../vendor/filament/filament/src/Resources/Pages/EditRecord.php) return a schema holding a `Form` wrapping an `EmbeddedSchema` and the form `Actions` | in progress | [CreateRecord](#createrecord), [EditRecord](#editrecord) |
| [Page table variant](../../../packages/panels/templates/partials/Page/table.html.twig) | no Filament counterpart — v5's list and manage-related pages have no view either; [`ListRecords::content()`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) and [`ManageRelatedRecords::content()`](../../../vendor/filament/filament/src/Resources/Pages/ManageRelatedRecords.php) return a schema holding an [`EmbeddedTable`](../../../vendor/filament/schemas/src/Components/EmbeddedTable.php) | in progress | [ListRecords](#listrecords), [ManageRelatedRecords](manage-related-records.md#the-page-renders-a-template-not-a-content-schema) |
| [EditRecord template](../../../packages/panels/templates/partials/EditRecord/index.html.twig) | same — the one-block child of the form variant, overriding `after_form` to hold the relation managers below the form | in progress | [EditRecord](#editrecord) |
| [SimplePage template](../../../packages/panels/templates/partials/SimplePage/index.html.twig) | [`components/page/simple.blade.php`](../../../vendor/filament/filament/resources/views/components/page/simple.blade.php) | in progress | [Layout templates](#layout-templates) |
| [RelationManager template](../../../packages/panels/templates/partials/RelationManager/index.html.twig) | [`resources/views/resources/relation-manager.blade.php`](../../../vendor/filament/filament/resources/views/resources/relation-manager.blade.php) | in progress | [RelationManager](relation-manager.md#the-manager-renders-a-template-not-a-content-schema) |
| [SubNavigation templates](../../../packages/panels/templates/components/SubNavigation/index.html.twig) — the dispatcher plus the sidebar, tabs and mobile-menu components | [`components/page/sub-navigation/`](../../../vendor/filament/filament/resources/views/components/page/sub-navigation) | in progress | [has-sub-navigation.md](has-sub-navigation.md#three-sibling-components-not-one-with-variants) |
| [SimpleLayout template](../../../packages/panels/templates/components/SimpleLayout/index.html.twig) | [`components/layout/simple.blade.php`](../../../vendor/filament/filament/resources/views/components/layout/simple.blade.php) | in progress | [Layout templates](#layout-templates) |
| [SimpleHeader template](../../../packages/panels/templates/components/SimpleHeader/index.html.twig) | [`components/header/simple.blade.php`](../../../vendor/filament/filament/resources/views/components/header/simple.blade.php) | in progress | [Layout templates](#layout-templates) |
| [`SylamentPanelsBundle`](../../../packages/panels/src/SylamentPanelsBundle.php) | [`FilamentServiceProvider.php`](../../../vendor/filament/filament/src/FilamentServiceProvider.php) | in progress | autoconfigures the four tags (`sylament.panels.panel_provider` / `.page` / `.resource` / `.relation_manager`), registers `PageComponentPass`, `RelationManagerComponentPass` and `ResourcePass`, wires `Panel`, the two registries, `CurrentPanel`, `RelationGroup`, `PageController` and the `routing.route_loader`, and prepends `@sylament/panels` into AssetMapper — [providers are collected by tag](#panels-are-collected-from-tagged-providers-not-registered-imperatively), [pages register through a pass](#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute), [relation managers through their own](relation-manager.md#a-manager-must-carry-aslivecomponent-and-the-pass-enforces-it), [the group needs a definition of its own to reach the container](relation-group.md#the-two-constructor-arguments-arrive-through-setters), [resources from a tag](#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem) |
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

The chevron points **up** when the group is open and flips through `-rotate-180`
when it collapses, which is Filament's pairing (`Heroicon::ChevronUp` plus
`.fi-collapsed .fi-sidebar-group-collapse-btn { -rotate-180 }`). A 180° turn is
direction-symmetric, so the rule carries no `rtl:` arm — the `-rotate-90` it
replaced needed one, and drawing a down chevron rotated a quarter turn pointed
the closed state sideways where Filament points it down.

`Topbar` is the full-width bar above the layout — v5's DOM, where the topbar is
a sibling of `.fi-layout` rather than a child of the main column, so the sidebar
sits *under* it and the bar spans the viewport. It holds the drawer toggle, a
`sy-pa-topbar-start` carrying the `Logo`, and a `sy-pa-topbar-end` carrying an
`end` block a caller may fill and then the `UserMenu`. The end wrapper is
unconditional: it is where the shell's own chrome goes, so it renders whether or
not a caller fills the block.

`Logo` is the port of `logo.blade.php`'s three arms, rendered twice — once in
the topbar start (hidden below `lg`) and once in the sidebar's own mobile
header, which is where the brand appears while the drawer is open. A `Markup`
brand logo renders raw inside a `<div class="sy-pa-logo">`, a string one as an
`<img>` whose alt is the brand name plus ` logo` (Filament's
`layout.logo.alt` translation, hardcoded English while i18n is parked), and
with neither the brand name falls back as a `<span>` — which still renders
**nothing at all** when the panel has no brand name either, which is what
leaves a brandless mobile header an empty strip rather than a gap with a
heading in it. The `Markup`-vs-string branch is Blade's `$logo instanceof
Htmlable`, and porting it is what added the `markup` `TwigTest` to support's
`MarkupExtension` (`rendition.logo is markup`) beside the existing `filled`
test — the reusable Twig-side half of the `Htmlable` → `Markup` escaping
contract. The logo arms carry an inline `height:` style
(`brandLogoHeight() ?? '1.5rem'`, defaulted in the template as Filament does);
the text fallback carries none, matching the Blade. When
`darkModeBrandLogo()` is set both renditions render, the light one stamped
`sy-pa-logo-light` (`dark:hidden`) and the dark one `sy-pa-logo-dark`
(`hidden dark:flex`) — Filament's `fi-logo-light`/`fi-logo-dark` pair,
CSS-toggled so no JS is involved. The three settings thread as props down the
same `Document` → `Layout` → `Topbar`/`Sidebar` chain `brandName` already
rides, where Filament's blade reads the `filament()` facade in place.

`ThemeSwitcher` and `UserMenu` are the topbar-end pair. The switcher is a
`role="group"` of three buttons — light, dark, system — each carrying its
`data-theme` value, an `aria-pressed` the controller keeps in sync, and its own
overridable icon alias; the buttons are written out rather than looped, because
a computed `constant('…THEME_SWITCHER_' ~ theme|upper ~ '_BUTTON')` would be
invisible to Twig-CS-Fixer's `ValidConstantFunctionRule`, the one check in the
toolchain that validates a hardcoded FQCN string, and Filament unrolls the same
three too. Each button also fires `sylament--support--dropdown#close` beside the
theme action, so picking a theme dismisses the menu it sits in — Filament's
`(theme = …) && close()` in one attribute. Rendered outside a dropdown the extra
action is inert, Stimulus ignoring an action whose controller is on no ancestor.

`UserMenu` is a support `Dropdown` whose trigger is an `IconButton` and whose
panel today holds only the theme switcher, rendered
[in place rather than teleported](#the-theme-switchers-dropdown-is-not-teleported).
It is **a stub for the auth epic**, and every part of that is visible in the
markup: there is no user name, no
avatar, no menu items, no `getUserMenuItems()`, and none of Filament's four
gates — `auth()->check()`, `hasThemeSwitcher()`, `hasDarkModeForced()`,
`getDefaultThemeMode()` — so the menu renders unconditionally for an anonymous
visitor. Its trigger icon is [an alias with no Filament
counterpart](#user_menu_trigger-is-an-addition-not-a-rename).

The shell's default icons are overridable defaults, not hardcoded names: the
group chevron, the topbar hamburger, the three theme-switcher glyphs and the
user-menu trigger all read
[`PanelsIconAlias`](../../../packages/panels/src/Twig/PanelsIconAlias.php)
constants through `constant()` the way
[Modal's close button](../../../packages/support/templates/components/Modal/index.html.twig)
does, so an app swaps any of them under [`sylament.icons`](../support/icon-manager.md)
without touching a template. Every value drops Filament's `panels::` prefix
([rename](../renames.md)); the constant names are Filament's, except the
user-menu one, which Filament has no equivalent for.

`Layout`'s `<main>` always carries a width class beside `sy-pa-main`. The
controller resolves one value per request — a page's
`getLayoutData()['maxContentWidth']`, else its `getMaxContentWidth()`, else the
panel's (`Width::SevenExtraLarge` for the shell, `Width::Large` for a simple
page) — and threads it through the `Document` as `maxContentWidthClass`. A
`Width` case becomes `sy-width-<token>`; a string that is no token is emitted
verbatim, Filament's escape hatch for a consumer's own class. The ladder is 26
rules in `layout.css` nested under `.sy-pa-main, .sy-pa-simple-main`, and
`.sy-pa-main` carries no `max-w-*` of its own, so the class is the single
source. The `sy-width-*` name is shared with schemas' `sy-width-full`
deliberately: that rule is compound-scoped under `.sy-sc-actions-ctn` and can
never match a `<main>`. All 26 tokens compile under the installed Tailwind
4.3.0 — `@apply container` expands to a base rule plus its five breakpoint
rules, so the compiled sheet carries more `sy-width-` rules than there are
tokens. Which shell wraps the page is the page's own `getLayout()`, read by
`PageController`: only `SylamentPanels:Layout` and `SylamentPanels:SimpleLayout`
ship, and any other value is a `LogicException` naming the page — a third shell
is a `Document` template override, not a `getLayout()` value.

`SimpleLayout` and `SimpleHeader` are the second shell, the one a `SimplePage`
picks through `getLayout()`: a centred card on an empty ground, with no sidebar,
no topbar and no navigation at all. The layout carries the shell body classes
(`sy sy-pa-body sy-pa-simple-layout`), and under `hasTopbar()` a skip link and a
`sy-pa-simple-layout-header` strip pinned to the top end corner; the header
renders the panel's `Logo`, an `h1` and a `p`, each only when it has something
to show. Where Filament gates that strip twice on `auth()->check()` and fills it
with database notifications and a `SimpleUserMenu`, ours is gated on
`hasTopbar()` alone and holds the existing `UserMenu` — with auth parked, that
menu is the only theme affordance a page with no topbar would otherwise have,
and `SimpleUserMenu` is not ported. The page partial is a deliberate copy rather
than a child of the base one: the two share the unsaved-changes preamble and the
sentinel verbatim, but a simple page has no header band, no breadcrumbs and no
sub-navigation, so there is no common parent left to extend — a `{% extends %}`
would inherit a chrome whose every element the child would have to empty.

What Filament's shell has and this does not, all todo:

- Desktop collapse (the narrow icon-only rail) and the collapsed-state tooltips
  on items — both need panel-level sidebar settings. M2 · 2 introduced the only
  one a panel carries (`collapsibleNavigationGroups()`), which is not these.
- Sub-navigation: Filament renders `childItems` nested under an active item, and
  the nesting arm of `NavigationItem` is deferred whole (see
  [NavigationItem](#navigationitem)).
- The whole account half of the user menu, and the panel toggles that gate the
  topbar chrome — all of it lands with auth.
- `avatar` and `tenant-menu` from `resources/views/components`, neither ported.

The Livewire-shell → static-component divergence these templates sit on is
[The shell is not live](#the-shell-is-not-live) above. Five more are theirs:
[sidebar group ids are positional](#sidebar-group-ids-are-positional-not-derived-from-the-label),
[the drawer state class sits on the shell root](#the-drawer-state-class-sits-on-the-shell-root-not-on-the-sidebar),
[one toggle button replaces v5's shown/hidden pair](#one-toggle-button-replaces-v5s-shownhidden-pair),
[`USER_MENU_TRIGGER` is an addition](#user_menu_trigger-is-an-addition-not-a-rename),
and [the render tests sit under
`tests/Integration/Twig/`](#the-panels-render-tests-live-under-testsintegrationtwig).

### Assets

`packages/panels/assets` is the `@sylament/panels` pnpm workspace, JS-only in the
same shape as `@sylament/notifications`: four Stimulus controllers build to its own
`dist`, while `src/styles/index.css` is imported by support's stylesheet and
compiles into support's single built CSS. Since M6 · 2 it carries
`@symfony/ux-live-component` as a JS peer — in both `peerDependencies` and
`devDependencies`, the workspace being `strictPeerDependencies: true` — which
stays a bare specifier the consuming app's importmap resolves, exactly as in
`@sylament/tables` and `@sylament/schemas`. The built `dist` is mapped into
AssetMapper as `@sylament/panels`. The eleven style files carry the `sy-pa-*` rules
mobile-first — off-canvas drawer below `lg`, two-column grid at `lg` and up — and
the first eight are imported in **shell-nesting** order rather than alphabetically
(`layout`, `logo`, `sidebar`, `topbar`, `user-menu`, `theme-switcher`, `header`,
`page`), so the file reads outside-in the way the DOM nests. `sub-navigation`,
`global-search` and `relation-manager` are the exceptions: appended ninth, tenth
and eleventh rather than sitting at their nesting positions. Four of them are
worth naming:

- `layout.css` owns `.sy-pa-body`, the shell root, and the two custom properties
  the shell reads off it: `--sy-sidebar-width` (the drawer's width, a
  [runtime token](../../styling.md) like every other `--sy-*` name — not one of
  the deliberately unprefixed emission-set variables the grid system uses, which
  are unprefixed only because they are Filament's verbatim) and `--font-sans`. It
  also carries the 26-rung
  [`sy-width-*` ladder](simple-page.md#the-ladder-shares-one-class-vocabulary-with-schemas-and-cannot-collide)
  nested under `.sy-pa-main, .sy-pa-simple-main`, and the four `.sy-pa-simple-*`
  shell rules — both M6 · 2's.
- `page.css` is the [page skeleton's](#routing) three rules — `py-8 gap-y-8` on
  the wrapper, a flex column on `sy-pa-page-main`
  ([a divergence](#the-pages-main-container-is-a-flex-column-where-v5-gates-that-on-a-full-height-page)),
  and `grid flex-1 auto-cols-fr gap-y-8` on `sy-pa-page-content` — plus, since
  M6 · 2, `.sy-pa-form` (`flex flex-col gap-6`, Filament's `fi-sc-form`, which
  is what replaced the old actions-row `mt-6` and error-list `mb-6`),
  `.sy-pa-form-errors` (the record-level error list, reading the `danger`
  palette the way forms' per-field errors do) and
  `.sy-pa-simple-page-content`. A twelfth stylesheet, `resources.css`, was
  **deleted** in the same change: its `.sy-pa-resource-form-errors` rule became
  `.sy-pa-form-errors` here and its `.sy-pa-resource-form-actions` wrapper is
  gone entirely, the row now being schemas' own `sy-sc-actions` — so
  `index.css` lost an import rather than gaining one.
- `header.css` is the page header's own rules, and M6 · 2 added three groups to
  it: the two-level actions container
  ([why the alignment rides the inner row](custom-pages.md#the-alignment-class-rides-the-inner-row-and-the-container-is-what-the-breadcrumb-offset-hangs-off)),
  its seven `sy-align-*` arms, and the three `.sy-pa-simple-header-*` rules.
- `relation-manager.css` is the [manager's](relation-manager.md) three rules:
  the column that stacks its header over its table, the header row, and the
  heading's own type — the chrome Filament's `Table::heading()` would otherwise
  carry.

The active sidebar item paints from the
[fixed `primary` palette](#the-active-sidebar-item-paints-a-fixed-primary-shade).

The third controller, `global-search`, arrived with
[the topbar search](global-search.md), and where it binds is load-bearing: it
rides the inner `.sy-pa-global-search` div rather than the live root above it,
because that root's attribute bag already carries `data-controller="live"` and
[`attrs()` replaces a repeated attribute rather than appending](global-search.md#the-stimulus-controller-rides-the-inner-element-not-the-live-root).
It is declared `fetch: eager` like the other two, and it shares their failure
mode: a consuming app that leaves the block out of `assets/controllers.json`
gets a field that renders and searches — `data-model` is what queries the
server — but no ⌘K binding, no Escape, no click-away and no arrow-key walk
through the results, with no error anywhere
([installation.md](../../installation.md)).

**The panel's font is Inter, and it is scoped.** `.sy-pa-body` sets
`font-(family-name:--sy-font)`, where `--sy-font` is a support-level token
defaulting to `'Inter Variable', ui-sans-serif, system-ui, sans-serif` — so the
face reaches every component inside the panel through inheritance, the scoped
preflight having dropped `font-*` from its `html` rule for exactly that reason
([styling.md](../../styling.md) rule 7), and nothing outside a panel is
touched. The same rule also redefines Tailwind's own **`--font-sans`** to
`var(--sy-font)` on that element: `sy-font-sans` compiles to `font-sans`, which
reads `--font-sans`, so without the scoped override a component asking for the
sans family inside a panel would opt *out* of the panel's face and back onto the
system stack. Filament closes the same gap by folding the panel font into
`--font-sans` in its theme; ours is that fold, scoped to the shell root instead
of the document.

The `sidebar` controller is declared `fetch: eager`, matching notifications: the
shell must apply stored collapse state on the first paint rather than after lazy
loading catches up. It owns the drawer open/close pair (`toggle`, `close`,
`closeOnEscape`), the per-group collapse toggle, and persistence under the
localStorage key `sylament.panels.sidebar.collapsedGroups` (a JSON array of group
labels read from `data-group-label`).

Opening the drawer dims the topbar along with the page. The overlay is `z-30`,
Filament's own value, and it comes after the topbar container in the DOM, so it
paints over it and the hamburger underneath stops being clickable — which is
also what v5 does, and what makes the close-on-click and close-on-Escape arms the
only ways out.

The `theme` controller is the second, also `fetch: eager`. It reads a stored
choice of `light` / `dark` / `system` under
[a namespaced key](#the-stored-theme-key-is-namespaced), toggles the `dark` class on
`document.documentElement`, keeps each switcher button's `aria-pressed` and
`sy-active` in sync, and — only while the stored choice is `system` — re-renders
when the OS preference changes. Two things it does not own. The `dark` **variant**
is support's (`@variant dark (&:where(.dark, .dark *))`), and so is
`:root.dark { color-scheme: dark }`, filed in support's `base.css` rather than
here: panels ships no base-layer stylesheet, the whole toolkit compiles into one
bundle either way, and the rule is correct on a non-panel page too. And the
**first paint** is the document's, not the controller's — see the head script in
[Routing](#routing), which has to run before the stylesheet or the page flashes
light before Stimulus boots.

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

The fourth controller, `unsaved-changes`, arrived with M6 · 2's page tier and is
the port of Filament's `unsaved-changes-alert.js`. It binds the sentinel div the
[page partial](#routing) emits rather than the live root above it — the same
constraint the `global-search` controller lives under, since
[`attrs()` replaces a repeated attribute rather than appending](global-search.md#the-stimulus-controller-rides-the-inner-element-not-the-live-root)
and a second `data-controller` there would take the live binding down. It reads
the host's state through `resolveLiveHost()` — `assets/src/utils/live_host.ts`,
the third byte-identical copy of the helper schemas and tables carry, because
schemas' `exports` map points at `src/`, which the split repo `export-ignore`s,
and panels' `BUNDLED_ENTRIES` is empty, so the util ships per-file beside the
controller and its logical path has to resolve on its own (which is what the
second case in `SylamentPanelsBundleTest` pins). It only ever **reads** —
`getData()`, `valueStore.getDirtyProps()` and the `mountedActions` prop — and
never `set()`s one: `savedDataHash` is not writable, so a write would surface as
a `HydrationException` on the next request rather than as a client-side error.

Two of its mechanics are counter-intuitive enough to name here, both written up
in [unsaved-changes-alert.md](unsaved-changes-alert.md). The `savedHash` value is
a **re-arm trigger, never the comparand** — nothing compares it to anything; a
morph re-stamps the sentinel's attribute in place without reconnecting the
controller, Stimulus fires `savedHashValueChanged()`, and the controller re-takes
its own client-side baseline
([why the hash cannot be recomputed in the browser](unsaved-changes-alert.md#no-hash-is-ever-computed-in-the-browser-so-the-prop-is-a-marker)).
And a **bfcache restore does not reconnect the controller**, so the baseline
taken before the page was frozen survives and the guard stays armed — navigating
away from a restored page with unsaved edits still prompts, which is the opposite
of what the epic's design note predicted and what the browser pass confirms
([the record](unsaved-changes-alert.md#a-bfcache-restore-keeps-the-guard-armed-rather-than-re-baselining)).

It is declared `fetch: eager` like the other three, and for a sharper reason
than they have: a guard that arms late is absent exactly when it matters. It
shares their failure mode too — a consuming app that leaves the block out of
`assets/controllers.json` navigates away from unsaved edits with no prompt and
no error anywhere ([installation.md](../../installation.md)); the page itself
still renders and still saves, so the omission is degraded, not broken.

What Filament's panel assets have and this does not, all todo:

- Desktop collapse (the icon-only rail) and its width/tooltip state — the
  controller tracks the mobile drawer only. M2 · 2 shipped the one sidebar
  setting a panel carries (`collapsibleNavigationGroups()`); the desktop-rail
  settings behind this are still unported.
- `scroll-sidebar.js` — restoring sidebar scroll position across navigations.
- `error-notifications.js` — the surface it serves exists (M2 · 3's
  [`CreateRecord`](#createrecord) / [`EditRecord`](#editrecord) forms, error
  outlet included), but the script is not ported, so a validation failure below
  the fold is silent. Its sibling `unsaved-changes-alert.js` **is** ported, as
  the `unsaved-changes` controller above, minus Filament's SPA arm: that one
  hangs off `Livewire.navigate`, which has no counterpart here.
- `echo.js` and the resource/page scripts — none of the surfaces they serve
  exist yet. `dark-mode.js` is the one that now has a counterpart: ours is the
  `theme` controller plus the document head script, and what is still missing is
  the panel-level configuration around it (`hasThemeSwitcher()`,
  `hasDarkModeForced()`, `getDefaultThemeMode()`), so the switcher is
  unconditional.

One fixture trap the mobile-drawer work left behind, recorded so the error is
recognisable rather than mysterious: panels' test fixture icon set carries the
single `chevron-left-20-solid` / `chevron-right-20-solid` pair (added with the
breadcrumb separators) but **not** the `chevron-double-*-20-solid` pair, and the
`ux_icons` `ignore_not_found` default is `false`. A panels test that ever renders
a **multi-page** table therefore throws `IconNotFoundException` on
[`Pagination`](../support/pagination.md)'s first/last buttons. The fix is one
`cp` from tables' fixture directory; the resource list page paginates to a single
page today, which is why nothing hits it.

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

A resource that [names a parent](nested-resources.md) mounts the same family one
level deeper: the loader walks the registration chain root-first, prefixing
`<parent slug>/{<parent parameter>}` per ancestor, so
`sylament.admin.resources.articles.sections.edit` sits at
`/admin/articles/{article}/sections/{record}/edit`. The route name is the
parent's plus the kebab-cased **relationship**, not the child's slug, and the
duplicate-name guard below is what keeps two children of one parent apart. Such a
route carries a fourth default, `_sylament_parent_params` — the ancestor
parameter names, root-first — which `PageController` reads to fold exactly those
route parameters into the page's one `parentRecords` prop, unsetting each so none
lands in the component's attribute bag. A flat route carries neither. The loader
refuses two shapes Filament builds silently: a chain naming a resource the panel
does not register, and a parent parameter colliding with `record` or with an
ancestor's.

Two things beyond mounting. Every page class file, every resource class file and
every provider class file is added to the collection as a `FileResource`: the
routes are derived from method bodies (`getSlug()`, `$routePath`, `getPages()`,
`panel()`) that Symfony's `ObjectLoader` cannot see — it tracks only the loader
class and its parents — so without them a dev-mode route cache would survive a
renamed slug. And an entry that is not the subclass it claims to be throws a
`LogicException` naming the class, at route-build time rather than at the request
that finally hits it: `pages()` entries against `Page`, `resources()` entries
against `Resource`, and a resource's own `getPages()` entries against `Page`.
M6 · 2 added a third refusal in the same shape:
[every `{placeholder}` in a mounted path must reach the page](custom-pages.md#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not)
— through a parent key the loader folds itself, a `mount()` parameter, or a
public property — else ux paints the leftover onto the live root as a stray HTML
attribute and nothing reports it.

Nothing is silently overwritten, either. `RouteCollection::add()` unsets a name
before assigning it and checks nothing, so two mounts deriving one route name
used to leave the later one standing and the earlier one unreachable — with no
error at route-build time, at request time or in `debug:router` — and resources
multiply the ways to collide (two pages sharing a `$slug`, two resources sharing
a slug, a resource page landing on a page's name). Every mount now goes through
one `addRoute()` that records the name's owner and throws a `LogicException`
naming both mounts when a second claims it. The compile-time
[component-name check](#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute)
does not cover this and cannot: it keys on the FQCN, which is exactly what
differs when two classes derive one slug.

The guard covers **names**, not **paths**. Two mounts that derive one path under
two different names both reach the collection, and the router answers one of them
— first-wins, silently, exactly the way the name collision used to behave. That
stays unguarded on purpose. A path collision is at least *visible*: both routes
are listed in `debug:router`, with their paths, where a name collision showed up
nowhere at all. And a useful path guard would have to reason about placeholder
equivalence (`/{record}/edit` and `/{id}/edit` are the same path) and about
matcher precedence between a static and a dynamic segment — router work, not
loader work, and duplicating it would only add a second opinion that can disagree
with the real matcher. Revisit if a real panel hits it.

Of the three name-collision *kinds* the guard covers, two are pinned
([`PanelRouteLoaderTest`](../../../packages/panels/tests/Unit/Routing/PanelRouteLoaderTest.php)
covers two pages sharing a slug and two resources sharing one). The third — a
resource page landing on a panel page's name — is deliberately left unpinned:
`addRoute()` is one method with one `$owners` map and no per-kind branch, so a
third case would exercise the same three lines a third time and pin nothing the
other two do not. It would only earn its keep if the owner tracking ever split
per kind.

[`PageController`](../../../packages/panels/src/Controllers/PageController.php)
is the single action every panel route points at. It resolves the panel through
`CurrentPanel`, resolves the page **service** from the class-keyed
`ServiceLocator`
[`PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php)
builds, and renders the Document template with `panel`, `pageTitle`, `layout`,
`maxContentWidthClass`, `hasTopbar`, `bodyAttributes`, `componentName` and
`componentProps` — the last six all read off the **un-mounted** locator instance,
like `getTitle()`, so none of them may depend on mounted state. Each failure mode
is its own `LogicException` naming the missing piece — a page that is not a
registered service names the registration to add, since a page reached through
`new` would have no dependencies injected, and a page naming a layout the toolkit
does not ship names both shipped ones
([why only two](custom-pages.md#a-third-shell-layout-is-a-document-override-not-a-getlayout-value)).

`componentProps` is the route → component bridge, and the rule is one line plus
the nested arm above: every matched route parameter (`_route_params`) whose name
does not start with `_`, after the ancestor parameters have been folded into
`parentRecords`. That drops the two `_sylament_*` defaults the loader writes and keeps
the user's own placeholders, so `/admin/articles/{record}/edit` hands the page
`{record: '12'}`. The document spreads it into `component()`, where
ux-twig-component matches each entry against the page's `mount()` parameters **by
name**, then against writable properties, and turns whatever is left into an HTML
attribute on the page's root element — so a placeholder no page mounts paints
itself into the markup rather than erroring, which is the one shape to watch for
and which [the loader now refuses at build time](custom-pages.md#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not).
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
has one), the theme head script, `sylamentStyles()`, the importmap, a
`<body>` carrying whatever
[`getExtraBodyAttributes()`](custom-pages.md#a-page-with-no-body-attributes-still-renders-a-stray-space)
answered, the shell component with the page's live component in its content block
(invoked by the tag name `Page::getComponentName()` returns, and given
`componentProps`), and the notifications outlet. Since M6 · 2 the shell is an
`{% if %}` over two literal `<twig:>` tags rather than one:
`SylamentPanels:SimpleLayout` when the page's
[`getLayout()`](custom-pages.md#a-third-shell-layout-is-a-document-override-not-a-getlayout-value)
named it, `SylamentPanels:Layout` otherwise, both handed the
`maxContentWidthClass` the controller
[resolved for this request](simple-page.md#the-width-resolution-is-one-ladder-in-the-controller).
The `component()` function is not used for the shell because a function call
cannot take a `<twig:block>`, which is how the page reaches it.

**The head script is the one inline `<script>` in the whole port**, and it is
there because nothing else can be. It reads `localStorage['sylament.theme']` and
stamps `dark` on `<html>` **before the stylesheet is requested**, which is the
only placement that avoids a light flash on every dark-mode page load; a
Stimulus controller connects far too late, and
[the shell is otherwise Turbo-indifferent](#the-shell-is-turbo-indifferent-and-worker-safe)
precisely to avoid inline scripts. It is not a second implementation of the
controller's rule but the same one, written twice and required to agree on all
six arms — explicit `dark` wins, explicit `light` wins, and *everything else* —
absent key, unreadable storage, unrecognised value — means system:

```js
theme === 'dark' || (theme !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)
```

Spelled as `(theme === null || theme === 'system') && matches` it would silently
disagree with the controller for an unrecognised stored value, leaving the page
light until Stimulus booted and then flipping it. The storage read is `try`-
guarded, so a browser refusing storage falls to the same system arm rather than
throwing before the stylesheet loads.
[`PageControllerTest`](../../../packages/panels/tests/Integration/Controllers/PageControllerTest.php)
pins both the body and its position ahead of the stylesheet block.

The notifications outlet is
`<twig:SylamentNotifications:Notifications />` as a direct `<body>` child outside
the layout — the position Filament's
`@livewire(Filament\Livewire\Notifications::class)` holds in `base.blade.php` —
and it is the document's job rather than the application's now that panels
declares `sylament/notifications`. It is also why a panel application needs
`framework.session` enabled: the outlet drains the flash bag on **every** page
render, and `RequestStack::getSession()` throws `SessionNotFoundException` on a
request that carries none — so a session-less panel fails on its first page, not
on its first toast. The **actions** outlet deliberately is not
here, and Filament agrees: `<x-filament-actions::modals />` sits in its *page*
view, not its layout. Ours has a second, harder reason —
[`Modals`](../actions/has-actions.md) resolves its host live component by walking
ux-twig-component's `ComponentStack`, so it only works rendered inside the
component whose actions it shows, which a document-level outlet is not.
`Page/index.html.twig` is what
`Page::getTemplate()` falls back to, and since M6 · 2 it is **the chrome every
other page template extends** rather than a skeleton each of them repeats. It
renders a `sy-pa-page` root — its classes coming from
[`getPageClasses()`](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class)
— then three nested elements: `sy-pa-page-header-main-ctn` wrapping the `Header`
(rendered when the page has header actions, breadcrumbs, a heading or a
subheading) above `sy-pa-page-main`, which holds `sy-pa-page-content`; the
`<twig:SylamentActions:Modals />` outlet follows the header-main container as the
root's child, and the
[unsaved-changes sentinel](unsaved-changes-alert.md#the-sentinel-is-an-inner-element-because-a-second-data-controller-would-take-the-live-binding-down)
follows that as the root's last child when the page or its panel arms the guard.

Three named blocks are the seam a page fills — `header`, `content` and `footer`,
with `content` defaulting to `{{ renderSchema('content') }}`; two variants beside
the chrome fill `content` for the shapes the toolkit ships
([`Page/form.html.twig`](form-actions.md) and `Page/table.html.twig`), and
`EditRecord/index.html.twig` is a seven-line child of the form one. The
[whole block contract](custom-pages.md#the-block-contract-is-the-page-bodys-public-api)
is `custom-pages.md`'s. The three classes are not decoration: they carry the
whole vertical rhythm, the wrapper supplying `py-8 gap-y-8` and
`sy-pa-page-content` the `gap-y-8` between whatever a page stacks inside it, so a
page that renders its own root and skips the layers loses its spacing with
nothing to see in the markup — which is now something a page has to go out of its
way to do, the chrome supplying them to every child.
`sy-pa-page-main` is a [divergence](#the-pages-main-container-is-a-flex-column-where-v5-gates-that-on-a-full-height-page).
All of them are `partials/`, not `components/` — they are rendered from PHP
(a controller and a `template_from_method` getter), never as a `<twig:>` tag, and
that is load-bearing rather than filing: `renderSchema()`, `renderTable()` and
`live_action()` read `this` out of the Twig context, which inside a `<twig:>`
component's block body is that component rather than the page.

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

### The theme switcher's pressed state is client-only, and survives only because the shell is outside the live root

`ThemeSwitcher` renders all three buttons with `aria-pressed="false"` and no
`sy-active`; the controller stamps the real state on `connect()` and on every
pick. The server never learns the choice — it is a `localStorage` value the
document head script and the controller both read — so the markup Twig produces
is deliberately *wrong* until JS runs, which is fine on a page load and would not
be fine inside a re-rendering fragment: ux-live-component morphs a component's
subtree from freshly rendered HTML, so a switcher inside a live root would have
its `aria-pressed` and `sy-active` reset on every round trip while the actual
theme, living on `<html>`, stayed put.

Panel pages are safe by construction and not by care: [the shell is not
live](#the-shell-is-not-live), and the [document](#routing) puts the page's live
component *inside* `Layout`'s content block, so the topbar — switcher included —
sits outside every live root on the page. The arrangement that would break it is
the mirror image: embedding `Layout` **inside** a live component, which is what
the demo's `PanelShellCard` does to show the shell as a showcase card. It works
there today because the card does not re-render; a card that did would watch its
switcher's aria state go stale.

### A mounted frame's record key resolves through the resource's own query

`HasActions::resolveMountedActionRecord()` turns a mounted frame's `recordKey`
context into a record, and the trait cannot do it alone: it ends at a
`resolveRecordUsing()` callback the host must register, throwing a `LogicException`
when a key arrives and none was. Every resource page overrides it in the class
body instead, and so no resource page ever registers that callback.

The override keeps both of the trait's **skip** guards verbatim, because they are
not about resolution at all — they are about which frames the host owns. A frame
carrying a `schemaComponent` belongs to a component inside a schema, which
resolves its own record; a frame carrying a `table` on a host that is an
`InteractsWithTable` belongs to the table, whose row already knows its record.
Both answer `null`, and dropping either would have the page resolve a key that
was never addressed to it.

What it replaces is the third guard, the `resolveRecordUsing()` throw. In its
place the key is checked for being scalar — a `recordKey` arrives off the wire, so
a non-scalar is a malformed request rather than a missing record, and it raises
`ActionNotResolvableException`. "Raises" is the accurate word rather than
"surfaces": `HasActions::cacheMountedActions()` catches that exception into an
empty stack, so a malformed key is a **silent non-mount** — the modal simply does
not open — where the `NotFoundHttpException` below propagates all the way out as
a 404. The two failures are deliberately asymmetric, one being a client sending
nonsense and the other a record that is really gone. The key is then handed to
`Resource::resolveRecordRouteBinding()`, with a `NotFoundHttpException` for a
miss — and, since that binding decodes before it binds, a key that is
well-formed JSON but malformed for the record class takes the same 404 with no
statement run, where it used to reach the database as a raw parameter.

Going through that method rather than `EntityManager::find()` is the whole point:
it is [the resource's scoped query](#a-record-is-resolved-through-the-resources-own-query),
so a row outside the resource's scope 404s instead of being mounted into a modal
by primary key. A row action on a scoped list is exactly where that would leak,
and it would leak silently.

What arrives in that context is the minting side's business, and the two sides
only agree where the mint knows the resource's rule. A **table**-bound action
carries the key the table itself minted, so a row action on a composite list
resolves against the same string the row rendered. Every other action reads the
literal `id` property — and, since M7 · 3, reads it
[only where `id` is the record's whole identity](../actions/action.md#getcontext-drops-the-record-model-gate).
Without that guard, an ordinary `EditAction::make()->record($this->getRecord())`
in a page header on a record keyed by `id` **and** a locale minted the partial
key `"5"`, which the strict decode then refuses — a 404 on click, from a trigger
whose record the page was already holding. The guard mints nothing there
instead, and the declared record stands, which is what a composite record with
no `id` field has always done. A resource declaring `$recordRouteKeyName` keeps
its own pre-existing mismatch: `['id']` **is** that record's identifier, so the
literal reading still fires and still names a key the resource does not use.

### A page lifecycle hook is a method name, not a registration

`callHook(string $hook)` is `method_exists($this, $hook)` and then a dynamic call,
which is the whole implementation and is Filament's. Nothing registers a hook and
nothing declares one: a page opts into `beforeSave` by *writing a method called*
`beforeSave`, and a page that writes none pays one `method_exists()` per site.

Two consequences are worth stating because the shape hides them. A **typo is
silent** — `beforeSve()` is simply never called, with no error at load or at run,
which is the cost Filament accepts for a zero-ceremony extension point and which
we accept with it. And the hooks are not an interface, so nothing on the base
declares them; the list of names is the contract, and it lives in the two page
sections above rather than in code. The sites and their order are Filament's
exactly: `beforeFill` / `afterFill` around a fill, `beforeValidate` /
`afterValidate` around the state read, `beforeCreate` / `afterCreate` around the
create write, and `beforeSave` / `afterSave` around the save — with `beforeSave`
firing *inside* the state read, before the mutator, which
[the edit page's lifecycle table](#editrecord) flags because normalising it would
be a divergence nobody would notice.

### The mounted schema's record answers for the top frame, and depends on the seed's laziness

`getMountedActionSchemaRecord()` takes no arguments. It is the last fallback in
[the host's seed chain](../actions/has-actions.md#the-per-mount-schema-is-seeded-with-the-actions-record-and-the-dotted-operation),
consulted only when the action, its schema container and its schema component all
answer nothing — and the chain runs **per frame**, while the hook is asked without
being told which frame is asking. Filament's is frame-agnostic in exactly the same
way, so the port keeps it, and the edit page's override reads `getMountedAction()`
with no index: the top frame. Under a nested modal that is the inner action, not
the one that opened it. Nothing today depends on the distinction, and recording it
is cheaper than re-deriving it the first time something does.

On every resource page the port ships today, that override is in fact **dead
code**. `resolveMountedActionSchemaRecord()` consults the host hook last, after
`$action->getRecordClass(withDefault: true)` — and `withDefault` reaches
`Page::getDefaultActionRecordClass()`, which always answers the resource's record
class. So the chain returns one hop early and never asks the page. All three
overrides (`ListRecords`', `EditRecord`'s, `CreateRecord`'s) are therefore
redundant rather than load-bearing, and they are kept on purpose: each answers
what the chain would have answered anyway, so an action that *does* opt out of the
default class — `record(null)`, a `schemaComponent`-owned frame, a future page
whose resource declares none — lands on the same value instead of `null`. Read
the sections below as *what the hook says*, not as behaviour a shipped page
exercises; nothing in the suite can distinguish them, which is why this paragraph
exists rather than a test.

The override is only *able* to read the mounted action because the seed's record
is a closure. `getMountedActionSchema()` builds the schema with
`->record(fn () => $this->resolveMountedActionSchemaRecord($mountedAction))` and
caches it; the closure runs later, when the schema is first asked for its record.
Were the seed eager, resolving it would call `getMountedActionSchemaRecord()`,
which calls `getMountedAction()`, which caches the whole mounted stack — from
inside the very call that is building and caching a frame's schema. The laziness
is what keeps that from re-entering, and it is why this override is safe where an
apparently equivalent eager one would not be.

### The edit page's own edit modal is the page form itself

`EditRecord::getDefaultActionSchemaResolver()` gives an `EditAction`
`fn (Schema $schema): Schema => $schema->components([EmbeddedSchema::make('form')])`
— Filament's own body, one line for one line. The modal renders the page's **own**
`form` schema: the same object the page beneath it is bound to, on the same state
path, with the same error bag. The arm waited on
[`EmbeddedSchema`](../schemas/embedded-schema.md), which M6 · 1 pulled forward to
mount relation managers and which this was the first consumer of.

What that retires is the port's one-epic divergence: the modal used to be a
**second component tree** over the same record — its own state path, its own
validation — which was correct for the modal and wrong for the page behind it,
still showing the values it was filled with at mount. `afterActionCalled()` closed
that gap by re-filling the page form through `fillForm()` whenever a modal edit of
the page's own record succeeded. Both are gone: one tree cannot drift from itself,
and a re-fill would now discard what the user just typed.

The cost, and it is Filament's too: **the modal's own submit writes nothing.** An
embed carries no state of its own, so the action's schema — bound to
`mountedActionsData.0` — is empty, and `EditAction`'s write maps that empty array
onto the record before reporting success. The typing is real, because it went
straight into the page's `data`; it is the page's own **Save** that persists it.
A header `EditAction` on an edit page is therefore a magnified view of the form
already on the page rather than a second way to save it, which is what Filament's
resolver makes it. `EditRecordTest` pins exactly that sequence, so the day the
behaviour changes it changes visibly.

The second cost, also Filament's: **while the modal is open, the page carries the
form's ids twice.** One schema rendered in two places emits one set of ids in two
places — on `EditArticle` with the modal mounted, the document holds
`id="form.title"` and `id="form.slug"` twice each, and `label[for="form.title"]`
twice. That is invalid HTML, `document.getElementById()` collides, and clicking
the modal's own label focuses the input *behind* the modal, because the browser
matches the first element in document order. It is new at this commit — while the
modal was a second tree on `mountedActionsData.0.*` the two sets of ids were
distinct — and it follows from what an embed is: the schema has one id path, not
one per place it renders. Closing it needs a render-time id scope on
`EmbeddedSchema`, which Filament has no counterpart for either, so it is recorded
rather than papered over — and "open the header Edit modal, then click a field
label" belongs on the browser checklist of any epic that touches this arm.

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

`sidebar.css` writes `text-primary-700 dark:text-primary-400` on the active
item's icon and label rather than reading a `--sy-text` slot; the button itself
carries no colour at all, only the `bg-gray-100 dark:bg-white/5` highlight. Both
halves are v5's own values
([`sidebar.css:157-179`](../../../vendor/filament/filament/resources/css/components/sidebar.css)),
so the divergence is not the shade but the *mechanism*.
[styling.md](../../styling.md)'s *never hardcode a shade for a colour a user can
set* rule (rule 6) governs the **per-instance** `color` palette, which is chosen
by a [contrast policy](../support/color-policies.md) because the palette is not
known until render. The sidebar has no per-instance colour API — neither
`NavigationItem` nor `NavigationGroup` composes `HasColor`, and there is nothing
to build a colour map from — so there is no policy to ask and no slot to read;
`primary` resolves through the `--sy-primary-*` runtime tokens, so a consumer
retheming the palette still gets their colour. It becomes a rule-6 case the day
the sidebar takes a colour, at which point the shade moves into a policy slot and
the icon/label pair reads the slot variables instead. The known residual risk
until then: a consumer registering an unusually light or dark `primary` gets a
fixed 700/400 against the fixed `gray-100` / `white/5` active background, which
no policy is measuring.

### The page's main container is a flex column where v5 gates that on a full-height page

`page.css` gives `.sy-pa-page-main` an unconditional `flex flex-col`. Filament
gives `.fi-page-main` **no base rule at all**: it becomes a flex column only
under `.fi-page-has-sub-navigation`, and gains `h-full` only under
`.fi-height-full`
([`page.css`](../../../vendor/filament/filament/resources/css/components/page.css)).
The consequence is one declaration further in, and it is the reason to record
this rather than treat it as a formatting difference: `.fi-page-content` carries
`flex-1`, which is **inert** in vendor's default page because its parent is not a
flex container — where ours is live, so a short page's content container grows to
fill the wrapper instead of hugging its rows. Nothing visible differs today,
because every page we ship fills its content anyway.

It is a deliberate simplification, not a mistake: gating a rule on a class
nothing emits would be dead CSS. **Half of that gate is now live.** M6 · 3 ships
[sub-navigation](has-sub-navigation.md), so `sy-pa-page-has-sub-navigation` is
emitted and carries the two rules the band needs — `gap-8`, and `md:flex-row
md:items-start` under the start/end modifiers — and the band behaves identically
to vendor's on both edges. What stays unconditional here is the base
`flex flex-col`, which vendor gates on the same class; that half, and the
`fullHeight` half beside it, revisit **at the `fullHeight` port**, where vendor's
own `h-full` cascade lands on the same three elements and the difference between
"always a column" and "a column when asked" starts to matter.

### The drawer state class sits on the shell root, not on the sidebar

Filament toggles `fi-sidebar-open` on the `<aside>` itself, from Alpine's
`$store.sidebar`, and the closed arm is a `&:not(.fi-sidebar-open)` on the same
element. Ours toggles `sy-pa-sidebar-open` on `.sy-pa-body` — the shell root —
and both the drawer and its overlay read it as a descendant selector. The
substitution is what lets **one** class drive two elements that are not nested in
each other: the overlay is a sibling of the sidebar, so v5 shows and hides it
with `x-show` and a transition directive on the element instead, which a static
template has no equivalent for. The cost is that the open state is only readable
from the root, so anything asking "is the drawer open?" from inside the sidebar
has to walk up; the Stimulus controller does exactly that by holding the root as
its controller element.

### One toggle button replaces v5's shown/hidden pair

Filament's topbar renders **two** icon buttons — a bars-3 opener under
`x-show="! $store.sidebar.isOpen"` and an x-mark closer under
`x-show="$store.sidebar.isOpen"`, each with its own icon alias
([`livewire/topbar.blade.php:17-46`](../../../vendor/filament/filament/resources/views/livewire/topbar.blade.php)).
Ours renders one `sy-pa-topbar-open-sidebar-btn` whose `toggle` action flips the
state and whose `aria-expanded` the controller keeps in sync. Two buttons swapped
by a reactive expression is an Alpine shape; a static template would have to ship
both and hide one with CSS, which buys a second glyph and a second alias for a
control that already announces its state to assistive technology. The visible
delta is that the open drawer's control still shows a hamburger rather than an
×, and that `TOPBAR_CLOSE_SIDEBAR_BUTTON` has no port — it returns if the pair
ever does.

### The stored theme key is namespaced

Filament persists the choice under the bare `localStorage['theme']`; ours uses
`sylament.theme`, the same namespacing the sidebar controller already applies to
`sylament.panels.sidebar.collapsedGroups`. A bare `theme` key is a collision
waiting to happen in a host application that has its own dark mode — and unlike
most collisions this one is invisible, since both sides would read a value that
parses. The cost is that a consumer migrating from Filament loses a visitor's
stored preference once, at the switch.

### The theme switcher's dropdown is not teleported

Filament's user-menu dropdown is teleported to the end of `<body>`; ours renders
in place, inside `.sy-pa-topbar-end`. That is [the same decision the dropdown
primitive already carries](../support/dropdown.md), and the topbar is the one
place it could plausibly clip, because `.sy-pa-topbar-ctn` sets
`overflow-x-clip`. It does not, and the distinction is
exactly why that property was chosen over `hidden`: `overflow-x: hidden` beside a
`visible` block axis promotes the block axis to `auto`, making the bar a scroll
container that clips downward, where `clip` leaves the other axis alone. So the
menu hangs below the bar untouched and only sideways overflow is trimmed —
verified on the rendered menu rather than inferred. Revisit if the topbar ever
gains a vertical clip of its own.

### `USER_MENU_TRIGGER` is an addition, not a rename

Every other value in [`PanelsIconAlias`](#layout-templates) is a Filament
constant with its `panels::` prefix dropped ([rename](../renames.md)). This one
has no Filament counterpart at all: v5's user-menu trigger is an
`<x-filament::avatar>`, an image, so there is no icon slot to alias and its
`USER_MENU_TOGGLE_BUTTON` names the *sidebar* variant's chevron instead. Ours
renders an `IconButton` holding `heroicons:user-circle` as a stand-in for the
unported Avatar, sized to the avatar's 32px by a `user-menu.css` rule rather than
the icon-button default's 20px, and the alias exists so an application can swap
that stand-in without waiting for the avatar. It is therefore surface a migrating
user cannot have been using, and it goes away — or becomes an avatar setting —
when [the auth epic](../roadmap.md) brings a real user to render.

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
the manager's per-request scratch lists to push *into* — two arrays seeded by
cloning the panel's own, which `Panel::navigationItems()` redirects into for as
long as a manager is set.

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
consulted by the panel-side loop, in Filament's own position, and so is
[`getParentResourceRegistration()`](nested-resources.md#the-suppressions-sit-where-the-trail-already-put-them)
since M6 · 3 — a nested resource is skipped there and nowhere else, so it still
answers `getNavigationItems()` when something asks it directly. `getCluster()`
guards an unported subsystem; `canAccess()` waits on authorization. Each of those lands in the loop when its subsystem does, not
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
[`PageComponentPass`](#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute)
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

### Pages register as live components through a compiler pass over a per-class attribute

Filament registers a panel's pages as Livewire components imperatively, from
`Panel::register()` through `registerLivewireComponents()` and
`queueLivewireComponentForRegistration()`. The Symfony translation is split in
two, and both halves are load-bearing: a **bare `#[AsLiveComponent]`** on every
concrete page class, and
[`PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php)
writing the registration over the top of it.

The pass alone was the original shape, and it was wrong. UX does not recognise a
live component by its container tag — it **reflects for the attribute**:
`LiveComponentStack::isLiveComponent()` is
`[] !== new \ReflectionClass($classname)->getAttributes(AsLiveComponent::class)`
(`vendor/symfony/ux-live-component/src/Util/LiveComponentStack.php:46-49`), and
that class is `final` with the method `private`, so there is no seam to teach it
about a tag. A tag-only page is therefore not a live *parent*: every live
component nested inside it is silently re-created on each re-render, with no
error anywhere. Relation managers are exactly that nesting, which is what forced
the attribute in. It is also the idiomatic answer (principles §2) — the
ecosystem already ships the marker for "this class is a live component", and
tagging without it was the deviation.

Class attributes do not inherit, and neither does the reflection check, so the
attribute cannot be hoisted onto the abstract `Page` base: every concrete page a
consumer writes repeats one line. Forgetting it used to fail badly and silently.
Now the pass throws at compile time, naming the class and the fix — the missing
marker is a container-build error rather than a live child that quietly loses its
state.

What the attribute does *not* do is register the component usefully. Attribute
autoconfiguration
(`LiveComponentExtension.php:94-102`) adds `twig.component` carrying
`AsLiveComponent::serviceConfig()` with the null and empty entries filtered — a
tag with **no `key`** and no template, which would leave `TwigComponentPass`
deriving a name from the FQCN against the *application's*
`twig_component.defaults` and throwing for a class outside them. So the pass
`clearTag('twig.component')` and re-adds the full eight-key payload (`key`,
`live`, `route`, `method`, `url_reference_type`, `expose_public_props`,
`attributes_var` and `template_from_method: 'getTemplate'`), plus
`controller.service_arguments`, which is harmlessly duplicated. The ordering that
makes the clear work is Symfony's own: `AttributeAutoconfigurationPass` and
`ResolveInstanceofConditionalsPass` run at beforeOptimization **priority 100**,
so by the time this pass runs at priority 10 the attribute-derived tag is already
materialized, and `TwigComponentPass` at priority 0 sees exactly one tag with an
explicit key. `template_from_method` is the payload the attribute's
`new FromMethod('getTemplate')` form produces, and it is the arm that matters: a
literal `template:` string is fixed at compile time, where `getTemplate()` lets
the base class supply the default and a subclass override `$template`.

Owning the tag rather than accepting the attribute's is what buys the two things
an attribute could not do. Two pages deriving the same component name throw at
compile time naming both services, where a duplicate attribute key would silently
leave one registration standing (`TwigComponentPass` has no duplicate guard on
the explicit-key path). And the pass builds the class-keyed `ServiceLocator`
`PageController` resolves pages through, so the same tag that makes a page
renderable makes it reachable.

The pass' shape — *clear the attribute-derived tag, re-add ours, throw without
the attribute* — is the contract every live-component pass in this package
follows.

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

The cost is an authoring-surface addition Filament does not have: one
`#[AsLiveComponent]` line per concrete page class, which a consumer coming from
Filament has no reason to expect. It buys the only live-parent recognition UX
offers, and the throw means it is never *silently* missing.

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
`Panel $panel` parameter is explicit rather than defaulted from a facade,
matching [`Page::getRouteName()`](#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade)
(the `Panel|string|null` widening is [`getUrl()`'s](#geturl-drops-two-parameters-and-widens-a-third),
where a caller may hold only an id).

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
its own alias still works, and the predicate is
[`IdentifierConstraint::equals()`](../schemas/record-key.md#the-operations-and-their-dql)
rather than one hand-written `alias.field = :sylament_record_key`, so an
identifier of any width narrows the same query in the same place. The class it
keys against is the **declared** `getRecordClass()`, never the builder's root
entity: a modifier may replace the builder wholesale, and the resource still
speaks for the class it says it does. And the decode runs **before** anything is
bound, so a key that class could never mint — the wrong arity, a lower-case
escape, a component its field's type cannot hold — is a 404 that issues no
statement rather than a database error.
`getRecordRouteKey()` is the generation side
of the same pair, replacing what Laravel got free from `UrlRoutable`; both sides
read `$recordRouteKeyName`, so they cannot disagree. It reads `getRecordClass()`
first, for the "speaks for" guard, so a resource declaring `$recordRouteKeyName`
without `$recordClass` throws the record-class `LogicException` rather than a key
one — fail-fast ordering, and deliberate: the missing declaration is the fix.

Filament's second parameter shipped with the cluster it belongs to.
`resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null)` takes a
query modifier, and the only closure Filament ever passes it is the parent-record
scope — so it was deferred until
[nested resources](nested-resources.md#the-parent-scope-is-the-readers-relationship-query-not-a-predicate-we-write)
arrived and now carries exactly that one caller, plus the mounted-action key
resolution the same scope guards. Two facts about the modifier are ours rather
than Filament's: it is handed the cloned query and keys against **what it
returns**, so a scope may replace the builder wholesale (the reader's
relationship query does), and a closure returning anything but a `QueryBuilder`
or null throws rather than being ignored — a `Closure` carries no return type,
so this is the only place the wrong shape is detectable.

### A custom `$recordRouteKeyName` names one column, and the codec never sees it

`$recordRouteKeyName` is Filament's own static and it keeps Filament's own
meaning: the URL segment is **one property** of the record, read through
schemas' `PropertyAccessor` on the way out and bound as one untyped parameter
on the way back. The key codec is consulted on neither side, and three
consequences follow that the default arm no longer has.

The bind is **untyped**, so a non-numeric segment against a numeric column is a
`DriverException` — a 500, because `PageController` converts only an
`HttpExceptionInterface` — where the default arm answers a 404 without
querying. The named column need not be **unique** — one field out of a
several-field identifier never is — so `getOneOrNullResult()` raises
`NonUniqueResultException`, a 500 for the same reason; the default arm cannot
reach that exception at any identifier width. And the value reaches the
`sy-pa-resource-record-<key>` class token raw, so a property holding whitespace
[splits that token in two](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class),
which the grammar makes impossible for a **composite** key.

None of the three is new and none is composite-only — a dotted or unmapped name
is a fourth, a `QueryException` 500, and a name that is an **association** a
fifth, which binds and resolves and then mints nothing, the property read
answering an entity that *"is not a printable value."* — and they are recorded
here because the
default arm stopped sharing them, which is what makes them addressable. The
arm is left byte-identical on purpose: a user who declares the static has told
us which column they mean, and the closing version of it — convert the value
through the field's own DBAL type and compare it back to what the client sent —
needs an addition to the frozen key contract, which is a later epic's to make.
Typing the bind alone was executed and is worse than the gap: it trades the 500
for `5abc` silently resolving record 5.

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

One deliberate improvement over Filament sits in the same guard: a scalar is
narrowed to `is_scalar($title) ? (string) $title : null` and then tested with
`filled()`, where Filament tests nothing. Filament returns whatever the attribute
holds, so a record whose title column is `''` — or `false`, or whitespace —
titles its page with an empty string, and the heading that reads `Edit ` looks
like a rendering bug rather than missing data. The extra test routes exactly
those to the record label, which is the value the method already answers for a
null record and an unreadable attribute; the same hole exists upstream, and
closing it costs nothing a caller could want. Casting **before** the test is what
reaches `false`: `blank()` mirrors Laravel in calling a bool filled, so testing
the raw value would let `false` through to stringify as `''`, while the cast
collapses it to the empty string and leaves `0` and `'0'` filled. The narrowing
carries the non-scalar arm too — an array or an object with no `__toString()`
falls to `null` and takes the same fallback — and it is what keeps the helper
call legal: `is_scalar($title) && filled((string) $title)`, the shape that reads
most directly, hands `filled()` a value PHPStan has already proved non-null and
is a level-max error, the
[`?string`-first order](../schemas/association-reader.md#the-record-key-is-tables-semantics-moved-onto-the-mapping)
the association reader settled on for the same reason.

### `getUrl()` drops two parameters and widens a third

Filament's signature carries seven parameters; ours carries five. `$tenant` and
`$configuration` belong to deferred features (tenancy, resource configurations)
and are the whole of what is missing — `$shouldGuessMissingParameters` was the
third until M6 · 3, and it ships with
[nested resources](nested-resources.md#guessing-walks-the-records-own-associations-never-the-request)
under Filament's name and a different mechanism: it walks the record's own
associations, where Filament reads the original request. What survives is
widened: Filament takes `?string $panel` (a panel id) where ours takes
`Panel|string|null`, because the caller that already holds the panel object —
`getNavigationItems()`, the route loader, a page — should not have to round-trip
through the registry by id. A `null` still resolves the current panel, then the
default one, which is Filament's `getCurrentOrDefaultPanel()` behaviour with the
facade replaced by the two injected services.

`getIndexUrl()` carries the flag too, since it delegates into `getUrl()` and
feeds the parent arms.

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
made](#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute),
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

### `route()` returns a registration rather than registering a route

The call shape is Filament's — `ListArticles::route('/')` inside a resource's
`getPages()` — and everything behind it differs. Filament's builds a
`PageRegistration` around a `Closure(Panel): ?Route` that, when the resource's
`registerRoutes()` finally invokes it, calls Laravel's `Route::get()` facade: the
closure **registers** the route as a side effect and hands back the `Route`
object so the caller can keep mutating it (`$route?->name($name)`, middleware).
Ours returns [`PageRegistration`](#resources), a `readonly` pair of the page
class-string and the path, and nothing happens until
[`PanelRouteLoader`](#routing) reads it. The path's leading slash is **optional**:
the loader joins the resource prefix and the registration path with exactly one
separator, so `'/{record}/edit'` and `'{record}/edit'` mount identically and both
`'/'` and `''` collapse onto the resource root. Filament's own examples all carry
the slash, so the docblock states the relaxation rather than leaving a
migrating user to discover that one form silently produced `/articles{record}`.

The reason is where routes come from. A Symfony route loader must **return** a
`RouteCollection`; there is no ambient router to push into mid-call, and a value
that quietly mutated global state during `getPages()` would be unreachable to the
loader anyway. So the name — which Filament applies after the fact from the
`getPages()` key — is composed by the loader from `getRouteBaseName()` plus that
key, and the middleware arguments Filament's closure carries have no
counterpart yet (route middleware is a Laravel concept; the Symfony equivalents,
firewall and access control, land with authorization).

What it costs: a registration cannot express a per-page route option Filament
would set on the returned `Route`. Nothing needs one today. When something does,
the option belongs on `PageRegistration` as data the loader reads, not as a
closure that reaches the collection.

### A resource page inherits page-route statics that answer for the wrong route

`Resources\Pages\Page` extends the panel [`Page`](#page), so it inherits
`getSlug()`, `getRoutePath()`, `getRouteName(Panel)`, `getNavigationItems(Panel)`
and the navigation getters — every one of which describes a page mounted
**directly on a panel**. A resource page is not: its path comes from the
`PageRegistration` its resource declares and its route name is
`getRouteBaseName($panel) . '.' . $key`. So `ListArticles::getRouteName($panel)`
answers `sylament.admin.pages.list-articles`, a route that does not exist, and
`getNavigationItems()` would build an item pointing at it.

Filament has the same inheritance and closes it by **overriding**
`getRouteName()` on `Resources\Pages\Page` to read
`static::getResource()::getRouteBaseName($panel) . '.' . static::getResourcePageName()`.
We cannot: both hops are instance methods on a service here, and these are static
methods with no `$this` to reach the registry from — the same wall
[the carried decision](#resource-is-a-service-not-a-static-class-string-registry)
predicted for the `can*()` statics. Fixing it means either dropping the statics
to instance methods (which changes a Filament API shape) or carrying the page's
registration key on the instance, and both are worth deciding once rather than
twice, so it waits for the task that needs a resource page to name its own route.

M6 · 3 was the first task that could have been it, and
[deliberately routed around it](has-sub-navigation.md#a-resource-page-item-is-built-by-the-band-because-a-page-cannot-name-its-own-route):
the sub-navigation band needs a sibling resource page's URL, which in Filament is
`static::getUrl()` over `getResourcePageName()`. The band builder runs on a page
**instance** holding the registry, so it scans the sibling's own resource
`getPages()` for the entry whose `page` matches and mints the URL from the key it
finds — a private helper, no new public static, and the parked decision stays
parked.

Until then this is a **known wrong-answer surface**, and it is narrow: nothing in
the framework calls those statics on a resource page. `Panel::getNavigation()`
loops over `pages()`, which lists panel pages only, and the route loader takes a
resource page's path from the registration. The wrong answers are reachable only
by a user who calls them directly, or who also lists a resource page in
`pages()` — which mounts it a **second** time under the panel's page family
(`/admin/list-articles` beside `/admin/articles`, two names, no collision for the
[duplicate guard](#routing) to catch) and is the one arrangement in which the
inherited statics answer truthfully.

### The list page's table is stamped query-first and resource-last

Filament's `ListRecords::makeTable()` is an eight-step chain (base table, query,
parent scoping, active tab, labels, `recordAction`, `recordUrl`, then
`static::getResource()::configureTable($table)`), and its own `table()` is a
no-op passthrough that `makeTable()` never calls. Ours is two lines in `table()`:

```php
$resource = $this->getResourceInstance();

return $resource->table($table
    ->query(fn (): QueryBuilder => $resource->getRecordQuery())
    ->recordLabel($resource->getRecordLabel(...))
    ->pluralRecordLabel($resource->getPluralRecordLabel(...))
    ->recordTitleAttribute($resource->getRecordTitleAttribute(...))
    ->recordTitle($resource->getRecordTitle(...)));
```

The **ordering** is what carries over: the query and the labels are stamped
before the resource sees the table, so `Resource::table()` can override any of
them (`->query()` again, `->modifyQueryUsing()`, `->recordLabel()`), and the
resource's configuration is applied last so it wins. The four label setters are
Filament's `configureTable()` stamp, arriving here rather than on `Resource`
because ours is an instance seam and Filament's is a static one; they are passed
as first-class callables, so the table evaluates each only if it reads it.
`recordTitle()` is the one that looks redundant beside the attribute already
stamped and is not: `Resource::getRecordTitle()` is overridable, and without the
stamp the table would read the raw attribute through its own accessor and the
override would never reach a row action. What
they buy is the whole [record-title chain](../actions/action.md#a-table-bound-action-titles-and-labels-through-its-table)
for row actions — a `ViewAction`'s heading reads the record, and the empty state
reads *"No articles"* rather than *"No records"*. What is still gone is the
middle of the chain, each piece with its own reason — the deprecated
`getTableQuery()` seeding is a deprecated delegate, and the rest of
`configureTable()`'s stamp (`authorizeReorder()`) is resource metadata whose
table-side authorization arm is unported.

#### The row click falls through to the resource's own view or edit page

Row-click defaulting was the one this method was predicted to miss, and M4 · 3
landed it — in `makeTable()` rather than in `table()`, which is the placement the
prediction got wrong and the reason matters. `table()` runs the resource hop last
so a resource can override anything; `makeTable()` runs **before** `table()`, so
seeding there means the resource's own `recordUrl()` still wins, which is the
same precedence read from the other end and it composes with `parent::table()`
being mandatory.

The fallthrough is Filament's, plus one hop Filament does not have. Both closures
walk `['view', 'edit']` against the table's own action set: `recordAction()`
returns the first action that is visible and carries **no** URL (a URL'd action is
a link, not a click target), and `recordUrl()` returns the first visible action's
URL. Where neither answers — the usual case, since a resource declares its row
actions as page links — `recordUrl()` falls through to
`$resource->getUrl('view' | 'edit', [...$this->parentRecords, 'record' => $record])`,
which is what makes a plain list's rows clickable with nothing declared at all.
That last hop is the list page's alone: the
[relation-manager tier](interacts-with-relationship-table.md) shares the two
action probes verbatim and stops there, because a related record's page is
reached through [`getDefaultActionUrl()`](interacts-with-relationship-table.md)
instead.

`recordUrl()` is stamped only `if (! $table->hasCustomRecordUrl())`, Filament's own
guard, so a resource that sets one keeps it; `recordAction()` is stamped
unconditionally, because it has no such predicate over there either.

The query closure is worth one note: `->query()` takes a `Closure` rather than
the builder, so `getRecordQuery()` runs at the table's first
`getBaseQuery()` — after configuration, memoised for the request — instead of at
page construction.

The consequence for a subclass: a `ListRecords` that overrides `table()` **must**
call `parent::table($table)`, because those two lines are the only place the
query stamp and the resource's own `table()` are applied. Filament's `table()` is
a passthrough its `makeTable()` ignores, so overriding it there costs nothing;
overriding it here without the `parent::` call yields a table with no data source
and none of the resource's columns or actions. It fails loudly rather than
subtly — tables refuses a configured table with neither `records()` nor
`query()` — so this is a note about *where* the failure comes from, not a trap
that ships a broken page.

### Header actions are bound lazily rather than at boot

Filament caches header actions into the page's action registry at boot, through
three hops: Livewire's `bootedInteractsWithActions()`
(`vendor/filament/actions/src/Concerns/InteractsWithActions.php:118`) calls
`cacheTraitActions()` (`:487`), which `class_uses_recursive()`-walks the class
for every `cache<TraitBasename>` method and so reaches
`cacheInteractsWithHeaderActions()`
(`vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php:18`).
That last one walks `getHeaderActions()`, stamps `->livewire($this)` on each
group, sets a dropdown placement, and registers every action through
`cacheAction()`; Blade then renders `getCachedHeaderActions()`, the already-bound
list. UX LiveComponent has no `booted*()`/`cache*()` trait hook to hang that on,
so [`Pages\Concerns\InteractsWithHeaderActions`](../../../packages/panels/src/Pages/Concerns/InteractsWithHeaderActions.php)
— composed by [`Pages\Page`](#page), so **every** panel page has it, not only a
resource page — does the same work **lazily**, at whichever of the two reads
comes first. [`InteractsWithFormActions`](form-actions.md) carries the identical
walk for the form row one list over.

`getCachedHeaderActions()` is that walk. It memoises into a
`?array $cachedHeaderActions` — set to `[]` *before* `getHeaderActions()` is
called, so a declaration that itself reaches `getAction()` cannot recurse —
stamps every `ActionGroup` with `liveComponent($this)` (which propagates to its
members) and `cacheAction()`s every plain entry and every `getFlatActions()`
member of a group, then keeps and returns the declared list. `cacheAction()` is
what does both halves in one call: it stamps `liveComponent($this)` *and*
registers the action under its own name.

The two reads are the two directions, and each needs a different half:

- **Out**, at render: the base page chrome reads `this.getCachedHeaderActions`
  rather than `this.getHeaderActions` — once, for every page that extends it —
  because the *binding* is what a trigger
  renders from. `CanOpenUrl::getUrl()` reaches the page through
  `getLiveComponentOrNull()`, so an unbound `CreateAction` renders as a
  `<button>` with no href instead of an `<a>` to the resource's create page —
  and `getDefaultActionRecordClass()`, `getDefaultActionRecordLabel()` and
  `getDefaultActionRecordTitle()` are skipped with it. No error anywhere, which
  is why the shipped fixture pins the rendered `href`.
- **In**, when the browser posts a mount back: `getAction()` is overridden on the
  base to try the trait's own lookup first — the action cache, then
  [actions' method discovery](../actions/has-actions.md), which finds a
  `<name>Action()` or `<name>()` method returning an `Action` — and, **only when
  that misses**, to run the walk once and look again. Render-time reading alone
  could never cover this: the request that *calls* a mounted action renders no
  header at all, so nothing would have populated the cache by the time the name
  was looked up.

So every declaration form works and none of them is stamped by hand.
`$this->fooAction()` listed in `getHeaderActions()` is the Filament shape and
still resolves through discovery; an action built **inline** in the list resolves
too, and renders its URL, because the list the template renders is the bound one.
An earlier arrangement had the walk on the way in only, leaving the template on
the raw `getHeaderActions()` and a user's own `cacheAction()` call as the price
of a working href — an easy line to forget, and silent when forgotten. A table
**record** action needs none of this either way: `BelongsToLiveComponent` falls
through the action's table to the table's host.

The hook's two remaining members shipped with M6 · 2. `$headerActionsAlignment`
and `getHeaderActionsAlignment()` sit on the trait and reach the `Header`
component's new two-level DOM
([where the class lands, and why](custom-pages.md#the-alignment-class-rides-the-inner-row-and-the-container-is-what-the-breadcrumb-offset-hangs-off)),
and the walk stamps `dropdownPlacement('bottom-end')` on an `ActionGroup` whose
placement is null — Filament's own default, so a bare header group opens under
the end of its trigger rather than wherever the component default put it.

The residue is the memoisation's scope. Filament's cache is per boot and ours is
per request object, which is the same thing for a page — but a
`getHeaderActions()` that returns a *different* list depending on state read
after the first call would be read once and frozen, where Filament would have
frozen it at boot instead. Both are caches of a declaration, and a declaration
that varies mid-request is out of contract in either framework.

### The actions host sits on the base because a trait outranks an inherited method

[`Pages\Page`](#page) — not `Resources\Pages\Page`, and not `ListRecords`,
`CreateRecord` or `EditRecord` — `implements InteractsWithActions` and `use`s
`HasActions` (through the ungrouped alias
`use HasActions { getAction as private getCachedOrDeclaredAction; }`), while
`Resources\Pages\Page` writes `getDefaultActionUrl()` and the rest of the
`getDefault*` family in its class body. That placement is forced, not stylistic.
PHP's precedence is *current class > inserted trait > inherited class*, so a
subclass that re-`use`d `HasActions` would insert the trait's
`getDefaultActionUrl(): ?string { return null; }` **over** the resource base's
override, and every `CreateAction` on that page would render with no href — no
error, no warning, just a dead button. Task 4 shipped `ListRecords` with its own
`use HasActions`, which is exactly the arrangement that would have swallowed the
hook; the trait moved onto the resource base when the override landed, and onto
the plain page base with M6 · 2.

That second move is the closer parity: Filament composes
`InteractsWithActions` on `BasePage`, so *every* page is an actions host there
too — and here it is what lets a hand-written page declare header actions
without extending a resource. The **same** rule and the same hazard apply one
tier down to `HasSchemas`, which M6 · 2 also moved up, and there the failure is
not a dead button but a 500 on the first dehydrate
([why every leaf had to drop it](custom-pages.md#the-empty-discovery-list-is-mandatory-and-so-is-its-removal-from-every-leaf)).
So the rule for this hierarchy is simply: **a page never re-`use`s a trait its
base already carries.** It reads like tidiness and is not;
`packages/panels/tests/Integration/Resources/Pages/ListRecordsTest.php` fails on
the rendered `href` the moment the actions half is broken, and
[`Unit/Resources/Pages/PageTest`](../../../packages/panels/tests/Unit/Resources/Pages/PageTest.php)
pins which class each shadowed member is declared on.

### The record prop carries the route key and needs a dehydration bridge

Filament's `InteractsWithRecord` declares `public Model | int | string | null $record`
and lets Livewire carry the model. Ours is
`#[LiveProp(dehydrateWith: 'dehydrateRecord')] public ?string $record = null`,
and both halves of that are forced by ux-live-component.

**Single type.** `LiveComponentMetadataFactory::createLivePropMetadata()` throws
`LogicException('Union or intersection types are not supported for LiveProps.')`
for any union-typed prop, so Filament's `int|string` cannot survive on the
property. It survives on `mount(int|string $record)`, which is not a prop and is
the signature a migrating user actually writes. A route parameter arrives as a
string anyway, so the cast in `mount()` is a formality.

**The bridge.** `LiveComponentHydrator::dehydrate()` reads each prop through
Symfony's `PropertyAccessor`, which prefers a same-named **getter** over the
property — and `getRecord()` returns the resolved **entity**. Left alone, the
wire would carry an object where a `?string` is declared, and the hydrator would
throw on a value it cannot dehydrate. `dehydrateRecord()` returns `$this->record`,
the raw key, and the prop is a scalar on the wire again. This is the
[CLAUDE.md dehydration trap](../../../CLAUDE.md) in its second shipped form,
mirroring tables' [`dehydrateTableSearch`](../tables/has-table.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary);
the fix is always the bridge, never renaming the Filament getter. A composite
key is one string too — the `?string` type, the checksum and the bridge carry
`1~A=2FB=20C` exactly as they carry `7` — so the widening of the key format
asked nothing of this prop, nor of the parent chain's
`array<string, string>`, nor of a relation manager's `ownerRecord`.

We could have sidestepped both by typing the prop to the entity and letting
`DoctrineEntityHydrationExtension` carry the identifier (the demo's `ArticleForm`
does exactly that, and it works). It was not done because a resource page must
serve **any** record class, and a `LiveProp`'s type is read off the property at
compile time, so there is no type to write on a generic base. The key is the one
thing every resource can put on the wire, and `Resource::resolveRecordRouteBinding()`
already exists to turn it back into a record through the resource's own query —
which is also stricter than a `find()`: a record the query scopes out 404s
instead of loading.

`packages/panels/tests/Integration/Resources/Pages/EditRecordTest.php` reads the
rendered `data-live-props-value` and asserts `record` is the key string, and
`CompositeRecordPagesTest` asserts the same of a two-component one. Removing the
bridge does not fail those two alone: the prop then dehydrates as the resolved
**entity**, which the hydrator refuses, so every case that renders a record page
goes red with it.

### Staying on the page after a save is a `null` redirect, not a caught authorization exception

Filament's `EditRecord::getRedirectUrl()` is a `try { $this->authorizeAccess(); return null; } catch (AuthorizationException | HttpExceptionInterface) { … }`
— it stays on the page when the user may still view it, and redirects to the
index when saving has just revoked their own access. With authorization deferred,
the predicate has nothing to test, so ours is `return null;` and `save()` reads
it as "stay":

```php
$redirectUrl = $this->getRedirectUrl();

return $redirectUrl === null ? null : new RedirectResponse($redirectUrl);
```

The nullable return type is Filament's, and the override point is unchanged — a
user returning a URL gets a redirect. What is gone is the exception-as-predicate,
and it comes back with the auth cluster rather than being emulated now. Note the
create page's `getRedirectUrl(): string` is **not** nullable, matching Filament:
a create always goes somewhere.

The visible half of this is that a successful save returns `null` from the
`#[LiveAction]`, so the response is the re-rendered component rather than a
redirect — which is what lets notifications' `LiveDeliverySubscriber` dispatch the
toast as a browser event on the same round trip. The create page's redirect takes
the other delivery path, the flash bag drained by the document's outlet. Both are
pinned.

### The create and save notification titles are literals, not translation keys

`getCreatedNotificationTitle()` returns `'Created'` and
`getSavedNotificationTitle()` returns `'Saved'`, where Filament returns
`__('filament-panels::resources/pages/create-record.notifications.created.title')`
and its edit-page twin. Same reason
[the dashboard title is a literal](#the-dashboard-title-is-a-literal-not-a-translation-key):
there is no translation layer in the port yet, and a `trans()` call against a
catalogue nobody ships would render the key. The **override points** are what a
consumer needs and they are Filament's exactly — `getCreatedNotificationTitle()`
/ `getSavedNotificationTitle()` for the copy, `getCreatedNotification()` /
`getSavedNotification()` for the whole notification — and each returns `null` when
its title is `blank()`, which is Filament's own "no title, no toast" contract.
Filament's deprecated `get*NotificationMessage()` pair is not ported; it exists
only to keep pre-v3 overrides working.

When translations land these become `trans()` calls and nothing else changes.

### The record page's schema is named `form`, and `schemas()` is narrowed to it

`HasSchemas` defaults to one schema named `schema`, discovered through a
`schema()` or `schemaSchema()` method. Both record pages instead declare
`form(Schema): Schema` and `schemas(): ['form']`, so every read is
`getSchema('form')` and the template says `renderSchema('form')`.

That is Filament's name — its pages carry a `form` schema and a
`@property-read Schema $form` — and it is what makes room for the schemas a
record page will grow (`infolist` on a view page, one per relation manager). The
narrowing of `schemas()` is not optional: the list is what the de/hydration hooks
walk, so leaving the default in place would demand a `schema()` method the page
does not have and throw `RuntimeException` on the first render — the same trap
[`ListRecords::schemas()` returning `[]`](#listrecords) documents.

The cost is that `HasSchemas::fill()` — the trait's convenience wrapper, hardcoded
to the default schema — is not usable on these pages; they call
`$this->getSchema('form')->fill(…)` explicitly, which is what a multi-schema host
does anyway.

### The document title of an edit page carries the record label, not the record title

Filament's edit page titles the document `Edit {getRecordTitle()}` — `Edit Alpha`.
Ours shows `Edit Article` in the browser tab while the page's own `<h1>` shows
`Edit Alpha`, and the split is [`PageController`](#routing)'s doing: it reads
`$page->getTitle()` off the **locator instance** to build the document, before the
page component is mounted and therefore before `$this->record` holds anything.
`getRecordTitle()` guards on exactly that and answers the resource's title-case
record label instead.

The guard is not cosmetic. Without it, `getTitle()` on an un-mounted page calls
`getRecord()`, which finds a null key and throws `NotFoundHttpException` — so
**every** edit-page GET would answer 404. That is what the mutation test shows
when the guard is removed, and it is why this is recorded as a divergence rather
than left as an oddity to be tidied later.

Revisit when `PageController` can mount the component before reading its title.
Today it cannot: mounting happens inside `{{ component(componentName, componentProps) }}`
during the Twig render, and the title is needed to build the document that
contains it. A controller that resolved props and called the page's `mount()`
itself would have both, at the cost of mounting twice or handing ux-twig-component
a pre-mounted instance.

### URL generation inside a live action resolves the default panel

`Resource::getUrl()` picks the panel through
`CurrentPanel::get() ?? PanelRegistry::getDefault()`, and `CurrentPanel` reads the
`_sylament_panel` request attribute — which the [route loader](#routing) puts on
every panel page route. A live request does not go to a panel route: it goes to
`ux_live_component`, which carries no such attribute, so `CurrentPanel::get()`
returns null and the **default panel** answers.

Everything a create/edit page generates runs there — the post-create redirect and
the `getDefaultActionUrl()` answers all happen inside a `#[LiveAction]` or a live
re-render. For the overwhelmingly common single-panel application, and for any
application whose resources live in the default panel, the answer is right. It is
wrong for a resource mounted **only** in a non-default panel: its create page
would redirect to a URL under the default panel's path, and the route name would
not exist, so `UrlGenerator` throws `RouteNotFoundException` — loud, at least,
rather than a wrong page.

Filament has the same problem and solves it with `original_request()`, a
Livewire helper that reaches the request that rendered the component rather than
the one carrying the update.

**M6 · 3 closed it for resource pages; M6 · 2 closed it for every page.** The
`#[LiveProp] public ?string $panelId`, minted by the getter its dehydration
reads and stamped back onto the request from a `#[PostHydrate]` hook — the
mechanism [`GlobalSearch`](global-search.md#the-component-restores-its-panel-onto-a-live-request)
already used, ported down a tier — landed on the resource-page base first and
moved up to [`Pages\Page`](#page) with the page tier, where the stamp also took
[priority 20](custom-pages.md#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism)
so it precedes actions' own re-resolution of a mounted action at 10. So a live
request that hydrates **any** panel page resolves the panel that page was opened
in, and every URL that request mints — the trail, the band, a redirect, an action
link — answers for it. Nesting forced the first half: on a non-default panel,
every live re-render of a nested page threw `RouteNotFoundException` before the
prop existed.

One half stays open. A **nested live component** hydrating on its own — a
[relation manager](relation-manager.md#every-url-a-manager-generates-resolves-the-default-panel)
answering a table sort — never hydrates its host page, so nothing stamps the
attribute for it. It waits on
[M6 · 6](../roadmap.md#m6--panel-depth)'s multi-panel URL row.

### A create modal on a record page is anonymous

`EditRecord::getDefaultActionRecord()` answers `null` for a `CreateAction` and the
page's record for everything else. Filament's record-page concern has no such arm —
its `getDefaultActionRecord()` is `$this->hasRecord() ? $this->getRecord() : null`,
so a `CreateAction` mounted on an edit page is handed the record that page is
editing.

That is coherent in Filament because a create modal on a record page is nearly
always a *relationship* create, where the page record is the parent the new row
hangs off, and Eloquent's relationship arm reads it as exactly that. Our
relationship arm exists — `CreateAction::relationship()` shipped with M4 · 8 and a
relation manager answers `getDefaultActionRelationship()` since M6 · 1 — but it
hangs off the *table* and the manager, never off an ambient page record, so on the
page itself the same default would still be read the only other way it can be: as
the record the create is *about*. A `CreateAction` seeded with an existing record fills its modal from
that record and, on submit, maps state onto it. The create modal would silently
become an edit of the page's record.

Filament's own `ManageRelatedRecords` is the shape borrowed here: it is the page
that hands a create action the *parent*, deliberately and explicitly, rather than
letting the ambient record stand in. Until that seam exists, `null` is the honest
answer — a create modal on a record page belongs to no record, and the resource's
record class (which `getMountedActionSchemaRecord()` still answers) is all the
schema needs to build a blank form.

Revisit with relation managers ([M6 · 1](../roadmap.md#m6--panel-depth)), which
is when a create modal on a record page will have a parent to be told about and a
relationship arm to read it.

### A modal's schema keeps the resource form's own column count

Filament's resource pages force a two-column grid onto every modal schema they
resolve: `ListRecords`, `EditRecord` and `ViewRecord` each build
`$schema->hasCustomColumns() ? $schema : $schema->columns(2)` before handing it
to the resource's `form()`, and the full-page `defaultForm()` does the same with
an inline-labels arm (`vendor/filament/filament/src/Resources/Pages/ListRecords.php:94`,
`EditRecord.php:300`, `CreateRecord.php:315`). The port's resolvers pass the
seeded schema through untouched — the list page's, which still carries both
actions on one arm, reads:

```php
// ListRecords::getDefaultActionSchemaResolver()
$action instanceof CreateAction, $action instanceof EditAction => $this->form(...),
```

(the arrow function collapses to a first-class callable once the wrapper is gone
— the schema still arrives by name, `HasSchema::getSchema()` injecting `$schema`
along with `form` and `infolist`). The edit page's own `EditAction` no longer
takes this arm at all: it embeds
[the page's own form](#the-edit-pages-own-edit-modal-is-the-page-form-itself),
already configured, leaving the create and view arms to it.

The `hasCustomColumns()` guard reads as "the *action* may override it", but the
schema it guards is the blank one the host seeds per mount — a resource form is
configured **inside** `Resource::form()`, one call later, so the guard can never
see what the resource itself set. The result is that a resource whose `form()`
declares a single-column layout renders one way on its own create/edit page and
two-up in a modal, for no reason a reader of that form can find. Dropping the
default makes the resource form the only thing that decides, and a page or action
that wants the two-column grid still says `$schema->columns(2)` — the same call
Filament makes, moved to where its intent is visible.

The full-page arms need no equivalent decision: `defaultForm()` is not ported —
`CreateRecord::form()` and `EditRecord::form()` stamp a state path, a record and
an operation and hand the schema to the resource — so a page form already renders
exactly what the resource declared, and this only aligns the modal with it.
