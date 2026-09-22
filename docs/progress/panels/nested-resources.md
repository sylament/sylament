# Nested resources (panels)

A resource that declares a parent — `protected static ?string $parentResource =
ArticleResource::class;` — and everything that follows from it: its pages mount
**under** the parent's records
(`/admin/articles/{article}/sections/{record}/edit`), every record it resolves is
scoped to the ancestor above it so a cross-parent URL 404s, a record created on
one of its pages joins the parent's association, its breadcrumbs chain through
the ancestors, and both the sidebar and global search leave it out. A nested
resource normally registers no `index` page at all: its listing is the parent's,
which is what [`ManageRelatedRecords`](manage-related-records.md) is.

The capability is spread over one value object, two concerns and arms on
everything that already knew how to mount, link and resolve a resource:

- [`Resources\ParentResourceRegistration`](../../../packages/panels/src/Resources/ParentResourceRegistration.php)
  — the parent → child link, and the association name a caller may name on it.
- [`Resource\Concerns\BelongsToParent`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php)
  — the resource half: the static, the registration, the derived relationship and
  route parameter, the parent-scoped query, the attach, and the ancestor walk the
  URL arms guess through.
- [`Resources\Pages\Concerns\InteractsWithParentRecord`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php)
  — the page half: one `parentRecords` `LiveProp` of route **keys**, resolved
  root-first and stepwise-scoped, memoized per request.
- [`Routing\PanelRouteLoader`](../../../packages/panels/src/Routing/PanelRouteLoader.php)
  — the nested path recursion and the `_sylament_parent_params` route default;
  [`Controllers\PageController`](../../../packages/panels/src/Controllers/PageController.php)
  turns those parameters into the one prop.
- [`Resource`](../../../packages/panels/src/Resources/Resource.php)'s URL arms —
  `getRouteBaseName()`, `getUrl()` (object conversion + parameter guessing),
  `getIndexUrl()`'s four parent arms — and
  `resolveRecordRouteBinding($key, ?Closure $modifyQuery)`, whose second
  parameter returns here.
- The page arms: [`CreateRecord`](README.md#createrecord) attaches,
  [`ListRecords`](README.md#listrecords) scopes, the
  [resource-page base](README.md#resource-pages) chains the breadcrumbs and
  threads the chain into every URL it mints.
- The suppressions:
  [`Panel\Concerns\HasNavigation`](../../../packages/panels/src/Panel/Concerns/HasNavigation.php)
  skips a nested resource in the navigation loop, and
  [`Resource\Concerns\HasGlobalSearch`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php)
  answers no result URL for one.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`Resources/ParentResourceRegistration.php`](../../../vendor/filament/filament/src/Resources/ParentResourceRegistration.php)
  — the registration, its inflected names and its route-parameter derivation
- [`Resources/Resource/Concerns/BelongsToParent.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToParent.php)
  — the resource half, including `scopeEloquentQueryToParent()`
- [`Resources/Pages/Concerns/InteractsWithParentRecord.php`](../../../vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithParentRecord.php)
  — the page half and its ancestor walk
- [`Resources/Resource/Concerns/CanGenerateUrls.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php)
  — `getUrl()`'s guessing arm and `getIndexUrl()`'s four parent arms
- [`Resources/Resource/Concerns/HasRoutes.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php)
  — the nested `registerRoutes()` recursion and `getRouteBaseName()`
- [`Resources/Resource/Concerns/HasBreadcrumbs.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasBreadcrumbs.php),
  [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php)
  `getResourceBreadcrumbs()` — the ancestor trail

## Surface

Every point of this capability. _Blocked by_ names what has to land first.
_Detail_ carries three links, any of them: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **semantics** → the
permanent note in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections
do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`ParentResourceRegistration`](../../../packages/panels/src/Resources/ParentResourceRegistration.php) — the two resources + `relationship()` / `getRelationshipName()` | [`ParentResourceRegistration.php`](../../../vendor/filament/filament/src/Resources/ParentResourceRegistration.php) | done | — | [diverges](#the-registration-is-a-pure-value-object-that-names-nothing-it-was-not-told) |
| `getParentResource()` / `getChildResource()` on the registration | same two | skipped | — | [diverges](#the-registration-is-a-pure-value-object-that-names-nothing-it-was-not-told) |
| `inverseRelationship()` / `getInverseRelationshipName()` / `getInverseRelationship()` | same three | skipped | — | [diverges](#the-inverse-relationship-is-never-a-name-we-hold) |
| `getRelationship(Model $parentRecord)` on the registration | same | skipped | — | [diverges](#the-registration-is-a-pure-value-object-that-names-nothing-it-was-not-told) |
| `getRouteName()` on the registration | same | skipped | — | [diverges](#the-registration-is-a-pure-value-object-that-names-nothing-it-was-not-told) |
| [`$parentResource` / `getParentResource()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | [`BelongsToParent`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToParent.php) | done | — | — |
| [`asParent()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | `BelongsToParent::asParent()` | done | — | [diverges](#asparent-takes-the-child-resource-because-there-is-no-stack-to-read-it-off) |
| [`getParentResourceRegistration()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | `BelongsToParent::getParentResourceRegistration()` | done | — | [semantics](#the-registration-and-the-derived-name-are-memoized-on-the-resource-service) |
| [`getParentRelationshipName()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | the registration's inflected `$relationshipName` | done | — | [diverges](#the-relationship-name-is-read-off-doctrines-mapping-never-inflected) |
| [`getParentRouteParameterName()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | `ParentResourceRegistration::getParentRouteParameterName()` | done | — | [diverges](#the-route-parameter-is-the-mappings-completing-field-and-is-not-singularised) |
| [`getParentScopedRecordQuery()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | `BelongsToParent::scopeEloquentQueryToParent()` | done | — | [diverges](#the-parent-scope-is-the-readers-relationship-query-not-a-predicate-we-write) |
| [`attachRecordToParent()`](../../../packages/panels/src/Resources/Resource/Concerns/BelongsToParent.php) | [`CreateRecord::associateRecordWithParent()`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) | done | — | [renamed](../renames.md#renames) · [diverges](#the-attach-lands-on-the-resource-and-writes-the-owning-side-in-memory) |
| [`getRouteBaseName()`](../../../packages/panels/src/Resources/Resource.php)'s nested arm | [`HasRoutes::getRouteBaseName()`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php) | done | — | [semantics](#a-nested-route-name-lights-the-parents-sidebar-item-for-free) |
| The nested route family — the path recursion + `_sylament_parent_params` | [`HasRoutes::registerRoutes()`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php)'s parent recursion | done | — | [diverges](#the-recursion-lives-in-the-loader-and-the-child-must-be-registered-on-the-panel) |
| The two route guards — a parameter colliding with `record` or with an ancestor | no counterpart — Filament builds the broken route | done | — | [diverges](#the-recursion-lives-in-the-loader-and-the-child-must-be-registered-on-the-panel) |
| [`getUrl()`](../../../packages/panels/src/Resources/Resource.php) — ancestor objects keyed | `CanGenerateUrls::getUrl()`'s `$parameters` walk | done | — | [semantics](#an-ancestor-travels-under-the-name-the-route-spells-and-a-page-already-holds-that-shape) |
| [`getUrl(shouldGuessMissingParameters:)`](../../../packages/panels/src/Resources/Resource.php) | the same parameter, filled from `original_request()` | done | — | [diverges](#guessing-walks-the-records-own-associations-never-the-request) |
| [`getIndexUrl()`](../../../packages/panels/src/Resources/Resource.php)'s four parent arms | `CanGenerateUrls::getIndexUrl()` | done | — | [diverges](#the-parent-arms-carry-no-relation-parameter) |
| [`resolveRecordRouteBinding($key, ?Closure $modifyQuery)`](../../../packages/panels/src/Resources/Resource.php) | `HasRoutes::resolveRecordRouteBinding()` | done | — | [semantics](#the-modifier-may-answer-with-a-different-query-and-refuses-anything-else) |
| [`$parentRecords`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) — the ancestor keys on the wire | `#[Locked] public ?Model $parentRecord` | done | — | [diverges](#the-chain-rides-one-liveprop-of-keys-assigned-inside-mount) |
| [`getParentRecord()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) + the stepwise chain resolution | `InteractsWithParentRecord::resolveParentRecord()` | done | — | [semantics](#the-chain-is-resolved-once-per-request-and-each-step-is-scoped-to-the-one-above-it) |
| [`getParentRecordTitle()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) | same | done | — | [diverges](#getparentrecordtitle-throws-for-a-parentless-page) |
| [`getParentResourceInstance()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) | `InteractsWithParentRecord::getParentResource()` | done | — | [diverges](#the-pages-getparentresource-static-is-an-instance-hop-here) |
| [`getParentRecordScope()`](../../../packages/panels/src/Resources/Pages/Concerns/InteractsWithParentRecord.php) — the one modifier every key resolution runs through | the `$modifyQuery` closure `resolveRecord()` builds | done | — | [semantics](#one-scope-serves-the-route-key-and-the-unchecksummed-action-key) |
| [`CreateRecord`](../../../packages/panels/src/Resources/Pages/CreateRecord.php)'s parent arm — attach before the flush | [`CreateRecord::handleRecordCreation()`](../../../vendor/filament/filament/src/Resources/Pages/CreateRecord.php) | done | — | [diverges](#the-attach-lands-on-the-resource-and-writes-the-owning-side-in-memory) |
| [`Page::getDefaultActionRelationship()`](../../../packages/panels/src/Resources/Pages/Page.php) — a modal create under the parent | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | done | — | — |
| [`ListRecords`](../../../packages/panels/src/Resources/Pages/ListRecords.php)'s scoped query | [`ListRecords::makeTable()`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php)'s `when($parentRecord)` arm | done | — | [semantics](#the-nested-list-page-ships-unexercised-by-any-shipped-page) |
| [`Resource\Concerns\HasBreadcrumbs`](../../../packages/panels/src/Resources/Resource/Concerns/HasBreadcrumbs.php) — `$breadcrumb` + `getBreadcrumb()` | [`HasBreadcrumbs`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasBreadcrumbs.php) | done | — | — |
| [`Page::getResourceBreadcrumbs()`](../../../packages/panels/src/Resources/Pages/Page.php) / `hasResourceBreadcrumbs()` — the ancestor trail | [`Resources/Pages/Page.php`](../../../vendor/filament/filament/src/Resources/Pages/Page.php) | done | — | [diverges](#a-crumb-whose-url-the-trail-already-spends-renders-unlinked) |
| The `canView()` / `canEdit()` gates on every crumb | the same two, per ancestor | todo | the authorization epic | [detail](#the-nesting-surface-is-ungated-until-the-authorization-epic) |
| Sidebar suppression — the panel loop skips a nested resource | `registerNavigationItems()`'s third early return | done | — | [semantics](#the-suppressions-sit-where-the-trail-already-put-them) |
| Global-search suppression — a nested resource answers no result URL | `getGlobalSearchResultUrl()`'s own admitted limitation | done | — | [semantics](#the-suppressions-sit-where-the-trail-already-put-them) |
| `authorizeParentRecordAccess()` / the page-level `canAccess(['record' => …])` arms | `InteractsWithParentRecord::authorizeParentRecordAccess()` | todo | the authorization epic | [detail](#the-nesting-surface-is-ungated-until-the-authorization-epic) |
| `$record->setRelation(inverse, $parentRecord)` — seeding the resolved record's back-reference | `resolveRecord()` / `resolveParentRecord()` | skipped | — | [diverges](#the-inverse-relationship-is-never-a-name-we-hold) |
| Morph arms in the parent scope | `scopeEloquentQueryToParent()`'s `MorphTo` branch | skipped | — | [diverges](#the-parent-scope-is-the-readers-relationship-query-not-a-predicate-we-write) |
| A cycle guard over `$parentResource` | no counterpart — Filament shares the hole | todo | — | [detail](#a-parent-resource-cycle-hangs-route-loading) |
| Clusters as a second nesting axis (`BelongsToCluster`) | [`BelongsToCluster.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToCluster.php) | todo | clusters | — |

[`Unit/Resources/ParentResourceRegistrationTest`](../../../packages/panels/tests/Unit/Resources/ParentResourceRegistrationTest.php)
pins the value object — the defaults, the wither leaving the original untouched,
a re-naming, and `asParent()` building one off the parent.
[`Unit/Resources/BelongsToParentTest`](../../../packages/panels/tests/Unit/Resources/BelongsToParentTest.php)
covers the derivation end to end: no registration and the parentless refusal, the
memoized registration, both derived names at one and two levels, all three route
parameter arms (`mappedBy`, `inversedBy`, the snake-cased basename), the
two-candidate refusal and the `->relationship()` override that settles it, the
attach surviving a flush and clear, and the scoped query's `record` alias, DQL
and rows.
[`Unit/Resources/ResourceNestedUrlTest`](../../../packages/panels/tests/Unit/Resources/ResourceNestedUrlTest.php)
pins the URL half against a real `UrlGenerator`: the nested base name, ancestor
objects keyed and a wrong-class one refused, seven guessing cases including the
many-to-many stop and the two refusals — nothing to guess from, and guessing
switched off — all four
`getIndexUrl()` arms plus the missing-parent message and the scalar-key arm, and
the three `resolveRecordRouteBinding()` modifier arms.
[`Unit/Resources/Pages/Concerns/InteractsWithParentRecordTest`](../../../packages/panels/tests/Unit/Resources/Pages/Concerns/InteractsWithParentRecordTest.php)
covers the page half: the parentless page, the one- and two-deep chains, three
404 shapes (another parent's record, an ancestor key naming nothing, no ancestor
key at all), the stepwise scope proof and the one-resolution-per-request memo.
[`Integration/Resources/Pages/NestedResourcePagesTest`](../../../packages/panels/tests/Integration/Resources/Pages/NestedResourcePagesTest.php)
is the HTTP half — a nested edit page at both depths, **three first-GET 404s over
a plain client**, the live re-request keeping both props on the wire, the trail
at depth two and its untitled-record arm, the scoped list, the create that
attaches and redirects, the create modal that attaches, the delete that redirects
onto the parent, the link-style action URLs carrying the whole chain, and the
mounted-frame guard in both directions.
[`Unit/Routing/PanelRouteLoaderTest`](../../../packages/panels/tests/Unit/Routing/PanelRouteLoaderTest.php)
and
[`Integration/Routing/PanelRoutesTest`](../../../packages/panels/tests/Integration/Routing/PanelRoutesTest.php)
pin the routes — the two depths, the collapsed nested index, the absent
`_sylament_parent_params` on every flat route, the three refusals, and the real
router agreeing with the paths the URL suite generates against.
[`Integration/Controllers/PageControllerTest`](../../../packages/panels/tests/Integration/Controllers/PageControllerTest.php)
pins the prop mapping, including that a parent parameter never lands as an
attribute of its own.
Suppression is
[`Integration/PanelNavigationTest`](../../../packages/panels/tests/Integration/PanelNavigationTest.php)
(the nested resource absent from the navigation, while `shouldRegisterNavigation()`
and `getNavigationItems()` still answer) and
[`Integration/Resources/Resource/Concerns/HasGlobalSearchTest`](../../../packages/panels/tests/Integration/Resources/Resource/Concerns/HasGlobalSearchTest.php).

## Details

Specifics for rows that are not finished. Delete a subsection when its row flips
to **done**; the table row and any divergence it earned stay behind.

### The nesting surface is ungated until the authorization epic

Filament gates the nesting in three places, and none of them is ported:
`InteractsWithParentRecord::authorizeParentRecordAccess()` aborts 403 unless the
parent resource's `canView()` or `canEdit()` allows the resolved parent record;
the breadcrumb walk links an ancestor crumb only when the same two pass; and a
nested page's own `canAccess()` takes the record. Stated plainly: **a nested
page's parent chain is checked for existence and for parentage, never for
permission** — the scoping is a correctness and data-leak guard, not an
authorization one. That is the same exposure every resource page already carries
([resource pages](README.md#resource-pages)), extended one record up the chain.
The crumb gates collapse to their unauthenticated arms in the meantime, which is
why the trail links view → edit → unlinked with no `can*()` call in between.

### A parent-resource cycle hangs route loading

`$parentResource` pointing back into its own chain — a resource naming itself, or
an A ↔ B pair — makes both ancestor walks loop forever: the loader's
`getAncestorLinks()` and `Resource::getRouteBaseName()`'s recursion, whichever is
reached first. There is no visited set and no depth cap. Filament shares the hole
exactly (`getRouteBaseName()` recurses through the registration the same way), so
this is not a regression, and the duplicate-parameter guard cannot catch it —
that guard runs *after* the chain is built. The cheap home for a fix is a
compiler pass over the registered resources, where a cycle is a build-time
`LogicException` rather than a request that never returns; it is recorded rather
than written because nothing in the toolkit validates resource registrations
today and one guard is not worth a pass of its own.

## Semantics

Faithful ports subtle enough that re-deriving them from the source would get them
wrong.

### The registration and the derived name are memoized on the resource service

`getParentResourceRegistration()` builds its `ParentResourceRegistration` once
and keeps it; `getParentRelationshipName()` does the same with the derived name.
Filament re-derives both on every call, which costs it nothing — its derivation
is string inflection. Ours reads Doctrine metadata, and the read happens on every
URL, every breadcrumb, every band item and every route-load pass, so the memo is
what keeps a live request O(1).

The memo rests on a resource being a **shared** service, which is what the
container gives it by default ([Resources](README.md#resources)). A consumer that
registers its resources non-shared gets a correct answer and pays the derivation
again per instance; nothing breaks, and nothing announces it either. Worth
knowing before declaring `shared: false` on a resource for unrelated reasons.

### A nested route name lights the parent's sidebar item for free

`getRouteBaseName()` composes a nested name as the **parent's** base name plus
the kebab-cased relationship — `sylament.admin.resources.articles.sections` —
which is Filament's shape exactly, and the segment is the *relationship*, not the
child's slug. The free consequence is that
[active state is a route-name prefix match](README.md#active-state-is-a-route-name-prefix-match):
the parent's navigation item is lit for any route under
`sylament.admin.resources.articles.`, so every nested page keeps the parent's
sidebar item highlighted with no code at all. `PanelNavigationTest` pins it, so a
later change to either half cannot quietly break the other.

### An ancestor travels under the name the route spells, and a page already holds that shape

Ancestor records and keys travel in `$parameters` keyed by each ancestor's
`getParentRouteParameterName()` — the same names the route path spells
(`['article' => 3]`). A page's own `$parentRecords` prop is already exactly that
array, so every nested caller threads it with a spread:
`[...$this->parentRecords, 'record' => $record]`. That is what the breadcrumb
walk, `CreateRecord::getRedirectUrl()`, the edit page's delete redirect and the
resource-page base's three `getDefaultActionUrl()` arms all do; without the
spread each of them would mint a parentless URL and throw
`MissingMandatoryParametersException` on every nested page. The route loader
refuses a parent parameter named `record`, so the spread can never collide with
the record key.

An **object** in any of those slots is converted to a route key by the resource
that owns the parameter — the record through `$this`, each ancestor through its
own resource — so a caller may hand over records, keys, or a mix.

### The chain is resolved once per request, and each step is scoped to the one above it

`getResolvedParentRecords()` walks the ancestors root-first and resolves each one
through **its own** resource's `resolveRecordRouteBinding()`, passing a scope
built from the resource one level *below* it — so at depth two, the chapter is
resolved unscoped, and the verse is resolved through
`VerseResource::getParentScopedRecordQuery($chapter)`. A key naming a real record
of another branch is therefore refused at the step it belongs to rather than
resolved and then compared, and the failure is a `NotFoundHttpException` at that
step. The whole walk is memoized for the request and re-run on the next one, so a
live request re-validates the chain it was handed rather than trusting the
previous render.

### One scope serves the route key and the unchecksummed action key

`getParentRecordScope()` is one closure, and both readers take it:
[`InteractsWithRecord::getRecord()`](interacts-with-record.md), resolving the
checksummed `record` prop, and the resource-page base's
`resolveMountedActionRecord()`, resolving the `context.recordKey` a mounted
action's descriptor carries — **which is client-supplied and carries no
checksum**. Filament scopes only the first. Sharing one helper is what keeps the
two from drifting: a mounted `DeleteAction` naming a foreign record on a nested
page resolves nothing and the frame never mounts, which
`NestedResourcePagesTest` pins in both directions (the foreign key refused, a
sibling under the same parent still mounting).

### The modifier may answer with a different query, and refuses anything else

`resolveRecordRouteBinding($key, ?Closure $modifyQuery)` hands the closure the
cloned record query and keys against **what it returns**, falling back to the
query it passed when the closure returns null — so the parent scope can replace
the builder wholesale rather than adding a predicate to it, which is what
[the reader's relationship query](#the-parent-scope-is-the-readers-relationship-query-not-a-predicate-we-write)
needs. A closure returning anything else throws a `LogicException` naming the
resource and the type it answered with, rather than being silently ignored: a
`Closure` carries no return type information, so the wrong shape is only
detectable here.

### The nested list page ships unexercised by any shipped page

`ListRecords::table()` swaps its base query for the parent-scoped one whenever
the page holds a parent record, and roots it at the same `record` alias, so every
column, sort, search and filter the resource declares works unchanged. Filament
supports the same hand-written nested list and its generator never produces one —
a nested resource's default shape is `create` + `edit` with the parent doing the
listing. Ours ships it for the same reason Filament does, and for one more: an
**unscoped** nested list would silently show every record in the table, which is
worse than not supporting the page at all. Nothing shipped or demoed renders one;
a fixture resource that registers an index page is the whole of its coverage.

### The suppressions sit where the trail already put them

Filament's `Resource::registerNavigationItems()` has four early returns, one of
which is a non-null parent registration. There is no port of that method —
[navigation is pulled, not pushed](README.md#resource-navigation-is-pulled-through-the-registry-not-pushed-by-the-resource)
— so the guard lands in `Panel::getNavigation()`'s resource loop, beside
`shouldRegisterNavigation()` and in Filament's own order. `PanelNavigationTest`
pins that a nested resource still answers `shouldRegisterNavigation()` **true**
and still builds an item when asked directly: the panel decides not to ask, which
is the recorded rule that a resource answering `getNavigationItems()` cannot also
decide it should not have been asked.

Global search is Filament's own admitted limitation ported with its shape:
`getGlobalSearchResultUrl()` returns null for a nested resource, because its
pages need ancestor parameters a search result carries nothing to fill. No
`canGloballySearch()` guard is needed on top — the default provider's loop already
`continue`s past a blank URL and skips a resource that yields no results — so a
searchable nested resource renders no dead rows rather than rows that go nowhere.

### A parent of any width scopes, and the two interim guards that stood in for it are gone

A nested resource scopes every read to its parent through
[the reader's relationship query](#the-parent-scope-is-the-readers-relationship-query-not-a-predicate-we-write),
and until [M7 · 5](../roadmap.md#m7--composite-identifiers) that query could not
bind a parent record carrying more than one identifier field. `BelongsToParent`
asked first, in panel vocabulary — *"whose records of [%s] are not keyed by a
single scalar identifier; scoping a nested resource to such a parent record is
not built yet"* — at two throw sites under one predicate:
`getParentScopedRecordQuery()`, covering every read, and `attachRecordToParent()`,
covering the routed nested **create** page's write, which reaches the first
nowhere and would otherwise have persisted a row and redirected into a 500.
[The twin one tier over](relation-manager.md#the-relationship-chokepoint-judges-nothing-about-the-owner-and-the-setter-it-needed-is-gone)
guarded relation managers on the same predicate.

Both are deleted, and nothing stands in their place: the reader now scopes an
owner of any width
[through the owner's own collection](../schemas/association-reader.md#an-owner-of-any-width-is-scoped-through-its-own-collection-rather-than-through-a-mapped-by-path),
so the whole nested tier works under a scalar-pair, a derived-identity and a
join-entity parent — the record page, the nested listing, a mounted action's
scope, the routed create page's write, the three ancestor route-key mints
(`Resource::getUrl()`, `guessAncestorParameters()` and
[`Page::getResourceBreadcrumbs()`](#a-crumb-whose-url-the-trail-already-spends-renders-unlinked)),
and a chain three deep with the composite level in the **middle**.

Three things the removal settled rather than merely dropped. The guards'
predicate was **wider** than the reader's — it also refused a *single derived
identity*, which the reader never refused — so panels and the relation layer had
disagreed about one flavour and now do not. The one path that reached the
relation layer **past** both throw sites, a modal `CreateAction` taking its
relationship from `Page::getDefaultActionRelationship()` on a page that resolves
no record of its own, was recorded as unguarded rather than closed, on the
grounds that refusing there would need a new public entry point on `Resource` for
a guard M7 · 5 would delete whole — and it simply works now, which is what that
bet was. And one unstated invariant is what keeps a parent placeholder safe and
is pinned rather than left to be rediscovered:
[`PanelRouteLoader`](#the-recursion-lives-in-the-loader-and-the-child-must-be-registered-on-the-panel)
always appends `/` after a parent placeholder, so M7 · 3's greedy `[^/]+` — which
is stamped on `{record}` alone — is not needed on one, and the compiler's default
pattern is enough.

## Divergences

### The registration is a pure value object that names nothing it was not told

Filament's `ParentResourceRegistration` constructor derives three things:
the child resource from `debug_backtrace(limit: 3)[2]['class']`, the relationship
name by inflecting the child model's basename, and the inverse relationship name
by inflecting the parent's and probing `method_exists()` for a plural. Ours holds
exactly what it was constructed with — `parentResource`, `childResource` and an
optional `relationshipName` — and answers `getRelationshipName(): ?string` with
**null** until a caller names one, leaving the derivation to the resource, which
is the object that holds the entity manager. That is the
[`Relationship`](../schemas/association-reader.md) precedent: a value object
carries what was declared, and metadata is read through a service.

Three consequences. The two resources are `public readonly` **properties**
(`$registration->parentResource`) where Filament exposes `getParentResource()` /
`getChildResource()` getters — the shape every pure VO in this codebase uses, so
a getter pair would be the odd one out. `getRelationship(Model $parentRecord)` is
not ported: it answers an Eloquent relation object, and the Doctrine equivalent
is a [`Relationship`](../../../packages/schemas/src/State/Relationship.php) the
resource builds where it needs one. And `getRouteName()` — the kebab-cased
relationship the route name and the `getIndexUrl()` page key are both built from
— has no member either: both call sites read
`getParentRelationshipName()` and kebab it, because the resource is where the
name is derived and a second accessor on the VO would only be able to kebab a
name the VO does not always hold.

The wither is `new self(...)` rather than `clone`: PHP forbids writing a
`readonly` property on a clone outside `__clone()`, so a clone-and-mutate would
fatal. The original is untouched either way, which is what the wither promises.

### `asParent()` takes the child resource because there is no stack to read it off

Filament's signature is `asParent(?string $childResource = null)`, and the null
default is filled by `debug_backtrace(limit: 3)[2]['class']` inside the
registration's constructor — the call frame two levels up is assumed to be the
child resource's `getParentResourceRegistration()`. Ours requires it:
`asParent(string $childResource)`, so an override that builds its registration
off the parent names itself —

```php
public function getParentResourceRegistration(): ?ParentResourceRegistration
{
    return $this->getResourceRegistry()
        ->get(ArticleResource::class)
        ->asParent(static::class)
        ->relationship('sections');
}
```

A stack-depth read is not portable to a container that may call the method from
anywhere — a compiler pass, a route load, a test — and the failure mode is a
registration silently naming the *wrong* child. `static::class` at the call site
says the same thing, is one word longer, and cannot be wrong.

`asParent()` is the long way round, and it is the arm that needs the required
argument: the shorter override, and the one the suite pins, never names a child
at all, because the base already built the registration —

```php
public function getParentResourceRegistration(): ?ParentResourceRegistration
{
    return parent::getParentResourceRegistration()?->relationship('appendixLeaves');
}
```

which is the whole customization surface. The **method** is the hook, exactly as
in Filament; there is no setter, and nothing derives anything a second time.

### The relationship name is read off Doctrine's mapping, never inflected

Filament derives the parent's association as
`camel(plural(classBasename($childModel)))`. Ours reads the **parent record
class's** metadata and collects every to-many association whose `targetEntity` is
the child's record class: exactly one is the answer, and zero or several throw a
`LogicException` naming both classes, the count, and `->relationship('…')` as the
fix.

This is load-bearing rather than tidier. The demo's child entity is
`ArticleSection` while the association on `Article` is `sections`; inflection
derives `articleSections`, and the route segment, the page key, every attach and
every scoped query would be built on a name the mapping does not have. Reading
the mapping also means the *forward* name is discovered, not just checked — which
is what lets a resource declare nothing but `$parentResource` in the common case.
The refusal is the price: two associations from `Article` to `ArticleSection`
cannot be guessed between, and Filament would silently pick the inflected one.

### The route parameter is the mapping's completing field, and is not singularised

Filament derives `{category}` as `snake(singular($inverseRelationshipName))` —
inflection over an inflected name. Ours reads the mapping: the forward
association's `mappedBy` (a bidirectional one-to-many names the child's own
field, so `article` → `{article}`), else an owning-side many-to-many's
`inversedBy` when it has one, else the parent record class's snake-cased
basename. Same answer as Filament in every ordinary case, from the mapping rather
than from a guess.

The one visible difference is that **nothing is singularised**. Where the
completing field is plural — the `inversedBy` arm of a many-to-many, whose child
field legitimately names a collection — the parameter is plural too:
`{collections}` where Filament would spell `{collection}`. Singularising a field
name the mapping states would be inventing a name no metadata carries, and the
parameter is a URL segment nobody types twice; `BelongsToParentTest` asserts the
plural form so the choice is visible rather than incidental.

### The inverse relationship is never a name we hold

`inverseRelationship()`, `getInverseRelationshipName()` and
`getInverseRelationship()` are not ported, and neither is the
`$record->setRelation($inverseName, $parentRecord)` seeding both of Filament's
resolvers do after a successful lookup. The reason is the one
[tables settled one shelf over](../renames.md#renames) for the identically-named
`Table` members: Doctrine's mapping *states* the counterpart —
[`AssociationTarget`](../../../packages/schemas/src/State/AssociationTarget.php)
surfaces `mappedBy` and `inversedBy` — so every job Filament needs the inverse
name for is already answered. The route parameter reads the completing field
directly, the parent scope is the reader's relationship query rather than a
`whereHas` over the inverse, and the attach writes the owning side the mapping
names.

The seeding has no equivalent either, and needs none: it exists so Eloquent's
lazy loading does not re-query the parent a resolver already has, where Doctrine
returns the same managed instance from the identity map for free.

### The parent scope is the reader's relationship query, not a predicate we write

Filament's `scopeEloquentQueryToParent(Builder $query, Model $parentRecord)` is a
**public static mutator**: it adds one of three predicates to a passed query —
`whereMorphedTo`, `whereBelongsTo` or a `whereHas` subquery — depending on what
the inverse relation turns out to be. It has no member here at all. Instead
`getParentScopedRecordQuery($parentRecord)` **returns**
`AssociationReader::createRelationshipQuery(new Relationship($parent, $name))` —
the same M4 · 8 machinery a relation-scoped table already reads through, rooted at
the same `record` alias `getRecordQuery()` uses, serving both cardinalities with
DQL that has been probed against a real schema.

Porting the mutator would mean copying the reader's one-to-many predicate and its
many-to-many subselect into panels, where they would be a second opinion that can
disagree with the first. Swapping the whole builder costs nothing, because our
base record query is a bare select-from: the scoped query selects the same
records with the same alias. That is why
[`resolveRecordRouteBinding()`'s modifier may answer with a different query](#the-modifier-may-answer-with-a-different-query-and-refuses-anything-else)
— the contract had to allow a replacement, not just a mutation.

Filament's three arms collapse to the reader's two: **morphs have no Doctrine
referent**, the same skip the tables tier already recorded, so a polymorphic
parent is not a shape this port has.

### The attach lands on the resource and writes the owning side in memory

Filament's create page calls
`associateRecordWithParent($record, $parent)`, which is
`getParentResourceRegistration()->getRelationship($parent)->save($record)` — an
Eloquent relation save that writes and persists in one call. Ours is
`Resource::attachRecordToParent($record, $parentRecord)`, which delegates to
`AssociationReader::attach()`: it writes the **owning side** in memory and
returns, and `handleRecordCreation()` persists and flushes as it already did.
Both halves matter. The owning side is what emits SQL — an inverse-only write
flushes nothing at all, which is the defect M4 · 8 built `attach()` to avoid — and
keeping the write in memory means the record joins its parent in the *same*
flush, so an association the mapping refuses to leave empty fails the form rather
than the write.

The move onto the resource is a [rename](../renames.md#renames) as well as a
relocation, and both follow from where the knowledge is: the relationship name is
derived on the resource, so a page-level method would only be able to ask the
resource for it and hand it straight back. `CreateRecord::handleRecordCreation()`
stays the page-level override seam it already was; a consumer's override of
Filament's `associateRecordWithParent()` is a method nothing calls, which is the
failure a rename row exists to make greppable.

### The recursion lives in the loader, and the child must be registered on the panel

Filament's nested `registerRoutes()` recurses on the resource: a child asks its
parent to register a route group and nests inside it, through the Laravel router
facade. There is no ambient router here, and
[the loader owns path construction](README.md#routing), so the recursion is
`PanelRouteLoader`'s: it walks the registration chain root-first, contributing
`<parent slug>/{<parent param>}` per ancestor, and mounts the child's own
`getPages()` entries under the result. The route names, the defaults and the
duplicate-name guard are unchanged — the guard is now genuinely load-bearing,
since two resources nesting under one parent derive names that differ only in the
relationship segment.

The child must be registered on the panel in its own right, exactly as in
Filament — a parent does not pull its children in. Ours says so: the loader
throws a `LogicException` naming both resources and the panel when a chain
mentions a resource the panel does not register, where Filament silently builds a
route family whose parent segment resolves to nothing.

Two guards have no Filament counterpart at all. A parent parameter deriving
`record` collides with the record placeholder, and a parameter an ancestor
already travels under collides with itself — a `Category` nested under `Category`
is the ordinary way to reach the second. Both throw at route-build time naming
the resource and the parameter; Filament builds the broken path and lets the
router answer whatever it answers.

The child's own slug stays its slug (`sections`), never the nested path: the
nesting lives in the route path and the route **name**, so `getSlug()` and every
consumer of it are untouched.

### Guessing walks the record's own associations, never the request

`getUrl(..., shouldGuessMissingParameters: true)` keeps Filament's parameter name
and fills missing ancestor parameters by a different mechanism. Filament copies
them off the current HTTP request through `original_request()` — a Livewire
helper that reconstructs the request that *rendered* the component. There is no
port: our live requests carry the `ux_live_component` route and nothing of the
page's own parameters, and that is exactly where the guessing is needed (an
action URL resolved during a live render).

So the walk is over the data instead. Starting from the nearest record object the
caller passed, each missing level is read through the child→parent property the
mapping names — `$section->getArticle()`, through schemas'
`PropertyAccessor` — and the record found becomes the anchor for the level above
it. Three properties of the walk are worth stating because they are not obvious
from the signature:

- **A caller-supplied ancestor is trusted, not re-derived.** Guessing only fills
  a parameter that is absent; a key the caller passed is left alone, and the walk
  continues *through* that level by extending the property path
  (`verse.chapter`), so a caller mixing one record with one key still gets the
  rest guessed. A contradictory key — a real ancestor the record does not
  actually hang under — produces a URL that 404s at request time, when the
  [stepwise scope](#the-chain-is-resolved-once-per-request-and-each-step-is-scoped-to-the-one-above-it)
  refuses it. URL time is not where parentage is checked.
- **A many-to-many step cannot be guessed.** There is no single field to read a
  parent off, so the walk drops its anchor and the generator raises Symfony's
  `MissingMandatoryParametersException` — the same refusal a caller gets with
  guessing switched off. Pinned, so the limit is asserted rather than assumed.
- **Nothing is guessed unless asked.** The flag defaults to `false` and the
  shipped callers pass `true` only where the chain genuinely cannot be threaded:
  the related-resource URL arms and the sub-navigation band's item URLs.

Filament's own code says why the mechanism could not be ported, better than this
note can: its guessing arm opens with a check for the Livewire test endpoint and
**returns an empty string** there, commented *"it is unable to resolve the
missing URL parameters from the parent records as it does not have access to the
original request"*. Every live request we serve is in exactly that position.

### The parent arms carry no `relation` parameter

`getIndexUrl()` on a nested resource answers with the **parent's** page, in
Filament's arm order: a page the parent registers under the kebab-cased
relationship key (the [`ManageRelatedRecords`](manage-related-records.md) page),
else the parent's `view`, else its `edit`, all three carrying the parent record
as `record`, else the parent's `index`, which takes no parent key at all. Only a
resource no parent lists falls through to an index page of its own.

One smaller difference rides along: all three record-carrying arms pass the
parent key **only when it is non-null**, where Filament always spreads
`'record' => $record`. Passing null makes the generator refuse a null parameter;
omitting it makes the generator name `record` as the missing one, which is the
message that tells a caller what it forgot.

Filament's arms 2 and 3 additionally append `'relation' => $relationshipName` to
preselect the relation-manager tab through its `#[Url(as: 'relation')]` property.
Ours is omitted, and the reason narrowed with M4 · 3: the property **is** now
[URL-bound](has-relation-managers.md#the-relation-binding-is-the-toolkits-only-static-url-mapping),
so the parameter would no longer be inert — it would preselect the tab exactly as
Filament's does. What is left is the judgment the omission always also rested on:
it is noise in a URL a user copies, and it pins a tab the reader did not ask for.
Kept omitted on those grounds rather than on the binding's absence, which means
the [unlinked crumb below](#a-crumb-whose-url-the-trail-already-spends-renders-unlinked)
is now a standing consequence rather than a temporary one.

This is also what closes the recorded trap that a resource with no `index` page
throws from its own breadcrumbs: a nested resource's index **is** its parent, so
the throw is now reachable only by a parentless resource that registers no index
page, which is the case the message was always about.

### The chain rides one `LiveProp` of keys, assigned inside `mount()`

Filament carries `#[Locked] public ?Model $parentRecord` — one resolved model,
re-resolved from the route on a non-Livewire request. Neither half ports. A
`LiveProp` cannot hold an entity (the generic base cannot declare the concrete
type, and the hydrator would be handed the class-string `object`), and a live
request has no route parameters to re-read. So the prop is
`#[LiveProp] public array $parentRecords` — the ancestor route **keys**, keyed by
the parameter each travels under and ordered root-first — the same key-on-the-wire
shape [the record prop](interacts-with-record.md) already uses, with trust riding
the LiveProp checksum and the resolution re-run per request.

The route parameter's *name* varies per relationship, and a `LiveProp` needs a
fixed one, so the loader stamps a `_sylament_parent_params` route default listing
the ancestor parameter names root-first and `PageController` folds exactly those
parameters into the one prop — unsetting each as it goes, because an unmatched
route parameter falls through ux-twig-component into the component's attribute
bag and would otherwise paint `chapter="1"` onto the page's root element. A flat
route carries no such default and therefore no prop.

**The assignment is a mount argument, not a property write, and that is a
security property rather than a style.** ux-twig-component assigns non-mount
props *after* `mount()` returns, and a record page resolves its record inside its
own mount tail — so a prop-only chain would arrive too late and the first GET
would resolve **unscoped**, serving exactly the foreign record this epic exists
to 404. Every record page therefore takes `mount(int|string $record, array
$parentRecords = [])` and assigns the parents first (`mountRecord()` is the one
place that ordering lives), and `CreateRecord`/`ListRecords` take
`mount(array $parentRecords = [])` for the same reason even though neither
resolves anything at mount today. The claim is pinned by first-GET tests over a
plain HTTP client — a `createLiveComponent()` test cannot see this failure at
all, because it assigns props before the component is used.

### `getParentRecordTitle()` throws for a parentless page

Filament's answers off `static::getParentResource()`, which is null for a page
whose resource nests under nothing — a null class-string reached statically, i.e.
a fatal one line later. Ours throws a `LogicException` naming the page and saying
its resource names no parent. Fail-fast with a message, rather than the same
failure without one; no shipped page reaches it, since a title for a parent that
does not exist has no meaning to fall back to.

### The page's `getParentResource()` static is an instance hop here

Filament's `InteractsWithParentRecord::getParentResource()` is a static answering
the parent's class-string. Ours is `getParentResourceInstance(): ?Resource`,
answering the resource **service** — the same translation
[`getResourceInstance()`](README.md#resource-pages) already made one level up,
and for the same reason: everything the caller then does with it (a title, a
label, a binding) is an instance method here. The class-string is still reachable
where it is declared, on the child resource's `getParentResource()`, under
Filament's name.

### A crumb whose URL the trail already spends renders unlinked

The trail a resource page heads is `[...ancestors, this resource's listing]`,
where each ancestor contributes its own listing and then the record itself, and
[the record page adds its own record crumb](interacts-with-record.md) on top. A
nested resource's "listing" is its parent's record page ([the parent arms
above](#the-parent-arms-carry-no-relation-parameter)) — which is the crumb
directly above it. The URL therefore repeats, and a repeated **string** key would
silently collapse two crumbs into one.

So `appendBreadcrumb()` emits a crumb whose URL the trail already carries as an
**int**-keyed entry, which [`Breadcrumbs`](../support/breadcrumbs.md) renders as
plain text: `Articles → Genesis → Sections → In the beginning → Edit`, where
`Sections` is unlinked because clicking it would land on `Genesis` again. Filament
never meets the edge — its arms 2 and 3 append `?relation=…`, which makes the two
URLs differ — so this is an edge our
[omission of that parameter](#the-parent-arms-carry-no-relation-parameter)
creates. It was recorded as resolving when the URL binding landed; the binding
landed with M4 · 3 and the omission did not go with it, so the crumb stays
unlinked until that omission is revisited on its own terms. Nothing else changed:
the trail is still correct, and the unlinked crumb still points at the page the
crumb above it already links.

The test is on the whole trail, not on adjacency: any URL already spent is
unlinked, which additionally makes a non-adjacent repeat impossible.

