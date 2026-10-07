---
description: 'The panel route loader, the page controller every panel route points at, and the document template it renders.'
ports:
  - vendor/filament/filament/routes/web.php
  - vendor/filament/filament/src/Pages/Concerns/HasRoutes.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php
  - vendor/filament/filament/resources/views/components/layout/base.blade.php
owns:
  - packages/panels/src/Controllers/PageController.php
  - packages/panels/src/Routing/
  - packages/panels/templates/partials/Document/index.html.twig
---
# Routing (panels)

[`PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php) turns every registered panel into routes, [`PageController`](../../../packages/panels/src/Controllers/PageController.php) is the action each of them points at, and the [Document template](../../../packages/panels/templates/partials/Document/index.html.twig) is the `<html>` shell the controller renders around the page.
The page chrome the page's own component renders is [custom-pages.md](custom-pages.md#the-block-contract-is-the-page-bodys-public-api)'s.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php), the page family | [`routes/web.php`](../../../vendor/filament/filament/routes/web.php)'s page loop and [`Pages/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasRoutes.php)' `registerRoutes()` / `routes()` | done | — | [semantics](#panelrouteloader-mounts-the-whole-panel-surface-in-one-pass-over-the-panel-registry) · [diverges](#the-route-import-carries-no-prefix-because-the-panel-path-is-authoritative) |
| [`PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php), the resource family and its nesting | [`Resources/Resource/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php)' `registerRoutes()` / `routes()` | done | — | [semantics](#panelrouteloader-mounts-the-whole-panel-surface-in-one-pass-over-the-panel-registry) · [nested-resources.md](nested-resources.md) |
| [`PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php), the route-name collision guard | no Filament counterpart | done | — | [diverges](#two-mounts-deriving-one-route-name-throw-and-a-path-collision-stays-unguarded) |
| [`PanelRouteLoader`'s placeholder guard](../../../packages/panels/src/Routing/PanelRouteLoader.php) | no Filament counterpart | done | — | [diverges](#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not) · [diverges](#the-loader-narrows-a-record-placeholder-a-separator-follows-because-the-compilers-default-excludes-it) |
| [`PageController`](../../../packages/panels/src/Controllers/PageController.php) | no Filament counterpart: a Filament page is its own route action | done | — | [diverges](#a-panel-page-is-rendered-by-a-controller-not-routed-to-directly) · [semantics](#the-controller-reads-the-document-values-off-the-un-mounted-page) |
| [`PageController`](../../../packages/panels/src/Controllers/PageController.php), the route parameters as component props | no Filament counterpart | done | — | [semantics](#componentprops-hands-the-page-every-route-parameter-not-starting-with-_) |
| [`PageController`](../../../packages/panels/src/Controllers/PageController.php), the mount-time HTTP exception unwrap | no Filament counterpart | done | — | [diverges](#a-mount-time-http-exception-is-unwrapped-from-twigs-render-error) |
| [Document template](../../../packages/panels/templates/partials/Document/index.html.twig) | [`components/layout/base.blade.php`](../../../vendor/filament/filament/resources/views/components/layout/base.blade.php) | in progress | — | [detail](#the-document-carries-the-shell-and-none-of-the-panels-appearance-settings) · [diverges](#the-document-calls-importmap-unguarded) |
| The document's theme head script | `base.blade.php`'s dark-mode script | done | — | [semantics](#the-head-script-is-the-one-inline-script-in-the-port-and-agrees-with-the-theme-controller) |
| The document's notifications outlet | `@livewire(Notifications::class)` in `base.blade.php` | done | — | [semantics](#the-notifications-outlet-is-the-documents-and-the-actions-outlet-is-not) |
| _rest not audited yet_ | — | — | — | — |

## Details

### The document carries the shell and none of the panel's appearance settings

`Document/index.html.twig` renders the title (the page title, then the brand name when the panel has one), the theme head script, `sylamentStyles()`, the importmap, a `<body>` carrying whatever [`getExtraBodyAttributes()`](custom-pages.md#a-page-with-no-body-attributes-still-renders-a-stray-space) answered, the shell component with the page's live component in its content block, and the notifications outlet.
The shell is an `{% if %}` over two literal `<twig:>` tags: `SylamentPanels:SimpleLayout` when the page's [`getLayout()`](custom-pages.md#a-third-shell-layout-is-a-document-override-not-a-getlayout-value) named it, `SylamentPanels:Layout` otherwise, both handed the `maxContentWidthClass` the controller [resolved for this request](simple-page.md#the-width-resolution-is-one-ladder-in-the-controller).
The `component()` function is not used for the shell because a function call cannot take a `<twig:block>`, which is how the page reaches it.

Filament's `base.blade.php` also carries what the unported panel traits configure: the `dir` attribute, the CSRF meta tag, the favicon, the theme and font links, the sidebar-width and font custom properties, the dark-mode configuration arms, the `fi-panel-<id>` body class, the render hooks and broadcasting.
Each lands with the `Panel/Concerns` trait that configures it.

## Semantics

### `PanelRouteLoader` mounts the whole panel surface in one pass over the panel registry

[`PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php) is a service route loader — tagged `routing.route_loader`, imported with the `service` type — so the whole panel surface reaches the router in one pass over `PanelRegistry::all()`.
For every page a panel registers it mounts one `GET` route at the panel path plus the page's route path, named [`Page::getRouteName($panel)`](page.md), carrying three defaults: `_controller` (always `PageController`), `_sylament_panel` (the id [`CurrentPanel`](panel.md#the-current-panel-is-derived-from-the-request-not-stored-on-a-manager) reads back) and `_sylament_page` (the page class the controller resolves).
The two halves are joined by trimming the concatenation on both sides and re-prefixing a single `/`, which is what makes the two degenerate cases work: `Dashboard`'s `/` route path collapses onto the panel root rather than leaving a trailing slash (`path('admin')` puts the dashboard at `/admin`), and a panel left at the default empty path mounts at the site root — `''` joined with `/settings-page` is `/settings-page`, not `//settings-page`, and its dashboard is `/`.
Trimming only the trailing slash would leave that leading pair in place, and a `//`-pathed route matches nothing.

Every resource a panel registers mounts a second family in the same pass, one `GET` route per [`getPages()`](resource.md) entry.
The name is `Resource::getRouteBaseName($panel)` plus the registration **key** — `sylament.<panel>.resources.<slug>.index`, `….create`, `….edit` — and the path joins three pieces through the same trim-and-re-prefix, so an `index` registered at `/` collapses onto the resource root (`/admin/articles`) while `/{record}/edit` keeps its placeholder (`/admin/articles/{record}/edit`).
The defaults are the page family's three verbatim: `_sylament_page` names the **page** class, never the resource, which is what lets one `PageController` serve both families — a resource page finds its resource through its own `$resource` static, not off the route.
Building these routes resolves each resource **out of the container**, the loader holding [`ResourceRegistry`](resource.md) beside the panel one, because a slug and a page map are instance methods here where Filament's are statics: route loading is therefore the first thing in a request that instantiates a resource service, and `getPages()` runs once at route-build time rather than per request.

A resource that [names a parent](nested-resources.md) mounts the same family one level deeper: the loader walks the registration chain root-first, prefixing `<parent slug>/{<parent parameter>}` per ancestor, so `sylament.admin.resources.articles.sections.edit` sits at `/admin/articles/{article}/sections/{record}/edit`.
The route name is the parent's plus the kebab-cased **relationship**, not the child's slug, and [the duplicate-name guard](#two-mounts-deriving-one-route-name-throw-and-a-path-collision-stays-unguarded) is what keeps two children of one parent apart.
Such a route carries a fourth default, `_sylament_parent_params` — the ancestor parameter names, root-first — which `PageController` reads to fold exactly those route parameters into the page's one `parentRecords` prop, unsetting each so none lands in the component's attribute bag.
A flat route carries neither.
The loader refuses two shapes Filament builds silently: a chain naming a resource the panel does not register, and a parent parameter colliding with `record` or with an ancestor's.

Two things beyond mounting.
Every page class file, every resource class file and every provider class file is added to the collection as a `FileResource`: the routes are derived from method bodies (`getSlug()`, `$routePath`, `getPages()`, `panel()`) that Symfony's `ObjectLoader` cannot see — it tracks only the loader class and its parents — so without them a dev-mode route cache would survive a renamed slug.
And an entry that is not the subclass it claims to be throws a `LogicException` naming the class, at route-build time rather than at the request that finally hits it: `pages()` entries against `Page`, `resources()` entries against `Resource`, and a resource's own `getPages()` entries against `Page`.
A third refusal has the same shape: [every `{placeholder}` in a mounted path must reach the page](#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not) — through a parent key the loader folds itself, a `mount()` parameter, or a public property — else ux paints the leftover onto the live root as a stray HTML attribute and nothing reports it.

### The controller reads the document values off the un-mounted page

`PageController` resolves the panel through `CurrentPanel`, resolves the page **service** from the class-keyed `ServiceLocator` [`PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php) builds, and renders the Document template with `panel`, `pageTitle`, `layout`, `maxContentWidthClass`, `hasTopbar`, `bodyAttributes`, `componentName` and `componentProps`.
`pageTitle`, `layout`, `maxContentWidthClass`, `hasTopbar` and `bodyAttributes` are read off the **un-mounted** locator instance, so none of the page methods behind them may depend on mounted state.
Each failure mode is its own `LogicException` naming the missing piece — a page that is not a registered service names the registration to add, since a page reached through `new` would have no dependencies injected, and a page naming a layout the toolkit does not ship names both shipped ones ([why only two](custom-pages.md#a-third-shell-layout-is-a-document-override-not-a-getlayout-value)).

### `componentProps` hands the page every route parameter not starting with `_`

`componentProps` is the route → component bridge: every matched route parameter (`_route_params`) whose name does not start with `_`, after the ancestor parameters have been folded into `parentRecords`.
That drops the `_sylament_*` defaults the loader writes and keeps the user's own placeholders, so `/admin/articles/{record}/edit` hands the page `{record: '12'}`.
The document spreads it into `component()`, where ux-twig-component matches each entry against the page's `mount()` parameters **by name**, then against writable properties, and turns whatever is left into an HTML attribute on the page's root element — so a placeholder no page mounts would paint itself into the markup rather than erroring, which is why [the loader refuses it at build time](#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not).
A prop must be a `#[LiveProp]` to survive past the first render: only live props are dehydrated into `data-live-props-value`, and a plain public property is gone by the time the component posts back.

### The head script is the one inline `<script>` in the port, and agrees with the theme controller

It is there because nothing else can be.
It reads `localStorage['sylament.theme']` and stamps `dark` on `<html>` **before the stylesheet is requested**, which is the only placement that avoids a light flash on every dark-mode page load; a Stimulus controller connects far too late, and [the shell is otherwise Turbo-indifferent](layout-templates.md#the-shell-is-turbo-indifferent-and-worker-safe) precisely to avoid inline scripts.
It is not a second implementation of the controller's rule but the same one, written twice and required to agree on all six arms — explicit `dark` wins, explicit `light` wins, and *everything else* — absent key, unreadable storage, unrecognised value, explicit `system` — means system:

```js
theme === 'dark' || (theme !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)
```

Spelled as `(theme === null || theme === 'system') && matches` it would silently disagree with the controller for an unrecognised stored value, leaving the page light until Stimulus booted and then flipping it.
The storage read is `try`-guarded, so a browser refusing storage falls to the same system arm rather than throwing before the stylesheet loads.

### The notifications outlet is the document's, and the actions outlet is not

The notifications outlet is `<twig:SylamentNotifications:Notifications />` as a direct `<body>` child outside the layout — the position Filament's `@livewire(Filament\Livewire\Notifications::class)` holds in `base.blade.php` — and it is the document's job rather than the application's because panels requires `sylament/notifications`.
It is also why a panel application needs `framework.session` enabled: the outlet drains the flash bag on **every** page render, and `RequestStack::getSession()` throws `SessionNotFoundException` on a request that carries none — so a session-less panel fails on its first page, not on its first toast.
The **actions** outlet deliberately is not here, and Filament agrees: `<x-filament-actions::modals />` sits in its *page* view, not its layout.
Ours has a second, harder reason — [`Modals`](../actions/has-actions.md) resolves its host live component by walking ux-twig-component's `ComponentStack`, so it only works rendered inside the component whose actions it shows, which a document-level outlet is not.

## Divergences

### A panel page is rendered by a controller, not routed to directly

Filament mounts a page with `Route::get($routePath, static::class)` — the Livewire component class *is* the route action, and Livewire's own request handling turns it into a full page render.
`symfony/ux-live-component` has no such arm: its single route (`ux_live_component`) serves component **actions** against an already-rendered component, never a page load.
So the route action is [`PageController`](#the-controller-reads-the-document-values-off-the-un-mounted-page) for every page, the page class travels as the `_sylament_page` route default, and the page is reached as a service through the class-keyed locator rather than instantiated by the router.
What a user writes is unchanged — a `Page` subclass listed in `pages()` — and the page is still a live component once rendered, so its actions round-trip through `ux_live_component` exactly as Filament's do through Livewire's.

One wiring consequence falls out of it, and it is invisible until a panel is mounted near the root: `ux_live_component`'s path is `/{_live_component}/{_live_action}`, so an application importing that route without the recipe's `/_components` prefix has it match `/admin` first and every panel page 404s with *Component "admin" not found*.
The panels test kernel mounts it under the prefix for that reason.

### The loader refuses a placeholder the page cannot mount, which Filament does not

For every `{name}` in a mounted path, [`PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php) requires one of three things: the name is a **parent parameter** the loader folds itself, the page's `mount()` **declares a parameter** of that name, or the page declares a **public property** of that name.
Otherwise it throws a `LogicException` naming the page, the path and the placeholder, at route-build time, the shape its collision guards use.

Filament needs no such guard because a Livewire page *is* its own route action and an unmatched parameter is a missing-argument error.
Here the parameter is handed to ux-twig-component, which matches it against `mount()` parameters, then against writable properties, and paints whatever is left onto the live root as an HTML attribute — no exception, no log, and a page that silently shows the wrong record or none at all.

The shipped guard reads placeholders with `/\{([^}]+)\}/`, so a Symfony inline requirement is captured **whole**: a path written `/{record<\d+>}` would be checked for a parameter literally named `record<\d+>` and refused.
Nothing in the toolkit or the demo writes one — a page path is a slug and a bare `{record}` — so the shape is recorded rather than worked around; normalising the capture is a one-line change if a consumer ever wants inline requirements.

### The loader narrows a record placeholder a separator follows, because the compiler's default excludes it

`RouteCompiler` compiles a placeholder to a pattern that excludes the **next static separator** as well as `/`: `/{record}/edit` gives `[^/]++`, but `/{record}-preview` gives `[^/\-]++` and `/{record}~preview` gives `[^/~]++`.
Four characters of the [key grammar](../schemas/record-key-contract.md#the-grammar) are in `RouteCompiler::SEPARATORS` — `-`, `_`, `~` and `=` — and the first two are *safe* bytes the codec never escapes, so the trap is not specific to composite keys: a UUID or slug `$recordRouteKeyName` already breaks against `/{record}-preview`.
And it fails at **generation**, not at match — `UrlGenerator::generate()` raises `InvalidParameterException`, so the **listing** that mints the row URL 500s and the record page is never reached, or, under `framework.router.strict_requirements: null`, the link generates and the click 404s.
"It 404s" sends a debugger to the wrong page.

So `PanelRouteLoader` stamps `requirements: ['record' => '[^/]+']` on a mounted path whose `{record}` is followed, up to the next `/` or the end of the path, by non-empty **pure static** text — text carrying no `{` — that opens on a separator other than `/`.
A declared requirement replaces the compiler's default wholesale, separator exclusion included, which is the whole fix.
Three properties of it are load-bearing.
It is **conditional**, so every path in this toolkit and in the demo — all `/{record}/…` or a bare `/{record}` — compiles to the compiler's own default pattern.
It must stay **greedy**: the possessive `[^/]++` never gives the trailing static text back, so it generates a URL that then matches nothing, which is exactly the "tidy the regex to match the compiler's default" change a later reader makes.
And only `{record}` is stamped — a parent placeholder is always followed by `/`, since the loader writes that slash itself, and any other placeholder on a page path is the developer's own, where narrowing it would change how *their* value splits.

The rule is *any* non-slash separator, so `/{record}.pdf` is narrowed too.
No **composite** key contains a dot and `.` is escaped into the pattern either way, so all the stamp buys there is that a single identifier holding one stops being refused.

**Pure static** is what keeps the stamp from taking a URL apart differently.
A greedy `[^/]+` runs to the **last** occurrence of the separator, so on a segment carrying a second placeholder it would re-split a URL that works unstamped: `/{record}-{locale}` reads `/5-en-GB` as `5` and `en-GB`, and a stamped pattern would read it `5-en` and `GB`.
Such a segment is therefore left to the compiler's default pattern, and two residuals follow.
On a placeholder-bearing segment a key containing that separator still cannot be generated — declare the page `/{record}/…` instead.
And where the stamp does apply, an earlier-registered `/{record}-x` claims `/5-y-x` with `record = "5-y"`, where without the stamp a later `/{record}-y-x` would match it with `record = "5"`: order-dependent, and visible only to an app that registered both shapes.

`PageRegistration` grows no `requirements()` member to go with it.
The narrowing is computed from the path the page already declares, and a second place to narrow a route from is a second place for the two to disagree; a consumer's own placeholder is theirs to name in the path.

Three shapes it does not touch.
A bare `/{record}` registered **before** `/{record}-preview` matches `/5-preview` with `record = "5-preview"` — route order decides it, the loader registers a resource's pages in `getPages()` order, and that is Symfony's matching, not ours.
And a Symfony inline requirement such as `/{record<\d+>}` can never match a composite key, which is [never numeric](../schemas/record-key-contract.md#the-grammar) — though the guard above refuses that path first, for its own reason.
A `{record}` written with any of Symfony's inline syntaxes — `{record<\d+>}`, `{record?}`, `{!record}` — is read raw by the requirement test and is never stamped either.

### Two mounts deriving one route name throw, and a path collision stays unguarded

`RouteCollection::add()` unsets a name before assigning it and checks nothing, so two mounts deriving one route name would leave the later one standing and the earlier one unreachable — with no error at route-build time, at request time or in `debug:router` — and resources multiply the ways to collide (two pages sharing a `$slug`, two resources sharing a slug, a resource page landing on a page's name).
Every mount goes through one `addRoute()` that records the name's owner and throws a `LogicException` naming both mounts when a second claims it.
The compile-time [component-name check](page.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute) does not cover this and cannot: it keys on the FQCN, which is exactly what differs when two classes derive one slug.
Two of the three collision kinds are pinned by [`PanelRouteLoaderTest`](../../../packages/panels/tests/Unit/Routing/PanelRouteLoaderTest.php); the third, a resource page landing on a panel page's name, runs through the same `$owners` map with no per-kind branch.

The guard covers **names**, not **paths**.
Two mounts that derive one path under two different names both reach the collection, and the router answers one of them — first-wins, silently.
That stays unguarded on purpose.
A path collision is at least *visible*: both routes are listed in `debug:router`, with their paths, where a name collision shows up nowhere at all.
And a useful path guard would have to reason about placeholder equivalence (`/{record}/edit` and `/{id}/edit` are the same path) and about matcher precedence between a static and a dynamic segment — router work, not loader work, and duplicating it would only add a second opinion that can disagree with the real matcher.
Revisit if a real panel hits it.

### The document calls `importmap()` unguarded

`Document/index.html.twig` calls `importmap('app')` with no check that AssetMapper is installed, so a consumer without `symfony/asset-mapper` gets an `Unknown "importmap" function` error on the first panel request.
That is the intended behaviour, not an oversight.
A guard cannot be written cheaply anyway — Twig resolves a function at **compile** time, so a call inside a dead `{% if %}` branch still has to exist — and, more decisively, the shell's JS (the sidebar Stimulus controller, live components) has no supported delivery path outside AssetMapper.
Rendering a silently script-less shell would hand the user a drawer that never opens and a page whose actions do nothing, with nothing in the output saying why; a hard error naming the missing function is the better failure.
`asset-mapper` therefore stays out of the package's composer `require` (it is the application's dependency, and the bundle's own [`prependExtension()` hook](../../../packages/panels/src/SylamentPanelsBundle.php) is guarded on it), and the entrypoint name is the Symfony-conventional `app` until panels grow asset settings of their own.

### The route import carries no prefix because the panel path is authoritative

Filament mounts each panel inside a `Route::prefix($panel->getPath())` group, so the path is applied by the router.
Ours cannot be: one [`routing.route_loader`](#panelrouteloader-mounts-the-whole-panel-surface-in-one-pass-over-the-panel-registry) pass covers **every** registered panel, and a single `import()` prefix cannot express a different path per panel.
So `PanelRouteLoader` joins the panel path itself and an application imports the loader bare:

```php
$routes->import('sylament_panels.route_loader', 'service');
```

Adding `->prefix('/admin')` there double-prefixes — a panel declaring `path('admin')` mounts at `/admin/admin` — and it fails as a 404 on every panel URL rather than as an error, because the routes exist and are simply somewhere else.
`Panel::path()` is the one place a panel's mount point is written.

### A mount-time HTTP exception is unwrapped from Twig's render error

An edit page whose `{record}` matches nothing 404s from `mount()`, and in Filament that needs no machinery: the page **is** the route action, so the exception its mount throws is already the one the framework answers.
Here it is not.
[A page is rendered by a controller](#a-panel-page-is-rendered-by-a-controller-not-routed-to-directly), the controller renders the document, and the document is what invokes the page's component — so the mount runs inside the Twig render, and `Twig\Template` wraps every non-`Twig\Error` thrown during a render in a `RuntimeError` carrying the original as its previous.
Left alone, every mount-time `NotFoundHttpException` is answered **500**, with the real exception two frames down in a stack trace.

So [`PageController`](../../../packages/panels/src/Controllers/PageController.php) catches `RuntimeError`, and rethrows `getPrevious()` when it is an `HttpExceptionInterface` — otherwise it rethrows the `RuntimeError` untouched, so a genuine template bug still reads as one.
One level is exactly enough and no loop is wanted: Twig re-throws a `Twig\Error` from an enclosing template or block as-is (only a *non*-`Error` gets wrapped), so however deep the component sits, there is one wrapper.
The unwrap is deliberately narrow — an `HttpExceptionInterface` is a value the kernel knows how to answer, and nothing else qualifies.

The general shape is worth naming, because it is not only about 404s: anything a page's `mount()` throws to the *framework* rather than to the user has to survive this trip, and a redirect-by-exception or an `AccessDeniedException` will want the same unwrap when authorization lands.
