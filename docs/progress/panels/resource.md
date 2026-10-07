---
description: 'The resource base a user extends once per record type, the registry that resolves its class-string, and its page registrations.'
ports:
  - vendor/filament/filament/src/Resources/Resource.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/
  - vendor/filament/filament/src/Resources/Pages/PageRegistration.php
owns:
  - packages/panels/src/DependencyInjection/Compiler/ResourcePass.php
  - packages/panels/src/Resources/Pages/PageRegistration.php
  - packages/panels/src/Resources/Resource.php
  - packages/panels/src/Resources/ResourceRegistry.php
---
# Resource (panels)

every `Resource` service is autoconfigured `sylament.panels.resource` and the pass collects the non-abstract ones into the class-keyed `ServiceLocator` `ResourceRegistry` reads — [Resources](#resource-is-the-abstract-base-a-user-extends-once-per-record-type)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`Resources\Resource`](../../../packages/panels/src/Resources/Resource.php) | [`Resources/Resource.php`](../../../vendor/filament/filament/src/Resources/Resource.php) and [`Resources/Resource/Concerns`](../../../vendor/filament/filament/src/Resources/Resource/Concerns) — `HasLabels`, `HasPages`, `HasRoutes`, `HasNavigation`, `CanGenerateUrls` | in progress | — | — |
| [`Resources\ResourceRegistry`](../../../packages/panels/src/Resources/ResourceRegistry.php) | no Filament counterpart — a Filament resource is reached by calling its statics on the class-string | in progress | — | — |
| [`Resources\Pages\PageRegistration`](../../../packages/panels/src/Resources/Pages/PageRegistration.php) | [`Resources/Pages/PageRegistration.php`](../../../vendor/filament/filament/src/Resources/Pages/PageRegistration.php) | in progress | — | — |
| [`DependencyInjection\Compiler\ResourcePass`](../../../packages/panels/src/DependencyInjection/Compiler/ResourcePass.php) | no Filament counterpart — resource discovery is [`Panel/Concerns/HasComponents.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasComponents.php) `discoverResources()`, a filesystem scan | in progress | — | — |
| _rest not audited yet_ | — | — | — | — |

## Details

### `Resource` is the abstract base a user extends once per record type

`Resource` is the abstract base a user extends once per record type.
It is a **service**, not a static class-string registry — the carried decision above, now implemented, with the identity question answered the way that decision recommended: a resource is identified by its **class-string** everywhere a user names one (`->resources([ArticleResource::class])`, `protected static string $resource = ArticleResource::class` on the pages to come), and `ResourceRegistry` turns that class-string into the container-built instance.
`ResourcePass` fills the registry's `ServiceLocator` from the `sylament.panels.resource` tag `SylamentPanelsBundle::build()` autoconfigures onto every `Resource` subclass an application registers, skipping abstract definitions and anything tagged that is not a `Resource`.
Both throws name the fix: an unregistered class-string says so, and a locator answering with a non-resource says that.

Because it is a service, the whole surface is instance methods.
`form(Schema)`, `infolist(Schema)` and `table(Table)` are Filament's identity defaults verbatim — `infolist()` ships ahead of infolists themselves because it is what a [`ViewAction`](../actions/view-action.md) mounted on a resource page resolves its modal schema through, and the view page fills it in; the five services the rest needs — `EntityManagerInterface`, `UrlGeneratorInterface`, `CurrentPanel`, `PanelRegistry` and schemas' `PropertyAccessor` — arrive through one `#[Required] setResourceServices()` setter, so a subclass declaring its own constructor dependencies never has to repeat them.
A resource built with `new` answers everything that is pure derivation (slugs, labels, pages) and [throws on the first method that needs a service](#a-new-built-resource-fails-fast-rather-than-degrading).

What is ported, by cluster:

- **Record class and labels.**
  `$recordClass` plus `getRecordClass()`; `$recordLabel` / `$pluralRecordLabel` and the four getters, deriving from the record class basename through Filament's exact pipeline (`BlogPost` → `blog post` → `blog posts` → `Blog Post` / `Blog Posts`).
  The singular step is support's [`get_record_label()`](../support/helpers.md) rather than a second copy of that derivation — actions' record family reads the same label off a bare class-string, so the pipeline lives in one place and the resource delegates to it.
  Title case is `u($label)->title(allWords: true)`, the house `Str::ucwords` equivalent [`Page`](page.md) already uses.
- **Slug.**
  `$slug`, else the class basename minus a trailing `Resource`, pluralized and kebab-cased — `ArticleResource` → `articles`, `BlogPostResource` → `blog-posts`.
  The suffix is stripped only when the name actually ends with it, so a class merely *containing* `Resource` keeps its whole basename rather than being emptied by `beforeLast()`.
- **Pages.**
  `getPages(): array<string, PageRegistration>` (empty by default) and `hasPage()`.
  The keys `'index'` / `'create'` / `'edit'` are load-bearing API, exactly as in Filament: they gate navigation and URL generation and become the route-name suffix.
  `PageRegistration` is a `readonly` pair of the page class-string and its path, which [`Resources\Pages\Page::route()`](resource-pages.md) mints — `ListArticles::route('/')`, Filament's call shape, returning [a value rather than registering a route](resource-pages.md#route-returns-a-registration-rather-than-registering-a-route).
  The route itself is built by [`PanelRouteLoader`](routing.md), which needs the path as data.
- **Query and record binding.**
  `getRecordQuery()` is the [`getEloquentQuery()`](../renames.md#renames) port and the single overridable base query.
  `resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null)` resolves a URL segment [through that query rather than `EntityManager::find()`](#a-record-is-resolved-through-the-resources-own-query), reading the root alias off the builder so an overridden query still works — the second parameter is Filament's, restored with the [nested resources](nested-resources.md) it belongs to, and it may answer with a **different** builder — the parent scope answers the resource's own record query narrowed to the parent, so an override holds on every nested page — and `getRecordRouteKey()` is the generation side of the same key — `$recordRouteKeyName` when set, else the key [`RecordKey::of()`](../schemas/record-key-contract.md#the-api) mints for an identifier of **any** width.
  The resolution side splits the same way: with no `$recordRouteKeyName` it decodes the segment strictly and narrows through `IdentifierConstraint::equals()`, so a key no record of the class could mint answers no record **without running a statement**; with one, it binds that one field, [untyped and uncodec'd](#a-custom-recordroutekeyname-names-one-column-and-the-codec-never-sees-it).
- **URLs.**
  `getUrl()` / `getIndexUrl()` over `UrlGeneratorInterface::generate()`, with Filament's `blank($name)` → index fallback and its verbatim "does not have an [index] page" message.
  `getRouteBaseName(Panel)` composes `sylament.<panel>.resources.<slug>` in [exactly one place](#the-route-base-name-is-composed-in-one-place).
  All three have a nested arm: the base name nests under the parent's plus the kebab-cased relationship, `getUrl()` keys ancestor **objects** and takes `shouldGuessMissingParameters`, and `getIndexUrl()` answers from the parent's pages — [nested-resources.md](nested-resources.md) carries all three.
- **Navigation.**
  `getNavigationItems(Panel)` contributes at most one item, only when an `index` page exists, matching Filament — including the `$activeNavigationIcon ?? $navigationIcon` fallback and the `getNavigationBadge()` / `getNavigationBadgeColor()` override hooks that return `null`.
  Active state is a [route-name prefix match](#active-state-is-a-route-name-prefix-match), which is what keeps the item lit on the resource's create and edit pages.
  [`Panel::getNavigation()`](panel.md) is what calls it — the resource never pushes its item anywhere, which is the [pull model](panel.md#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource).
- **Global search.**
  The one cluster that is a **trait** rather than a section of `Resource` itself, filed exactly where Filament files it: `Sylament\Panels\Resources\Resource\Concerns\HasGlobalSearch`, which is what created the package's first `Resources/Resource/Concerns/` directory.
  Its surface, what it defers, and the whole feature around it — the two value objects, the provider contract and the panel knobs — are [global-search.md](global-search.md); nothing about it is repeated here.

- **Relations.**
  `getRelations()` returns the relation managers a record page of this resource mounts, keyed by the identity each one's tab is activated under.
  It is an instance method where Filament's is static, for the reason every other hook here is — [HasRelationManagers](has-relation-managers.md#getrelations-is-an-instance-method-like-every-other-resource-hook).

Deferred, all todo: authorization (`canAccess()` and the whole `HasAuthorization` cluster), widgets, clusters, tenancy, and resource configurations.
Two features that list once held ship: **nested/parent resources** as [`Resource\Concerns\BelongsToParent`](nested-resources.md) — the `$parentResource` static, the registration, the derived relationship name and route parameter, the parent-scoped query and the attach — and **sub-navigation** as `getRecordSubNavigation(Page)` plus `getSubNavigationPosition()`, the resource rungs of [the band](has-sub-navigation.md).
`configureTable()`'s seeding of table-level labels landed with the tables label surface — on [`ListRecords::table()`](list-records.md#the-list-pages-table-is-stamped-query-first-and-resource-last), the instance seam that stands in for Filament's static one.
The title-case **toggle** is unported with them — Filament's `$hasTitleCaseModelLabel` (default `true`) with `hasTitleCaseModelLabel()` and `titleCaseModelLabel(bool)` — so `getTitleCaseRecordLabel()` title-cases unconditionally and a subclass declaring the static silently no-ops; the two getters themselves are ported.
`NavigationItem`'s `key()`, `parentItem()` and `badgeTooltip()` are skipped here because [they are not ported on the item yet](navigation-item.md); `shouldRegisterNavigation()` is ported and, as in Filament, is consulted by the panel-side loop rather than by `getNavigationItems()` itself.
`Resource\Concerns\HasBreadcrumbs` is ported too, and it closes what this section used to record as a missing API: `protected static ?string $breadcrumb` with `getBreadcrumb(): string` answering `static::$breadcrumb ?? getTitleCasePluralRecordLabel()`, which is Filament's default verbatim.
Every crumb the [page base](resource-pages.md) builds for a resource — this one's and each ancestor's — reads it, so a resource can rename itself in the trail without touching a page.

One consequence of the breadcrumb trail lands here rather than on the page: a resource with no `index` page **throws** the moment any of its pages renders, where before the trail existed it merely contributed no navigation item.
The trail's first crumb is `getIndexUrl()`, and that is Filament's verbatim *"does not have an [index] page.
Define [getIndexUrl()] for alternative routing"* `LogicException` ([`CanGenerateUrls.php:122-123`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php)) — not a `RouteNotFoundException`, which is what Filament's own unconditional `getResourceUrl()` call would raise a layer further down.
Parity, and the message names the fix.

Nesting narrows which resources can reach it.
A **nested** resource's index is its parent's page, so `getIndexUrl()` answers from [four parent arms](nested-resources.md#the-parent-arms-carry-no-relation-parameter) before it ever tests `hasPage('index')` — which is the shape a generated nested resource has, `create` and `edit` and nothing else.
What still throws is a resource that names no parent and registers no index page, which is the case the message was always about.

Divergences, each with its own entry: [the record class is declared, never derived](#the-record-class-is-declared-never-derived-from-an-application-namespace), [the slug has no namespace-path arm](#the-slug-has-no-namespace-path-arm), [pluralization takes the inflector's last candidate](#pluralization-takes-the-inflectors-last-candidate), [there is no locale gate on pluralization](#there-is-no-locale-gate-on-pluralization), [the route base name is composed in one place](#the-route-base-name-is-composed-in-one-place), [nothing is memoised in a static](#nothing-is-memoised-in-a-static), [resources are collected from a tag, not discovered on the filesystem](#resources-are-collected-from-a-tag-not-discovered-on-the-filesystem), [a record is resolved through the resource's own query](#a-record-is-resolved-through-the-resources-own-query), [`getRecordTitle()` returns a plain string](#getrecordtitle-returns-a-plain-string), [`getUrl()` drops two parameters and widens a third](#geturl-drops-two-parameters-and-widens-a-third), [active state is a route-name prefix match](#active-state-is-a-route-name-prefix-match), and [a `new`-built resource fails fast](#a-new-built-resource-fails-fast-rather-than-degrading).

**One structural decision is deferred, not settled.**
Filament splits its `Resource` across twelve traits in [`Resources/Resource/Concerns`](../../../vendor/filament/filament/src/Resources/Resource/Concerns), five of which cover what shipped first — `HasLabels`, `HasPages`, `HasRoutes`, `HasNavigation`, `CanGenerateUrls` — and ours holds all five in one file.
`HasGlobalSearch` is the exception and does not reopen the question: it arrived whole, as one self-contained cluster with nothing of the original five entangled in it, so there was no guess to make about where its members belong.
That is the bar the rest of the split has to clear too.
The one file is deliberate for now rather than a miss: the class is the largest file in the resource tier, but the deferred list above is longer than what shipped, so splitting now would file each trait against a guess at where the unported members land — and a trait whose contents are guessed is harder to move later than a method is.
The names are Filament's and stay available; revisit when the authorization and pages clusters land, since those are what would push the file past one screenful per concern, and the categories can then be read off the finished set instead of predicted.

## Divergences

### `Resource` is a service, not a static class-string registry

**Decided — implemented.**
The base, its registry and the container wiring ship; see [Resources](#resource-is-the-abstract-base-a-user-extends-once-per-record-type) for what the class actually holds.

Filament's `Resource` is entirely static — `OrderResource::form($schema)`, `::table($table)`, `::getUrl()` — which works because Laravel's `app()` global lets a static method reach any service.
Symfony has no such global, so a static method cannot reach a dependency at all.

The consequence is not cosmetic.
If `Resource` stays static, its `form()` and `table()` cannot hold a dependency, so neither can the `OrderForm`/`OrdersTable` aggregator classes they delegate to, and the **only** remaining place a dependency can live is a per-field or per-column leaf class.
That would force every user to split everything, which is the opposite of the intent: users who want one class per form should be able to have one.

So `Resource` is registered as a service and collected by tag — the shape `AsController`/`AsEventListener` use — and `form()`/`table()`/`infolist()` become instance methods.

**What survives unchanged.**
The user-facing API stays a class-string: `protected static string $resource = OrderResource::class;` on pages, and `OrderResource::class` wherever a resource is named.
Pages resolve it through a `ServiceLocator`, exactly as `$this->createForm(OrderType::class)` resolves a form type through Symfony Form's `FormRegistry`.
That precedent is the reason this reads as Symfony idiom rather than divergence for its own sake.

**What it costs.**
`static::getResource()::form($schema)` and its siblings become a locator lookup — 66 call sites across 10 files in `vendor/filament/filament/src`, all framework code, none of it user code.
`getUrl()` and the `can*()` authorization statics cannot stay static either (they need `UrlGeneratorInterface` and an authorization checker); expect `getUrl()` to gain a Twig function and `can*()` to delegate to voters.

**Decide before writing any of it.**
Whether a resource is identified by class-string (recommended — preserves Filament's API and inherits the `FormRegistry` precedent) or by name/slug.
Pages, routes, and navigation all hang off that choice, and it is expensive to reverse once they exist.

**Answered: class-string.**
`->resources([ArticleResource::class])` on the panel, `ArticleResource::class` wherever one is named, and [`ResourceRegistry`](#resource-is-the-abstract-base-a-user-extends-once-per-record-type) resolving that class-string to the container-built instance through a `ServiceLocator` a compiler pass fills — the recommended arm, taken for the reasons above.
The slug stays what it always was in Filament, a URL segment derived from the class name and overridable per resource; it identifies a resource in a *URL*, never in code.

**Why it is safe to defer until now.**
Nothing below `Resource` depends on it: component classes get their dependencies from the container the moment they are registered, whether a static or an instance aggregator created them.

### The record class is declared, never derived from an application namespace

Filament's `getModel()` falls back to `class_basename(static::class)->beforeLast('Resource')->prepend(app()->getNamespace() . 'Models\\')`, so `ArticleResource` finds `App\Models\Article` with nothing declared.
`app()->getNamespace()` reads the application's PSR-4 root out of its `composer.json` — a Laravel-container concept with no Symfony equivalent, and one that also assumes every entity lives in a single flat namespace, which Doctrine applications routinely do not.
So the arm is dropped rather than approximated: `$recordClass` must be declared, and `getRecordClass()` throws a `LogicException` naming the resource and the property when it is not.
The cost is one line per resource; the gain is that the entity a resource speaks for is stated rather than guessed from a naming convention the port cannot enforce.

### The slug has no namespace-path arm

Filament's `resolveDefaultSlug()` has a second branch: when the resource's parent namespace segment equals the pluralized basename (`App\Filament\Resources\Articles\ArticleResource`), the slug is built from the namespace *path* after `\Resources\`, so nested directories become nested URL segments.
Ours only ever derives from the class basename.
The convention it keys on is Filament's own generator layout, which no Sylament application has yet, and the nested-slug feature it enables (nested resources, clusters) is deferred whole.
`str_replace('/', '.')` is still applied when composing the route base name, so a `$slug` containing a slash already works and the arm can return without disturbing anything downstream.

### Pluralization takes the inflector's last candidate

Laravel's `Str::plural` returns one string; Symfony's `EnglishInflector::pluralize()` returns *candidates*, ascending in specificity — `pluralize('status')` is `['status', 'statuses']` and `pluralize('person')` is `['persons', 'people']`.
Taking `[0]`, the obvious reading, would leave `StatusResource` un-pluralized and give `persons` where Laravel gives `people`, so the port takes the **last** candidate.
That matches Laravel on every word tested, but the two inflectors are different engines and their candidate sets diverge for exotic nouns; `$slug` and `$pluralRecordLabel` are the overrides when one does.
One wart is inherited from the engine rather than chosen: Symfony's inflector pluralizes an already-plural word (`Articles` → `Articless`), so a resource class whose basename is already plural and does not end with `Resource` needs an explicit `$slug`.

### There is no locale gate on pluralization

Filament wraps `Str::plural` in `locale_has_pluralization()`, which asks Symfony's own `MessageSelector` whether the active locale has more than one plural form and skips pluralizing when it does not.
The gate needs a locale, and the port has no i18n layer at all — no catalogues, no `trans` calls, no user-facing string carrying a message id ([the dashboard title](dashboard.md#the-dashboard-title-is-a-literal-not-a-translation-key) records the same absence).
Labels are English-derived literals here exactly as they are in Filament, which never translates them either, so the gate would guard nothing.
It returns with i18n.

### The route base name is composed in one place

Filament prepends the literal `resources.` in `getRouteBaseName()` *and* independently in `registerRoutes()`'s `Route::name('resources.')`, so the name a URL is generated from and the name a route is registered under are built by two pieces of code that must agree.
Ours composes `sprintf('sylament.%s.resources.%s', …)` once, in `getRouteBaseName(Panel)`, and the route loader reads it — one derivation, no lockstep to maintain.
The `Panel $panel` parameter is explicit rather than defaulted from a facade, matching [`Page::getRouteName()`](page.md#getnavigationitems-takes-the-panel-rather-than-reaching-a-facade) (the `Panel|string|null` widening is [`getUrl()`'s](#geturl-drops-two-parameters-and-widens-a-third), where a caller may hold only an id).

### Nothing is memoised in a static

Filament caches two pure derivations in `protected static` arrays keyed by class-string — `HasRoutes::$cachedDefaultSlugs` and `Resources\Pages\Page::$cachedResourcePageNames`.
Under PHP-FPM that is a per-request cache; under a long-lived worker (FrankenPHP, Swoole, RoadRunner) it lives for the worker's whole life, which is safe only because the derivations are deterministic and the classes immutable.
The port keeps no such state: every derivation recomputes, which is a handful of string operations per call and keeps the [worker-safe](layout-templates.md#the-shell-is-turbo-indifferent-and-worker-safe) constraint the shell is built under.
The same reasoning rules out Filament's mutating static setters (`navigationLabel()`, `navigationSort()`, `navigationGroup()`, `navigationIcon()`, …), which write process-global state from panel configuration; a resource is a service, so panel-level customisation of a third-party resource will be configuration on the `Panel`, not a static write.

### A record is resolved through the resource's own query

`resolveRecordRouteBinding()` clones `getRecordQuery()` and adds the identifier constraint, rather than calling `EntityManager::find()`.
The clone is there for the override: the shipped `getRecordQuery()` mints a fresh builder every call, but a subclass is free to return a memoised one, and adding a `WHERE` to a builder the list page also reads would poison it.
This is the security property Filament's `getEloquentQuery()` comment describes: a resource that scopes its base query to the current user's rows must yield a **404** for a row outside that scope, not the row.
Going through `find()` would bypass the scope entirely and leak by primary key, and the leak would be invisible — the list page would look correctly filtered while the edit URL served anything.
Under a parent the property holds the same way: every nested read — the binding, the nested list, a mounted action's key and each ancestor step — starts from a clone of the resource's own `getRecordQuery()` and only narrows it to the parent ([the composition](nested-resources.md#the-parent-scope-is-the-readers-narrowing-of-the-resources-own-query-not-a-predicate-we-write)), so an override hides its rows on every nested page at every depth.
The root alias is read off the builder (`getRootAliases()[0]`) so an overridden query that names its own alias still works, and the predicate is [`IdentifierConstraint::equals()`](../schemas/record-key-contract.md#the-operations-and-their-dql) rather than one hand-written `alias.field = :sylament_record_key`, so an identifier of any width narrows the same query in the same place.
The class it keys against is the **declared** `getRecordClass()`, never the builder's root entity: a modifier may replace the builder wholesale, and the resource still speaks for the class it says it does.
And the decode runs **before** anything is bound, so a key that class could never mint — the wrong arity, a lower-case escape, a component its field's DBAL type refuses and its `convertToDatabaseValue()` refuses too, [a type refusing it on read only](../schemas/record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer) being read back through its own writer, or one it would print back differently — is a 404 that issues no statement rather than a database error.
The **column's** width is no part of that question: a canonical integer that PHP holds and the column cannot still decodes, prints back to itself, binds, and raises the platform's own range error — a `22003` 500 on PostgreSQL, for a single `id` and for a composite key alike ([the residual](../schemas/record-key.md#an-integer-beyond-the-columns-width-still-raises-the-platforms-range-error)).
`getRecordRouteKey()` is the generation side of the same pair, replacing what Laravel got free from `UrlRoutable`; both sides read `$recordRouteKeyName`, so they cannot disagree.
It reads `getRecordClass()` first, for the "speaks for" guard, so a resource declaring `$recordRouteKeyName` without `$recordClass` throws the record-class `LogicException` rather than a key one — fail-fast ordering, and deliberate: the missing declaration is the fix.
On the default arm a record that cannot be keyed is refused by the **codec**, in its own words: `Object records must expose a readable non-blank identifier; [X] has none set.` for an identifier nothing has filled, and `The identifier of [X] is not a printable value.` for a component that is blank.
The custom `$recordRouteKeyName` arm keeps `The route key of [X] is not a printable value.` byte-identical, so the two arms no longer refuse in the same words.

Filament's second parameter shipped with the cluster it belongs to.
`resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null)` takes a query modifier, and the only closure Filament ever passes it is the parent-record scope — so it was deferred until [nested resources](nested-resources.md#the-parent-scope-is-the-readers-narrowing-of-the-resources-own-query-not-a-predicate-we-write) arrived and now carries exactly that one caller, plus the mounted-action key resolution the same scope guards.
Two facts about the modifier are ours rather than Filament's: it is handed the cloned query and keys against **what it returns**, so a scope may replace the builder wholesale, and a closure returning anything but a `QueryBuilder` or null throws rather than being ignored — a `Closure` carries no return type, so this is the only place the wrong shape is detectable.

### A custom `$recordRouteKeyName` names one column, and the codec never sees it

`$recordRouteKeyName` is Filament's own static and it keeps Filament's own meaning: the URL segment is **one property** of the record, read through schemas' `PropertyAccessor` on the way out and bound as one untyped parameter on the way back.
The key codec is consulted on neither side, and three consequences follow that the default arm no longer has.

The bind is **untyped**, so a non-numeric segment against a numeric column is a `DriverException` — a 500, because `PageController` converts only an `HttpExceptionInterface` — where the default arm answers a 404 without querying.
The named column need not be **unique** — the check below refuses one field of a several-field identifier, but asks nothing of any other column — so `getOneOrNullResult()` raises `NonUniqueResultException`, a 500 for the same reason; the default arm cannot reach that exception at any identifier width.
And the value reaches the `sy-pa-resource-record-<key>` class token raw, so a property holding whitespace [splits that token in two](custom-pages.md#getpageclasses-is-the-only-source-of-a-per-page-root-class), which the grammar makes impossible for a **composite** key.

None of the three is new and none is composite-only.
Two more shapes are refused on first use rather than left as 500s: a name the record class maps as no field — unmapped, or dotted without being an embeddable's field — would be a `QueryException`, and a name that is an **association** would bind, resolve, and then mint nothing, the property read answering an entity that *"is not a printable value."*
A name that is **one field of a several-field identifier**, which is never unique on its own, is refused too: the first render of a list minting its row URLs, or the first binding, now throws a `LogicException` naming the resource, the name and the two ways out — leave the static unset, or override `getRecordRouteKey()` and `resolveRecordRouteBinding()` together — before any statement runs.
The check reads the record class's metadata and nothing else, so it costs no statement, and a resource that overrides the pair is never refused, since only the two default arms consult it.
What stays open is what the metadata cannot see: the untyped bind, and a named column that is not unique.
The arm is otherwise left byte-identical on purpose: a user who declares the static has told us which column they mean, and the closing version of it — convert the value through the field's own DBAL type and compare it back to what the client sent — needs an addition to the frozen key contract, which is not made yet.
Typing the bind alone was executed and is worse than the gap: it trades the 500 for `5abc` silently resolving record 5.

### `getRecordTitle()` returns a plain string

Filament types it `string | Htmlable | null`, which under the project's escaping contract would be `string|Markup|null`.
Ours returns `string`.
The null arm is already unreachable in Filament — every path falls back to `getModelLabel()` — and the `Markup` arm buys nothing here because record titles compose into sentences (`sprintf('Edit %s', $title)`, a breadcrumb, a page heading), where a raw-HTML title would either be escaped by the surrounding template anyway or smuggle markup into a heading.
A resource that genuinely wants markup in a title overrides the getter and widens its own return type.
Note the fallback follows Filament exactly and is the *un*-title-cased `getRecordLabel()`, not `getTitleCaseRecordLabel()`.

The read itself needs a `catch` Filament does not: `$record?->getAttribute($attr)` answers `null` for an attribute an Eloquent model does not have, while schemas' `PropertyAccessor::getFromRecord()` propagates Symfony's `AccessException` for a property with no accessor or an uninitialized one.
Catching it and falling back to the label is what reproduces Filament's behaviour — a mis-declared `$recordTitleAttribute` degrades to the label rather than fataling a whole list page.
It is the only swallowed exception on the class; `getRecordRouteKey()` deliberately lets the same failure through, because a route key that cannot be read is a broken URL, not a cosmetic default.

One deliberate improvement over Filament sits in the same guard: a scalar is narrowed to `is_scalar($title) ? (string) $title : null` and then tested with `filled()`, where Filament tests nothing.
Filament returns whatever the attribute holds, so a record whose title column is `''` — or `false`, or whitespace — titles its page with an empty string, and the heading that reads `Edit ` looks like a rendering bug rather than missing data.
The extra test routes exactly those to the record label, which is the value the method already answers for a null record and an unreadable attribute; the same hole exists upstream, and closing it costs nothing a caller could want.
Casting **before** the test is what reaches `false`: `blank()` mirrors Laravel in calling a bool filled, so testing the raw value would let `false` through to stringify as `''`, while the cast collapses it to the empty string and leaves `0` and `'0'` filled.
The narrowing carries the non-scalar arm too — an array or an object with no `__toString()` falls to `null` and takes the same fallback — and it is what keeps the helper call legal: `is_scalar($title) && filled((string) $title)`, the shape that reads most directly, hands `filled()` a value PHPStan has already proved non-null and is a level-max error, the [`?string`-first order](../schemas/association-reader.md#the-record-key-is-tables-semantics-moved-onto-the-mapping) the association reader settled on for the same reason.

### `getUrl()` drops two parameters and widens a third

Filament's signature carries seven parameters; ours carries five.
`$tenant` and `$configuration` belong to deferred features (tenancy, resource configurations) and are the whole of what is missing — `$shouldGuessMissingParameters` ships with [nested resources](nested-resources.md#guessing-walks-the-records-own-associations-never-the-request) under Filament's name and a different mechanism: it walks the record's own associations, where Filament reads the original request.
What survives is widened: Filament takes `?string $panel` (a panel id) where ours takes `Panel|string|null`, because the caller that already holds the panel object — `getNavigationItems()`, the route loader, a page — should not have to round-trip through the registry by id.
A `null` still resolves the current panel, then the default one, which is Filament's `getCurrentOrDefaultPanel()` behaviour with the facade replaced by the two injected services.

`getIndexUrl()` carries the flag too, since it delegates into `getUrl()` and feeds the parent arms.

### Active state is a route-name prefix match

Filament matches with `original_request()->routeIs($routeBaseName . '.*')`, a route-name glob, so the `Articles` navigation item stays lit on the resource's create and edit pages.
Symfony's router has no `routeIs()`, so the glob becomes `str_starts_with($request->attributes->get('_route'), $routeBaseName . '.')`.
The trailing dot matters: without it `…resources.articles` would also match `…resources.articles-archive`.
This is the wildcard arm [`Page::getNavigationItems()`](page.md) deliberately did not need — a page matches its one route by equality — and it is why a resource's `isActiveWhen()` closure takes `RequestStack` from [the item's own injected service set](navigation-item.md#the-item-hands-its-own-services-to-evaluated-closures) rather than a matcher helper, of which there is still none.

### A `new`-built resource fails fast rather than degrading

The five services arrive through a `#[Required]` setter, so a resource built with `new` — a unit test, a user constructing one by hand — holds none of them.
Every method that needs one throws a `LogicException` naming the resource and saying it was built outside the container, rather than returning null or an empty result.
The alternative, degrading to a container-less fallback the way [`EvaluatesClosures`](../support/component.md) does for its `Invoker`, is wrong here: there is no meaningful entity manager or URL generator to fall back to, and a silently empty query is exactly the failure a reader will not think to question.
Pure derivations — slugs, labels, `getPages()`, `hasPage()`, `getRouteBaseName()` — need no service and keep working, which is what makes the class cheap to unit-test.

### Resources are collected from a tag, not discovered on the filesystem

Filament finds resources two ways, both imperative: `->resources([X::class])` listed on the panel, and `->discoverResources(in: …, for: …)`, which globs a directory and reflects every class it finds.
The port keeps the first and replaces the second with the tag — `SylamentPanelsBundle::build()` autoconfigures `sylament.panels.resource` onto every `Resource` service, and [`ResourcePass`](../../../packages/panels/src/DependencyInjection/Compiler/ResourcePass.php) collects the non-abstract ones into a class-keyed `ServiceLocator` on `ResourceRegistry`.
This is the same move [pages made](page.md#pages-register-as-live-components-through-a-compiler-pass-over-a-per-class-attribute), and for a related reason: a resource must be a **service** to hold dependencies, and a Symfony application already declares its services with a PSR-4 glob in `services.yaml`.
A filesystem scan on top of that would be a second discovery mechanism disagreeing with the first — a class the glob excluded would still be reflected, and a resource registered by hand with constructor arguments would be found twice.

Two consequences worth stating.
The locator has **no stable service id** — `ServiceLocatorTagPass::register()` mints one, and the only way in is `ResourceRegistry`, which is what keeps the collection an implementation detail.
And what the pass collects is every registered `Resource` **service**, not the resources a panel lists in `resources()`: the registry can resolve a class-string the panel never mentioned, exactly as `PageComponentPass` tags pages no panel routes.
The panel list stays authoritative for routing and navigation; the registry is only the class-string → instance lookup.

`discoverResources()` itself is deferred rather than rejected.
If it ever returns it is a convenience over `services.yaml` — a helper that registers the services — not a parallel registry.

### URL generation inside a live action resolves the default panel

`Resource::getUrl()` picks the panel through `CurrentPanel::get() ?? PanelRegistry::getDefault()`, and `CurrentPanel` reads the `_sylament_panel` request attribute — which the [route loader](routing.md) puts on every panel page route.
A live request does not go to a panel route: it goes to `ux_live_component`, which carries no such attribute, so `CurrentPanel::get()` returns null and the **default panel** answers.

Everything a create/edit page generates runs there — the post-create redirect and the `getDefaultActionUrl()` answers all happen inside a `#[LiveAction]` or a live re-render.
For the overwhelmingly common single-panel application, and for any application whose resources live in the default panel, the answer is right.
It is wrong for a resource mounted **only** in a non-default panel: its create page would redirect to a URL under the default panel's path, and the route name would not exist, so `UrlGenerator` throws `RouteNotFoundException` — loud, at least, rather than a wrong page.

Filament has the same problem and solves it with `original_request()`, a Livewire helper that reaches the request that rendered the component rather than the one carrying the update.

**It is closed for every page.**
The `#[LiveProp] public ?string $panelId`, minted by the getter its dehydration reads and stamped back onto the request from a `#[PostHydrate]` hook — the mechanism [`GlobalSearch`](global-search.md#the-component-restores-its-panel-onto-a-live-request) already used, ported down a tier — sits on [`Pages\Page`](page.md), where the stamp takes [priority 20](custom-pages.md#the-stamp-runs-above-actions-own-hydration-hook-and-the-priority-is-the-whole-mechanism) so it precedes actions' own re-resolution of a mounted action at 10.
So a live request that hydrates **any** panel page resolves the panel that page was opened in, and every URL that request mints — the trail, the band, a redirect, an action link — answers for it.
Nesting is what makes it necessary: without the prop, every live re-render of a nested page on a non-default panel throws `RouteNotFoundException`.

One half stays open.
A **nested live component** hydrating on its own — a [relation manager](relation-manager.md#every-url-a-manager-generates-resolves-the-default-panel) answering a table sort — never hydrates its host page, so nothing stamps the attribute for it.
It waits on the panel id reaching nested live components as well.
