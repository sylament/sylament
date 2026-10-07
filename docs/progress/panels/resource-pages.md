---
description: 'The base every page a resource registers extends, and the resource hop it adds to the panel page.'
ports:
  - vendor/filament/filament/src/Resources/Pages/Page.php
owns:
  - packages/panels/src/Resources/Pages/Page.php
---
# Resource pages (panels)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Resources\Pages\Page`](../../../packages/panels/src/Resources/Pages/Page.php) | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### `Resources\Pages\Page` is the base every resource page extends, adding the resource hop to the panel page

`Resources\Pages\Page` is the base every page a resource registers extends — Filament's `Resources\Pages\Page`, which likewise extends the panel [`Page`](page.md) rather than standing beside it, so a resource page keeps the whole page surface (title, heading, template, live-component registration) and adds the resource hop.

The **hosting** sits one tier up: `implements InteractsWithActions`, the aliased `use HasActions`, `implements InteractsWithSchemas` and `use HasSchemas` all sit on [`Pages\Page`](page.md) now, so a resource page composes only what it adds (tables' `HasTable` on the list page, `InteractsWithRecord` and `HasRelationManagers` on the record ones) and this base keeps its `getDefault*` overrides in the class body, where PHP's precedence keeps them winning.
That is not a tidying choice: PHP inserts a trait's members *over* an inherited class's, so a `getDefaultActionUrl()` here would be silently shadowed by `HasActions`' null-returning default in any subclass that re-`use`d the trait — [the actions host sits on the base because a trait outranks an inherited method](page.md#the-actions-host-sits-on-the-base-because-a-trait-outranks-an-inherited-method).
The header-action walk itself, `callHook()`, `halt()` and the `panelId` prop sit one tier up as well, in [`Pages\Concerns\InteractsWithHeaderActions`](custom-pages.md) and the page base; what stayed is everything that needs a **resource**.

Eleven members, plus the nesting ones — the parent-record chain, the breadcrumb walk, `getDefaultActionRelationship()`, the sub-navigation position and item seam — and `getResourceUrl()`.
[`getPageClasses()`](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class) is **not** one of them: it arrives one tier up, on [`Pages\Page`](page.md), and each concrete page overrides it.
`protected static string $resource` is the class-string a subclass declares and `getResource()` reads — untyped-default exactly as in Filament, so a page that forgets it fails on the first read rather than resolving something plausible.
`route(string $path): PageRegistration` is the registration factory a resource's `getPages()` calls.
`getResourceInstance(): Resource` is the hop itself: an `#[Required] setResourceRegistry()` setter takes [`ResourceRegistry`](resource.md) and the getter resolves `static::getResource()` through it, throwing a `LogicException` naming container registration when the setter never fired — the same fail-fast shape [a `new`-built resource](resource.md#a-new-built-resource-fails-fast-rather-than-degrading) has, for the same reason.
[`getResourceUrl()`](custom-pages.md#getresourceurl-fills-the-record-and-threads-the-parent-chain) is the instance-side URL helper a custom record page reaches for, filling the record from the page and threading the parent chain.
`getHeaderActions(): array` returns `[]`, the override hook a page fills — it lives on [`Pages\Concerns\InteractsWithHeaderActions`](custom-pages.md) and is **public** where Filament's is `protected`, because the page template reads the bound list and Twig only reaches public members.
That widening is self-enforcing: PHP refuses to narrow visibility in an override, so a user copying Filament's `protected function getHeaderActions()` gets a fatal at class load rather than a silently empty header.
`getDefaultActionUrl(Action): ?string` is the fifth — actions' [url fallthrough hook](../actions/interacts-with-actions.md), answering for exactly the three prebuilt actions a resource can have a page for:

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

Every clause earns its place.
The `instanceof` discrimination is what keeps a `DeleteAction` mounting its confirmation instead of becoming a link that opens nothing — the hook is asked for **every** url-less action the page renders, and any non-null answer wins, which is why Filament writes the same chain of guards.
`! $this instanceof EditRecord` stops an edit page linking a record-bound `EditAction` at the page already showing that record; the `ViewAction` arm below it carries no `! $this instanceof ViewRecord` twin only because there is no view page class to name yet — that clause lands with the view page, and its absence changes nothing until it does.
`getRecord() !== null` guards the two record arms, because a URL for a record the action does not carry would be generated with a null parameter.

`$ownsRecordClass` is Filament's `blank($actionModel) || $actionModel === …::getModel()` pair under [the record vocabulary](../renames.md#the-model-vocabulary-collapses-onto-record), hoisted out of the three arms because it asks one question for all of them: an action bound to a record class this resource does not own must not be linked at this resource's pages — a `CreateAction::make()->recordClass(Comment::class)` on an articles page mounts its modal instead.
It is almost always `true`, and deliberately so: `Action::getRecordClass()` walks its chain down to `getDefaultActionRecordClass()`, which on a resource page is the resource's own class, so the two sides of the comparison agree unless the action was told otherwise.

The `=== null` half is Filament's `blank($actionModel)` kept verbatim, and on a resource page it is very nearly unreachable.
The hook is only ever entered through `CanOpenUrl::getUrl()`'s `getLiveComponentOrNull()?->getDefaultActionUrl($this)`, so the action always has a host by the time it is asked; that host is this page, whose `getDefaultActionRecordClass()` is the last hop of the chain and never answers null (the record pair below says why).
It is kept because it is half of a ported pair and because a subclass is free to override that hook with null, not because a resource page can reach it.

The record pair is sixth and seventh.
`getDefaultActionRecordClass(Action)` and `getDefaultActionRecordLabel(Action)` answer the last hop of actions' [record chain](../actions/action.md#the-record-chain-reaches-the-schema-the-component-and-the-host) off the resource — its record class and its singular label — so every prebuilt record action mounted on a resource page knows what it is acting on, and a `DeleteAction`'s "Delete article" heading comes out right with nothing declared on the action.
The contract types the class hook `?string`, but **it can never answer null from a resource page**: `Resource::getRecordClass()` [throws for a resource that declares none](resource.md#the-record-class-is-declared-never-derived-from-an-application-namespace) rather than returning null, which is the right shape — a resource without a record class is broken, not unopinionated — and the null the interface allows is for hosts with no resource behind them.

`resolveMountedActionRecord(array $descriptor)` is the eighth: the class-body override that resolves a mounted frame's record key through the resource's own scoped query rather than a bare lookup — and through [the page's parent scope](nested-resources.md#one-scope-serves-the-route-key-and-the-unchecksummed-action-key) as well, because that key is client-supplied and unchecksummed where the route key is not.
It keeps both of the trait's skip guards and replaces its third — [the whole shape is a semantics note](#a-mounted-frames-record-key-resolves-through-the-resources-own-query).
It is `protected`, and it is on this base for the same reason the URL hook is — [a trait outranks an inherited method](page.md#the-actions-host-sits-on-the-base-because-a-trait-outranks-an-inherited-method).
`callHook(string $hook)` was the ninth and now sits on [`Pages\Page`](page.md) instead, where every page can dispatch its own lifecycle hooks: [a hook is a method name, not a registration](page.md#a-page-lifecycle-hook-is-a-method-name-not-a-registration).

Filament's own three-branch method is `static::getResource()::hasPage(…)` all the way down; ours goes through `getResourceInstance()` for the same reason every other hop does.
An action only reaches the hook when it is **stamped with the live component** — see [header actions](custom-pages.md#header-actions-are-bound-lazily-rather-than-at-boot) for where the page does that, and note that a table record action needs nothing, since `BelongsToLiveComponent` already falls through the table to its host.

`getResourceInstance()` is **new API**, with no Filament counterpart to preserve: Filament writes `static::getResource()::getModel()` — a static call on a class-string — at 32 sites in `Resources/Pages/Page.php` alone and 61 across `Resources/Pages/`, and a service-backed resource cannot be reached that way.
The name follows schemas' `getRecordInstance()` precedent (class-string in, instance out) rather than inventing a third convention.
`getResource()` keeps Filament's name and return type, so a migrating user's `static::getResource()` still answers the class-string.

The last two are the breadcrumb pair, and they are what puts a trail above every resource page.
`protected static ?string $breadcrumb` with `getBreadcrumb(): string|Markup` is Filament's default verbatim (`static::$breadcrumb ?? $this->getTitle()`), and `getBreadcrumbs()` overrides the [base page's](page.md) empty map with the trail Filament builds.
The three concrete pages narrow `getBreadcrumb()` to `string` and answer `List` / `Create` / `Edit` — v5's `list-records.breadcrumb`, `create-record.breadcrumb` and `edit-record.breadcrumb` lang values as literals, the same call [the notification titles](create-record.md#the-create-and-save-notification-titles-are-literals-not-translation-keys) made.

The return type is **widened** to `string|Markup` where Filament types the same method `?string`, and the widening is a fix rather than a preference: `getTitle()` already returns `string|Markup`, so Filament's own default chain can hand a `Htmlable` title into a `?string` return — which only survives there because its code is not under `strict_types`, and which would stringify the markup and then let the template escape it, breaking the `Htmlable` → `Markup` escaping contract this port holds throughout.
The narrowed overrides on the three concrete pages keep the common case a plain `string`.

Nesting fills that trail in: `getBreadcrumbs()` is `[...getResourceBreadcrumbs(), $this->getBreadcrumb()]`, and `getResourceBreadcrumbs()` — with `hasResourceBreadcrumbs()` beside it, Filament's pair — walks the page's **already-resolved** ancestor chain root-first, contributing each ancestor's own listing and then the record itself, and ending with this resource's listing.
It costs no queries beyond the chain resolution the page did anyway.
A resource's listing crumb reads [`Resource::getBreadcrumb()`](resource.md) rather than its plural label directly, so the resource-level override finally has a reader, and each record crumb links to that resource's `view` page, else its `edit` page, else nothing — [Filament's ladder with the authorization arms flattened](interacts-with-record.md#the-crumb-links-through-an-ungated-ladder).
Two helpers carry it: `appendBreadcrumb()`, which emits a crumb whose URL the trail already spends as an int-keyed (unlinked) entry [rather than letting a repeated key collapse two crumbs into one](nested-resources.md#a-crumb-whose-url-the-trail-already-spends-renders-unlinked), and `getRecordBreadcrumbUrl()`, the view → edit → null ladder itself.
A **record** page inserts its own record crumb between the two through [`InteractsWithRecord`](interacts-with-record.md#the-record-crumb-lands-on-plain-resources-too-and-that-is-new-behaviour), which is new behaviour for plain resources as well as nested ones: a titled resource's edit page trails three crumbs where it trailed two.

A plain resource page therefore still trails `Articles → Create`; a titled record page trails `Articles → Alpha → Edit`; and a nested one trails the whole chain, `Articles → Genesis → Sections → In the beginning → Edit`.

One `LiveProp` lives one tier up, on [`Pages\Page`](page.md): `public ?string $panelId`, minted by its own getter's dehydration read and stamped back onto the request by a `#[PostHydrate(priority: 20)]` hook, so a live request — which reaches ux's route and therefore carries no `_sylament_panel` attribute — resolves the panel the page was **opened in** rather than the default one.
It is the [`GlobalSearch`](global-search.md#the-component-restores-its-panel-onto-a-live-request) mechanism ported one tier down, and it closes [the recorded multi-panel URL defect](resource.md#url-generation-inside-a-live-action-resolves-the-default-panel) for every panel page.
Nesting is what forced it: on a non-default panel every live re-render of a nested page threw `RouteNotFoundException` for the default panel's route name, so the prop was a prerequisite rather than an improvement — and [the priority is what makes it beat actions' own hydration hook](custom-pages.md#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism).

Two divergences hang off this base:

- [`route()` returns a registration rather than registering a route](#route-returns-a-registration-rather-than-registering-a-route) — the call shape is Filament's, but a Symfony loader must *return* a `RouteCollection`, so nothing is registered until the loader reads the pair.
- [A resource page inherits page-route statics that answer for the wrong route](#a-resource-page-inherits-page-route-statics-that-answer-for-the-wrong-route) — every static describing a panel-mounted page is inherited and wrong here.

Deferred, all todo: `getResourcePageName()` (Filament's reverse lookup of a page's own `getPages()` key — [the sub-navigation band deliberately routes around it](has-sub-navigation.md#a-resource-page-item-is-built-by-the-band-because-a-page-cannot-name-its-own-route) rather than forcing the parked decision), the authorization concern, and `ViewRecord` / `ManageRecords`.
Three features that list once held ship: sub-navigation ([the position override and the band's item seam](has-sub-navigation.md)), the parent-record concern ([`InteractsWithParentRecord`](nested-resources.md), on this base exactly as Filament composes its own), and [`ManageRelatedRecords`](manage-related-records.md).

## Semantics

### A mounted frame's record key resolves through the resource's own query

`HasActions::resolveMountedActionRecord()` turns a mounted frame's `recordKey` context into a record, and the trait cannot do it alone: it ends at a `resolveRecordUsing()` callback the host must register, throwing a `LogicException` when a key arrives and none was.
Every resource page overrides it in the class body instead, and so no resource page ever registers that callback.

The override keeps both of the trait's **skip** guards verbatim, because they are not about resolution at all — they are about which frames the host owns.
A frame carrying a `schemaComponent` belongs to a component inside a schema, which resolves its own record; a frame carrying a `table` on a host that is an `InteractsWithTable` belongs to the table, whose row already knows its record.
Both answer `null`, and dropping either would have the page resolve a key that was never addressed to it.

What it replaces is the third guard, the `resolveRecordUsing()` throw.
In its place the key is checked for being scalar — a `recordKey` arrives off the wire, so a non-scalar is a malformed request rather than a missing record, and it raises `ActionNotResolvableException`.
"Raises" is the accurate word rather than "surfaces": `HasActions::cacheMountedActions()` catches that exception into an empty stack, so a malformed key is a **silent non-mount** — the modal simply does not open — where the `NotFoundHttpException` below propagates all the way out as a 404.
The two failures are deliberately asymmetric, one being a client sending nonsense and the other a record that is really gone.
The key is then handed to `Resource::resolveRecordRouteBinding()`, with a `NotFoundHttpException` for a miss — and, since that binding decodes before it binds, a key that is well-formed JSON but malformed for the record class takes the same 404 with no statement run, where it used to reach the database as a raw parameter.
Going through that method rather than `EntityManager::find()` is the whole point: it is [the resource's scoped query](resource.md#a-record-is-resolved-through-the-resources-own-query), so a row outside the resource's scope 404s instead of being mounted into a modal by primary key.
A row action on a scoped list is exactly where that would leak, and it would leak silently.

What arrives in that context is the minting side's business, and the two sides only agree where the mint knows the resource's rule.
A **table**-bound action carries the key the table itself minted, so a row action on a composite list resolves against the same string the row rendered.
Every other action reads the literal `id` property — and reads it [only where one scalar field is the record's whole identity](../actions/action.md#getcontext-drops-the-record-model-gate).
Without that guard, an ordinary `EditAction::make()->record($this->getRecord())` in a page header on a record keyed by `id` **and** a locale minted the partial key `"5"`, which the strict decode then refuses — a 404 on click, from a trigger whose record the page was already holding.
The guard mints nothing for a composite or an association identifier instead, and the declared record stands, which is what a composite record with no `id` field has always done.
A record keyed by **one** scalar field mints exactly what it always did, whatever that field is called.
What the gate cannot see is a readable `id` that is not the identifier: a resource declaring `$recordRouteKeyName` keeps its own pre-existing mismatch — one scalar field *is* that record's identifier, so the literal reading still fires and still names a key the resource does not use — and so does a record carrying a legacy serial `id` beside the natural key it is really identified by.
Both are a 404 on click, and both are closed only by the host hook that would let a page mint its own key, which does not exist yet.

## Divergences

### `route()` returns a registration rather than registering a route

The call shape is Filament's — `ListArticles::route('/')` inside a resource's `getPages()` — and everything behind it differs.
Filament's builds a `PageRegistration` around a `Closure(Panel): ?Route` that, when the resource's `registerRoutes()` finally invokes it, calls Laravel's `Route::get()` facade: the closure **registers** the route as a side effect and hands back the `Route` object so the caller can keep mutating it (`$route?->name($name)`, middleware).
Ours returns [`PageRegistration`](resource.md), a `readonly` pair of the page class-string and the path, and nothing happens until [`PanelRouteLoader`](routing.md) reads it.
The path's leading slash is **optional**: the loader joins the resource prefix and the registration path with exactly one separator, so `'/{record}/edit'` and `'{record}/edit'` mount identically and both `'/'` and `''` collapse onto the resource root.
Filament's own examples all carry the slash, so the docblock states the relaxation rather than leaving a migrating user to discover that one form silently produced `/articles{record}`.

The reason is where routes come from.
A Symfony route loader must **return** a `RouteCollection`; there is no ambient router to push into mid-call, and a value that quietly mutated global state during `getPages()` would be unreachable to the loader anyway.
So the name — which Filament applies after the fact from the `getPages()` key — is composed by the loader from `getRouteBaseName()` plus that key, and the middleware arguments Filament's closure carries have no counterpart yet (route middleware is a Laravel concept; the Symfony equivalents, firewall and access control, land with authorization).

What it costs: a registration cannot express a per-page route option Filament would set on the returned `Route`.
Nothing needs one today.
When something does, the option belongs on `PageRegistration` as data the loader reads, not as a closure that reaches the collection.

### A resource page inherits page-route statics that answer for the wrong route

`Resources\Pages\Page` extends the panel [`Page`](page.md), so it inherits `getSlug()`, `getRoutePath()`, `getRouteName(Panel)`, `getNavigationItems(Panel)` and the navigation getters — every one of which describes a page mounted **directly on a panel**.
A resource page is not: its path comes from the `PageRegistration` its resource declares and its route name is `getRouteBaseName($panel) . '.' . $key`.
So `ListArticles::getRouteName($panel)` answers `sylament.admin.pages.list-articles`, a route that does not exist, and `getNavigationItems()` would build an item pointing at it.

Filament has the same inheritance and closes it by **overriding** `getRouteName()` on `Resources\Pages\Page` to read `static::getResource()::getRouteBaseName($panel) . '.' . static::getResourcePageName()`.
We cannot: both hops are instance methods on a service here, and these are static methods with no `$this` to reach the registry from — the same wall [the carried decision](resource.md#resource-is-a-service-not-a-static-class-string-registry) predicted for the `can*()` statics.
Fixing it means either dropping the statics to instance methods (which changes a Filament API shape) or carrying the page's registration key on the instance, and both are worth deciding once rather than twice, so it waits for the task that needs a resource page to name its own route.

The sub-navigation band was the first task that could have been it, and it [deliberately routes around it](has-sub-navigation.md#a-resource-page-item-is-built-by-the-band-because-a-page-cannot-name-its-own-route): the sub-navigation band needs a sibling resource page's URL, which in Filament is `static::getUrl()` over `getResourcePageName()`.
The band builder runs on a page **instance** holding the registry, so it scans the sibling's own resource `getPages()` for the entry whose `page` matches and mints the URL from the key it finds — a private helper, no new public static, and the parked decision stays parked.

Until then this is a **known wrong-answer surface**, and it is narrow: nothing in the framework calls those statics on a resource page.
`Panel::getNavigation()` loops over `pages()`, which lists panel pages only, and the route loader takes a resource page's path from the registration.
The wrong answers are reachable only by a user who calls them directly, or who also lists a resource page in `pages()` — which mounts it a **second** time under the panel's page family (`/admin/list-articles` beside `/admin/articles`, two names, no collision for the [duplicate guard](routing.md) to catch) and is the one arrangement in which the inherited statics answer truthfully.
