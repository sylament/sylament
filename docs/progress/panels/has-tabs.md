---
description: 'The record filters a listing offers as a strip above its table: the tabs a host declares, the one that is active, and the scope that one lays over the… (draft)'
ports:
  - vendor/filament/filament/src/Resources/Concerns/HasTabs.php
  - vendor/filament/filament/src/Resources/Pages/ListRecords.php
  - vendor/filament/schemas/src/Components/Tabs.php
owns:
  - packages/panels/src/Resources/Concerns/HasTabs.php
---
# HasTabs (panels)

[`Sylament\Panels\Resources\Concerns\HasTabs`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) — the record filters a listing offers as a strip above its table: the tabs a host declares, the one that is active, and the scope that one lays over the table's query.
It ports [`Resources/Concerns/HasTabs.php`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) member for member, with the strip's rendering seam reshaped for a Twig partial and the active key's persistence reshaped for a `LiveProp`.

Three hosts compose it, and only two of them are routed.
[`ListRecords`](list-records.md) composes it directly; [`InteractsWithRelationshipTable`](interacts-with-relationship-table.md) composes it once for both of its own hosts, so a [`RelationManager`](relation-manager.md) and a [`ManageRelatedRecords`](manage-related-records.md) page offer the same record filters over the same association.
The difference between them is the URL: `ManageRelatedRecords` and `ListRecords` map `tab` onto the query string through [`getTableUrlMapping()`](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page), and an embedded relation manager maps nothing, so its tabs are transient by design — a nested live component writing `?tab=` into the page's URL would fight the page's own.

The strip itself is schemas' [`Tabs`](../schemas/tabs.md) in its `liveComponentProperty()` mode: server-driven, writing the active key back onto this host through `live#update`, with no client controller loaded.
It is a **record filter**, not a layout — the tabs hold no components, only a query scope — which is why the whole feature is nine methods, two properties and a `Tabs` call rather than a component of its own.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`$activeTab` — the `#[LiveProp]` and its `modifyActiveTabProp()` modifier](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`HasTabs::$activeTab`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) + [`#[Url(as: 'tab')]` on `ListRecords`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) | done | — | [diverges](#the-url-binding-moves-off-the-page-and-onto-the-props-modifier) |
| [`getTabs()` — the user hook](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`HasTabs::getTabs()`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) | done | — | [translations.md](../translations.md#names-with-no-filament-source) — the name collides with schemas' own `Tabs::getTabs()`, which answers what a component holds rather than declaring what a page offers. Both spellings are Filament's, and the collision is Filament's too |
| [`getCachedTabs()` — the memoised set, with generated labels stamped](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`HasTabs::getCachedTabs()`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) | done | — | [semantics](#the-cache-is-nullable-because-a-typed-uninitialised-property-cannot-be-asked) |
| [`getDefaultActiveTab()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`HasTabs::getDefaultActiveTab()`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) | done | — | typed `?string` where Filament's is `string \| int \| null`, because the key is a `LiveProp`'s value and the wire carries strings |
| [`loadDefaultActiveTab()` — the `#[PostMount(priority: 30)]` hook, healing a blank key **and** one the host no longer declares](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`HasTabs::loadDefaultActiveTab()`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php), called from [`ListRecords::mount()`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) | done | — | [diverges](#the-default-loads-from-a-post-mount-hook-and-again-at-render) |
| [`generateTabLabel()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`HasTabs::generateTabLabel()`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) | done | — | [semantics](#the-label-generator-is-two-replaces-because-symfonystring-takes-one-needle) |
| [`updatedActiveTab()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`HasTabs::updatedActiveTab()`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) | done | — | [diverges](#the-update-hook-heals-flushes-and-resets-where-filaments-resets-and-rebuilds-columns) |
| [`getTabsSchema()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`HasTabs::getTabsContentComponent()`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) | done | — | [diverges](#the-strip-is-a-schema-a-partial-renders-not-a-component-a-content-schema-holds) · [renames.md](../renames.md#renames) |
| [`modifyQueryWithActiveTab()`](../../../packages/panels/src/Resources/Concerns/HasTabs.php) | [`HasTabs::modifyQueryWithActiveTab()`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) | done | — | [semantics](#a-blank-key-and-an-unknown-key-are-both-no-ops-and-that-is-what-heals-a-bookmark) |
| The column-manager half of `updatedActiveTab()` — `$cachedDefaultTableColumnState`, `applyTableColumnManager()` | [`HasTabs::updatedActiveTab()`](../../../vendor/filament/filament/src/Resources/Concerns/HasTabs.php) | todo | — | Two of the three statements Filament's update hook runs reset and re-apply the column manager, which tables ships ([column-manager.md](../tables/column-manager.md)); `updatedActiveTab()` resets the page and flushes the record cache and does not yet call `applyTableColumnManager()` or clear `$cachedDefaultTableColumnState` |
| Deferred tab badges — `$isBadgeDeferred` on a listing's tabs | [`Tab`](../../../vendor/filament/schemas/src/Components/Tabs/Tab.php) | skipped | — | [relation-manager.md](relation-manager.md#deferred-tab-badges-do-not-port) — the same decision one tier over: a deferred badge needs the client-mode `Tabs`, and a record filter renders in server mode |

The nine methods are covered by [`HasTabsTest`](../../../packages/panels/tests/Unit/Resources/Concerns/HasTabsTest.php) — thirteen Unit cases over a fixture host: the empty default, the generated label on a tab declaring none and the memoisation that keeps it, both separators, the first-key default and the already-filled short-circuit, the update hook's page reset and cache flush, the query narrowing in all four of its arms (active, blank, stale key, and the `excludeQueryWhenResolvingRecord()` pair), and the modifier answering a mapping only where the host maps one.
The rendered half is [`ListRecordsTabsTest`](../../../packages/panels/tests/Integration/Resources/Pages/ListRecordsTabsTest.php), which boots the real panel: the strip above the table and its absence, the first tab active with every record under it, `?tab=` narrowing the list, the page reset on a tab change, and the four healing cases the divergences below describe — a bookmarked key the list no longer declares, an empty `?tab=`, a key the client blanks, and a strip whose first render is a hydration.

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
Symfony's `u()->replace()` takes a single needle rather than an array, so the port chains two calls, and `title()` stands in for `ucfirst()` — with its default `allWords: false` it title-cases the first word-boundary character and leaves the rest, which is what `ucfirst()` does.
Both separators are pinned by a test precisely because the two-call form is where a translation like this silently loses one.

### A blank key and an unknown key are both no-ops, and that is what heals a bookmark

`modifyQueryWithActiveTab()` returns the query untouched in three cases — `blank($this->activeTab)`, a key the host no longer declares, and a tab that answers `shouldExcludeQueryWhenResolvingRecord()` while a single record is being resolved.
All three are Filament's, and the middle one is the load-bearing one: a bookmarked `?tab=drafts` on a listing that has since dropped its Drafts tab narrows nothing rather than throwing, so the page serves every record.
That is a **silent** degradation by design; the loud alternative would 404 a link a user saved months ago.

Here it is a fence rather than the user-visible behaviour, because [the mount hook resets a stale key](#the-default-loads-from-a-post-mount-hook-and-again-at-render) before the query is ever built — the arm answers only for a query assembled ahead of a heal, and for the paths (a `records()` closure, a directly-called scope) that reach it without one.
Filament, whose mount does not reset, leans on this arm as the whole of its answer.

The third arm is the one that reads oddly.
A tab's scope normally applies to the single-record lookup too, so a row action cannot reach a record the active tab filtered out; `excludeQueryWhenResolvingRecord()` on a `Tab` turns that off for tabs whose scope is presentational rather than authorisation-shaped.
Both arms are pinned.

## Divergences

### The URL binding moves off the page and onto the prop's modifier

Filament declares `public ?string $activeTab = null` on the trait and `#[Url(as: 'tab')] public ?string $activeTab = null` **again** on `ListRecords`, redeclaring the property to attach the attribute — so the page decides the binding and the trait knows nothing about it.
The port cannot redeclare: a `LiveProp` is declared once, and a subclass repeating it would give the hydrator two metadata entries for one name.

So the binding rides a [`modifier:`](../tables/has-table.md#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) instead.
`$activeTab` carries `#[LiveProp(writable: true, onUpdated: 'updatedActiveTab', modifier: 'modifyActiveTabProp')]`, and `modifyActiveTabProp()` asks the host's `getTableUrlMapping('tab')` and calls `$liveProp->withUrl($mapping)` when it answers one.
The **page** still decides — that is the point of routing it through the same hook the four table props use — but the decision is now a method the host overrides rather than a property it redeclares, and one hook answers for `tab` alongside the four table props instead of an attribute per property spread over the page and the trait.

Two consequences.
`writable: true` is required and is not a widening: the strip writes the key back through `live#update`, so the client is the property's only mutator, and [the update hook is the untrusted edge](#the-update-hook-heals-flushes-and-resets-where-filaments-resets-and-rebuilds-columns).
And an **embedded** relation manager gets no binding at all, because `getTableUrlMapping()` is null on that host — which is a behaviour difference from Filament, where a relation manager's tabs are likewise unbound but for the different reason that only `ListRecords` redeclares the property.

### The default loads from a post-mount hook, and again at render

Filament calls `loadDefaultActiveTab()` from `ListRecords::mount()`.
The port cannot: ux-twig-component's `ComponentFactory::mountFromObject()` assigns whatever `mount()` did not consume **after** `mount()` has run, so a `getTabs()` reading any other property of the page would build against the declared default rather than the mounted value.
The load is therefore a `#[PostMount(priority: 30)]`, which runs after every property is in place and above the table's own post-mount hooks, so anything they reach reads a resolved tab.

Priority 30 also puts it *below* UX's query-string seeding, which is a listener on the same event at priority 256 — and every listener runs before the first `#[PostMount]` method.
So a `?tab=` naming a tab the host declares wins here, and the two ways it can fail are both healed rather than honoured.
An **empty** `?tab=` is the first: the seeding writes `''` over whatever the mount left, `filled()` is false, and the default is loaded on top.
That much is Filament-identical, reached from the other end — over there `?tab=` seeds before `mount()` runs, and `filled()` rejects the empty string just the same.

The second is **ours**, and it is a divergence.
The guard is `filled($this->activeTab) && array_key_exists($this->activeTab, $this->getCachedTabs())`, so a key the host no longer declares — a bookmark from before a tab was renamed or dropped — is reset to the default too.
Filament's guard is `filled()` alone, so a stale key survives its mount and renders a strip with **nothing selected** over rows nothing scoped: `modifyQueryWithActiveTab()`'s unknown-key arm returns the query untouched, which is the right thing for the query and leaves the chrome saying something false about it.
Healing is the [self-heal semantics the port already applies one tier over](has-relation-managers.md#the-relation-binding-is-the-toolkits-only-static-url-mapping) — `healActiveRelationManager()` resets an `activeRelationManager` the registrations no longer carry, and this guard is deliberately its mirror image.
The [unknown-key no-op](#a-blank-key-and-an-unknown-key-are-both-no-ops-and-that-is-what-heals-a-bookmark) stays as the fence for a query that runs ahead of either heal, rather than as the thing the user sees.

The second load is the one with no Filament counterpart: [`getTabsSchema()`](#the-strip-is-a-schema-a-partial-renders-not-a-component-a-content-schema-holds) calls `loadDefaultActiveTab()` again at render time.
It is a guard rather than a duplicate, and it exists because two paths reach a render with a null key that no mount hook will see.
A client can blank the property (it is writable, and the strip is what writes it); and a host can begin declaring tabs on a **hydration**, where no `#[PostMount]` runs at all — the fixture `ListNotes` does exactly that behind a flag.
Either way support's `TabsItem` refuses a null `data-value` and the page 500s, so the invariant is *a host with tabs never renders the strip with a null active tab*, and the render seam is the only place left to enforce it.

**Residual, recorded rather than fixed:** the render-time heal is **DOM-only**.
UX computes `data-live-props-value` on `PreRenderEvent`, before the template body runs, so a key healed inside `getTabsSchema()` reaches the markup but not the serialized props of that same response — the wire lags the DOM until the next request runs a post-mount.
Nothing observable follows today, because the next request re-heals before anything reads the key, and the strip the user sees is correct either way.
A `#[PostHydrate]` hook would close it by healing before the dehydrate; it is not added because it would be a fourth load-point for a case no test can currently make fail.

### The update hook heals, flushes and resets, where Filament's resets and rebuilds columns

Both hooks fire when the active key changes.
Filament's runs three statements: `resetPage()`, nulling `$cachedDefaultTableColumnState`, and `applyTableColumnManager()`.
Ours runs three different ones: `loadDefaultActiveTab()`, `$this->tablePage = 1`, and `flushCachedTableRecords()`.

The page reset is the shared one, spelled differently because [the port's page prop is not writable](../tables/host-can-paginate-records.md#gototablepage-replaces-livewires-paginator-trio) and there is no `resetPage()` to call.
The two column-manager statements are **todo**, not dropped — tables ships the column manager, and this hook does not call it yet — and have a row above.

The two additions are the interesting half.
`loadDefaultActiveTab()` runs first because this hook *is* the untrusted edge: the strip is the only element that writes the property, so a client can post `null` or `''`, and healing here rather than only at render means the healed value still reaches the wire on this response.
`flushCachedTableRecords()` runs because the tab changed the query and the host caches its resolved records per request — Filament needs no equivalent because its `resetPage()` invalidates the same cache on the way through.

### The strip is a `Schema` a partial renders, not a `Component` a content schema holds

Filament's `getTabsContentComponent()` returns a `Tabs` **component**, carrying `->hidden(empty($tabs))`, for `ListRecords::content()` to place in a schema beside its `EmbeddedTable`.
The port has no `content()` on a list page and no `EmbeddedTable`, so there is no schema to place a component into: the page partials render the table directly.
`getTabsSchema()` therefore returns a whole `Schema` — keyed `tabs`, holding the same `Tabs` keyed `resourceTabs` with `liveComponentProperty('activeTab')` and `contained(false)` — which [`renderComponent()`](../support/view-component.md) can render on its own.

The rename is recorded in [renames.md](../renames.md#renames); it is a reshape rather than a spelling change, and `Schema` is what the returned object now is.
The `hidden(empty($tabs))` arm goes with the reshape: all three partials guard the call with `{% if tabs is not empty %}` against `getCachedTabs()`, which is cheaper than building a schema in order to hide it, and it is why the strip's absence on a tabless list is a template branch rather than a component state.

One consequence is worth naming because it will look like duplication: the guard and the render are **byte-identical in two partials** — `Page/table.html.twig` and `RelationManager/index.html.twig` both carry the same `{% set tabs = this.getCachedTabs %}` and the same two-line `{% if %}`.
That is deliberate.
A shared partial would need `this` to be the host, which an `{% include %}` preserves but a `<twig:>` component does not ([the `renderTable()` trap](list-records.md)), and the two lines are the whole of it.
Filament duplicates its own call three times for the same reason — `ListRecords::content()`, `ManageRelatedRecords::content()` and `RelationManager::content()` each place `getTabsContentComponent()` into their own schema, once per class.

### The seeding and the strip are duplicated across the two tiers, as Filament's are

`ListRecords::makeTable()` and [`InteractsWithRelationshipTable::makeTable()`](interacts-with-relationship-table.md#both-seeding-probes-clone-the-action-where-vendor-clones-in-one-of-four) each open with `->modifyQueryUsing($this->modifyQueryWithActiveTab(...))` followed by a byte-identical `recordAction()` probe, and each carries its own copy of `stampRecordOnActionChain()`.
Filament's two `makeTable()` implementations are duplicated the same way, statement for statement, and the port kept the shape rather than hoisting a shared trait.

The reason to keep it is that the two are only *currently* identical.
`ListRecords`' `recordUrl()` probe has a third hop the relationship tier must not have — the fall-through to the resource's own view/edit page — and the tier's `makeTable()` continues into the relationship seed and the related resource's stamp.
Hoisting the common prefix would put a trait between two methods that are diverging rather than converging, and the duplication is what makes each host's whole seeding readable in one place.
Recorded here so the next reader sees a decision rather than a copy-paste.
