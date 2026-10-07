---
description: 'The panel definition a provider configures, the provider contract, the registry that collects panels, the current-panel lookup and the bundle wiring.'
ports:
  - vendor/filament/filament/src/Panel.php
  - vendor/filament/filament/src/Panel/Concerns/HasBrandLogo.php
  - vendor/filament/filament/src/Panel/Concerns/HasBrandName.php
  - vendor/filament/filament/src/Panel/Concerns/HasComponents.php
  - vendor/filament/filament/src/Panel/Concerns/HasId.php
  - vendor/filament/filament/src/Panel/Concerns/HasNavigation.php
  - vendor/filament/filament/src/Panel/Concerns/HasRoutes.php
  - vendor/filament/filament/src/Panel/Concerns/HasSidebar.php
  - vendor/filament/filament/src/PanelProvider.php
  - vendor/filament/filament/src/PanelRegistry.php
  - vendor/filament/filament/src/FilamentManager.php
  - vendor/filament/filament/src/FilamentServiceProvider.php
  - vendor/filament/filament/src/Http/Middleware/SetUpPanel.php
owns:
  - packages/panels/src/CurrentPanel.php
  - packages/panels/src/Panel.php
  - packages/panels/src/PanelProvider.php
  - packages/panels/src/PanelRegistry.php
---
# Panel (panels)

`Sylament\Panels\Panel` is the definition a [`PanelProvider`](../../../packages/panels/src/PanelProvider.php) configures; [`PanelRegistry`](../../../packages/panels/src/PanelRegistry.php) collects the panels and [`CurrentPanel`](../../../packages/panels/src/CurrentPanel.php) finds the one serving the request.
Four of the panel's traits are documented with the code that reads them: [`HasSubNavigation`](has-sub-navigation.md), [`HasGlobalSearch`](global-search.md), [`HasMaxContentWidth`](simple-page.md#the-width-resolution-is-one-ladder-in-the-controller) and [`HasUnsavedChangesAlerts`](unsaved-changes-alert.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Panel::make()`](../../../packages/panels/src/Panel.php) | [`Panel::make()`](../../../vendor/filament/filament/src/Panel.php) | done | — | [diverges](#make-configures-the-panel-before-its-id-is-set) |
| [`default()` / `isDefault()`](../../../packages/panels/src/Panel.php) | the same pair | done | — | — |
| `register()` / `boot()` / `bootUsing()` | the panel lifecycle | skipped | — | [diverges](#the-panel-has-no-register-or-boot) |
| [`HasId`](../../../packages/panels/src/Panel/Concerns/HasId.php): `id()` / `getId()` | [`Panel/Concerns/HasId.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasId.php) | done | — | — |
| [`HasRoutes`](../../../packages/panels/src/Panel/Concerns/HasRoutes.php): `path()` / `getPath()` | [`Panel/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasRoutes.php) | done | — | [routing.md](routing.md#the-route-import-carries-no-prefix-because-the-panel-path-is-authoritative) |
| `HasRoutes`: domains, the home URL, the route closures, `getUrl()` and `getRedirectUrl()` | the rest of `HasRoutes.php` | todo | — | — |
| [`HasComponents`](../../../packages/panels/src/Panel/Concerns/HasComponents.php): `pages()` / `resources()` / `getPages()` / `getResources()` | [`Panel/Concerns/HasComponents.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasComponents.php) | done | — | [diverges](#getpages-re-indexes-where-filament-keeps-array_uniques-gapped-keys) · [semantics](#a-registered-page-or-resource-is-validated-by-the-route-loader-not-at-registration) |
| `HasComponents`: `discoverPages()`, `discoverResources()`, `discoverClusters()`, `discoverWidgets()`, `discoverLivewireComponents()` and the component cache | the discovery arms | todo | — | [detail](#filesystem-discovery-would-return-only-as-a-convenience-over-service-registration) |
| `HasComponents`: `widgets()` / `getWidgets()` | the widget registration arm | blocked | a `Widget` class in `sylament/widgets` | — |
| `HasComponents`: page and resource configurations, clusters, `getModelResource()`, the relation-manager read-only default and the create / edit page redirects | the rest of `HasComponents.php` | todo | — | [custom-pages.md](custom-pages.md#the-configuration-object-is-not-ported) |
| [`HasNavigation`](../../../packages/panels/src/Panel/Concerns/HasNavigation.php): `navigationGroups()` / `navigationItems()` / their getters / `getNavigation()` | [`Panel/Concerns/HasNavigation.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasNavigation.php) | done | — | [semantics](#getnavigation-merges-every-source-before-it-sorts-and-buckets) · [diverges](#navigation-is-assembled-on-the-panel-not-by-a-navigationmanager) · [diverges](#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource) |
| `HasNavigation`: `navigation()`, `NavigationBuilder`, `hasNavigation()` / `hasNavigationBuilder()` | the navigation builder | todo | — | [detail](#the-navigation-builder-escape-hatch-is-deferred-not-rejected) |
| [`HasSidebar`](../../../packages/panels/src/Panel/Concerns/HasSidebar.php): `collapsibleNavigationGroups()` / `hasCollapsibleNavigationGroups()` | [`Panel/Concerns/HasSidebar.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasSidebar.php) | done | — | [navigation-group.md](navigation-group.md) |
| `HasSidebar`: `sidebarCollapsibleOnDesktop()` / `sidebarFullyCollapsibleOnDesktop()` / `sidebarWidth()` / `collapsedSidebarWidth()`, their getters and `sidebarLivewireComponent()` | the rest of `HasSidebar.php` | todo | — | — |
| [`HasBrandName`](../../../packages/panels/src/Panel/Concerns/HasBrandName.php) | [`Panel/Concerns/HasBrandName.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasBrandName.php) | done | — | [diverges](#getbrandname-returns-null-where-filament-falls-back-to-the-app-name) |
| [`HasBrandLogo`](../../../packages/panels/src/Panel/Concerns/HasBrandLogo.php) | [`Panel/Concerns/HasBrandLogo.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasBrandLogo.php) | done | — | [layout-templates.md](layout-templates.md) |
| `darkMode()` / `themeSwitcher()` / `hasDarkMode()` / `hasDarkModeForced()` / `hasThemeSwitcher()`, `defaultThemeMode()` / `getDefaultThemeMode()` | [`Panel/Concerns/HasDarkMode.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasDarkMode.php), [`Panel/Concerns/HasTheme.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasTheme.php) | todo | — | — |
| `breadcrumbs()` / `hasBreadcrumbs()` / `hasNavigationHierarchyInBreadcrumbs()` | [`Panel/Concerns/HasBreadcrumbs.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasBreadcrumbs.php) | todo | — | [page.md](page.md#the-navigation-hierarchy-breadcrumbs-wait-on-the-panels-breadcrumb-settings) |
| The other `Panel/Concerns` traits no Sylament code composes: auth, tenancy, colours, icons, assets, render hooks, middleware, plugins, fonts and the rest | [`Panel/Concerns/`](../../../vendor/filament/filament/src/Panel/Concerns) | todo | — | — |

## Surface — PanelProvider

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`panel()`](../../../packages/panels/src/PanelProvider.php) | [`PanelProvider::panel()`](../../../vendor/filament/filament/src/PanelProvider.php) | done | — | [diverges](#panels-are-collected-from-tagged-providers-not-registered-imperatively) |
| `register()` | `PanelProvider::register()` | skipped | — | [diverges](#panels-are-collected-from-tagged-providers-not-registered-imperatively) |

## Surface — PanelRegistry

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`all()`](../../../packages/panels/src/PanelRegistry.php) | [`PanelRegistry::all()`](../../../vendor/filament/filament/src/PanelRegistry.php) | done | — | [diverges](#panels-are-collected-from-tagged-providers-not-registered-imperatively) |
| [`get()`](../../../packages/panels/src/PanelRegistry.php) | `PanelRegistry::get()` | done | — | [diverges](#panelregistryget-has-no-fuzzy-arm) |
| [`getDefault()`](../../../packages/panels/src/PanelRegistry.php) | `PanelRegistry::getDefault()` | done | — | — |
| [`getProviderClass()`](../../../packages/panels/src/PanelRegistry.php) | no Filament counterpart | done | — | [routing.md](routing.md#panelrouteloader-mounts-the-whole-panel-surface-in-one-pass-over-the-panel-registry) |
| `register()` | `PanelRegistry::register()` | skipped | — | [diverges](#panels-are-collected-from-tagged-providers-not-registered-imperatively) |

## Surface — CurrentPanel

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`CurrentPanel::get()`](../../../packages/panels/src/CurrentPanel.php) | [`FilamentManager`](../../../vendor/filament/filament/src/FilamentManager.php)'s current panel and [`SetUpPanel`](../../../vendor/filament/filament/src/Http/Middleware/SetUpPanel.php) | done | — | [diverges](#the-current-panel-is-derived-from-the-request-not-stored-on-a-manager) |
| `getCurrentOrDefaultPanel()` | `FilamentManager::getCurrentOrDefaultPanel()` | skipped | — | [diverges](#the-current-panel-is-derived-from-the-request-not-stored-on-a-manager) |
| _rest not audited yet_ | — | — | — | — |

## Surface — SylamentPanelsBundle

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`SylamentPanelsBundle`](../../../packages/panels/src/SylamentPanelsBundle.php): autoconfiguration tags, compiler passes, services and the `@sylament/panels` AssetMapper path | [`FilamentServiceProvider.php`](../../../vendor/filament/filament/src/FilamentServiceProvider.php) | done | — | [diverges](#panels-are-collected-from-tagged-providers-not-registered-imperatively) |
| `Make*` commands (`hasCommands()`) | [`FilamentServiceProvider.php`](../../../vendor/filament/filament/src/FilamentServiceProvider.php) | todo | — | — |
| Translations (`hasTranslations()`) | [`FilamentServiceProvider.php`](../../../vendor/filament/filament/src/FilamentServiceProvider.php) | todo | — | [i18n](../../internals/i18n.md#translation-is-one-deferred-pass-not-a-per-component-port) |
| _rest not audited yet_ | — | — | — | — |

## Details

### Filesystem discovery would return only as a convenience over service registration

Filament's `discover*()` arms scan directories for classes to register.
Resources and pages are [collected from a service tag](resource.md#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem) here, which replaces discovery for services, so a discovery arm would return only as a convenience over the application's service configuration.

### The navigation builder escape hatch is deferred, not rejected

Filament's `navigation(Closure | bool)` with `NavigationBuilder`, plus `hasNavigation()` / `hasNavigationBuilder()`, replaces the derived navigation with a hand-built one.
It needs nothing from [the assembly](#getnavigation-merges-every-source-before-it-sorts-and-buckets) and can land whole, unchanged in shape, whenever a consumer wants it.

## Semantics

### `getNavigation()` merges every source before it sorts and buckets

`getNavigation()` clones every item each registered page contributes, then every item each registered resource contributes, then the panel's own `navigationItems()`, drops the hidden ones, sorts by `getSort()`, and buckets what is left by `getGroup()`.
All three sources land in one list before that pass, so a resource item sorts and groups against the page items rather than trailing them; source order decides ties alone, `usort` being stable.
The ungrouped bucket becomes the leading label-less group; each registered group then claims its bucket by key **or** label (a clone, so the registration survives repeated calls); buckets no registration matched follow in encounter order.
Every group that comes out is stamped with `collapsibleFallback($this->hasCollapsibleNavigationGroups())`, and a group with no items is never built, so a registration nothing matched does not render.
Cloning at both ends is what makes the method idempotent — `items()` mutates, and the registered objects are the panel's, not the render's.
A resource's items are built fresh on every call, so cloning them is not needed for that, and it is done anyway: it keeps the three loops identical to read, and a memoisation inside `getNavigationItems()` could not leak one render's [destructive `isActive()`](#navigation-is-assembled-on-the-panel-not-by-a-navigationmanager) into the next.

### A `new`-built panel cannot assemble resource navigation

A registered resource is a class-string, and turning it into the instance whose `getNavigationItems($this)` answers means going through [`ResourceRegistry`](resource.md), so `HasNavigation` takes one through a `#[Required] setResourceRegistry()` setter, as [`NavigationItem`](navigation-item.md#the-item-hands-its-own-services-to-evaluated-closures) takes its three services.
`Panel` is a `sylament.component`-tagged, container-built, non-shared service, so the setter fires on every `Panel::make()`.
A panel built with `new` holds no registry, and `getNavigation()` then throws *"Resource navigation needs the container-built panel"* the moment it reaches a registered resource — the same [fail-fast](resource.md#a-new-built-resource-fails-fast-rather-than-degrading) shape `Resource`'s own service set uses, rather than silently dropping half the navigation.
A `new`-built panel that registers no resource still assembles normally, because the loop body is what needs the registry.

### A registered page or resource is validated by the route loader, not at registration

`pages()` and `resources()` accumulate across calls and type their entries `class-string<Page>` and `class-string<Resource>`, but nothing checks an entry when it is registered.
[`PanelRouteLoader`](routing.md#panelrouteloader-mounts-the-whole-panel-surface-in-one-pass-over-the-panel-registry) is where a non-`Page` or non-`Resource` entry is refused, at route-build time.

## Divergences

### Panels are collected from tagged providers, not registered imperatively

Filament's `PanelProvider` extends Laravel's `ServiceProvider` and its `register()` calls `Filament::registerPanel(fn () => $this->panel(Panel::make()))`, so a panel exists because a provider imperatively pushed it into the manager.
Symfony has no service-provider tier to hook: ours is a plain abstract class carrying `panel()` alone, autoconfigured with `sylament.panels.panel_provider` by `SylamentPanelsBundle`, and `PanelRegistry` receives the tagged iterator and builds each panel once, on the first `all()`, keyed by id.
The user-facing shape is unchanged — write a `PanelProvider` subclass with a `panel()` method — while registration becomes container wiring, the same trade `AsController`/`AsEventListener` make.

The tag buys one behaviour Filament's registry has no place for: two providers claiming the same id throw naming both classes, where Filament's `$this->panels[$panel->getId()] = $panel` silently overwrites the first.

### The current panel is derived from the request, not stored on a manager

Filament holds `?Panel $currentPanel` on the `FilamentManager` singleton and `Http\Middleware\SetUpPanel` assigns it per request.
That is exactly the mutable static per-request state [the shell is worker-safe](layout-templates.md#the-shell-is-turbo-indifferent-and-worker-safe) forbids: a long-running worker would leak one request's panel into the next.
`CurrentPanel` stores nothing — it reads the `_sylament_panel` request attribute through `RequestStack` on every call, so the answer belongs to the request being served and a worker has nothing to reset.
An id the registry does not know is a `LogicException` rather than a silent null, since only our own routing writes that attribute.

What statelessness buys is visible in Filament's own code.
`FilamentManager` carries a second piece of remembered state beside the panel — `protected bool $isCurrentPanelBooted = false`, guarding `bootCurrentPanel()` ([`FilamentManager.php:65-74`](../../../vendor/filament/filament/src/FilamentManager.php)) — and nothing ever sets it back to `false`.
Under php-fpm that is invisible, because the manager dies with the request.
Under a long-running worker the second request onwards finds the flag already true and skips booting its panel entirely, so the colours, icons and render hooks of whichever panel happened to be served first are the ones every later request gets.
The port has no flag to forget to reset because it has nothing to boot ([the panel has no `register()` or `boot()`](#the-panel-has-no-register-or-boot)) and nothing to remember.

Filament's `getCurrentOrDefaultPanel()` fallback is not ported: `get()` returns `null` outside a panel, and a caller that wants the default asks `PanelRegistry::getDefault()` for it.

### `PanelRegistry::get()` has no fuzzy arm

Filament's `get(?string $id, bool $isStrict = true)` can lower-case and strip dashes/underscores from every registered id to match a sloppy one.
Ours takes the id alone and matches exactly.
Nothing calls the fuzzy arm with `$isStrict: false` except Filament's own artisan commands resolving a panel from console input, which has no counterpart here; a normalising lookup that silently answers for a panel the caller did not name is worth having only once something needs it.

### Navigation is assembled on the panel, not by a `NavigationManager`

Filament's `Panel::getNavigation()` resolves a `NavigationManager` out of the container, hands itself to it via a `$navigationManager` property the registration setters then redirect to, and reads the result back — a round trip that buys a per-request scratch object (it clones the panel's groups and items in its constructor) which `navigationGroups()`/`navigationItems()` can still be called on *while* the navigation is being built, the seam plugins register through.
Ours does the assembly in `HasNavigation::getNavigation()` directly.
The panel already holds every input (its pages, groups and items), the method needs no service beyond the resource registry, and the manager's mid-build registration arm has no caller here until plugins land.
The piece worth keeping from it is the cloning discipline, which ours has: groups and items are cloned on the way out, so repeated calls neither accumulate items on a registered group nor let one render's mutation reach the next.

That discipline covers `getNavigation()` and nothing else.
`getNavigationItems()` and `getNavigationGroups()` hand back the registered objects themselves, and `PanelRegistry` builds each panel once and memoises it — so under a long-running worker those are the same instances for every request the process serves.
Read against them, [`NavigationItem::isActive()`](navigation-item.md) is destructive: it overwrites its `isActiveWhen()` closure with the boolean the first evaluation produced, so a raw item asked once whether it is active answers that way for the life of the process, on every request, whatever the route.
Navigation is read through `getNavigation()`, whose clones absorb exactly that; the raw getters are registration-time composition surface — what a provider reads to add to what an earlier call registered — and are not a render path.

### Resource navigation is pulled through the registry, not pushed by the resource

Filament's `Resource::registerNavigationItems()` is a `void` static that reaches `Filament::getCurrentOrDefaultPanel()` and **pushes** its items onto the panel with `->navigationItems(...)`; `NavigationManager::mountNavigation()` calls it once per registered page and resource, then reads the accumulated list back off itself.
Both halves of that are forced by the same thing: a Filament resource is a static class with no panel in hand, so it needs the facade to find one, and pushing onto the real panel would accumulate items across requests, so it needs the manager's per-request scratch lists to push *into* — two arrays seeded by cloning the panel's own, which `Panel::navigationItems()` redirects into for as long as a manager is set.

Ours pulls.
`getNavigation()` resolves each registered class-string through [`ResourceRegistry`](resource.md) and calls `getNavigationItems($this)` on the instance — the panel is handed over, not looked up, the same inversion [`Page::getNavigationItems(Panel)`](page.md#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade) uses — and merges the returned array locally.
Nothing mutates the panel, so there is no accumulation to clone away and no scratch object to own the result.
The one thing the push model buys that this does not is a third party queueing items into a build already under way; that is the plugin seam, deferred with the rest of plugins.

So `registerNavigationItems()` has no port, and its four early returns are distributed rather than dropped: `shouldRegisterNavigation()` is ported and consulted by the panel-side loop, in Filament's own position, and so is `getParentResourceRegistration()` — a [nested resource](nested-resources.md) is skipped there and nowhere else, so it still answers `getNavigationItems()` when something asks it directly.
`getCluster()` guards an unported subsystem; `canAccess()` waits on authorization.
Each of those lands in the loop when its subsystem does, not back inside the resource — a resource that answers `getNavigationItems()` with an array cannot also decide it should not have been asked.

### The panel has no `register()` or `boot()`

Filament's `Panel` carries both lifecycle methods and neither is ported.
`register()` queues the panel's Livewire components, registers persistent middleware, sets a tenant route pattern and registers assets when running in console; `boot()` installs per-request global state — `FilamentColor::register()`, `FilamentIcon::register()`, `FilamentView::spa()`, the render hooks, and an `Action::configureUsing()` for database transactions.

Neither has a job here.
Component registration is a **container** concern, done once at compile time by [`PageComponentPass`](page.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute) rather than per request by the panel.
And `boot()`'s remaining work is exactly the mutable global state [the shell is worker-safe](layout-templates.md#the-shell-is-turbo-indifferent-and-worker-safe) forbids: pushing this request's panel colours and icons into process-wide registries is undebuggable the moment two panels are served by one worker.
Ours are container configuration instead — [`sylament.colors` and `sylament.icons`](../support/icon-manager.md) are read into `ColorManager`/`IconManager` at container build and never rewritten — so there is nothing for a panel to install and nothing to reset.
Render hooks, middleware and assets are all unported surface that will have to answer the same question when it lands; the answer this establishes is *configure at build, do not install at boot*.

### `make()` configures the panel before its id is set

Filament's `Panel::make()` is `app(static::class)` and configures nothing; `configure()` is called from exactly one place, `HasId::id()`, immediately after the assignment.
So every `Panel::configureUsing()` closure Filament runs sees a panel that already knows its id, and a closure keyed on it — `fn (Panel $panel) => $panel->getId() === 'admin' ? … : …` — works.

Ours follows the house `make()` shape instead: `Panel::make()` calls `newInstance()` then `configure()`, the same two lines every other Sylament component's factory has, and `id()` is a plain write-once setter.
The ordering inverts — configuration runs first, the id arrives after — and the cost is narrow but real: a `Panel::configureUsing()` closure that reads `getId()` does not see a different id, it throws, because `HasId::getId()` throws a `LogicException` while no id is set.
Nothing in-repo does that (the panels suite registers no panel-level `configureUsing()`), so the divergence is recorded rather than fixed.
Adopting Filament's placement would mean `id()` calling `configure()`, which double-configures every panel built through `make()` unless `make()` stops doing it — a change to the one factory shape the whole toolkit shares, for a closure nobody has written yet.
Revisit the moment a consumer hits it.

### `getBrandName()` returns null where Filament falls back to the app name

Filament's `getBrandName()` ends `?? config('app.name')`, so an unconfigured panel still prints *Laravel* in its `<title>` and brand slot.
Symfony has no framework-owned application name to fall back to — there is no `framework.name`, and `kernel.project_dir`'s basename is a directory, not a product — so ours returns `null` and the [document template](routing.md) drops the title suffix entirely rather than printing a placeholder.
A consumer who wants one calls `brandName()`, which is the call a Filament user makes anyway the first time they see the default.

### `getPages()` re-indexes where Filament keeps `array_unique()`'s gapped keys

Filament returns `array_unique($this->pages)` directly, which preserves the original keys and therefore leaves gaps wherever a duplicate was removed.
Ours wraps it in `array_values()`.
Order is unchanged and the surviving entry is still the first occurrence — the only difference is that the result is a list.
It matters because the return is a collection every caller iterates or serialises, and a gapped integer-keyed array is the shape that silently becomes a JSON object instead of an array the first time something encodes it.
Nothing here depends on the keys, so there is nothing to lose by dropping them.
`getResources()` is built the same way, for the same reason.
