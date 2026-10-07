---
description: 'The record-filter tab strip above a listing: the tabs a host declares, the active one, and the scope it lays over the table query.'
ports:
  - vendor/filament/filament/src/Resources/Concerns/HasTabs.php
  - vendor/filament/filament/src/Resources/Pages/ListRecords.php
owns:
  - packages/panels/src/Resources/Concerns/HasTabs.php
---
# HasTabs (panels)

[`Sylament\Panels\Resources\Concerns\HasTabs`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) ports [`Resources/Concerns/HasTabs.php`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) member for member, with the strip's rendering seam reshaped for a Twig partial and the active key's persistence reshaped for a `LiveProp`.

Three hosts compose it, and two of them are routed.
[`ListRecords`](list-records.md) composes it directly; [`InteractsWithRelationshipTable`](interacts-with-relationship-table.md) composes it once for both of its own hosts, so a [`RelationManager`](relation-manager.md) and a [`ManageRelatedRecords`](manage-related-records.md) page offer the same record filters over the same association.
The difference between them is the URL: `ManageRelatedRecords` and `ListRecords` map `tab` onto the query string through [`getTableUrlMapping()`](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page), and an embedded relation manager maps nothing, so its tabs are transient by design — a nested live component writing `?tab=` into the page's URL would fight the page's own.

The strip itself is schemas' [`Tabs`](../schemas/tabs.md) in its `liveComponentProperty()` mode: server-driven, writing the active key back onto this host through `live#update`, with no client controller loaded.
It is a **record filter**, not a layout: the tabs hold no components, only a query scope.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$activeTab`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) and its `modifyActiveTabProp()` modifier | [`HasTabs::$activeTab`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) and the `#[Url(as: 'tab')]` redeclaration on the pages | done | — | [diverges](#the-url-binding-moves-off-the-page-and-onto-the-props-modifier) |
| [`getTabs()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | `HasTabs::getTabs()` | done | — | [diverges](#tab-keys-are-strings-where-filaments-may-be-integers) · [translations.md](../translations.md#gettabs-and-getsteps-mean-something-else-on-filaments-page-tier) |
| [`getCachedTabs()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | `HasTabs::getCachedTabs()` | done | — | [semantics](#the-cache-is-nullable-because-a-typed-uninitialised-property-cannot-be-asked) |
| [`getDefaultActiveTab()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | `HasTabs::getDefaultActiveTab()` | done | — | [diverges](#tab-keys-are-strings-where-filaments-may-be-integers) |
| [`loadDefaultActiveTab()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php), a `#[PostMount(priority: 30)]` hook | `HasTabs::loadDefaultActiveTab()`, called from each host's `mount()` | done | — | [diverges](#the-default-loads-from-a-post-mount-hook-and-again-at-render) |
| [`generateTabLabel()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | `HasTabs::generateTabLabel()` | done | — | [semantics](#the-label-generator-is-two-replaces-because-symfonystring-takes-one-needle) |
| [`updatedActiveTab()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | `HasTabs::updatedActiveTab()` | done | — | [diverges](#the-update-hook-heals-flushes-and-resets-where-filaments-resets-and-rebuilds-columns) |
| [`getTabsSchema()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | `HasTabs::getTabsContentComponent()` | done | — | [diverges](#the-strip-is-a-schema-a-partial-renders-not-a-component-a-content-schema-holds) · [renames.md](../renames.md#renames) |
| [`modifyQueryWithActiveTab()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | `HasTabs::modifyQueryWithActiveTab()` | done | — | [semantics](#a-blank-key-and-an-unknown-key-are-both-no-ops-and-that-is-what-heals-a-bookmark) |
| The column-manager half of `updatedActiveTab()` | `HasTabs::updatedActiveTab()`'s `$cachedDefaultTableColumnState` reset and `applyTableColumnManager()` call | todo | — | [detail](#updatedactivetab-does-not-re-apply-the-column-manager) |

## Details

### `updatedActiveTab()` does not re-apply the column manager

Filament's update hook runs three statements, and two of them reset and re-apply the column manager: it nulls `$cachedDefaultTableColumnState` and calls `applyTableColumnManager()`.
Tables ships both members ([`HasColumnManager`](../../../packages/tables/src/Concerns/HasColumnManager.php), [column-manager.md](../tables/column-manager.md)), and the port's hook calls neither: it heals the key, resets the page and flushes the record cache.

## Semantics

### The cache is nullable because a typed uninitialised property cannot be asked

Filament declares `protected array $cachedTabs;` with no initialiser and reads it through `??=`, which is legal — `??` and `??=` use `isset()` semantics and do not throw on an uninitialised typed property.
The port declares `protected ?array $cachedTabs = null` and branches on `!== null` instead.
The behaviour is identical; what changes is that nothing can be surprised by the property.
An uninitialised typed property throws for any *direct* read — `ReflectionProperty::getValue()`, `var_dump()`, a debugger, a `PropertyAccessor` walk — and this trait is composed onto **live components**, which are reflected over more than an ordinary object is.
The explicit null is also the house shape for a memo field (`HasTable`'s `protected ?Table $cachedTable = null` is the same line one package over), so the two reasons agree.

The memoisation itself is load-bearing rather than an optimisation.
`getCachedTabs()` **mutates** the tabs it caches — it stamps a generated label onto every tab that declares none — so a second, uncached build would re-run `getTabs()` and hand back objects the strip and the query-scope arm disagree about.
Filament caches for the same reason.

### The label generator is two replaces because symfony/string takes one needle

`generateTabLabel('needs_review')` is `Needs review`, from Filament's `str($key)->replace(['_', '-'], ' ')->ucfirst()`.
Symfony's `u()->replace()` takes a single needle rather than an array, so the port chains two calls, and `title()` stands in for `ucfirst()` — with its default `allWords: false` it title-cases the first character and leaves the rest, which is what `ucfirst()` does.
Both separators are pinned by a test precisely because the two-call form is where a translation like this silently loses one.

### A blank key and an unknown key are both no-ops, and that is what heals a bookmark

`modifyQueryWithActiveTab()` returns the query untouched in three cases — `blank($this->activeTab)`, a key the host does not declare, and a tab that answers `shouldExcludeQueryWhenResolvingRecord()` while a single record is being resolved.
All three are Filament's, and the middle one is the load-bearing one: a bookmarked `?tab=drafts` on a listing that has dropped its Drafts tab narrows nothing rather than throwing, so the page serves every record.
That is a **silent** degradation by design; the loud alternative would 404 a link a user saved months ago.

Here it is a fence rather than the user-visible behaviour, because [the mount hook resets a stale key](#the-default-loads-from-a-post-mount-hook-and-again-at-render) before the query is ever built — the arm answers only for a query assembled ahead of a heal, and for the paths (a `records()` closure, a directly-called scope) that reach it without one.
Filament, whose mount does not reset, leans on this arm as the whole of its answer.

The third arm is the one that reads oddly.
A tab's scope normally applies to the single-record lookup too, so a row action cannot reach a record the active tab filtered out; `excludeQueryWhenResolvingRecord()` on a `Tab` turns that off for tabs whose scope is presentational rather than authorisation-shaped.
Both arms are pinned.

## Divergences

### The URL binding moves off the page and onto the prop's modifier

Filament declares `public ?string $activeTab = null` on the trait and redeclares it with `#[Url(as: 'tab')]` on `ListRecords` and on `ManageRelatedRecords` to attach the attribute, so the page decides the binding and the trait knows nothing about it.
The port cannot redeclare: a `LiveProp` is declared once, and a subclass repeating it would give the hydrator two metadata entries for one name.

So the binding rides a [`modifier:`](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) instead.
`$activeTab` carries `#[LiveProp(writable: true, onUpdated: 'updatedActiveTab', modifier: 'modifyActiveTabProp')]`, and `modifyActiveTabProp()` asks the host's `getTableUrlMapping('tab')` and calls `$liveProp->withUrl($mapping)` when it answers one.
The **page** still decides — that is the point of routing it through the same hook the table props use — but the decision is a method the host overrides rather than a property it redeclares, and one hook answers for `tab` alongside the table props instead of an attribute per property spread over the page and the trait.

Two consequences.
`writable: true` is required and is not a widening: the strip writes the key back through `live#update`, so the client is the property's only mutator, and [the update hook is the untrusted edge](#the-update-hook-heals-flushes-and-resets-where-filaments-resets-and-rebuilds-columns).
And an **embedded** relation manager gets no binding at all, because `getTableUrlMapping()` is null on that host; Filament's relation manager is likewise unbound, because only the two routed pages redeclare the property.

### Tab keys are strings, where Filament's may be integers

Filament types the tab array `array<string | int, Tab>` and `getDefaultActiveTab()` as `string | int | null`.
The port types both as string-keyed, because the key is a `LiveProp`'s value and the wire carries strings.
PHP turns an integer-looking key such as `'1'` into an integer array key, so such a key is outside the contract: under `strict_types` `getDefaultActiveTab()` throws a `TypeError` returning it, and `getCachedTabs()` does too when it generates that tab's label.

### The default loads from a post-mount hook, and again at render

Filament calls `loadDefaultActiveTab()` from `mount()` on each of its three hosts.
The port cannot: ux-twig-component's `ComponentFactory::mountFromObject()` assigns whatever `mount()` did not consume **after** `mount()` has run, so a `getTabs()` reading any other property of the page would build against the declared default rather than the mounted value.
The load is therefore a `#[PostMount(priority: 30)]`, which runs after every property is in place and above the table's own post-mount hooks, so anything they reach reads a resolved tab.

Priority 30 also puts it *below* UX's query-string seeding, which is a listener on the same event at priority 256 — and every listener runs before the first `#[PostMount]` method.
So a `?tab=` naming a tab the host declares wins here, and the two ways it can fail are both healed rather than honoured.
An **empty** `?tab=` is the first: the seeding writes `''` over whatever the mount left, `filled()` is false, and the default is loaded on top.
That much is Filament-identical, reached from the other end — over there `?tab=` seeds before `mount()` runs, and `filled()` rejects the empty string just the same.

The second is **ours**, and it is a divergence.
The guard is `filled($this->activeTab) && array_key_exists($this->activeTab, $this->getCachedTabs())`, so a key the host does not declare — a bookmark from before a tab was renamed or dropped — is reset to the default too.
Filament's guard is `filled()` alone, so a stale key survives its mount and renders a strip with **nothing selected** over rows nothing scoped: `modifyQueryWithActiveTab()`'s unknown-key arm returns the query untouched, which is the right thing for the query and leaves the chrome saying something false about it.
Healing is the [self-heal semantics the port applies one tier over](has-relation-managers.md#the-relation-binding-is-the-toolkits-only-static-url-mapping) — `healActiveRelationManager()` resets an `activeRelationManager` the registrations do not carry, and this guard is deliberately its mirror image.
The [unknown-key no-op](#a-blank-key-and-an-unknown-key-are-both-no-ops-and-that-is-what-heals-a-bookmark) stays as the fence for a query that runs ahead of either heal, rather than as the thing the user sees.

The second load is the one with no Filament counterpart: [`getTabsSchema()`](#the-strip-is-a-schema-a-partial-renders-not-a-component-a-content-schema-holds) calls `loadDefaultActiveTab()` again at render time.
It is a guard rather than a duplicate, and it exists because two paths reach a render with a null key that no mount hook will see.
A client can blank the property (it is writable, and the strip is what writes it); and a host can begin declaring tabs on a **hydration**, where no `#[PostMount]` runs at all — the fixture `ListNotes` does exactly that behind a flag.
Either way support's `TabsItem` refuses a null `data-value` and the page 500s, so the invariant is *a host with tabs never renders the strip with a null active tab*, and the render seam is the only place left to enforce it.

**Residual, recorded rather than fixed:** the render-time heal is **DOM-only**.
UX computes `data-live-props-value` on `PreRenderEvent`, before the template body runs, so a key healed inside `getTabsSchema()` reaches the markup but not the serialized props of that same response — the wire lags the DOM until the next request runs a post-mount.
Nothing observable follows, because the next request re-heals before anything reads the key, and the strip the user sees is correct either way.
A `#[PostHydrate]` hook would close it by healing before the dehydrate; it is not added because it would be a fourth load-point for a case no test can make fail.

### The update hook heals, flushes and resets, where Filament's resets and rebuilds columns

Both hooks fire when the active key changes.
Filament's runs three statements: `resetPage()`, nulling `$cachedDefaultTableColumnState`, and `applyTableColumnManager()`.
Ours runs three different ones: `loadDefaultActiveTab()`, `$this->tablePage = 1`, and `flushCachedTableRecords()`.

The page reset is the shared one, spelled differently because [the port's page prop is not writable](../tables/host-can-paginate-records.md#gototablepage-replaces-livewires-paginator-trio) and there is no `resetPage()` to call.
The two column-manager statements are [a todo row](#updatedactivetab-does-not-re-apply-the-column-manager), not dropped.

The two additions are the interesting half.
`loadDefaultActiveTab()` runs first because this hook *is* the untrusted edge: the strip is the only element that writes the property, so a client can post `null` or `''`, and healing here rather than only at render means the healed value still reaches the wire on this response.
`flushCachedTableRecords()` runs because the tab changed the query and the host caches its resolved records per request — Filament needs no equivalent because its `resetPage()` invalidates the same cache on the way through.

### The strip is a `Schema` a partial renders, not a `Component` a content schema holds

Filament's `getTabsContentComponent()` returns a `Tabs` **component**, carrying `->hidden(empty($tabs))`, for each host's `content()` to place in a schema beside its `EmbeddedTable`.
The port has no `content()` on a list page and no `EmbeddedTable`, so there is no schema to place a component into: the page partials render the table directly.
`getTabsSchema()` therefore returns a whole `Schema` — keyed `tabs`, holding the same `Tabs` keyed `resourceTabs` with `liveComponentProperty('activeTab')` and `contained(false)` — which [`renderComponent()`](../support/view-component.md) can render on its own.

The rename is recorded in [renames.md](../renames.md#renames); it is a reshape rather than a spelling change, because the returned object is a `Schema`.
The `hidden(empty($tabs))` arm goes with the reshape: both partials that render the strip guard the call with `{% if tabs is not empty %}` against `getCachedTabs()`, which is cheaper than building a schema in order to hide it, and it is why the strip's absence on a tabless list is a template branch rather than a component state.

Those two partials repeat the guard and the render: `Page/table.html.twig` (which `ListRecords` and `ManageRelatedRecords` both name as their template) and `RelationManager/index.html.twig` both carry the same `{% set tabs = this.getCachedTabs %}` and the same `{% if %}` block.
That is deliberate.
A shared partial would need `this` to be the host, which an `{% include %}` preserves but a `<twig:>` component does not ([the `renderTable()` trap](list-records.md#rendertable-reads-this-so-the-variant-extends-the-chrome-rather-than-wrapping-it)), and the lines repeated are the whole of it.
Filament repeats its own call three times for the same reason — `ListRecords::content()`, `ManageRelatedRecords::content()` and `RelationManager::content()` each place `getTabsContentComponent()` into their own schema.

### The seeding and the strip are duplicated across the two tiers, as Filament's are

`ListRecords::makeTable()` and [`InteractsWithRelationshipTable::makeTable()`](interacts-with-relationship-table.md#both-seeding-probes-clone-the-action-where-vendor-clones-in-one-of-four) each open with `->modifyQueryUsing($this->modifyQueryWithActiveTab(...))` followed by an identical `recordAction()` probe, and each carries its own copy of `stampRecordOnActionChain()`.
Filament's two `makeTable()` implementations are duplicated the same way, statement for statement, and the port keeps the shape rather than hoisting a shared trait.

The two are identical only in their common prefix.
`ListRecords`' `recordUrl()` probe has a third hop the relationship tier must not have — the fall-through to the resource's own view/edit page — and the tier's `makeTable()` continues into the relationship seed and the related resource's stamp.
Hoisting the common prefix would put a trait between two methods that are diverging rather than converging, and the duplication is what makes each host's whole seeding readable in one place.
