---
description: 'Resources nested under a parent resource: the registration, parent-scoped reads, nested routes, URLs and attach, and the page chain of ancestor records.'
ports:
  - vendor/filament/filament/src/Resources/ParentResourceRegistration.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToParent.php
  - vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithParentRecord.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasBreadcrumbs.php
owns:
  - packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php
  - packages/panels/src/Resources/ParentResourceRegistration.php
  - packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php
  - packages/panels/src/Resources/Resource/Concerns/HasBreadcrumbs.php
---
# Nested resources (panels)

A resource that declares a parent — `protected static ?string $parentResource = ArticleResource::class;` — mounts its pages **under** the parent's records (`/admin/articles/{article}/sections/{record}/edit`), scopes every record it resolves to the ancestor above it so a cross-parent URL 404s, attaches a record created on its pages to the parent's association, chains its breadcrumbs through the ancestors, and stays out of the sidebar and global search.
A nested resource normally registers no `index` page: its listing is the parent's, which is what [`ManageRelatedRecords`](manage-related-records.md) is.
The resource half is `BelongsToParent`, the page half `InteractsWithParentRecord`, and the rest is nested arms on the resource's URL methods, the [route loader](routing.md), the record pages and the panel's navigation loop.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`ParentResourceRegistration`](../../../packages/panels/src/Resources/ParentResourceRegistration.php) — the two resources + `relationship()` / `getRelationshipName()` | [`ParentResourceRegistration.php`](../../../vendor/filament/filament/src/Resources/ParentResourceRegistration.php) | done | — | [diverges](#the-registration-is-a-pure-value-object-that-names-nothing-it-was-not-told) |
| `getParentResource()` / `getChildResource()` on the registration | [`ParentResourceRegistration.php`](../../../vendor/filament/filament/src/Resources/ParentResourceRegistration.php) | skipped | — | [diverges](#the-registration-is-a-pure-value-object-that-names-nothing-it-was-not-told) |
| `inverseRelationship()` / `getInverseRelationshipName()` / `getInverseRelationship()` | [`ParentResourceRegistration.php`](../../../vendor/filament/filament/src/Resources/ParentResourceRegistration.php) | skipped | — | [diverges](#the-inverse-relationship-is-never-a-name-we-hold) |
| `getRelationship(Model $parentRecord)` on the registration | [`ParentResourceRegistration.php`](../../../vendor/filament/filament/src/Resources/ParentResourceRegistration.php) | skipped | — | [diverges](#the-registration-is-a-pure-value-object-that-names-nothing-it-was-not-told) |
| `getRouteName()` on the registration | [`ParentResourceRegistration.php`](../../../vendor/filament/filament/src/Resources/ParentResourceRegistration.php) | skipped | — | [diverges](#the-registration-is-a-pure-value-object-that-names-nothing-it-was-not-told) |
| `page()` / `getPageName()` on the registration | [`ParentResourceRegistration.php`](../../../vendor/filament/filament/src/Resources/ParentResourceRegistration.php) | todo | — | [detail](#the-parent-page-key-cannot-be-renamed) |
| [`$parentResource` / `getParentResource()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | [`BelongsToParent.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToParent.php) | done | — | — |
| [`asParent()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | [`BelongsToParent.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToParent.php) | done | — | [diverges](#asparent-takes-the-child-resource-because-there-is-no-stack-to-read-it-off) |
| [`getParentResourceRegistration()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | [`BelongsToParent.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToParent.php) | done | — | [semantics](#the-registration-and-the-derived-name-are-memoized-on-the-resource-service) |
| [`getParentRelationshipName()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | the registration's inflected `$relationshipName` | done | — | [diverges](#the-relationship-name-is-read-off-doctrines-mapping-never-inflected) |
| [`getParentRouteParameterName()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | `ParentResourceRegistration::getParentRouteParameterName()` | done | — | [diverges](#the-route-parameter-is-the-mappings-completing-field-and-is-not-singularised) |
| [`getParentScopedRecordQuery()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | `BelongsToParent::scopeEloquentQueryToParent()` | done | — | [diverges](#the-parent-scope-is-the-readers-narrowing-of-the-resources-own-query-not-a-predicate-we-write) |
| [`attachRecordToParent()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | [`CreateRecord::associateRecordWithParent()`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) | done | — | [renamed](../renames.md#renames) · [diverges](#the-attach-lands-on-the-resource-and-writes-the-owning-side-in-memory) |
| [`getRouteBaseName()`](../../../packages/panels/src/Resources/Resource.php)'s nested arm | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | done | — | [semantics](#a-nested-route-name-lights-the-parents-sidebar-item-for-free) |
| The nested route family — the path recursion + `_sylament_parent_params` | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) `registerRoutes()` | done | — | [diverges](#the-recursion-lives-in-the-loader-and-the-child-must-be-registered-on-the-panel) |
| The two route guards — a parameter colliding with `record` or with an ancestor | — | done | — | [diverges](#the-recursion-lives-in-the-loader-and-the-child-must-be-registered-on-the-panel) |
| [`getUrl()`](../../../packages/panels/src/Resources/Resource.php) — ancestor objects keyed | [`CanGenerateUrls.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php) | done | — | [semantics](#an-ancestor-travels-under-the-name-the-route-spells-and-a-page-already-holds-that-shape) |
| [`getUrl(shouldGuessMissingParameters:)`](../../../packages/panels/src/Resources/Resource.php) | [`CanGenerateUrls.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php) | done | — | [diverges](#guessing-walks-the-records-own-associations-never-the-request) |
| [`getIndexUrl()`](../../../packages/panels/src/Resources/Resource.php)'s parent arms | [`CanGenerateUrls.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php) | done | — | [diverges](#the-parent-arms-carry-no-relation-parameter) |
| [`resolveRecordRouteBinding($key, ?Closure $modifyQuery)`](../../../packages/panels/src/Resources/Resource.php) | [`HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | done | — | [resource.md](resource.md#the-modifier-may-answer-with-a-different-query-and-refuses-anything-else) |
| [`$parentRecords`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) — the ancestor keys on the wire | `#[Locked] public ?Model $parentRecord` | done | — | [diverges](#the-chain-rides-one-liveprop-of-keys-assigned-inside-mount) |
| [`getParentRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) + the stepwise chain resolution | [`InteractsWithParentRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) `resolveParentRecord()` | done | — | [semantics](#the-chain-is-resolved-once-per-request-and-each-step-is-scoped-to-the-one-above-it) |
| [`getParentRecordTitle()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) | [`InteractsWithParentRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) | done | — | [diverges](#getparentrecordtitle-throws-for-a-parentless-page) |
| [`getParentResourceInstance()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) | `InteractsWithParentRecord::getParentResource()` | done | — | [diverges](#the-pages-getparentresource-static-is-an-instance-hop-here) |
| [`getParentRecordScope()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) — the one modifier every key resolution runs through | the `$modifyQuery` closure `resolveParentRecord()` builds | done | — | [semantics](#one-scope-serves-the-route-key-and-the-unchecksummed-action-key) |
| [`CreateRecord`](../../../packages/panels/src/Resources/Pages/CreateRecord.php)'s parent arm — the two attaches and the many-to-many hand-back | [`CreateRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) `handleRecordCreation()` | done | — | [diverges](#the-attach-lands-on-the-resource-and-writes-the-owning-side-in-memory) · [create-record.md](create-record.md#a-child-whose-constructor-takes-its-parent-is-built-in-makerecord-and-both-attaches-leave-that-parent-alone) |
| [`ListRecords`](../../../packages/panels/src/Resources/Pages/ListRecords.php)'s scoped query | [`ListRecords.php`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) `makeTable()` | done | — | [semantics](#a-nested-resources-own-list-page-is-scoped-to-its-parent) |
| [`Resource\Concerns\HasBreadcrumbs`](../../../packages/panels/src/Resources/Resource/Concerns/HasBreadcrumbs.php) — `$breadcrumb` + `getBreadcrumb()` | [`HasBreadcrumbs.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasBreadcrumbs.php) | done | — | [resource-pages.md](resource-pages.md#the-trail-walks-the-already-resolved-ancestor-chain) |
| The `canView()` / `canEdit()` gates on every ancestor crumb | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) `getResourceBreadcrumbs()` | blocked | the resource's `HasAuthorization` | [detail](#the-nesting-surface-is-ungated-until-authorization-lands) |
| `authorizeParentRecordAccess()` / the page-level `canAccess(['record' => …])` arms | [`InteractsWithParentRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) | blocked | the resource's `HasAuthorization` | [detail](#the-nesting-surface-is-ungated-until-authorization-lands) |
| Sidebar suppression — the panel loop skips a nested resource | [`HasNavigation.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php) `registerNavigationItems()` | done | — | [semantics](#the-sidebar-skips-a-nested-resource-in-the-panels-loop) |
| Global-search suppression — a nested resource answers no result URL | `HasGlobalSearch::getGlobalSearchResultUrl()` | done | — | [global-search.md](global-search.md#a-nested-resource-answers-no-global-search-result-url) |
| `$record->setRelation(inverse, $parentRecord)` — seeding the resolved record's back-reference | [`InteractsWithParentRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) `resolveParentRecord()` | skipped | — | [diverges](#the-inverse-relationship-is-never-a-name-we-hold) |
| Morph arms in the parent scope | `scopeEloquentQueryToParent()`'s `MorphTo` branch | skipped | — | [diverges](#the-parent-scope-is-the-readers-narrowing-of-the-resources-own-query-not-a-predicate-we-write) |
| A cycle guard over `$parentResource` | — | todo | — | [detail](#a-parent-resource-cycle-hangs-route-loading) |

## Details

### The nesting surface is ungated until authorization lands

Filament gates the nesting in three places, and none of them is ported: `InteractsWithParentRecord::authorizeParentRecordAccess()` aborts 403 unless the parent resource's `canView()` or `canEdit()` allows the resolved parent record; the breadcrumb walk links an ancestor crumb only when the same two pass; and a nested page's own `canAccess()` takes the record.
**A nested page's parent chain is checked for existence and for parentage, never for permission**: the scoping is a correctness and data-leak guard, not an authorization one.
That is the exposure [every resource page carries](resource.md#no-resource-gates-anything-until-authorization-is-ported), extended one record up the chain.
The crumb gates collapse to their unauthenticated arms, which is why the trail links view → edit → unlinked with no `can*()` call in between.

### The parent page key cannot be renamed

Filament's registration takes `page(?string $name)`, and `getPageName()` answers it or the kebab-cased relationship; `getIndexUrl()`'s first parent arm looks the parent's page up under that key.
The port has neither member: the first parent arm always looks for the kebab-cased relationship name, so a parent registering its `ManageRelatedRecords` page under another key is reached only through the `view`, `edit` or `index` arms.
The member would be one more optional constructor argument on [the value object](#the-registration-is-a-pure-value-object-that-names-nothing-it-was-not-told), read where `getIndexUrl()` computes the key.

### A parent-resource cycle hangs route loading

`$parentResource` pointing back into its own chain — a resource naming itself, or an A ↔ B pair — makes every ancestor walk loop forever: the loader's `getAncestorLinks()`, `Resource::getRouteBaseName()`'s recursion and the page's chain resolution, whichever is reached first.
There is no visited set and no depth cap.
Filament shares the hole (`getRouteBaseName()` recurses through the registration the same way), and the duplicate-parameter guard cannot catch it, because that guard runs after the chain is built.
The cheap home for a fix is a compiler pass over the registered resources, where a cycle is a build-time `LogicException` rather than a request that never returns; no pass validates resource registrations, and one guard alone is not worth a pass of its own.

## Semantics

### The registration and the derived name are memoized on the resource service

`getParentResourceRegistration()` builds its `ParentResourceRegistration` once and keeps it; `getParentRelationshipName()` does the same with the derived name.
Filament re-derives both on every call, which costs it nothing, since its derivation is string inflection.
Ours reads Doctrine metadata on every URL, breadcrumb, band item and route-load pass, so the memo is what keeps a live request from re-reading it.
The memo rests on a resource being a **shared** service, which the container gives it by default; a resource registered `shared: false` gets correct answers and pays the derivation again per instance, silently.

### A nested route name lights the parent's sidebar item for free

`getRouteBaseName()` composes a nested name as the **parent's** base name plus the kebab-cased relationship — `sylament.admin.resources.articles.sections` — Filament's shape, with the *relationship* as the segment rather than the child's slug.
Since [active state is a route-name prefix match](resource.md#active-state-is-a-route-name-prefix-match), the parent's navigation item is lit for any route under `sylament.admin.resources.articles.`, so every nested page keeps the parent's sidebar item highlighted with no code of its own.

### An ancestor travels under the name the route spells, and a page already holds that shape

Ancestor records and keys travel in `$parameters` keyed by each ancestor's `getParentRouteParameterName()`, the names the route path spells (`['article' => 3]`).
A page's own `$parentRecords` prop is already that array, so every nested caller threads it with a spread: `[...$this->parentRecords, 'record' => $record]`.
The breadcrumb walk, `CreateRecord::getRedirectUrl()`, the edit page's delete redirect and the resource-page base's `getDefaultActionUrl()` arms all do; without the spread each would mint a parentless URL and throw `MissingMandatoryParametersException` on every nested page.
The route loader refuses a parent parameter named `record`, so the spread can never collide with the record key.
An **object** in any of those slots is converted to a route key by the resource that owns the parameter — the record through `$this`, each ancestor through its own resource — so a caller may hand over records, keys, or a mix.

### The chain is resolved once per request, and each step is scoped to the one above it

`getResolvedParentRecords()` walks the ancestors root-first and resolves each through **its own** resource's `resolveRecordRouteBinding()`, passing a scope built from the resource one level *below* it, so at depth two the chapter is resolved unscoped and the verse through `VerseResource::getParentScopedRecordQuery($chapter)`.
A key naming a real record of another branch is refused at the step it belongs to rather than resolved and then compared, and the failure is a `NotFoundHttpException` at that step.
The walk is memoized for the request and re-run on the next one, so a live request re-validates the chain it was handed rather than trusting the previous render.

### One scope serves the route key and the unchecksummed action key

`getParentRecordScope()` is one closure, and both readers take it: [`InteractsWithRecord::getRecord()`](interacts-with-record.md), resolving the checksummed `record` prop, and [the resource-page base's `resolveMountedActionRecord()`](resource-pages.md#a-mounted-frames-record-key-resolves-through-the-resources-own-query), resolving the `context.recordKey` a mounted action's descriptor carries, **which is client-supplied and carries no checksum**.
Filament scopes only the first.
Sharing one helper keeps the two from drifting: a mounted `DeleteAction` naming a foreign record on a nested page resolves nothing and the frame never mounts, while a sibling under the same parent still mounts.

### A nested resource's own list page is scoped to its parent

`ListRecords::table()` swaps its base query for the parent-scoped one whenever the page holds a parent record — the resource's own record query narrowed to that parent, under the same root alias — so every column, sort, search and filter the resource declares works unchanged, and every condition an overridden `getRecordQuery()` carries holds there too.
Filament supports the same hand-written nested list, and its generator never produces one: a nested resource's default shape is `create` + `edit`, with the parent doing the listing.
An **unscoped** nested list would silently show every record in the table, which is worse than not supporting the page at all.

### The sidebar skips a nested resource in the panel's loop

Filament's `Resource::registerNavigationItems()` returns early for a resource with a parent registration.
There is no port of that method — [navigation is pulled, not pushed](panel.md#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource) — so the guard sits in `Panel::getNavigation()`'s resource loop, beside `shouldRegisterNavigation()` and in Filament's order.
A nested resource still answers `shouldRegisterNavigation()` **true** and still builds an item when asked directly: the panel decides not to ask.

### A parent of any width is scoped by the reader, with no width check of panels' own

Every nested read narrows through [the reader's scope of the resource's own record query](#the-parent-scope-is-the-readers-narrowing-of-the-resources-own-query-not-a-predicate-we-write), which scopes an owner of any width [through the owner's own collection](../schemas/relationship.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path), and panels adds no predicate beyond it and the codec's `RecordKey` / `IdentifierConstraint`.
So the whole nested tier works under a parent of any width: the record page, the nested listing, a mounted action's scope, the routed create page's write, a modal `CreateAction` joining through `getDefaultActionRelationship()`, the three ancestor route-key mints (`Resource::getUrl()`, `guessAncestorParameters()` and `Page::getResourceBreadcrumbs()`), and a chain three deep with the composite level in the middle.
Panels' fixtures pin parents keyed by two scalars, by an association plus a scalar, and by a single derived identity; the join-entity flavour, whose whole identifier is two associations, is pinned one tier down at the reader.
A parent placeholder needs no pattern of its own: `PanelRouteLoader` always follows one with `/`, so the compiler's default pattern is enough, and only `{record}` is ever given `[^/]+`.

## Divergences

### The registration is a pure value object that names nothing it was not told

Filament's `ParentResourceRegistration` constructor derives three things: the child resource from `debug_backtrace(limit: 3)[2]['class']`, the relationship name by inflecting the child model's basename, and the inverse relationship name by inflecting the parent's and probing `method_exists()` for a plural.
Ours is a `final readonly` class holding exactly what it was constructed with — `parentResource`, `childResource` and an optional relationship name — and answers `getRelationshipName(): ?string` with **null** until a caller names one, leaving the derivation to the resource, which holds the entity manager.
That is the [`Relationship`](../schemas/association-reader.md) precedent: a value object carries what was declared, and metadata is read through a service.

The two resources are `public readonly` **properties** (`$registration->parentResource`) where Filament exposes `getParentResource()` / `getChildResource()`, the shape every pure value object in this codebase uses.
`getRelationship(Model $parentRecord)` is not ported: it answers an Eloquent relation object, and the Doctrine equivalent is a [`Relationship`](../../../packages/schemas/src/State/Relationship.php) the resource builds where it needs one.
`getRouteName()`, the kebab-cased relationship the route name and the parent page key are built from, has no member either: both call sites kebab `getParentRelationshipName()`, because the resource is where the name is derived and the value object does not always hold it.
The wither `relationship()` returns `new self(...)` rather than mutating, as a `readonly` class requires, so the original is untouched.

### `asParent()` takes the child resource because there is no stack to read it off

Filament's signature is `asParent(?string $childResource = null)`, and the null default is filled by `debug_backtrace(limit: 3)[2]['class']` inside the registration's constructor, assuming the frame two levels up is the child resource's `getParentResourceRegistration()`.
Ours requires it: `asParent(string $childResource)`, so an override that builds its registration off the parent names itself —

```php
public function getParentResourceRegistration(): ?ParentResourceRegistration
{
    return $this->getResourceRegistry()
        ->get(ArticleResource::class)
        ->asParent(static::class)
        ->relationship('sections');
}
```

A stack-depth read is not portable to a container that may call the method from anywhere — a compiler pass, a route load, a test — and its failure mode is a registration silently naming the wrong child.
The shorter override never names a child at all, because the base already built the registration —

```php
public function getParentResourceRegistration(): ?ParentResourceRegistration
{
    return parent::getParentResourceRegistration()?->relationship('appendixLeaves');
}
```

and the **method** is the hook, as in Filament; there is no setter.

### The relationship name is read off Doctrine's mapping, never inflected

Filament derives the parent's association as `camel(plural(classBasename($childModel)))`.
Ours reads the **parent record class's** metadata and collects every to-many association whose `targetEntity` is the child's record class: exactly one is the answer, and zero or several throw a `LogicException` naming both classes, the count, and `->relationship()` as the fix.
The demo's child entity is `ArticleSection` while the association on `Article` is `sections`; inflection would derive `articleSections`, and the route segment, the page key, every attach and every scoped query would be built on a name the mapping does not have.
Reading the mapping discovers the forward name rather than checking it, which lets a resource declare nothing but `$parentResource` in the common case; the price is the refusal when two associations from the parent target the child, where Filament would silently pick the inflected one.

### The route parameter is the mapping's completing field, and is not singularised

Filament derives `{category}` as `snake(singular($inverseRelationshipName))`, inflection over an inflected name.
Ours reads the mapping: the forward association's `mappedBy` (a bidirectional one-to-many names the child's own field, so `article` → `{article}`), else an owning-side many-to-many's `inversedBy`, else the parent record class's snake-cased basename.
That gives Filament's answer in every ordinary case, from the mapping rather than from a guess.
**Nothing is singularised**: where the completing field is plural — the `inversedBy` arm of a many-to-many, whose child field names a collection — the parameter is plural too, `{collections}` where Filament would spell `{collection}`, because singularising a field name the mapping states would invent a name no metadata carries.

### The inverse relationship is never a name we hold

`inverseRelationship()`, `getInverseRelationshipName()` and `getInverseRelationship()` are not ported, and neither is the `$record->setRelation($inverseName, $parentRecord)` seeding Filament's resolvers do after a lookup.
Doctrine's mapping *states* the counterpart — [`AssociationTarget`](../../../packages/schemas/src/State/AssociationTarget.php) surfaces `mappedBy` and `inversedBy` — so every job Filament needs the inverse name for is already answered: the route parameter reads the completing field directly, the parent scope is the reader narrowing the resource's own query rather than a `whereHas` over the inverse, and the attach writes the owning side the mapping names.
The seeding exists so Eloquent's lazy loading does not re-query a parent the resolver already has; Doctrine returns the same managed instance from the identity map.

### The parent scope is the reader's narrowing of the resource's own query, not a predicate we write

Filament's `scopeEloquentQueryToParent(Builder $query, Model $parentRecord)` is a public static mutator that adds one of three predicates to a passed query — `whereMorphedTo`, `whereBelongsTo` or a `whereHas` subquery — depending on what the inverse relation is, and every nested read hands it the resource's own `getEloquentQuery()`, so a resource that narrows its base query stays narrowed under a parent.
It has no member here.
`getParentScopedRecordQuery($parentRecord)` clones the resource's own `getRecordQuery()` and hands it to [`AssociationReader::scopeToRelationship()`](../schemas/relationship.md#scopetorelationship-narrows-a-callers-query-rather-than-building-one) under the builder's own root alias — the machinery a relation-scoped table reads through, serving both cardinalities and an owner of any width.
Every nested read consumes that one method: the record binding, the nested list, a mounted action's key and each intermediate step of the ancestor chain, so an override that hides rows — the [404-not-the-row property](resource.md#a-record-is-resolved-through-the-resources-own-query) — holds on every nested page and at every depth, Filament's composition exactly.

Porting the mutator would copy the reader's one-to-many predicate and many-to-many subselect into panels, a second opinion that can disagree with the first.
The method does not answer the reader's own freshly built relationship query either, because that would skip an overridden `getRecordQuery()`, at the resource and at every intermediate ancestor.
The clone is there for the reason [the binding clones](resource.md#a-record-is-resolved-through-the-resources-own-query): an override may return a memoised builder.
A record query rooted at an alias other than `record` is narrowed under that alias, and one naming no root alias is refused by name.
Composing onto a builder the resource wrote has two consequences: the narrowing claims the reader's parameter names and subselect aliases (`sylament_relationship_owner`, `sy_owner`, `sy_related`), so an override must not use them, and an override that fetch-joins a collection meets the binding's single-result read over a correlated `EXISTS`, an exposure Filament carries too.
Filament's morph arm has no counterpart: morphs have no Doctrine referent, the skip the tables tier records, so a polymorphic parent is not a shape this port has.

### The attach lands on the resource and writes the owning side in memory

Filament's create page calls `associateRecordWithParent($record, $parent)`, which is `getParentResourceRegistration()->getRelationship($parent)->save($record)`, an Eloquent relation save that writes and persists in one call.
Ours is `Resource::attachRecordToParent($record, $parentRecord)`, which delegates to `AssociationReader::attach()`: it writes the **owning side** in memory and returns, and `handleRecordCreation()` persists and flushes.
The owning side is what emits SQL — an inverse-only write flushes nothing — and keeping the write in memory joins the record to its parent in the same flush, so an association the mapping refuses to leave empty fails the form rather than the write.
Writing in memory costs what Eloquent's save does not: a create refused after the attach would leave the record in a many-to-many parent's collection for the rest of the request, so [the create page](create-record.md) hands it back on refusal, as [`CreateAction`](../actions/create-action.md#the-created-record-joins-the-relationship-in-the-same-flush) does.

The move onto the resource is a [rename](../renames.md#renames) as well as a relocation, because the relationship name is derived on the resource and a page-level method could only ask the resource for it and hand it back.
`CreateRecord::handleRecordCreation()` is the page-level override seam, and a consumer's override of Filament's `associateRecordWithParent()` is a method nothing calls, which is the failure a rename row exists to make greppable.

### The recursion lives in the loader, and the child must be registered on the panel

Filament's nested `registerRoutes()` recurses on the resource: a child asks its parent to register a route group and nests inside it, through the Laravel router facade.
There is no ambient router here and [the loader owns path construction](routing.md), so the recursion is `PanelRouteLoader`'s: it walks the registration chain root-first, contributing `<parent slug>/{<parent param>}` per ancestor, and mounts the child's own `getPages()` entries under the result.
The duplicate-name guard is load-bearing here, since two resources nesting under one parent derive names that differ only in the relationship segment.

The child must be registered on the panel in its own right, as in Filament; a parent does not pull its children in.
The loader throws a `LogicException` naming both resources and the panel when a chain mentions a resource the panel does not register, where Filament silently builds a route family whose parent segment resolves to nothing.
Two more guards have no Filament counterpart: a parent parameter deriving `record` collides with the record placeholder, and a parameter an ancestor already travels under collides with itself (a `Category` nested under `Category`); both throw at route-build time naming the resource and the parameter, where Filament builds the broken path.
The child's own slug stays its slug (`sections`): the nesting lives in the route path and the route **name**, so `getSlug()` and its consumers are untouched.

### Guessing walks the record's own associations, never the request

`getUrl(..., shouldGuessMissingParameters: true)` keeps Filament's parameter name and fills missing ancestor parameters by a different mechanism.
Filament copies them off the current HTTP request through `original_request()`, a Livewire helper that reconstructs the request that rendered the component.
Our live requests carry the `ux_live_component` route and none of the page's own parameters, and that is where guessing is needed (an action URL resolved during a live render).
Filament's own guessing arm admits the same wall: under its Livewire test endpoint it returns an empty string, commented *"it is unable to resolve the missing URL parameters from the parent records as it does not have access to the original request"*.

So the walk is over the data.
Starting from the nearest record object the caller passed, each missing level is read through the child→parent property the mapping names — `$section->getArticle()`, through schemas' `PropertyAccessor` — and the record found anchors the level above it.

- **A caller-supplied ancestor is trusted, not re-derived.**
  Guessing fills only an absent parameter; a key the caller passed is left alone, and the walk continues through that level by extending the property path (`verse.chapter`), so a caller mixing one record with one key still gets the rest guessed.
  A contradictory key produces a URL that 404s at request time, when the [stepwise scope](#the-chain-is-resolved-once-per-request-and-each-step-is-scoped-to-the-one-above-it) refuses it; URL time is not where parentage is checked.
- **A many-to-many step cannot be guessed.**
  There is no single field to read a parent off, so the walk drops its anchor and the generator raises Symfony's `MissingMandatoryParametersException`, the refusal a caller gets with guessing switched off.
- **Nothing is guessed unless asked.**
  The flag defaults to `false` on the resource; it is `true` by default on the page's `getResourceUrl()`, as in Filament, and the related-resource URL arms and the sub-navigation band's item URLs pass it, where the chain cannot be threaded.

### The parent arms carry no `relation` parameter

`getIndexUrl()` on a nested resource answers with the **parent's** page, in Filament's arm order: a page the parent registers under the kebab-cased relationship key (the [`ManageRelatedRecords`](manage-related-records.md) page), else the parent's `view`, else its `edit`, all three carrying the parent record as `record`, else the parent's `index`, which takes no parent key.
Only a resource no parent lists falls through to an index page of its own, which is why [a nested resource with no index page does not throw](resource.md#a-parentless-resource-with-no-index-page-throws-from-its-first-breadcrumb).
The three record-carrying arms pass the parent key **only when it is non-null**, where Filament always spreads `'record' => $record`: passing null makes the generator refuse a null parameter, while omitting it makes the generator name `record` as the missing one, which tells a caller what it forgot.

Filament's `view` and `edit` arms also append `'relation' => $relationshipName` to preselect the relation-manager tab through its `#[Url(as: 'relation')]` property.
Ours omits it although the property **is** [URL-bound](has-relation-managers.md#the-relation-binding-is-the-toolkits-only-static-url-mapping), so the parameter would preselect the tab exactly as Filament's does: it is noise in a URL a user copies, and it pins a tab the reader did not ask for.
The [unlinked crumb below](#a-crumb-whose-url-the-trail-already-spends-renders-unlinked) is a consequence of that omission.

### The chain rides one `LiveProp` of keys, assigned inside `mount()`

Filament carries `#[Locked] public ?Model $parentRecord`, one resolved model, re-resolved from the route on a non-Livewire request.
Neither half ports: a `LiveProp` cannot hold an entity (the generic base cannot declare the concrete type, and the hydrator would be handed the class-string `object`), and a live request has no route parameters to re-read.
So the prop is `#[LiveProp] public array $parentRecords` — the ancestor route **keys**, keyed by the parameter each travels under and ordered root-first — the key-on-the-wire shape [the record prop](interacts-with-record.md) uses, with trust riding the LiveProp checksum and the resolution re-run per request.

The route parameter's name varies per relationship and a `LiveProp` needs a fixed one, so the loader stamps a `_sylament_parent_params` route default listing the ancestor parameter names root-first, and `PageController` folds exactly those parameters into the one prop, unsetting each as it goes, because an unmatched route parameter falls through ux-twig-component into the component's attribute bag and would paint `chapter="1"` onto the page's root element.
A flat route carries no such default and therefore no prop.

**The assignment is a mount argument, not a property write, and that is a security property.**
ux-twig-component assigns non-mount props *after* `mount()` returns, and a record page resolves its record inside its own mount tail, so a prop-only chain would arrive too late and the first GET would resolve **unscoped**, serving the foreign record the parent scoping exists to 404.
Every record page therefore takes `mount(int|string $record, array $parentRecords = [])` and assigns the parents first ([`mountRecord()`](interacts-with-record.md#mountrecord-is-the-one-place-the-assignment-order-lives) is the one place that ordering lives), and `CreateRecord` and `ListRecords` take `mount(array $parentRecords = [])` for the same reason, although neither resolves a record at mount.
A `createLiveComponent()` test cannot see this failure, because it assigns props before the component is used, so the claim is pinned by first-GET tests over a plain HTTP client.

### `getParentRecordTitle()` throws for a parentless page

Filament's answers off `static::getParentResource()`, which is null for a page whose resource nests under nothing, so the next line's static call on null is a fatal.
Ours throws a `LogicException` naming the page and saying its resource names no parent: the same failure, with a message.

### The page's `getParentResource()` static is an instance hop here

Filament's `InteractsWithParentRecord::getParentResource()` is a static answering the parent's class-string.
Ours is `getParentResourceInstance(): ?Resource`, answering the resource **service**, the translation [`getResourceInstance()`](resource-pages.md#getresourceinstance-is-the-hop-filaments-static-calls-cannot-make) makes one level up, because everything a caller does with it (a title, a label, a binding) is an instance method here.
The class-string is reachable where it is declared, on the child resource's `getParentResource()`, under Filament's name.

### A crumb whose URL the trail already spends renders unlinked

The trail a resource page heads is `[...ancestors, this resource's listing]`, where each ancestor contributes its own listing and then the record itself, and [the record page adds its own record crumb](interacts-with-record.md) on top.
A nested resource's "listing" is its parent's record page ([the parent arms above](#the-parent-arms-carry-no-relation-parameter)), which is the crumb directly above it, so the URL repeats, and a repeated **string** key would silently collapse two crumbs into one.

So `appendBreadcrumb()` emits a crumb whose URL the trail already carries as an **int**-keyed entry, which [`Breadcrumbs`](../support/breadcrumbs.md) renders as plain text: `Articles → Genesis → Sections → In the beginning → Edit`, where `Sections` is unlinked because clicking it would land on `Genesis` again.
Filament never meets the edge, because its `view` and `edit` arms append `?relation=…`, which makes the two URLs differ; the crumb stays unlinked for as long as [that omission](#the-parent-arms-carry-no-relation-parameter) stands.
The test is on the whole trail, not on adjacency: any URL already spent is unlinked, so a non-adjacent repeat cannot collapse either.
