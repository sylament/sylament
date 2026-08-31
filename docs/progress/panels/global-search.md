# Global search (panels)

The topbar search that answers across every resource of a panel: the two value
objects a search answers with, the `Resource` concern that turns a search term
into records, the provider that walks the panel's resources, the `Panel` knobs
that configure the field, and the live component that renders it.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`filament/src/GlobalSearch/GlobalSearchResult.php`](../../../vendor/filament/filament/src/GlobalSearch/GlobalSearchResult.php),
  [`GlobalSearchResults.php`](../../../vendor/filament/filament/src/GlobalSearch/GlobalSearchResults.php)
  — the value objects
- [`filament/src/Resources/Resource/Concerns/HasGlobalSearch.php`](../../../vendor/filament/filament/src/Resources/Resource/Concerns/HasGlobalSearch.php)
  — the per-resource search surface
- [`filament/src/GlobalSearch/Providers/`](../../../vendor/filament/filament/src/GlobalSearch/Providers)
  — the provider contract and the default provider
- [`filament/src/Panel/Concerns/HasGlobalSearch.php`](../../../vendor/filament/filament/src/Panel/Concerns/HasGlobalSearch.php),
  [`filament/src/Livewire/GlobalSearch.php`](../../../vendor/filament/filament/src/Livewire/GlobalSearch.php)
  — the panel knobs and the component

## Surface

Every point of this feature. _Blocked by_ names what has to land first. _Detail_
carries three links, any of them: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **semantics** → the
permanent note in [Semantics](#semantics); **diverges** → the permanent note in
[Divergences](#divergences). Rows never disappear — only their detail sections
do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`GlobalSearchResult`](../../../packages/panels/src/GlobalSearch/GlobalSearchResult.php) | [`GlobalSearchResult.php`](../../../vendor/filament/filament/src/GlobalSearch/GlobalSearchResult.php) | done | — | [diverges](#hasdetaillabels-is-a-per-result-check) |
| [`GlobalSearchResults`](../../../packages/panels/src/GlobalSearch/GlobalSearchResults.php) | [`GlobalSearchResults.php`](../../../vendor/filament/filament/src/GlobalSearch/GlobalSearchResults.php) | done | — | [diverges](#the-value-objects-answer-with-arrays-not-collections) · [diverges](#globalsearchresultsmake-has-no-container-seam) |
| [`canGloballySearch()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::canGloballySearch()` | done | — | [diverges](#the-searchability-check-and-the-result-url-ladder-drop-filaments-authorization-gates) |
| [`getGloballySearchableAttributes()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::getGloballySearchableAttributes()` | done | — | — |
| [`getGlobalSearchResults()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::getGlobalSearchResults()` | done | — | [semantics](#the-result-limit-is-spent-before-the-url-filter-runs) |
| [`getGlobalSearchResultTitle()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::getGlobalSearchResultTitle()` | done | — | — |
| [`getGlobalSearchResultDetails()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::getGlobalSearchResultDetails()` | done | — | — |
| [`getGlobalSearchResultActions()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::getGlobalSearchResultActions()` | done | — | — |
| [`getGlobalSearchResultUrl()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::getGlobalSearchResultUrl()` | done | — | [diverges](#the-searchability-check-and-the-result-url-ladder-drop-filaments-authorization-gates) |
| [`applyGlobalSearchAttributeConstraints()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::applyGlobalSearchAttributeConstraints()` | done | — | [diverges](#the-per-attribute-constraint-hook-is-collapsed-into-one-method) |
| [`modifyGlobalSearchQuery()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::modifyGlobalSearchQuery()` | done | — | — |
| [`getGlobalSearchRecordQuery()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::getGlobalSearchEloquentQuery()` | done | — | [diverges](#getglobalsearcheloquentquery-is-getglobalsearchrecordquery) |
| [`getGlobalSearchResultsLimit()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::getGlobalSearchResultsLimit()` | done | — | — |
| [`isGlobalSearchForcedCaseInsensitive()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::isGlobalSearchForcedCaseInsensitive()` | done | — | — |
| [`shouldSplitGlobalSearchTerms()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::shouldSplitGlobalSearchTerms()` | done | — | — |
| [`getGlobalSearchSort()`](../../../packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php) | `HasGlobalSearch::getGlobalSearchSort()` | done | — | — |
| `globalSearchSort(?int)` | `HasGlobalSearch::globalSearchSort()` | skipped | — | [diverges](#the-resource-opt-in-mode-and-the-globalsearchsort-setter-are-not-ported) |
| Resource opt-in mode | `canGloballySearch()`'s `ReflectionProperty` dance | skipped | — | [diverges](#the-resource-opt-in-mode-and-the-globalsearchsort-setter-are-not-ported) |
| [`GlobalSearchProvider`](../../../packages/panels/src/GlobalSearch/Providers/Contracts/GlobalSearchProvider.php) | [`Providers/Contracts/GlobalSearchProvider.php`](../../../vendor/filament/filament/src/GlobalSearch/Providers/Contracts/GlobalSearchProvider.php) | done | — | — |
| [`DefaultGlobalSearchProvider`](../../../packages/panels/src/GlobalSearch/Providers/DefaultGlobalSearchProvider.php) | [`Providers/DefaultGlobalSearchProvider.php`](../../../vendor/filament/filament/src/GlobalSearch/Providers/DefaultGlobalSearchProvider.php) | done | — | [semantics](#the-default-provider-searches-the-current-panel-and-sorts-before-it-skips) · [diverges](#the-default-provider-narrows-the-contracts-nullable-return) |
| [`GlobalSearchProviderRegistry`](../../../packages/panels/src/GlobalSearch/GlobalSearchProviderRegistry.php) | — | done | — | [diverges](#the-provider-is-resolved-through-a-tagged-registry-rather-than-app) |
| _rest not audited yet_ | | | | |

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

_Nothing outstanding on the listed rows._

## Semantics

Faithful ports subtle enough to be misread.

### The result limit is spent before the URL filter runs

`getGlobalSearchResults()` caps the **query** at
`getGlobalSearchResultsLimit()`, then drops every record whose
`getGlobalSearchResultUrl()` came back blank — so a resource that answers no URL
for some of its records returns fewer results than the limit, never a topped-up
page. Filament's `limit()`-then-`filter()` chain does exactly the same thing;
re-deriving it the other way round (filter, then cap) would quietly change how
many results a group shows.

### The default provider searches the current panel, and sorts before it skips

The resources it walks are the **current** panel's
(`CurrentPanel::get() ?? PanelRegistry::getDefault()`), so the same provider
service answers differently per request — Filament reads the same thing off its
`Filament` facade. It sorts that whole list by
`getGlobalSearchSort() ?? 0` **before** dropping the unsearchable and the
empty-handed, so a resource's sort is independent of how many of the resources
around it answered; `usort()` is stable, so resources that share a sort — every
resource that sets none — keep the panel's registration order.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold.

### The searchability check and the result URL ladder drop Filament's authorization gates

Panels has no authorization layer yet, so `canGloballySearch()` is
`$isGloballySearchable && $this->getGloballySearchableAttributes() !== []` with
no `canAccess()` term, and `getGlobalSearchResultUrl()` walks view page → edit
page → index URL with no `canView()`/`canEdit()` gate in front of either page
arm. **A panel with global search on therefore surfaces every record of every
searchable resource to every viewer.** Filament's two remaining fallbacks — the
index URL carrying `tableAction`/`tableActionRecord` query parameters so the
list page opens a view or edit modal — have no port target either: our tables
read no such parameters. Authorization revisits all three when it lands.

### The value objects answer with arrays, not collections

`GlobalSearchResults::getCategories()` returns
`array<string, array<GlobalSearchResult>>` and `getGlobalSearchResults()` a
`list<GlobalSearchResult>`, where Filament returns Illuminate `Collection`s.
Same systematic translation the rest of the port makes: no collection library is
in the graph, and every consumer here iterates or counts.

### `GlobalSearchResults::make()` has no container seam

Filament's `make()` is `app(static::class)`, so an application can rebind the
value object. Ours is `new static()`. The seam that matters is the **provider** —
`GlobalSearchProvider` is a contract resolved through a registry — and a value
object with no dependencies has nothing for the container to give it.

### `hasDetailLabels()` is a per-result check

Filament's `global-search.blade.php` decides once per render whether details
carry labels, memoising `Arr::isAssoc()` over the first result it sees. We put
the question on the result itself (`! array_is_list($this->details)`), so two
results in one search may legitimately disagree — a list of values renders
label-less beside a labelled map. The name has no Filament source; the
[renames trail](../renames.md) carries it.

### `getGlobalSearchEloquentQuery()` is `getGlobalSearchRecordQuery()`

The Eloquent → record translation the rest of the resource surface already
makes (`getEloquentQuery()` → `getRecordQuery()`), and the method still defaults
to `getRecordQuery()`, so the read path cannot drift unless a resource
deliberately overrides it. Recorded in the [renames table](../renames.md).

### The per-attribute constraint hook is collapsed into one method

Filament splits the work in two: `applyGlobalSearchAttributeConstraints()` walks
the words and `applyGlobalSearchAttributeConstraint()` — a protected hook taking
a by-reference `$isFirst` flag to pick `where` vs `orWhere` — walks one
attribute group. We keep only the plural method: every predicate goes into one
`Orx` per word, so there is no first-versus-rest distinction to carry, and the
per-attribute seam a subclass would want is
[`SearchConstraint::forColumn()`](../../../packages/tables/src/Query/SearchConstraint.php),
which is also what resolves a dot-path into a correlated `EXISTS` subselect
rather than a join.

### The provider is resolved through a tagged registry rather than `app()`

Filament resolves a provider with `app($panel->getGlobalSearchProvider())`,
its container answering any class-string. Symfony's does not, so the seam is a
tag: every `GlobalSearchProvider` implementation is autoconfigured with
`sylament.panels.global_search_provider`,
[`GlobalSearchProviderPass`](../../../packages/panels/src/DependencyInjection/Compiler/GlobalSearchProviderPass.php)
collects the tagged definitions into a class-keyed `ServiceLocator`, and
[`GlobalSearchProviderRegistry`](../../../packages/panels/src/GlobalSearch/GlobalSearchProviderRegistry.php)
reads that locator — the same three-piece shape
[`ResourceRegistry`](../../../packages/panels/src/Resources/ResourceRegistry.php)
has, down to the `LogicException` a miss throws. The registry name has no
Filament source; the [renames trail](../renames.md) carries it. A custom
provider therefore has to be a registered service, which is also what lets it
take constructor dependencies — the default one takes three.

### The default provider narrows the contract's nullable return

The contract keeps Filament's `?GlobalSearchResults`: `null` is a custom
provider saying it answered nothing at all, which is not the same as a result
set with no categories in it. `DefaultGlobalSearchProvider::getResults()`
always hands back the builder, so its own signature narrows to
`GlobalSearchResults` — level max rejects a nullable return a method never
returns, and the covariant narrowing states the guarantee rather than hiding
it.

### The resource opt-in mode and the `globalSearchSort()` setter are not ported

Two arms of Filament's resource concern stay out. `globalSearchResourceOptIn()`
— a panel-level switch that makes `canGloballySearch()` reflect on **which
class** declared `$isGloballySearchable`, so only resources that redeclare it
are searchable — buys an opt-in mode a resource can already express by
redeclaring the property as `false` on the base it shares. And the static
`globalSearchSort(?int $sort): void` setter is dropped: a Sylament resource
configures its sort by redeclaring `$globalSearchSort`, the same way it
redeclares every other static on this concern. The `getGlobalSearchSort()`
getter **is** ported, and the provider sorts by it.
