---
description: 'The topbar search across a panel''s resources: the result value objects, the resource concern, the providers, the panel knobs and the live component.'
ports:
  - vendor/filament/filament/src/GlobalSearch/GlobalSearchResult.php
  - vendor/filament/filament/src/GlobalSearch/GlobalSearchResults.php
  - vendor/filament/filament/src/Resources/Resource/Concerns/HasGlobalSearch.php
  - vendor/filament/filament/src/GlobalSearch/Providers/Contracts/GlobalSearchProvider.php
  - vendor/filament/filament/src/GlobalSearch/Providers/DefaultGlobalSearchProvider.php
  - vendor/filament/filament/src/Panel/Concerns/HasGlobalSearch.php
  - vendor/filament/filament/src/Livewire/GlobalSearch.php
  - vendor/filament/filament/resources/views/livewire/global-search.blade.php
owns:
  - packages/panels/assets/src/controllers/global_search_controller.ts
  - packages/panels/assets/src/styles/components/global-search.css
  - packages/panels/src/GlobalSearch/
  - packages/panels/src/Panel/Concerns/HasGlobalSearch.php
  - packages/panels/src/Resources/Resource/Concerns/HasGlobalSearch.php
  - packages/panels/src/Twig/Components/GlobalSearch.php
  - packages/panels/templates/components/GlobalSearch/
---
# Global search (panels)

The topbar search that answers across every resource of a panel.
The `Topbar` template that mounts it is [layout-templates.md](layout-templates.md)'s.

## Surface

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
| [`DependencyInjection\Compiler\GlobalSearchProviderPass`](../../../packages/panels/src/DependencyInjection/Compiler/GlobalSearchProviderPass.php) | — | done | — | [diverges](#the-provider-is-resolved-through-a-tagged-registry-rather-than-app) |
| [`globalSearch()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | `Panel\Concerns\HasGlobalSearch::globalSearch()` | done | — | [diverges](#the-provider-is-resolved-through-a-tagged-registry-rather-than-app) · [diverges](#the-panels-position-platform-suffix-and-opt-in-arms-are-not-ported) |
| [`getGlobalSearchProviderClass()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | `Panel\Concerns\HasGlobalSearch::getGlobalSearchProvider()` | done | — | [diverges](#the-provider-is-resolved-through-a-tagged-registry-rather-than-app) |
| [`globalSearchDebounce()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | `Panel\Concerns\HasGlobalSearch::globalSearchDebounce()` | done | — | — |
| [`getGlobalSearchDebounce()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | `Panel\Concerns\HasGlobalSearch::getGlobalSearchDebounce()` | done | — | — |
| [`getGlobalSearchDebounceMilliseconds()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | — | done | — | [semantics](#the-debounce-is-translated-for-ux-live-component-the-way-tables-already-does-it) |
| [`globalSearchKeyBindings()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | `Panel\Concerns\HasGlobalSearch::globalSearchKeyBindings()` | done | — | [diverges](#the-key-binding-is-suppressed-while-a-modal-is-open) |
| [`getGlobalSearchKeyBindings()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | `Panel\Concerns\HasGlobalSearch::getGlobalSearchKeyBindings()` | done | — | [diverges](#the-key-binding-is-suppressed-while-a-modal-is-open) |
| [`globalSearchFieldSuffix()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | `Panel\Concerns\HasGlobalSearch::globalSearchFieldSuffix()` | done | — | — |
| [`getGlobalSearchFieldSuffix()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | `Panel\Concerns\HasGlobalSearch::getGlobalSearchFieldSuffix()` | done | — | — |
| [`isGlobalSearchEnabled()`](../../../packages/panels/src/Panel/Concerns/HasGlobalSearch.php) | `FilamentManager::isGlobalSearchEnabled()` | done | — | [diverges](#isglobalsearchenabled-sits-on-the-panel-not-on-a-manager) |
| `globalSearchPosition` | `HasGlobalSearch::globalSearch()`'s `$position` | skipped | — | [diverges](#the-panels-position-platform-suffix-and-opt-in-arms-are-not-ported) |
| `globalSearchFieldKeyBindingSuffix()` | `Panel\Concerns\HasGlobalSearch::globalSearchFieldKeyBindingSuffix()` | skipped | — | [diverges](#the-panels-position-platform-suffix-and-opt-in-arms-are-not-ported) |
| `globalSearchResourceOptIn()` | `Panel\Concerns\HasGlobalSearch::globalSearchResourceOptIn()` | skipped | — | [diverges](#the-panels-position-platform-suffix-and-opt-in-arms-are-not-ported) |
| [`GlobalSearch` component](../../../packages/panels/src/Twig/Components/GlobalSearch.php) | [`Livewire/GlobalSearch.php`](../../../vendor/filament/filament/src/Livewire/GlobalSearch.php) | done | — | [semantics](#the-component-restores-its-panel-onto-a-live-request) · [diverges](#nothing-dispatches-an-open-event-a-morph-reopens-the-panel) · [diverges](#the-searched-term-is-trimmed) |
| [Search template](../../../packages/panels/templates/components/GlobalSearch/index.html.twig) | [`livewire/global-search.blade.php`](../../../vendor/filament/filament/resources/views/livewire/global-search.blade.php) | done | — | [semantics](#the-stimulus-controller-rides-the-inner-element-not-the-live-root) |
| [Topbar mount](../../../packages/panels/templates/components/Topbar/index.html.twig) | [`livewire/topbar.blade.php`](../../../vendor/filament/filament/resources/views/livewire/topbar.blade.php)'s `fi-topbar-end` | done | — | [semantics](#the-topbar-mount-is-a-threaded-flag-rather-than-a-facade-call) |
| [`PanelsIconAlias::GLOBAL_SEARCH_FIELD`](../../../packages/panels/src/Twig/PanelsIconAlias.php) | `PanelsIconAlias::GLOBAL_SEARCH_FIELD` | done | — | — |
| The `sylament--panels--global-search` Stimulus controller | the field's `x-mousetrap.global` binding and the results container's inline Alpine `x-data` | done | — | [diverges](#the-key-binding-is-suppressed-while-a-modal-is-open) |
| Render hooks around the field | `PanelsRenderHook::GLOBAL_SEARCH_*` | blocked | render hooks | [detail](#the-render-hooks-around-the-field-have-no-port-target) |
| Busy indicator on the field | the input wrapper's `wire:target="search"` in [`livewire/global-search.blade.php`](../../../vendor/filament/filament/resources/views/livewire/global-search.blade.php) | blocked | a loading branch on support's [`InputWrapper`](../../../packages/support/templates/components/InputWrapper/index.html.twig) | [detail](#the-field-carries-no-busy-indicator) |
| Results panel enter/leave fade | `x-transition:enter-start` / `x-transition:leave-end` on `fi-global-search-results-ctn` | skipped | — | [diverges](#the-panel-opens-and-closes-with-no-fade) |
| Topbar-end `x-persist` | [`livewire/topbar.blade.php`](../../../vendor/filament/filament/resources/views/livewire/topbar.blade.php)'s `x-persist="topbar.end.panel-…"` | skipped | — | [diverges](#there-is-no-spa-navigation-for-the-field-to-survive) |
| _rest not audited yet_ | — | — | — | — |

## Details

### The field carries no busy indicator

Filament passes `wire:target="search"` to its input wrapper, which swaps the prefix magnifying glass for a spinner while the search request is in flight.
Support's [`InputWrapper`](../../../packages/support/templates/components/InputWrapper/index.html.twig) has no loading branch, and adding one is a cross-package template change every suite that renders a field depends on, so the field ships without the affordance rather than working around it locally.

The design when it lands: ux-live-component ships a `LoadingPlugin` that reads `data-loading` off any element inside a live root and scopes it to one action or model (`data-loading="model(search)|…"`, the direct translation of `wire:target="search"`), so the seam is a prop on `InputWrapper` — a loading target it stamps onto the prefix slot — rather than anything global search owns.
Doing it that way is what makes the same indicator available to tables' search field and to every text-like forms control, which is also why it is not worth half-building inside this template.

### The render hooks around the field have no port target

`PanelsRenderHook::GLOBAL_SEARCH_START`/`END` (inside the component) and `GLOBAL_SEARCH_BEFORE`/`AFTER` (around the topbar mount) are four of Filament's render hooks, and the render-hook system as a whole has no port.
Nothing here is specific to global search: the hooks come back with the system, or not at all.

## Semantics

### The result limit is spent before the URL filter runs

`getGlobalSearchResults()` caps the **query** at `getGlobalSearchResultsLimit()`, then drops every record whose `getGlobalSearchResultUrl()` came back blank — so a resource that answers no URL for some of its records returns fewer results than the limit, never a topped-up page.
Filament's `limit()`-then-`filter()` chain does exactly the same thing; re-deriving it the other way round (filter, then cap) would quietly change how many results a group shows.

### A nested resource answers no global search result URL

`getGlobalSearchResultUrl()` returns null for a resource with a parent registration, Filament's own admitted limitation ported with its shape: a nested resource's pages need ancestor route parameters a search result carries nothing to fill.
No `canGloballySearch()` guard is needed on top: the resource's own `getGlobalSearchResults()` loop skips a record whose URL is blank, and the default provider skips a resource that yields no results, so a searchable nested resource renders no rows that go nowhere.
A nested resource that overrides `getGlobalSearchResultUrl()` with its own URL is searched like any other.

### The default provider searches the current panel, and sorts before it skips

The resources it walks are the **current** panel's (`CurrentPanel::get() ?? PanelRegistry::getDefault()`), so the same provider service answers differently per request — Filament reads the same thing off its `Filament` facade.
It sorts that whole list by `getGlobalSearchSort() ?? 0` **before** dropping the unsearchable and the empty-handed, so a resource's sort is independent of how many of the resources around it answered; `usort()` is stable, so resources that share a sort — every resource that sets none — keep the panel's registration order.

The walk has no `try`/`catch`, and neither has Filament's: a resource that throws while answering — a refusal raised while keying one of its hits — takes the whole panel's topbar search down with it rather than dropping that resource's category.
That is the honest failure for a misconfigured resource, and it is the one to look for when a single resource's refusal surfaces as a panel-wide search error.

### The debounce is translated for ux-live-component the way tables already does it

`getGlobalSearchDebounce()` keeps Filament's duration string (`500ms` by default, evaluated so a closure may answer it), and `getGlobalSearchDebounceMilliseconds()` turns it into the bare integer `data-model="debounce(N)|search"` wants — `1s` → 1000, a bare `300` → 300, anything unparseable → 500.
The body is [`CanSearchRecords::getSearchDebounceMilliseconds()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) verbatim, the same translation tables' own search field already needed; the name has no Filament source and the [renames trail](../renames.md) carries it beside the tables one.

### The component restores its panel onto a live request

The topbar field is a live component of its own, so it re-renders on a request carrying no panel route default — `_sylament_panel` is a default of the *page* routes, and a live request goes to ux-live-component's own route instead.
`mount()` therefore reads the panel once (`CurrentPanel::get() ?? PanelRegistry::getDefault()`) into a `LiveProp`, and a `#[PostHydrate]` hook stamps that id back onto the request every subsequent round trip.
Without the stamp the provider would silently search the **default** panel's resources and every `Resource::getUrl()` underneath would generate against it — right on a single-panel application, wrong the moment there are two.

### The Stimulus controller rides the inner element, not the live root

The root `div` renders the live component's own `attributes` bag, which always carries `data-controller="live"`, and [`attrs()`](../../../packages/support/src/Twig/Extension/AttributeExtension.php) resolves a repeated scalar attribute by letting the later source win rather than by appending — so a `stimulus_controller()` merged into that same bag would replace the live binding and take the whole component down with it.
The search controller sits on the inner `.sy-pa-global-search` element instead, and every target and action it owns is inside that element.

### The topbar mount is a threaded flag rather than a facade call

Filament's topbar blade asks the `filament()` facade (`isGlobalSearchEnabled()`) mid-template.
There is no facade here, and a panel is reached through `CurrentPanel`/`PanelRegistry` rather than from Twig, so the answer is computed once where the panel already is — `Document/index.html.twig` — and threaded down as `hasGlobalSearch` through `Layout` into `Topbar`, defaulting to `false` so a shell rendered outside a panel — a `Layout` or `Topbar` invoked as a bare tag, which is how the showcase demonstrates it — carries no field.
The position inside `.sy-pa-topbar-end` is Filament's: the search opens the group, ahead of the end slot and the user menu.

## Divergences

### `isGlobalSearchEnabled()` sits on the panel, not on a manager

Filament puts this on `FilamentManager`, which delegates every other question here to the current panel anyway.
There is no manager in this port — a panel is reached through `PanelRegistry`/`CurrentPanel` — so the method sits on `Panel` under its Filament name: false when the provider is switched off, otherwise true as soon as one registered resource `canGloballySearch()`.
Filament memoises the answer per request because each resource's check runs an authorization policy; ours only asks whether a resource named searchable attributes, so it re-reads them instead.

### Nothing dispatches an open event; a morph reopens the panel

Filament's component dispatches `open-global-search-results` from `getResults()`, and the results container listens for it on the window to set its Alpine `isOpen` back to true.
We need no such event: the container is only in the markup when `getResults()` answered non-null, and every live morph re-stamps the server's markup over whatever the client did — so a client-set `hidden` from the close action is wiped by the next search response, which is exactly the reopen Filament buys with the event.
Behaviour identical, one less moving part.

### The searchability check and the result URL ladder drop Filament's authorization gates

Panels has no authorization layer, so `canGloballySearch()` is `$isGloballySearchable && $this->getGloballySearchableAttributes() !== []` with no `canAccess()` term, and `getGlobalSearchResultUrl()` walks view page → edit page → index URL with no `canView()`/`canEdit()` gate in front of either page arm.
**A panel with global search on therefore surfaces every record of every searchable resource to every viewer.**
Filament's two remaining fallbacks — the index URL carrying `tableAction`/`tableActionRecord` query parameters so the list page opens a view or edit modal — have no port target either: our tables read no such parameters.
Authorization revisits all three when it lands.

### The value objects answer with arrays, not collections

`GlobalSearchResults::getCategories()` returns `array<string, array<GlobalSearchResult>>` and `getGlobalSearchResults()` a `list<GlobalSearchResult>`, where Filament returns Illuminate `Collection`s.
Same systematic translation the rest of the port makes: no collection library is in the graph, and every consumer here iterates or counts.

### `GlobalSearchResults::make()` has no container seam

Filament's `make()` is `app(static::class)`, so an application can rebind the value object.
Ours is `new static()`.
The seam that matters is the **provider** — `GlobalSearchProvider` is a contract resolved through a registry — and a value object with no dependencies has nothing for the container to give it.

### `hasDetailLabels()` is a per-result check

Filament's `global-search.blade.php` decides once per render whether details carry labels, memoising `Arr::isAssoc()` over the first result it sees.
We put the question on the result itself (`$this->details !== [] && ! array_is_list($this->details)`), so two results in one search may legitimately disagree — a list of values renders label-less beside a labelled map.
The name has no Filament source; the [renames trail](../renames.md) carries it.

### `getGlobalSearchEloquentQuery()` is `getGlobalSearchRecordQuery()`

The Eloquent → record translation the rest of the resource surface already makes (`getEloquentQuery()` → `getRecordQuery()`), and the method still defaults to `getRecordQuery()`, so the read path cannot drift unless a resource deliberately overrides it.
Recorded in the [renames table](../renames.md).

### The per-attribute constraint hook is collapsed into one method

Filament splits the work in two: `applyGlobalSearchAttributeConstraints()` walks the words and `applyGlobalSearchAttributeConstraint()` — a protected hook taking a by-reference `$isFirst` flag to pick `where` vs `orWhere` — walks one attribute group.
We keep only the plural method: every predicate goes into one `Orx` per word, so there is no first-versus-rest distinction to carry, and the per-attribute seam a subclass would want is [`SearchConstraint::forColumn()`](../../../packages/tables/src/Query/SearchConstraint.php), which is also what resolves a dot-path into a correlated `EXISTS` subselect rather than a join.
It also refuses a dotted globally searchable attribute whose head is an alias the resource's global-search query fetch-joins as a collection: the first global search throws the `LogicException` a table search throws, naming the attribute and the alias ([has-query.md](../tables/has-query.md#a-searchable-dotted-path-and-a-fetch-join-alias-may-not-share-a-head-segment)).
That message is worded for both its callers: it speaks of a *searchable path* and *the query*, leads with the way out that exists here — alias that join something the attribute does not begin with — and offers `searchable(query:)` only as what a **table column** can also do, since a globally searchable attribute has none.
The message is pinned one tier down, in tables' [`FetchJoinedSearchAliasTest`](../../../packages/tables/tests/Integration/FetchJoinedSearchAliasTest.php) and `SearchConstraintTest`: global search reaches the same `SearchConstraint::forColumn()` a table search does, and no panels test quotes the message.

### The provider is resolved through a tagged registry rather than `app()`

Filament resolves a provider with `app($panel->getGlobalSearchProvider())`, its container answering any class-string — so the panel's getter answers an **instance**.
Ours answers the class: `getGlobalSearchProviderClass()` (`false` → `null`, `true` → `DefaultGlobalSearchProvider::class`, a validated class-string straight through), and the caller resolves it.
Symfony's container does not answer an arbitrary class-string, so the seam is a tag: every `GlobalSearchProvider` implementation is autoconfigured with `sylament.panels.global_search_provider`, [`GlobalSearchProviderPass`](../../../packages/panels/src/DependencyInjection/Compiler/GlobalSearchProviderPass.php) collects the tagged definitions into a class-keyed `ServiceLocator`, and [`GlobalSearchProviderRegistry`](../../../packages/panels/src/GlobalSearch/GlobalSearchProviderRegistry.php) reads that locator — the same three-piece shape [`ResourceRegistry`](../../../packages/panels/src/Resources/ResourceRegistry.php) has, down to the `LogicException` a miss throws.
The registry name has no Filament source; the [renames trail](../renames.md) carries it.
A custom provider therefore has to be a registered service, which is also what lets it take constructor dependencies — the default one takes three.

### The default provider narrows the contract's nullable return

The contract keeps Filament's `?GlobalSearchResults`: `null` is a custom provider saying it answered nothing at all, which is not the same as a result set with no categories in it.
`DefaultGlobalSearchProvider::getResults()` always hands back the builder, so its own signature narrows to `GlobalSearchResults` — level max rejects a nullable return a method never returns, and the covariant narrowing states the guarantee rather than hiding it.

### The panel's position, platform-suffix and opt-in arms are not ported

Three arms of Filament's panel concern stay out, so `globalSearch()` takes the provider alone.
`GlobalSearchPosition` and the `$position` parameter buy a sidebar mount for a shell that may have no topbar; ours always has one, so there is nothing to choose between.
`globalSearchFieldKeyBindingSuffix()` — which renders the first key binding as a platform glyph (`⌘K` on a Mac, `CTRL+K` elsewhere) — rides on `Support\Enums\Platform`, a User-Agent sniff with no port; `globalSearchFieldSuffix()` **is** ported, so a panel spells the hint out itself.
And `globalSearchResourceOptIn()` is the panel half of the resource opt-in mode dropped [below](#the-resource-opt-in-mode-and-the-globalsearchsort-setter-are-not-ported) — with no `ReflectionProperty` dance on the resource side there is no switch for it to flip.

### The resource opt-in mode and the `globalSearchSort()` setter are not ported

Two arms of Filament's resource concern stay out.
`globalSearchResourceOptIn()` — a panel-level switch that makes `canGloballySearch()` reflect on **which class** declared `$isGloballySearchable`, so only resources that redeclare it are searchable — buys an opt-in mode a resource can already express by redeclaring the property as `false` on the base it shares.
And the static `globalSearchSort(?int $sort): void` setter is dropped: a Sylament resource configures its sort by redeclaring `$globalSearchSort`, the same way it redeclares every other static on this concern.
The `getGlobalSearchSort()` getter **is** ported, and the provider sorts by it.

### The searched term is trimmed

`GlobalSearch::getResults()` trims once and searches the trimmed term.
Filament trims too — but only for the guard: `$search = trim($this->search)` decides whether to bail, and the line after it hands the provider **`$this->search`**, the untrimmed prop.
So typing `"  ada "` there searches for `"  ada "`, and what that costs depends on the term splitter: with `shouldSplitGlobalSearchTerms()` on, the splitter drops the blank words and the query is the same; switch it off and the padded string is the `LIKE` pattern, matching nothing.
Ours cannot disagree with itself that way, and the shape is [`CanSearchRecords::getTableSearch()`](../../../packages/tables/src/Concerns/CanSearchRecords.php)'s — `mb_trim()`, blank treated as nothing searched for — already the port's answer for the table search field one package down.
Two search inputs in the same shell answering the same keystrokes differently would be the stranger result.

### The key binding is suppressed while a modal is open

Filament binds ⌘K through `x-mousetrap.global`, which fires wherever focus is — including inside an open modal, where the shortcut pulls focus out of a dialog the user is still answering and past the modal's own focus trap.
The `sylament--panels--global-search` controller tests for a visible `[aria-modal="true"]` element that does not contain the search field before it takes the keystroke, and does nothing when it finds one.
That is a deliberate behaviour difference rather than a mechanism translation, and it is the reason the binding is a bare `keydown` listener with a hand-written modifier test rather than Stimulus' own key filters: those demand an exact modifier match, so one `mod+k` binding covering both platforms cannot be expressed as a descriptor — the same call [`sidebar_controller.ts`](../../../packages/panels/assets/src/controllers/sidebar_controller.ts) already makes for its Escape handler.
The binding **values** are Filament's mousetrap-style combos, unchanged.

### The panel opens and closes with no fade

Filament's results container is Alpine-driven: `x-show="isOpen"` plus `x-transition:enter-start="fi-transition-enter-start"` and `x-transition:leave-end="fi-transition-leave-end"`, a pair of opacity classes that fade the panel in and out.
Ours is the bare `hidden` attribute — the controller sets it to close, and the next morph re-stamps the server's markup without it — so both directions are instant.
`hidden` is `display: none`, which a plain CSS transition does not animate in or out of, so keeping the fade would mean re-introducing the two state classes and the timing that sequences them against the attribute, for an affordance a server-driven open/close already makes unambiguous.
The two `fi-transition-*` rules are therefore not ported either.

### There is no SPA navigation for the field to survive

Filament wraps its whole `fi-topbar-end` in `x-persist="topbar.end.panel-{id}"`, so a Livewire SPA navigation re-uses the existing topbar DOM instead of re-rendering it and a half-typed query survives the page change.
Our shell does full page loads — [it is not live](layout-templates.md#the-shell-is-not-live), and nothing in it teleports across a navigation — so there is no re-render for the directive to skip.
It returns only if the shell ever gains SPA navigation, and then for the whole topbar rather than for the search alone.

