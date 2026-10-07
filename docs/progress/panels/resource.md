---
description: 'The abstract resource base a user extends once per record type, the registry resolving its class-string, its page registrations and the pass collecting it.'
ports:
  - vendor/filament/filament/src/Resources/Resource.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasPages.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasAuthorization.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasConfiguration.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToCluster.php
  - vendor/filament/filament/src/Resources/Pages/PageRegistration.php
owns:
  - packages/panels/src/DependencyInjection/Compiler/ResourcePass.php
  - packages/panels/src/Resources/Pages/PageRegistration.php
  - packages/panels/src/Resources/Resource.php
  - packages/panels/src/Resources/ResourceRegistry.php
---
# Resource (panels)

`Sylament\Panels\Resources\Resource` is the abstract base a user extends once per record type: a container service, named by its class-string everywhere a user names it and resolved to the instance through `ResourceRegistry` ([why](#resource-is-a-service-not-a-static-class-string-registry)).
Its global search is [global-search.md](global-search.md), its parent nesting and breadcrumb label [nested-resources.md](nested-resources.md), its relation managers [has-relation-managers.md](has-relation-managers.md), its record sub-navigation [has-sub-navigation.md](has-sub-navigation.md) and the pages it registers [resource-pages.md](resource-pages.md).

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`form()` / `infolist()` / `table()`](../../../packages/panels/src/Resources/Resource.php) | [`Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php) | done | — | [diverges](#resource-is-a-service-not-a-static-class-string-registry) |
| `configureTable()` | [`Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php) | done | — | [list-records.md](list-records.md#the-list-pages-table-is-stamped-query-first-and-resource-last) |
| [`$recordClass` / `getRecordClass()`](../../../packages/panels/src/Resources/Resource.php) | `Resource.php` `$model` / `getModel()` | done | — | [diverges](#the-record-class-is-declared-never-derived-from-an-application-namespace) · [renames.md](../renames.md#the-model-vocabulary-collapses-onto-record) |
| [`$recordLabel` / `$pluralRecordLabel` and their four getters](../../../packages/panels/src/Resources/Resource.php) | [`HasLabels.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php) | done | — | [diverges](#pluralization-takes-the-inflectors-last-candidate) · [renames.md](../renames.md#the-model-vocabulary-collapses-onto-record) |
| `$label` / `getLabel()` / `$pluralLabel` / `getPluralLabel()` (deprecated) | [`HasLabels.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php) | skipped | — | [diverges](#the-deprecated-label-pair-is-not-ported) |
| `$hasTitleCaseModelLabel` / `titleCaseModelLabel()` / `hasTitleCaseModelLabel()` | [`HasLabels.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php) | todo | — | [detail](#the-title-case-toggle-is-unported-so-a-record-label-always-title-cases) |
| [`$recordTitleAttribute` / `getRecordTitleAttribute()` / `hasRecordTitle()` / `getRecordTitle()`](../../../packages/panels/src/Resources/Resource.php) | [`HasLabels.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php) | done | — | [diverges](#getrecordtitle-returns-a-plain-string) |
| [`$slug` / `getSlug()`](../../../packages/panels/src/Resources/Resource.php) | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | done | — | [diverges](#the-slug-has-no-namespace-path-arm) · [diverges](#nothing-is-memoised-in-a-static) |
| [`getPages()` / `hasPage()`](../../../packages/panels/src/Resources/Resource.php) | [`HasPages.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasPages.php) | done | — | — |
| [`getRecordQuery()`](../../../packages/panels/src/Resources/Resource.php) | `Resource.php` `getEloquentQuery()` | done | — | [renames.md](../renames.md#renames) · [diverges](#a-record-is-resolved-through-the-resources-own-query) |
| `getRecordRouteBindingEloquentQuery()` | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | skipped | — | [renames.md](../renames.md#renames) |
| [`resolveRecordRouteBinding()`](../../../packages/panels/src/Resources/Resource.php) | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | done | — | [diverges](#a-record-is-resolved-through-the-resources-own-query) · [semantics](#the-modifier-may-answer-with-a-different-query-and-refuses-anything-else) |
| [`$recordRouteKeyName` / `getRecordRouteKeyName()` / `getRecordRouteKey()`](../../../packages/panels/src/Resources/Resource.php) | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | done | — | [diverges](#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it) |
| [`getRouteBaseName(Panel)`](../../../packages/panels/src/Resources/Resource.php) | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | done | — | [diverges](#the-route-base-name-is-composed-in-one-place) · [nested-resources.md](nested-resources.md#a-nested-route-name-lights-the-parents-sidebar-item-for-free) |
| `routes()` / `registerRoutes()` / `getRelativeRouteName()` / `getRoutePrefix()` | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | skipped | — | [diverges](#the-route-base-name-is-composed-in-one-place) · [routing.md](routing.md) |
| `$routeMiddleware` / `$withoutRouteMiddleware` and their getters, `getEmailVerifiedMiddleware()` / `isEmailVerificationRequired()` | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | blocked | authorization (firewall and access control) | [resource-pages.md](resource-pages.md#route-returns-a-registration-rather-than-registering-a-route) |
| [`getUrl()`](../../../packages/panels/src/Resources/Resource.php) | [`CanGenerateUrls.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php) | done | — | [diverges](#geturl-drops-two-parameters-and-widens-a-third) · [diverges](#url-generation-inside-a-live-action-resolves-the-panel-the-page-was-opened-in) |
| [`getIndexUrl()`](../../../packages/panels/src/Resources/Resource.php) | [`CanGenerateUrls.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php) | done | — | [semantics](#a-parentless-resource-with-no-index-page-throws-from-its-first-breadcrumb) · [nested-resources.md](nested-resources.md#the-parent-arms-carry-no-relation-parameter) |
| [`getNavigationItems(Panel)`](../../../packages/panels/src/Resources/Resource.php) with the label, icon, active-icon, group, sort, badge and badge-colour getters and `shouldRegisterNavigation()` | [`HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php) | done | — | [diverges](#active-state-is-a-route-name-prefix-match) · [panel.md](panel.md#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource) · [navigation-item.md](navigation-item.md#group-and-getgroup-carry-no-unitenum-arm) |
| `getNavigationItemActiveRoutePattern()` | [`HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php) | skipped | — | [diverges](#active-state-is-a-route-name-prefix-match) |
| `registerNavigationItems()` | [`HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php) | skipped | — | [panel.md](panel.md#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource) |
| `navigationGroup()` / `navigationIcon()` / `navigationLabel()` / `navigationSort()` static setters | [`HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php) | skipped | — | [diverges](#nothing-is-memoised-in-a-static) |
| `getNavigationUrl()` | [`HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php) | todo | — | — |
| `$navigationParentItem` / `getNavigationParentItem()` / `navigationParentItem()` and the item's `key()` | [`HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php) | blocked | `NavigationItem::key()` and `parentItem()` | [navigation-item.md](navigation-item.md#the-nesting-arm-waits-on-key) |
| `$navigationBadgeTooltip` / `getNavigationBadgeTooltip()` | [`HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php) | blocked | `NavigationItem::badgeTooltip()` | [navigation-item.md](navigation-item.md#the-badge-tooltip-and-extra-attributes-wait-on-a-sidebar-template-that-renders-them) |
| [`$subNavigationPosition` / `getSubNavigationPosition()` / `getRecordSubNavigation(Page)`](../../../packages/panels/src/Resources/Resource.php) | [`HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php) | done | — | [has-sub-navigation.md](has-sub-navigation.md) |
| [`getRelations()`](../../../packages/panels/src/Resources/Resource.php) | [`Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php) | done | — | [has-relation-managers.md](has-relation-managers.md#getrelations-is-an-instance-method-like-every-other-resource-hook) |
| `getWidgets()` | [`Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php) | blocked | the widgets package's `Widget` | — |
| `$isDiscovered` / `isDiscovered()` | [`Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php) | todo | — | [diverges](#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem) |
| `HasAuthorization` — `canAccess()`, the `can*()`, `authorize*()` and `get*AuthorizationResponse()` statics, the policy switches | [`HasAuthorization.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasAuthorization.php) | todo | — | [detail](#no-resource-gates-anything-until-authorization-is-ported) |
| `HasConfiguration` — `make()`, `getConfiguration()`, `withConfiguration()`, and `getDefaultSlug()` beside `getSlug()` | [`HasConfiguration.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasConfiguration.php) | todo | — | [diverges](#geturl-drops-two-parameters-and-widens-a-third) |
| `BelongsToTenant` — the tenant scope, ownership relationship and subscription middleware | [`BelongsToTenant.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php) | blocked | panel tenancy | — |
| `BelongsToCluster` — `$cluster` / `getCluster()` and the cluster arms of the route base name, sub-navigation position and navigation | [`BelongsToCluster.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToCluster.php) | blocked | a `Cluster` page class | — |
| [`Resources\ResourceRegistry`](../../../packages/panels/src/Resources/ResourceRegistry.php) | — | done | — | [diverges](#resource-is-a-service-not-a-static-class-string-registry) |
| [`Resources\Pages\PageRegistration`](../../../packages/panels/src/Resources/Pages/PageRegistration.php) | [`PageRegistration.php`](../../../vendor/filament/filament/src/Resources/Pages/PageRegistration.php) | done | — | [resource-pages.md](resource-pages.md#route-returns-a-registration-rather-than-registering-a-route) |
| [`DependencyInjection\Compiler\ResourcePass`](../../../packages/panels/src/DependencyInjection/Compiler/ResourcePass.php) | — | done | — | [diverges](#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem) |

## Details

### No resource gates anything until authorization is ported

Filament's `HasAuthorization` answers `canAccess()`, a `can*()` and `authorize*()` static per policy ability, and a `get*AuthorizationResponse()` per ability that pages and actions consult; none of it is ported, so every resource page, prebuilt action, crumb and band item is reachable by anyone who reaches the panel.
The statics cannot stay static here: they need an authorization checker, which a static method cannot reach ([why](#resource-is-a-service-not-a-static-class-string-registry)), so they arrive as instance methods delegating to Symfony voters.
The places that wait on them are recorded where they sit: [the nesting gates](nested-resources.md#the-nesting-surface-is-ungated-until-authorization-lands), [the record crumb ladder](interacts-with-record.md#the-crumb-links-through-an-ungated-ladder), [global search](global-search.md#the-searchability-check-and-the-result-url-ladder-drop-filaments-authorization-gates) and [relation managers](relation-manager.md#every-registered-manager-is-reachable-by-anyone-until-authorization-lands).

### The title-case toggle is unported, so a record label always title-cases

Filament's `$hasTitleCaseModelLabel` (default `true`) with `titleCaseModelLabel()` and `hasTitleCaseModelLabel()` lets a resource keep its labels in their own case.
Here `getTitleCaseRecordLabel()` and `getTitleCasePluralRecordLabel()` title-case unconditionally through `u($label)->title(allWords: true)`, so a subclass declaring the static declares a property nothing reads.
The toggle's setter is a mutating static, which [this port does not keep](#nothing-is-memoised-in-a-static); the flag itself can return as a static the two getters read.

## Semantics

### A parentless resource with no `index` page throws from its first breadcrumb

Every resource page heads its trail with the resource's `getIndexUrl()` ([the trail](resource-pages.md)), and `getIndexUrl()` throws Filament's verbatim `LogicException` — *The resource [X] does not have an [index] page. Define [getIndexUrl()] for alternative routing.* — when the resource has no `index` page and no parent arm answers.
So such a resource throws the moment any of its pages renders, where its only other symptom is contributing no navigation item.
That is Filament's behaviour too: its trail reaches the same throw through `getResourceUrl()` → `getUrl(null)` → `getIndexUrl()` ([`CanGenerateUrls.php:124-125`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php)).
A **nested** resource's index is its parent's page, so `getIndexUrl()` answers from [the parent arms](nested-resources.md#the-parent-arms-carry-no-relation-parameter) before it tests `hasPage('index')`, and a nested resource registering only `create` and `edit` never reaches the throw.

### The modifier may answer with a different query, and refuses anything else

`resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null)` hands the closure the cloned record query and keys against **what it returns**, falling back to the query it passed when the closure returns null, so a modifier may replace the builder wholesale rather than add a predicate to it.
A closure returning anything else throws a `LogicException` naming the resource and the type it answered with, rather than being silently ignored: a `Closure` carries no return type, so the wrong shape is only detectable here.
The decode keys against the **declared** `getRecordClass()`, never the builder's root entity, so a modifier that re-roots the query still decodes against the class the resource speaks for.
Filament's only caller of the parameter is the parent scope, and here it is [the page's parent scope](nested-resources.md#one-scope-serves-the-route-key-and-the-unchecksummed-action-key) too, which answers the resource's own record query narrowed to the parent.

## Divergences

### `Resource` is a service, not a static class-string registry

Filament's `Resource` is entirely static — `OrderResource::form($schema)`, `::table($table)`, `::getUrl()` — which works because Laravel's `app()` global lets a static method reach any service.
Symfony has no such global, so a static method cannot reach a dependency at all.
If `form()` and `table()` stayed static they could hold no dependency, and neither could any aggregator class they delegate to, which would leave a per-field or per-column leaf class as the only place a dependency can live.

So `Resource` is a service, autoconfigured with the `sylament.panels.resource` tag, and `form()`, `infolist()`, `table()` and every other hook are instance methods.
The services it needs — the entity manager, the URL generator, `CurrentPanel`, `PanelRegistry`, schemas' `PropertyAccessor` and `AssociationReader`, and `ResourceRegistry` — arrive through one `#[Required] setResourceServices()` setter, so a subclass declaring its own constructor dependencies never repeats them.

The user-facing API stays a class-string: `protected static string $resource = ArticleResource::class;` on a page, `->resources([ArticleResource::class])` on a panel.
`ResourceRegistry::get()` turns that class-string into the container-built instance through a class-keyed `ServiceLocator`, as `createForm(OrderType::class)` resolves a form type through Symfony Form's registry.
It throws a `LogicException` naming the fix for a class-string no service answers, and another when the locator answers with something that is not a `Resource`.
The slug stays what it is in Filament, a URL segment derived from the class name and overridable per resource; it identifies a resource in a URL, never in code.

The cost is on the framework side only: Filament's own `static::getResource()::…` calls become a registry lookup, and none of them is user code.

### The record class is declared, never derived from an application namespace

Filament's `getModel()` falls back to `class_basename(static::class)->beforeLast('Resource')->prepend(app()->getNamespace() . 'Models\\')`, so `ArticleResource` finds `App\Models\Article` with nothing declared.
`app()->getNamespace()` reads the application's PSR-4 root out of its `composer.json`, a Laravel-container concept with no Symfony equivalent, and it assumes every entity lives in one flat namespace, which Doctrine applications routinely do not.
So the arm is dropped rather than approximated: `$recordClass` must be declared, and `getRecordClass()` throws a `LogicException` naming the resource and the property when it is not.

### The slug has no namespace-path arm

Filament's `resolveDefaultSlug()` has a second branch: when the resource's parent namespace segment equals the pluralized basename (`App\Filament\Resources\Articles\ArticleResource`), the slug is built from the namespace path after `\Resources\`, so nested directories become nested URL segments.
Ours derives only from the class basename: the basename minus a trailing `Resource`, pluralized and kebab-cased (`BlogPostResource` → `blog-posts`), and the suffix is stripped only when the name ends with it.
The convention the branch keys on is Filament's generator layout, which a Sylament application does not follow, and [nested resources](nested-resources.md#the-recursion-lives-in-the-loader-and-the-child-must-be-registered-on-the-panel) carry their nesting in the route path and name rather than in the slug.
`getRouteBaseName()` still turns a `/` in the slug into `.`, so a `$slug` containing a slash works and the branch can return without disturbing anything downstream.

### Pluralization takes the inflector's last candidate

The slug and the plural record label pluralize through support's [`plural()`](../support/helpers.md#wrap-and-the-laravel-globals-are-reimplemented-not-forwarded), which takes the last of `EnglishInflector`'s candidates and has no locale gate, so `StatusResource` slugs to `statuses` and a `Person` record's plural label is `people`.
The two inflectors are different engines and their candidate sets diverge for exotic nouns; `$slug` and `$pluralRecordLabel` are the overrides when one does.
Symfony's inflector also pluralizes an already-plural word (`Articles` → `Articless`), so a resource class whose basename is already plural and does not end with `Resource` needs an explicit `$slug`.

### The route base name is composed in one place

Filament prepends the literal `resources.` in `getRouteBaseName()` and again in `registerRoutes()`'s `Route::name('resources.')`, so the name a URL is generated from and the name a route is registered under are built by two pieces of code that must agree.
Ours composes `sprintf('sylament.%s.resources.%s', …)` once, in `getRouteBaseName(Panel)`, and the [route loader](routing.md) reads it.
So `routes()`, `registerRoutes()`, `getRelativeRouteName()` and `getRoutePrefix()` have no member: the loader builds every resource route from `getRouteBaseName()`, `getSlug()` and `getPages()`, because a Symfony loader returns a route collection rather than registering into an ambient router.
The `Panel $panel` parameter is explicit rather than defaulted from a facade, matching [`Page::getRouteName()`](page.md#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade).

### Nothing is memoised in a static

Filament caches two pure derivations in `protected static` arrays keyed by class-string, `HasRoutes::$cachedDefaultSlugs` and `Resources\Pages\Page::$cachedResourcePageNames`.
Under a long-lived worker (FrankenPHP, Swoole, RoadRunner) such an array lives for the worker's whole life, which is safe only because the derivations are deterministic.
The port keeps no such state: every derivation recomputes, which keeps the [worker-safe](layout-templates.md#the-shell-is-turbo-indifferent-and-worker-safe) constraint the shell is built under.
The same reasoning rules out Filament's mutating static setters (`navigationLabel()`, `navigationSort()`, `navigationGroup()`, `navigationIcon()`), which write process-global state from panel configuration; panel-level customisation of a third-party resource belongs on the `Panel`, not in a static write.

### A record is resolved through the resource's own query

`resolveRecordRouteBinding()` clones `getRecordQuery()` and adds the identifier constraint, rather than calling `EntityManager::find()`.
This is the security property Filament's `getEloquentQuery()` comment describes: a resource that scopes its base query to the current user's rows must yield a **404** for a row outside that scope, not the row.
Going through `find()` would bypass the scope and leak by primary key, invisibly: the list page would look correctly filtered while the edit URL served anything.
The clone is there for the override: the shipped `getRecordQuery()` mints a fresh builder every call, but a subclass may return a memoised one, and adding a `WHERE` to a builder the list page also reads would poison it.
Under a parent every nested read starts from the same clone and only narrows it ([the composition](nested-resources.md#the-parent-scope-is-the-readers-narrowing-of-the-resources-own-query-not-a-predicate-we-write)), so an override hides its rows on every nested page at every depth.

The root alias is read off the builder (`getRootAliases()[0]`, a `LogicException` when there is none), so an override naming its own alias still works, and the predicate is [`IdentifierConstraint::equals()`](../schemas/record-key-contract.md#the-operations-and-their-dql), so an identifier of any width narrows the same query in the same place.
The decode runs **before** anything is bound, so a key the record class could never mint — the wrong arity, a lower-case escape, a component its field's DBAL type refuses, or one it would print back differently — is a 404 that issues no statement rather than a database error; [a type refusing its printed form on read only](../schemas/record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer) is decoded through its own writer.
The column's width is no part of that question: a canonical integer the column cannot hold still decodes, binds, and raises the platform's own range error ([the residual](../schemas/record-key.md#an-integer-beyond-the-columns-width-still-raises-the-platforms-range-error)).

`getRecordRouteKey()` is the generation side of the same pair, replacing what Laravel gets from `UrlRoutable`; both sides read `$recordRouteKeyName`, so they cannot disagree.
It refuses a record that is not an instance of the declared record class, reading `getRecordClass()` first, so a resource declaring `$recordRouteKeyName` without `$recordClass` throws the record-class `LogicException` rather than a key one.
On the default arm the key is [`RecordKey::of()`](../schemas/record-key-contract.md#the-api), so a record that cannot be keyed is refused by the codec in its own words.

### A custom `$recordRouteKeyName` names one column, and the codec never sees it

`$recordRouteKeyName` is Filament's own static with Filament's meaning: the URL segment is one property of the record, read through schemas' `PropertyAccessor` on the way out and bound as one untyped parameter on the way back.
The key codec is consulted on neither side, so three things hold on this arm that the default arm rules out.
The bind is **untyped**, so a non-numeric segment against a numeric column is a `DriverException` — a 500, because `PageController` converts only an `HttpExceptionInterface` — where the default arm answers a 404 without querying.
The named column need not be **unique**, so `getOneOrNullResult()` may raise `NonUniqueResultException`, a 500 for the same reason.
And the value reaches the `sy-pa-resource-record-<key>` class token raw, so a property holding whitespace [splits that token in two](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class).
A value that is blank or not scalar is refused on the way out with *The route key of [X] is not a printable value.*

Three shapes are refused on first use, from the record class's metadata alone and before any statement runs: a name that is an **association** (which would bind, resolve, and then mint nothing), a name the class maps as **no field** (which would be a `QueryException`), and a name that is **one field of a several-field identifier**, which is never unique on its own.
Each `LogicException` names the resource, the name and the way out — name a mapped field or leave the static unset, or override `getRecordRouteKey()` and `resolveRecordRouteBinding()` together; a resource that overrides the pair is never refused, since only the two default arms run the check.
The untyped bind and a non-unique column stay open, because the metadata cannot see them.
Closing them means converting the value through the field's own DBAL type and comparing it back to what the client sent, which needs an addition to the frozen key contract; typing the bind alone is worse than the gap, trading the 500 for `5abc` silently resolving record 5.

### `getRecordTitle()` returns a plain string

Filament types it `string | Htmlable | null`, which under the project's escaping contract would be `string|Markup|null`; ours returns `string`.
The null arm is unreachable in Filament, since every path falls back to `getModelLabel()`, and a `Markup` arm buys nothing because record titles compose into sentences, a breadcrumb or a heading, where raw HTML would either be escaped anyway or smuggle markup into a heading.
A resource that wants markup in a title overrides the getter and widens its own return type.
The fallback follows Filament: the un-title-cased `getRecordLabel()`, not `getTitleCaseRecordLabel()`.

The read needs a `catch` Filament does not: `$record?->getAttribute($attr)` answers `null` for an attribute an Eloquent model does not have, while schemas' `PropertyAccessor::getFromRecord()` propagates Symfony's `AccessException` for a property with no accessor or an uninitialized one.
Catching it and falling back to the label reproduces Filament's behaviour, so a mis-declared `$recordTitleAttribute` degrades to the label rather than fataling a list page.
It is the only swallowed exception on the class; `getRecordRouteKey()` lets the same failure through, because a route key that cannot be read is a broken URL, not a cosmetic default.

The value is also tested where Filament tests nothing: a `Stringable` is cast, anything else non-scalar becomes `null`, a scalar is cast to a string, and a result that is not `filled()` falls back to the label.
So a title column holding `''`, `false` or whitespace titles the record with its label instead of a heading that reads `Edit `, while `0` and `'0'` stay titles.
The cast comes before the test because `blank()` mirrors Laravel in calling `false` filled.

### `getUrl()` drops two parameters and widens a third

Filament's signature carries seven parameters; ours carries five.
`$tenant` and `$configuration` belong to unported features (tenancy and resource configurations) and are the whole of what is missing; `$shouldGuessMissingParameters` keeps Filament's name with [a different mechanism](nested-resources.md#guessing-walks-the-records-own-associations-never-the-request).
`$panel` is widened from `?string` (a panel id) to `Panel|string|null`, because a caller already holding the panel object — `getNavigationItems()`, the route loader, a page — should not round-trip through the registry by id.
`getIndexUrl()` carries the same `$panel` widening and lacks `$tenant`, the only one of the two it had.

### URL generation inside a live action resolves the panel the page was opened in

`Resource::getUrl()` picks the panel through `CurrentPanel::get() ?? PanelRegistry::getDefault()`, and `CurrentPanel` reads the `_sylament_panel` request attribute the [route loader](routing.md) puts on every panel route.
A live request goes to `ux_live_component`, which carries no such attribute, so without help every URL minted inside a `#[LiveAction]` or a live re-render — a post-create redirect, a `getDefaultActionUrl()` answer, a crumb — would answer for the **default** panel, and a resource mounted only in another panel would throw `RouteNotFoundException`.
Filament solves the same problem with `original_request()`, a Livewire helper that reaches the request that rendered the component.
Here the panel page carries the panel id as a `LiveProp` and stamps it back onto the live request before anything resolves ([the stamp](custom-pages.md#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism)), so every URL a page's live request mints answers for the panel that page was opened in.
A nested live component that hydrates without its host page gets no stamp, which [relation managers](relation-manager.md#every-url-a-manager-generates-on-its-own-live-request-resolves-the-default-panel) record.

### Active state is a route-name prefix match

Filament matches with `original_request()->routeIs(static::getNavigationItemActiveRoutePattern())`, a route-name glob of `getRouteBaseName() . '.*'`, so the item stays lit on the resource's create and edit pages.
Symfony's router has no `routeIs()`, so the item's `isActiveWhen()` closure tests `str_starts_with($route, $routeBaseName . '.')` on the current request's `_route`, taking `RequestStack` from [the item's own injected services](navigation-item.md#the-item-hands-its-own-services-to-evaluated-closures).
The trailing dot keeps `…resources.articles` from also matching `…resources.articles-archive`.
With no glob to hand over, there is no `getNavigationItemActiveRoutePattern()` hook; a resource that wants another rule overrides `getNavigationItems()`.

### A `new`-built resource fails fast rather than degrading

The services arrive through a `#[Required]` setter, so a resource built with `new` — a unit test, a hand-built instance — holds none of them.
Every method that needs one throws a `LogicException` naming the resource and saying it was built outside the container, rather than returning null or an empty result.
Degrading to a container-less fallback, the way [`EvaluatesClosures`](../support/component.md) does for its `Invoker`, is wrong here: there is no meaningful entity manager or URL generator to fall back to, and a silently empty query is the failure a reader will not think to question.
Pure derivations — the slug, the labels once `$recordClass` is declared, `getPages()`, `hasPage()`, and `getRouteBaseName()` for a resource with no parent — need no service and keep working.

### Resources are collected from a tag, not discovered on the filesystem

Filament finds resources two ways: `->resources([X::class])` listed on the panel, and `->discoverResources(in: …, for: …)`, which globs a directory and reflects every class it finds, skipping one whose `$isDiscovered` is false.
The port keeps the first and replaces the second with the tag: `SylamentPanelsBundle::build()` autoconfigures `sylament.panels.resource` onto every `Resource` service, and `ResourcePass` collects the tagged definitions that are not abstract and are `Resource` subclasses into the class-keyed `ServiceLocator` `ResourceRegistry` reads.
A resource must be a service to hold dependencies, and a Symfony application already declares its services with a PSR-4 glob, so a filesystem scan on top would be a second discovery mechanism disagreeing with the first; [pages make the same move](page.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute).
The locator has no stable service id (`ServiceLocatorTagPass::register()` mints one), so `ResourceRegistry` is the only way in.
What the pass collects is every registered `Resource` service, not the resources a panel lists: the registry can resolve a class-string no panel mentions, while the panel list stays authoritative for routing and navigation.
`discoverResources()` and `$isDiscovered` can return as a convenience that registers services, not as a parallel registry.

### `Resource` keeps its ported concerns in one class body

Filament splits its `Resource` across traits in [`Resources/Resource/Concerns`](../../../vendor/filament/filament/src/Resources/Resource/Concerns), and ours holds the members of `HasLabels`, `HasPages`, `HasRoutes`, `HasNavigation` and `CanGenerateUrls` in `Resource.php` itself.
Only self-contained clusters arrive as traits under Filament's names — `HasGlobalSearch`, `BelongsToParent` and `HasBreadcrumbs` — because nothing of the five is entangled in them.
Splitting the five now would file each trait against a guess at where the unported members land, and a trait whose contents are guessed is harder to move than a method; the names stay available.
Revisit when the authorization cluster lands, since that is what would push the class past one screenful per concern.

### The deprecated label pair is not ported

Filament's `$label` / `getLabel()` and `$pluralLabel` / `getPluralLabel()` are `@deprecated` in favour of `getModelLabel()` and `getPluralModelLabel()`, which are `getRecordLabel()` and `getPluralRecordLabel()` here ([renames.md](../renames.md#the-model-vocabulary-collapses-onto-record)).
The replacements read `$recordLabel` and `$pluralRecordLabel`, so a migrating resource renames the static.
