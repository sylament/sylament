---
description: 'The panel route loader, the page controller every panel route points at, and the document and page templates it renders.'
ports:
  - vendor/filament/filament/routes/web.php
  - vendor/filament/filament/src/Pages/Concerns/HasRoutes.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php
  - vendor/filament/filament/resources/views/components/layout/base.blade.php
  - vendor/filament/filament/resources/views/components/page/index.blade.php
owns:
  - packages/panels/src/Controllers/PageController.php
  - packages/panels/templates/partials/Document/index.html.twig
---
# Routing (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Routing\PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php) | [`routes/web.php`](../../../vendor/filament/filament/routes/web.php) — the per-panel route group and its `$page::registerRoutes($panel)` / `$resource::registerRoutes($panel)` loops, [`Pages/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Pages/Concerns/HasRoutes.php) `routes()`, [`Resources/Resource/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) `registerRoutes()` | in progress | — | — |
| [`Controllers\PageController`](../../../packages/panels/src/Controllers/PageController.php) | no Filament counterpart — a Filament page **is** its own route action | in progress | — | — |
| [Document template](../../../packages/panels/templates/partials/Document/index.html.twig) | [`components/layout/base.blade.php`](../../../vendor/filament/filament/resources/views/components/layout/base.blade.php) | in progress | — | — |
| [Page template](../../../packages/panels/templates/partials/Page/index.html.twig) | [`components/page/index.blade.php`](../../../vendor/filament/filament/resources/views/components/page/index.blade.php) | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

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
The route name is the parent's plus the kebab-cased **relationship**, not the child's slug, and the duplicate-name guard below is what keeps two children of one parent apart.
Such a route carries a fourth default, `_sylament_parent_params` — the ancestor parameter names, root-first — which `PageController` reads to fold exactly those route parameters into the page's one `parentRecords` prop, unsetting each so none lands in the component's attribute bag.
A flat route carries neither.
The loader refuses two shapes Filament builds silently: a chain naming a resource the panel does not register, and a parent parameter colliding with `record` or with an ancestor's.

Two things beyond mounting.
Every page class file, every resource class file and every provider class file is added to the collection as a `FileResource`: the routes are derived from method bodies (`getSlug()`, `$routePath`, `getPages()`, `panel()`) that Symfony's `ObjectLoader` cannot see — it tracks only the loader class and its parents — so without them a dev-mode route cache would survive a renamed slug.
And an entry that is not the subclass it claims to be throws a `LogicException` naming the class, at route-build time rather than at the request that finally hits it: `pages()` entries against `Page`, `resources()` entries against `Resource`, and a resource's own `getPages()` entries against `Page`.
A third refusal has the same shape: [every `{placeholder}` in a mounted path must reach the page](custom-pages.md#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not) — through a parent key the loader folds itself, a `mount()` parameter, or a public property — else ux paints the leftover onto the live root as a stray HTML attribute and nothing reports it.

Nothing is silently overwritten, either.
`RouteCollection::add()` unsets a name before assigning it and checks nothing, so two mounts deriving one route name used to leave the later one standing and the earlier one unreachable — with no error at route-build time, at request time or in `debug:router` — and resources multiply the ways to collide (two pages sharing a `$slug`, two resources sharing a slug, a resource page landing on a page's name).
Every mount now goes through one `addRoute()` that records the name's owner and throws a `LogicException` naming both mounts when a second claims it.
The compile-time [component-name check](page.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute) does not cover this and cannot: it keys on the FQCN, which is exactly what differs when two classes derive one slug.

The guard covers **names**, not **paths**.
Two mounts that derive one path under two different names both reach the collection, and the router answers one of them — first-wins, silently, exactly the way the name collision used to behave.
That stays unguarded on purpose.
A path collision is at least *visible*: both routes are listed in `debug:router`, with their paths, where a name collision showed up nowhere at all.
And a useful path guard would have to reason about placeholder equivalence (`/{record}/edit` and `/{id}/edit` are the same path) and about matcher precedence between a static and a dynamic segment — router work, not loader work, and duplicating it would only add a second opinion that can disagree with the real matcher.
Revisit if a real panel hits it.

Of the three name-collision *kinds* the guard covers, two are pinned ([`PanelRouteLoaderTest`](../../../packages/panels/tests/Unit/Routing/PanelRouteLoaderTest.php) covers two pages sharing a slug and two resources sharing one).
The third — a resource page landing on a panel page's name — is deliberately left unpinned: `addRoute()` is one method with one `$owners` map and no per-kind branch, so a third case would exercise the same three lines a third time and pin nothing the other two do not.
It would only earn its keep if the owner tracking ever split per kind.

[`PageController`](../../../packages/panels/src/Controllers/PageController.php) is the single action every panel route points at.
It resolves the panel through `CurrentPanel`, resolves the page **service** from the class-keyed `ServiceLocator` [`PageComponentPass`](../../../packages/panels/src/DependencyInjection/Compiler/PageComponentPass.php) builds, and renders the Document template with `panel`, `pageTitle`, `layout`, `maxContentWidthClass`, `hasTopbar`, `bodyAttributes`, `componentName` and `componentProps` — the last six all read off the **un-mounted** locator instance, like `getTitle()`, so none of them may depend on mounted state.
Each failure mode is its own `LogicException` naming the missing piece — a page that is not a registered service names the registration to add, since a page reached through `new` would have no dependencies injected, and a page naming a layout the toolkit does not ship names both shipped ones ([why only two](custom-pages.md#a-third-shell-layout-is-a-document-override-not-a-getlayout-value)).

`componentProps` is the route → component bridge, and the rule is one line plus the nested arm above: every matched route parameter (`_route_params`) whose name does not start with `_`, after the ancestor parameters have been folded into `parentRecords`.
That drops the two `_sylament_*` defaults the loader writes and keeps the user's own placeholders, so `/admin/articles/{record}/edit` hands the page `{record: '12'}`.
The document spreads it into `component()`, where ux-twig-component matches each entry against the page's `mount()` parameters **by name**, then against writable properties, and turns whatever is left into an HTML attribute on the page's root element — so a placeholder no page mounts paints itself into the markup rather than erroring, which is the one shape to watch for and which [the loader now refuses at build time](custom-pages.md#the-loader-refuses-a-placeholder-the-page-cannot-mount-which-filament-does-not).
A prop must be a `#[LiveProp]` to survive past the first render: only live props are dehydrated into `data-live-props-value`, and a plain public property is gone by the time the component posts back.

A page can 404 from its `mount()`, and the controller is what lets that reach the kernel.
The mount runs **inside** `Twig::render()` — the document is what invokes the component — and Twig wraps any non-`Twig\Error` thrown during a render in a `RuntimeError`, so a `NotFoundHttpException` would arrive at the kernel as a template error and be answered 500.
The controller catches `RuntimeError` and rethrows its previous exception when that is an `HttpExceptionInterface`, [unwrapping the wrapper and nothing else](#a-mount-time-http-exception-is-unwrapped-from-twigs-render-error).

The two templates split the document from the page.
`Document/index.html.twig` is the `<html>` shell — title (the page title, then the brand name when the panel has one), the theme head script, `sylamentStyles()`, the importmap, a `<body>` carrying whatever [`getExtraBodyAttributes()`](custom-pages.md#a-page-with-no-body-attributes-still-renders-a-stray-space) answered, the shell component with the page's live component in its content block (invoked by the tag name `Page::getComponentName()` returns, and given `componentProps`), and the notifications outlet.
The shell is an `{% if %}` over two literal `<twig:>` tags rather than one: `SylamentPanels:SimpleLayout` when the page's [`getLayout()`](custom-pages.md#a-third-shell-layout-is-a-document-override-not-a-getlayout-value) named it, `SylamentPanels:Layout` otherwise, both handed the `maxContentWidthClass` the controller [resolved for this request](simple-page.md#the-width-resolution-is-one-ladder-in-the-controller).
The `component()` function is not used for the shell because a function call cannot take a `<twig:block>`, which is how the page reaches it.

**The head script is the one inline `<script>` in the whole port**, and it is there because nothing else can be.
It reads `localStorage['sylament.theme']` and stamps `dark` on `<html>` **before the stylesheet is requested**, which is the only placement that avoids a light flash on every dark-mode page load; a Stimulus controller connects far too late, and [the shell is otherwise Turbo-indifferent](layout-templates.md#the-shell-is-turbo-indifferent-and-worker-safe) precisely to avoid inline scripts.
It is not a second implementation of the controller's rule but the same one, written twice and required to agree on all six arms — explicit `dark` wins, explicit `light` wins, and *everything else* — absent key, unreadable storage, unrecognised value — means system:

```js
theme === 'dark' || (theme !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)
```

Spelled as `(theme === null || theme === 'system') && matches` it would silently disagree with the controller for an unrecognised stored value, leaving the page light until Stimulus booted and then flipping it.
The storage read is `try`- guarded, so a browser refusing storage falls to the same system arm rather than throwing before the stylesheet loads.
[`PageControllerTest`](../../../packages/panels/tests/Integration/Controllers/PageControllerTest.php) pins both the body and its position ahead of the stylesheet block.

The notifications outlet is `<twig:SylamentNotifications:Notifications />` as a direct `<body>` child outside the layout — the position Filament's `@livewire(Filament\Livewire\Notifications::class)` holds in `base.blade.php` — and it is the document's job rather than the application's now that panels declares `sylament/notifications`.
It is also why a panel application needs `framework.session` enabled: the outlet drains the flash bag on **every** page render, and `RequestStack::getSession()` throws `SessionNotFoundException` on a request that carries none — so a session-less panel fails on its first page, not on its first toast.
The **actions** outlet deliberately is not here, and Filament agrees: `<x-filament-actions::modals />` sits in its *page* view, not its layout.
Ours has a second, harder reason — [`Modals`](../actions/has-actions.md) resolves its host live component by walking ux-twig-component's `ComponentStack`, so it only works rendered inside the component whose actions it shows, which a document-level outlet is not.
`Page/index.html.twig` is what `Page::getTemplate()` falls back to, and it is **the chrome every other page template extends** rather than a skeleton each of them repeats.
It renders a `sy-pa-page` root — its classes coming from [`getPageClasses()`](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class) — then three nested elements: `sy-pa-page-header-main-ctn` wrapping the `Header` (rendered when the page has header actions, breadcrumbs, a heading or a subheading) above `sy-pa-page-main`, which holds `sy-pa-page-content`; the `<twig:SylamentActions:Modals />` outlet follows the header-main container as the root's child, and the [unsaved-changes sentinel](unsaved-changes-alert.md#the-sentinel-is-an-inner-element-because-a-second-data-controller-would-take-the-live-binding-down) follows that as the root's last child when the page or its panel arms the guard.

Three named blocks are the seam a page fills — `header`, `content` and `footer`, with `content` defaulting to `{{ renderSchema('content') }}`; two variants beside the chrome fill `content` for the shapes the toolkit ships ([`Page/form.html.twig`](form-actions.md) and `Page/table.html.twig`), and `EditRecord/index.html.twig` is a seven-line child of the form one.
The [whole block contract](custom-pages.md#the-block-contract-is-the-page-bodys-public-api) is `custom-pages.md`'s.
The three classes are not decoration: they carry the whole vertical rhythm, the wrapper supplying `py-8 gap-y-8` and `sy-pa-page-content` the `gap-y-8` between whatever a page stacks inside it, so a page that renders its own root and skips the layers loses its spacing with nothing to see in the markup — which is now something a page has to go out of its way to do, the chrome supplying them to every child.
`sy-pa-page-main` is a [divergence](assets.md#the-pages-main-container-is-a-flex-column-where-v5-gates-that-on-a-full-height-page).
All of them are `partials/`, not `components/` — they are rendered from PHP (a controller and a `template_from_method` getter), never as a `<twig:>` tag, and that is load-bearing rather than filing: `renderSchema()`, `renderTable()` and `live_action()` read `this` out of the Twig context, which inside a `<twig:>` component's block body is that component rather than the page.

Four divergences are theirs: [a page is rendered by a controller rather than routed to directly](#a-panel-page-is-rendered-by-a-controller-not-routed-to-directly), [the document calls `importmap()` unguarded](#the-document-calls-importmap-unguarded), [the route import carries no prefix](#the-route-import-carries-no-prefix-because-the-panel-path-is-authoritative), and [a mount-time HTTP exception is unwrapped from Twig's render error](#a-mount-time-http-exception-is-unwrapped-from-twigs-render-error).

## Divergences

### A panel page is rendered by a controller, not routed to directly

Filament mounts a page with `Route::get($routePath, static::class)` — the Livewire component class *is* the route action, and Livewire's own request handling turns it into a full page render.
`symfony/ux-live-component` has no such arm: its single route (`ux_live_component`) serves component **actions** against an already-rendered component, never a page load.
So the route action is [`PageController`](#panelrouteloader-mounts-the-whole-panel-surface-in-one-pass-over-the-panel-registry) for every page, the page class travels as the `_sylament_page` route default, and the page is reached as a service through the class-keyed locator rather than instantiated by the router.
What a user writes is unchanged — a `Page` subclass listed in `pages()` — and the page is still a live component once rendered, so its actions round-trip through `ux_live_component` exactly as Filament's do through Livewire's.

One wiring consequence falls out of it, and it is invisible until a panel is mounted near the root: `ux_live_component`'s path is `/{_live_component}/{_live_action}`, so an application importing that route without the recipe's `/_components` prefix has it match `/admin` first and every panel page 404s with *Component "admin" not found*.
The panels test kernel mounts it under the prefix for that reason.

### The document calls `importmap()` unguarded

`Document/index.html.twig` calls `importmap('app')` with no check that AssetMapper is installed, so a consumer without `symfony/asset-mapper` gets an `Unknown "importmap" function` error on the first panel request.
That is the intended behaviour, not an oversight.
A guard cannot be written cheaply anyway — Twig resolves a function at **compile** time, so a call inside a dead `{% if %}` branch still has to exist — and, more decisively, the shell's JS (the sidebar Stimulus controller, live components) has no supported delivery path outside AssetMapper today.
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
[A page is rendered by a controller](#a-panel-page-is-rendered-by-a-controller-not-routed-to-directly), the controller renders the document, and the document is what invokes the page's component — so the mount runs inside `Twig::render()`, and `Twig\Template` wraps every non-`Twig\Error` thrown during a render in a `RuntimeError` carrying the original as its previous.
Left alone, every mount-time `NotFoundHttpException` is answered **500**, with the real exception two frames down in a stack trace.

So [`PageController`](../../../packages/panels/src/Controllers/PageController.php) catches `RuntimeError`, and rethrows `getPrevious()` when it is an `HttpExceptionInterface` — otherwise it rethrows the `RuntimeError` untouched, so a genuine template bug still reads as one.
One level is exactly enough and no loop is wanted: Twig re-throws a `Twig\Error` from an enclosing template or block as-is (only a *non*-`Error` gets wrapped), so however deep the component sits, there is one wrapper.
The unwrap is deliberately narrow — an `HttpExceptionInterface` is a value the kernel knows how to answer, and nothing else qualifies.

The general shape is worth naming, because it is not only about 404s: anything a page's `mount()` throws to the *framework* rather than to the user has to survive this trip, and a redirect-by-exception or an `AccessDeniedException` will want the same unwrap when authorization lands.
