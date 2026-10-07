---
description: 'The base every resource page extends: the resource hop, route registration, breadcrumb trail and host action defaults it adds to the panel page.'
ports:
  - vendor/filament/filament/src/Resources/Pages/Page.php
owns:
  - packages/panels/src/Resources/Pages/Page.php
---
# Resource pages (panels)

`Sylament\Panels\Resources\Pages\Page` extends the panel [`Page`](page.md), as Filament's does, so a resource page keeps the whole page surface — title, heading, template, live-component registration, the actions and schemas hosts — and adds what needs a resource.
It composes [`InteractsWithParentRecord`](nested-resources.md), as Filament's does; the record pages add [`InteractsWithRecord`](interacts-with-record.md) and [`HasRelationManagers`](has-relation-managers.md), and the list page tables' `HasTable`.
`getResourceUrl()` is [custom-pages.md](custom-pages.md#getresourceurl-fills-the-record-and-threads-the-parent-chain)'s row, and the band item seam and `getSubNavigationPosition()` are [has-sub-navigation.md](has-sub-navigation.md)'s.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$resource` / `getResource()`](../../../packages/panels/src/Resources/Pages/Page.php) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | done | — | [diverges](#getresourceinstance-is-the-hop-filaments-static-calls-cannot-make) |
| [`getResourceInstance()`](../../../packages/panels/src/Resources/Pages/Page.php) | — | done | — | [diverges](#getresourceinstance-is-the-hop-filaments-static-calls-cannot-make) |
| [`route()`](../../../packages/panels/src/Resources/Pages/Page.php) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | done | — | [diverges](#route-returns-a-registration-rather-than-registering-a-route) |
| `getRouteName()` / `getUrl()` / `getNavigationUrl()` / `getResourcePageName()` / `getNavigationItems()` / `getNavigationItemActiveRoutePattern()` / `shouldRegisterNavigation($parameters)` | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | todo | — | [detail](#a-resource-page-inherits-page-route-statics-that-answer-for-the-wrong-route) |
| `getEmailVerifiedMiddleware()` / `isEmailVerificationRequired()` | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | blocked | panel email verification | — |
| `getTenantSubscribedMiddleware()` / `isTenantSubscriptionRequired()` | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | blocked | panel tenancy | — |
| [`$breadcrumb` / `getBreadcrumb()`](../../../packages/panels/src/Resources/Pages/Page.php) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | done | — | [diverges](#getbreadcrumb-widens-to-stringmarkup) |
| [`getBreadcrumbs()` / `getResourceBreadcrumbs()` / `hasResourceBreadcrumbs()`](../../../packages/panels/src/Resources/Pages/Page.php) — the ancestor trail | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | done | — | [semantics](#the-trail-walks-the-already-resolved-ancestor-chain) · [nested-resources.md](nested-resources.md#a-crumb-whose-url-the-trail-already-spends-renders-unlinked) |
| The navigation-hierarchy and cluster arms of `getResourceBreadcrumbs()`, with `getNavigationBreadcrumbItemKey()` / `getNavigationBreadcrumbItemUrl()` / `getSubNavigationBreadcrumbItemKey()` / `getSubNavigationBreadcrumbItemUrl()` | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | blocked | `Panel::breadcrumbs()`'s navigation-hierarchy flag | [page.md](page.md#the-navigation-hierarchy-breadcrumbs-wait-on-the-panels-breadcrumb-settings) |
| `canAccess($parameters)`, `CanAuthorizeResourceAccess`, `getDefaultActionAuthorizationResponse()` / `getDefaultActionIndividualRecordAuthorizationResponseResolver()` | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | blocked | the resource's `HasAuthorization` | [resource.md](resource.md#no-resource-gates-anything-until-authorization-is-ported) |
| `getModel()` on the page | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | todo | — | [renames.md](../renames.md#the-model-vocabulary-collapses-onto-record) |
| `getModelLabel()` (deprecated) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | skipped | — | [diverges](#the-deprecated-page-level-getmodellabel-is-not-ported) |
| `getRenderHookScopes()` | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | blocked | render hooks | — |
| `afterActionCalled()` — the `RecordCreated` / `RecordSaved` / `RecordUpdated` events | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | todo | — | — |
| [`getDefaultActionRecordClass()` / `getDefaultActionRecordLabel()`](../../../packages/panels/src/Resources/Pages/Page.php) | `getDefaultActionModel()` / `getDefaultActionModelLabel()` | done | — | [semantics](#the-host-defaults-answer-off-the-resource) · [renames.md](../renames.md#the-model-vocabulary-collapses-onto-record) |
| [`getDefaultActionUrl()`](../../../packages/panels/src/Resources/Pages/Page.php) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | done | — | [semantics](#the-host-defaults-answer-off-the-resource) |
| [`getDefaultActionRelationship()`](../../../packages/panels/src/Resources/Pages/Page.php) — a modal create under the parent | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | done | — | [semantics](#the-host-defaults-answer-off-the-resource) |
| [`resolveMountedActionRecord()`](../../../packages/panels/src/Resources/Pages/Page.php) | — | done | — | [semantics](#a-mounted-frames-record-key-resolves-through-the-resources-own-query) |

## Details

### A resource page inherits page-route statics that answer for the wrong route

`Resources\Pages\Page` extends the panel [`Page`](page.md), so it inherits `getSlug()`, `getRoutePath()`, `getRouteName(Panel)`, `getNavigationItems(Panel)` and the navigation getters, every one of which describes a page mounted **directly on a panel**.
A resource page is not: its path comes from the `PageRegistration` its resource declares and its route name is `getRouteBaseName($panel) . '.' . $key`.
So `ListArticles::getRouteName($panel)` answers `sylament.admin.pages.list-articles`, a route that does not exist, and `getNavigationItems()` would build an item pointing at it.

Filament closes the same inheritance by overriding `getRouteName()` to read `static::getResource()::getRouteBaseName($panel) . '.' . static::getResourcePageName()`, and builds `getUrl()`, `getNavigationUrl()` and `getNavigationItems()` on it, with `getResourcePageName()` the reverse lookup of the page's own `getPages()` key.
Here both hops are instance methods on a service, and these are static methods with no `$this` to reach the registry from — the wall [the service decision](resource.md#resource-is-a-service-not-a-static-class-string-registry) puts in front of every resource static.
Fixing it means either turning the statics into instance methods, which changes a Filament API shape, or carrying the page's registration key on the instance; it waits for the task that needs a resource page to name its own route.
The sub-navigation band, which needs a sibling resource page's URL, [routes around it](has-sub-navigation.md#a-resource-page-item-is-built-by-the-band-because-a-page-cannot-name-its-own-route) by scanning the sibling's resource `getPages()` from a page instance.

Nothing in the framework calls those statics on a resource page: `Panel::getNavigation()` loops over `pages()`, which lists panel pages only, and the route loader takes a resource page's path from the registration.
The wrong answers are reachable only by a user who calls them directly, or who also lists a resource page in `pages()`, which mounts it a second time under the panel's page family (`/admin/list-articles` beside `/admin/articles`, two names, no collision for the [duplicate guard](routing.md) to catch) — the one arrangement in which the inherited statics answer truthfully.

## Semantics

### The host defaults answer off the resource

`getDefaultActionRecordClass()` and `getDefaultActionRecordLabel()` answer the last hop of actions' [record chain](../actions/action.md#the-record-chain-reaches-the-schema-the-component-and-the-host) with the resource's record class and singular label, so a prebuilt record action on a resource page knows what it acts on and a `DeleteAction`'s "Delete article" heading needs nothing declared on the action.
The contract types the class hook `?string`, but a resource page never answers null: `Resource::getRecordClass()` [throws for a resource that declares none](resource.md#the-record-class-is-declared-never-derived-from-an-application-namespace).

`getDefaultActionUrl()` links a `CreateAction` at the `create` page, an `EditAction` at the `edit` page and a `ViewAction` at the `view` page, each only when the resource registers that page, and answers null for every other action, which is what keeps a `DeleteAction` mounting its confirmation ([why the hook must discriminate](../actions/interacts-with-actions.md#a-host-answering-the-url-hook-must-discriminate-by-action)).
Each arm also requires that the action's record class is null or the resource's own — Filament's `blank($actionModel) || $actionModel === …::getModel()` — so `CreateAction::make()->recordClass(Comment::class)` on an articles page mounts its modal instead.
Since `Action::getRecordClass()` walks down to `getDefaultActionRecordClass()`, the two sides agree unless the action was told otherwise, and the null half is reachable only through a subclass overriding that hook with null.
The `edit` arm skips an `EditRecord`, which would link the page at itself; the `view` arm carries no `ViewRecord` twin of that guard, where Filament's does, because there is no view page class to name.
The two record arms also require the action to carry a record, where Filament's do not, because a URL for a record the action does not carry would be generated with a null parameter.
Every arm spreads the page's `parentRecords` into the URL, so a nested page links within its own chain.
These hooks sit in the class body rather than in a trait, because [a trait outranks an inherited method](page.md#the-actions-host-sits-on-the-base-because-a-trait-outranks-an-inherited-method).

`getDefaultActionRelationship()` answers a `CreateAction` on a page holding a parent record with a schemas `Relationship` over the parent and the resource's [derived relationship name](nested-resources.md#the-relationship-name-is-read-off-doctrines-mapping-never-inflected), so a modal create on a nested page joins the parent as the routed create page does; every other action gets null.

### The trail walks the already-resolved ancestor chain

`getBreadcrumbs()` is `[...getResourceBreadcrumbs(), $this->getBreadcrumb()]`, and `getResourceBreadcrumbs()` walks the page's [resolved ancestor chain](nested-resources.md#the-chain-is-resolved-once-per-request-and-each-step-is-scoped-to-the-one-above-it) root-first, contributing each ancestor's own listing and then the record itself, and ends with this resource's listing, so it costs no query beyond the resolution the page does anyway.
Filament walks the other way, from the parent record up through each inverse relationship.
A listing crumb reads [`Resource::getBreadcrumb()`](nested-resources.md) and its URL is that resource's `getIndexUrl()`; a record crumb reads the record title, or the title-case label when the resource names no title attribute, and links to that resource's `view` page, else its `edit` page, else nothing — [Filament's ladder with the authorization arms flattened](interacts-with-record.md#the-crumb-links-through-an-ungated-ladder).
A record page inserts its own record crumb through [`InteractsWithRecord`](interacts-with-record.md), so a plain resource page trails `Articles → Create`, a titled record page `Articles → Alpha → Edit`, and a nested one the whole chain, `Articles → Genesis → Sections → In the beginning → Edit`.

### A mounted frame's record key resolves through the resource's own query

`HasActions::resolveMountedActionRecord()` turns a mounted frame's `recordKey` context into a record, and the trait ends at a `resolveRecordUsing()` callback the host must register, throwing a `LogicException` when a key arrives and none was.
Every resource page overrides it in the class body instead, so no resource page registers that callback.

The override keeps both of the trait's **skip** guards, because they decide which frames the host owns rather than how a key resolves.
A frame carrying a `schemaComponent` belongs to a component inside a schema, which resolves its own record; a frame carrying a `table` on a host that is an `InteractsWithTable` belongs to the table, whose row already knows its record.
Both answer `null`, and dropping either would have the page resolve a key that was never addressed to it.

It replaces the third guard, the `resolveRecordUsing()` throw.
A non-scalar key is a malformed request rather than a missing record, and raises `ActionNotResolvableException`, which `HasActions::cacheMountedActions()` catches into an empty stack, so the modal silently does not open.
A scalar key goes to `Resource::resolveRecordRouteBinding()` through [the page's parent scope](nested-resources.md#one-scope-serves-the-route-key-and-the-unchecksummed-action-key), because the key is client-supplied and unchecksummed where the route key is not, and a miss is a `NotFoundHttpException` that propagates as a 404; a key malformed for the record class takes the same 404 with no statement run, since the binding decodes before it binds.
The asymmetry is deliberate: one is a client sending nonsense, the other a record that is really gone.
Going through the binding rather than `EntityManager::find()` is the point: it is [the resource's scoped query](resource.md#a-record-is-resolved-through-the-resources-own-query), so a row outside the resource's scope 404s instead of being mounted into a modal by primary key, which a row action on a scoped list would otherwise leak silently.

What arrives in that context is the minting side's business, and the two sides agree only where the mint knows the resource's rule.
A **table**-bound action carries the key the table itself minted, so a row action on a composite list resolves against the same string the row rendered.
Every other action reads the literal `id` property, and only [where one scalar field is the record's whole identity](../actions/action.md#getcontext-drops-the-record-model-gate); for a composite or an association identifier it mints nothing and the declared record stands.
What that gate cannot see is a readable `id` that is not the key the resource uses: a resource declaring `$recordRouteKeyName`, and a record carrying a legacy serial `id` beside the natural key it is identified by, both mint an `id` the resource does not resolve by, a 404 on click.
Both are closed only by a host hook that lets a page mint its own key, which does not exist.

## Divergences

### `route()` returns a registration rather than registering a route

The call shape is Filament's — `ListArticles::route('/')` inside a resource's `getPages()` — and everything behind it differs.
Filament's builds a `PageRegistration` around a `Closure(Panel): ?Route` that, when the resource's `registerRoutes()` invokes it, calls Laravel's `Route::get()` facade: the closure **registers** the route as a side effect and hands back the `Route` so the caller can keep mutating it (`$route?->name($name)`, middleware).
Ours returns [`PageRegistration`](resource.md), a `readonly` pair of the page class-string and the path, and nothing happens until [`PanelRouteLoader`](routing.md) reads it.
The path's leading slash is optional: the loader joins the resource prefix and the registration path with exactly one separator, so `'/{record}/edit'` and `'{record}/edit'` mount identically and both `'/'` and `''` collapse onto the resource root.

A Symfony route loader must **return** a `RouteCollection`; there is no ambient router to push into mid-call, and a value that mutated global state during `getPages()` would be unreachable to the loader anyway.
So the name, which Filament applies after the fact from the `getPages()` key, is composed by the loader from `getRouteBaseName()` plus that key, and the middleware Filament's closure attaches has no counterpart: route middleware is a Laravel concept, and its Symfony equivalents, firewall and access control, belong to authorization.
A registration cannot express a per-page route option Filament would set on the returned `Route`; when one is needed, it belongs on `PageRegistration` as data the loader reads, not as a closure that reaches the collection.

### `getResourceInstance()` is the hop Filament's static calls cannot make

Filament reaches the resource with `static::getResource()::getModel()` and its siblings, a static call on a class-string, throughout `Resources/Pages/`, and a service-backed resource cannot be reached that way.
`getResourceInstance(): Resource` resolves `static::getResource()` through the [`ResourceRegistry`](resource.md) an `#[Required] setResourceRegistry()` setter injects, and throws a `LogicException` naming container registration when the setter never fired, the fail-fast shape [a `new`-built resource](resource.md#a-new-built-resource-fails-fast-rather-than-degrading) has.
The name follows schemas' `getRecordInstance()` (class-string in, instance out) rather than inventing a third convention.
`getResource()` keeps Filament's name and return type, so a migrating user's `static::getResource()` still answers the class-string, and `protected static string $resource` stays uninitialised by default, so a page that forgets it fails on the first read rather than resolving something plausible.

### `getBreadcrumb()` widens to `string|Markup`

`getBreadcrumb()` is Filament's default, `static::$breadcrumb ?? $this->getTitle()`, typed `string|Markup` where Filament types it `?string`.
`getTitle()` returns `string|Markup`, so Filament's own default can hand an `Htmlable` title into a `?string` return, which survives there only because its code is not under `strict_types`, and which would stringify the markup and let the template escape it, breaking the `Htmlable` → `Markup` escaping contract.
The list, create and edit pages narrow it to `string` and answer `List`, `Create` and `Edit` — v5's `list-records.breadcrumb`, `create-record.breadcrumb` and `edit-record.breadcrumb` lang values as literals, as [the notification titles](create-record.md#the-create-and-save-notification-titles-are-literals-not-translation-keys) are.

### The deprecated page-level `getModelLabel()` is not ported

Filament's `Resources\Pages\Page::getModelLabel()` is `@deprecated` in favour of overriding the resource's `getModelLabel()`, which is [`Resource::getRecordLabel()`](resource.md#the-deprecated-label-pair-is-not-ported) here.
So `getDefaultActionRecordLabel()` answers the resource's label directly, without Filament's `$this->getModelLabel() ??` arm in front of it.
