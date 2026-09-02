# Table (tables)

[`Sylament\Tables\Table`](../../../packages/tables/src/Table.php) — the root
object of the tables package, the counterpart to schemas'
[`Schema`](../schemas/schema.md): a
[`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) a
host builds through [`HasTable`](has-table.md), carrying the record data source
and the empty-state copy. It ports Filament's
[`Filament\Tables\Table`](../../../vendor/filament/tables/src/Table.php) together
with the `Table\Concerns\` traits it composes — sixteen today
([`BelongsToLiveComponent`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php),
[`HasRecords`](../../../packages/tables/src/Table/Concerns/HasRecords.php),
[`HasQuery`](../../../packages/tables/src/Table/Concerns/HasQuery.php),
[`HasEmptyState`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php),
[`HasColumns`](../../../packages/tables/src/Table/Concerns/HasColumns.php),
[`HasActions`](../../../packages/tables/src/Table/Concerns/HasActions.php),
[`HasAggregateValues`](../../../packages/tables/src/Table/Concerns/HasAggregateValues.php),
[`HasRecordActions`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php),
[`HasToolbarActions`](../../../packages/tables/src/Table/Concerns/HasToolbarActions.php),
[`HasHeaderActions`](../../../packages/tables/src/Table/Concerns/HasHeaderActions.php),
[`HasBulkActions`](../../../packages/tables/src/Table/Concerns/HasBulkActions.php),
[`CanPaginateRecords`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php),
[`CanSortRecords`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php),
[`CanSearchRecords`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php),
[`HasFilters`](../../../packages/tables/src/Table/Concerns/HasFilters.php),
[`HasFilterIndicators`](../../../packages/tables/src/Table/Concerns/HasFilterIndicators.php)),
thirty-one in Filament, which has no counterpart for `HasAggregateValues` — the
side store the batched aggregate read fills is the port's own. Eleven of the
sixteen are folded into the surface table below rather than earning rows
upstairs, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing); the
sixteen still unported keep a per-file row in the package
[README](README.md#features), the way
[`actions`](../actions/README.md#features) tracks the concerns `Action` has not
reached yet — as do the three action sets landed by
[M4 · 1](../roadmap.md#m4--tables-widening), keeping theirs because the two
selection ones still owe the settings [listed below](#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed)
and `HasToolbarActions` is simply **done** where a reader looks for it, and the
two filter concerns, keeping theirs for the form seam and
the chrome actions they do not carry yet. The rows below group those files by
feature, which is what the per-file rows upstairs point at.

The `Table` fetches nothing itself. It declares *how* records are reached — a
`records()` closure, or a Doctrine `query()` — and the host resolves and caches
them; see [`has-table.md`](has-table.md) for that half of the axis.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Table.php`](../../../vendor/filament/tables/src/Table.php)
  — the class, its `make()`, `setUp()` and closure-injection hook
- [`tables/src/Table/Concerns/BelongsToLivewire.php`](../../../vendor/filament/tables/src/Table/Concerns/BelongsToLivewire.php)
- [`tables/src/Table/Concerns/HasRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php)
- [`tables/src/Table/Concerns/HasQuery.php`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php)
- [`tables/src/Table/Concerns/HasEmptyState.php`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php)
- [`tables/src/Table/Concerns/HasColumns.php`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php)
- [`tables/src/Table/Concerns/HasActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasActions.php)
- [`tables/src/Table/Concerns/HasRecordActions.php`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php)
- [`tables/src/Table/Concerns/CanPaginateRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php)
- [`tables/src/Table/Concerns/CanSortRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php)
- [`tables/src/Table/Concerns/CanSearchRecords.php`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php)
- [`tables/src/Enums/RecordActionsPosition.php`](../../../vendor/filament/tables/src/Enums/RecordActionsPosition.php)

## Surface

Every point of this component. _Blocked by_ names what has to land first.
_Detail_ carries up to three links: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences); **semantics** → the permanent
note in [Semantics](#semantics). A **skipped** row states its reason inline
instead, where the decision is one line and applies a policy recorded elsewhere.
Rows never disappear — only their detail sections do.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`make(InteractsWithTable $liveComponent)`](../../../packages/tables/src/Table.php) | [`Table::make()`](../../../vendor/filament/tables/src/Table.php) | done | — | [diverges](../renames.md#make-arguments-move-from-constructors-to-setters) |
| [`liveComponent()` / `getLiveComponent()`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php) | [`BelongsToLivewire`](../../../vendor/filament/tables/src/Table/Concerns/BelongsToLivewire.php) | done | — | [semantics](#the-host-binding-is-required-where-the-schemas-one-is-optional) |
| [`records()` / `getDataSource()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::records()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [has-table.md](has-table.md#the-records-injections-stop-at-seven-of-filaments-eight) |
| [`getRecords()` — always the current page's keyed array](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecords()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) · [semantics](#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) |
| [`getPaginator(): ?Paginator`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | — (Sylament-only; Filament's `getRecords()` **is** the paginator) | done | — | [pagination.md](../support/pagination.md) · [semantics](#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) |
| [`emptyStateHeading()` / `getEmptyStateHeading()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [semantics](#the-empty-state-texts-read-the-record-labels) |
| [`emptyStateDescription()` / `getEmptyStateDescription()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) + [the `setUp()` default](../../../packages/tables/src/Table.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php), [`Table::setUp()`](../../../vendor/filament/tables/src/Table.php) | done | — | [semantics](#the-empty-state-texts-read-the-record-labels) |
| [`emptyStateIcon()` / `getEmptyStateIcon()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [diverges](#the-empty-state-icon-getter-returns-null-and-the-fallback-lives-in-the-template) |
| [`$evaluationIdentifier = 'table'` + the `$liveComponent` name injection](../../../packages/tables/src/Table.php) | [`Table::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Table.php) | done | — | — |
| [`$template`](../../../packages/tables/src/Table.php) + [the partial it names](../../../packages/tables/templates/partials/Table/index.html.twig) | [`$view = 'filament-tables::index'`](../../../vendor/filament/tables/src/Table.php), [`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) · [diverges](#every-rule-in-tablecss-is-scoped-under-the-sy-ta-root-one-class-above-vendors) · [diverges](#a-markup-header-label-does-not-switch-the-sortable-header-to-a-span) · [semantics](#aria-sort-is-dropped-with-null-because-false-would-render-as-a-string) · [diverges](#the-selection-cells-carry-no-header-cell-class-and-their-checkbox-skin-is-supports) |
| [`emptyState()` / `getEmptyState()` — the whole replacement block](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState::emptyState()`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | — |
| [`emptyStateActions()` / `pushEmptyStateActions()` / `getEmptyStateActions()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | — |
| [Record labels and titles — `recordLabel()` / `pluralRecordLabel()` / `recordTitle()` / `recordTitleAttribute()` / `getRecordLabel()` / `getPluralRecordLabel()` / `getRecordTitle()` / `getRecordTitleAttribute()` / `hasCustomRecordTitle()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [rename](../renames.md#renames) · [semantics](#the-record-title-reads-the-attribute-through-the-accessor-and-falls-back-to-the-label) · [semantics](#the-empty-state-texts-read-the-record-labels) |
| [Record identity — `getRecordKey()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecordKey()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) |
| [Morph-stable row and cell keys](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `wire:key` stamps](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-morph-key-is-an-id-attribute-not-wirekey) · [semantics](#the-row-and-cell-morph-keys-disagree-on-record-vs-records) |
| [The filters trigger — the toolbar dropdown, the funnel icon button and the active-count badge](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `FiltersLayout::Dropdown` branch](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-trigger-badge-is-composed-in-the-table-partial-because-iconbutton-has-no-slot) · [diverges](#the-filters-panel-teleports-because-the-scroll-container-wraps-the-toolbar-too) |
| [The above- and below-content layouts, the collapsible `<details>` and the filters modal](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `AboveContent`, `BelowContent` and `Modal` branches](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-collapsible-panel-is-a-details-element-and-the-modal-opens-through-an-invoker-command) |
| [The filters panel — `<twig:SylamentTables:Filters>`](../../../packages/tables/templates/components/Filters/index.html.twig) | [`views/components/filters.blade.php`](../../../vendor/filament/tables/resources/views/components/filters.blade.php) | done | — | [diverges](#the-panel-is-fixed-chrome-with-no-action-objects-behind-it) |
| [The indicators row — the active-filters label, the chips and the remove-all button](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `fi-ta-filter-indicators` block](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-chip-is-one-badge-and-the-delete-affordance-is-supports) |
| The pivot surface — `hasPivotRecordKeys()`, `allowDuplicates()`, `selectPivotDataInQuery()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php), [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | skipped | — | The `BelongsToMany` pivot arm: a Doctrine association table carrying payload is an entity of its own rather than a pivot with casts, so there is no pivot model to key rows by, to allow duplicates of, or to alias columns out of |
| [`columns()` / `getColumns()` / `getColumn()`](../../../packages/tables/src/Table/Concerns/HasColumns.php) | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | in progress | — | [column.md](column.md) · [detail](#columns-takes-columns-only-and-does-not-push) |
| Column grouping and layouts — `pushColumns()`, `getColumnsLayout()`, `hasColumnGroups()` | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | todo | — | [detail](#columns-takes-columns-only-and-does-not-push) |
| The column manager — user-toggleable column visibility | [`HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | todo | — | — |
| [The `query()` lane — `query()` / `getQuery()` / `modifyQueryUsing()` / `applyQueryScopes()` / `getQueryEntityManager()`](../../../packages/tables/src/Table/Concerns/HasQuery.php) | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | done | — | [semantics](#every-read-clones-a-memoised-base-query) |
| [`hasQuery()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::hasQuery()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#the-query-lane-is-on-exactly-while-no-records-closure-is-set) |
| [`getRecordClass()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getModel()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [rename](../renames.md#renames) · [semantics](#the-record-class-is-read-off-the-base-querys-root-entity) |
| [The relation-scoped table — `relationship()` / `getRelationship()` / `getRelationshipQuery()` / `isRelationshipBaseQuery()`](../../../packages/tables/src/Table/Concerns/HasQuery.php) | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | done | — | [semantics](#the-relationship-query-is-the-base-query-rooted-at-the-target) · [semantics](#the-mappings-order-is-the-relation-scoped-tables-default-sort) |
| The inverse-name derivation — `inverseRelationship()`, `getInverseRelationship()`, `getInverseRelationshipFor()` | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | skipped | — | [diverges](#the-inverse-relationship-is-read-off-the-mapping-not-derived) |
| [`paginated()` / `isPaginated()` / `paginationPageOptions()` / `getPaginationPageOptions()` / `defaultPaginationPageOption()` / `getDefaultPaginationPageOption()` / `extremePaginationLinks()` / `hasExtremePaginationLinks()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | [diverges](#the-ten-per-page-default-is-matched-strictly) · [diverges](#ispaginated-has-no-groups-only-arm) |
| [`paginationMode()` / `getPaginationMode()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) + [`PaginationMode`](../../../packages/tables/src/Enums/PaginationMode.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php), [`Enums/PaginationMode`](../../../vendor/filament/tables/src/Enums/PaginationMode.php) | in progress | its `Cursor` arm — a keyset carrier ([pagination.md](../support/pagination.md)) | [detail](#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) · [has-table.md](has-table.md#the-page-state-has-its-query-lane-and-no-session-or-url-half) |
| [`paginatedWhileReordering()` / `isPaginatedWhileReordering()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | — |
| `scrollToTopOnPageChange()` | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | todo | — | [detail](#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) |
| [`getSortableVisibleColumn()` / `getSortColumn()` / `getSortDirection()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [diverges](#getsortablevisiblecolumn-has-no-hidden-arm-to-check) |
| [`defaultSort()` / `getDefaultSort()` / `getDefaultSortDirection()` / `defaultKeySort()` / `hasDefaultKeySort()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [semantics](#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker) · [has-table.md](has-table.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) · [has-table.md](has-table.md#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded) · [semantics](#the-mappings-order-is-the-relation-scoped-tables-default-sort) |
| `defaultSortOptionLabel()` / `getDefaultSortOptionLabel()` | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | skipped | — | It labels the "unsorted" entry of the mobile sort **dropdown**, which the port does not render and nothing else reads; it belongs to that layout rather than to sorting, and lands only if the layout does |
| [`persistSortInSession()` / `persistsSortInSession()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [has-table.md](has-table.md#no-table-state-is-persisted-in-the-session) |
| [`searchable()` / `isSearchable()` / `getExtraSearchableColumns()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [semantics](#a-table-is-searchable-when-a-column-says-so-and-searchable-overrides-in-both-directions) |
| [The search chrome — `searchPlaceholder()`, `searchDebounce()`, `searchOnBlur()` and their getters](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [diverges](#getsearchdebouncemilliseconds-is-a-sylament-only-translation-of-filaments-duration-string) · [diverges](#searchonblur-renders-onchange-because-ux-live-component-has-no-blur-event) · [diverges](#the-search-field-flattens-supports-input-wrapper-into-two-classes) · [diverges](#items-center-is-lifted-onto-the-search-field-because-the-prefix-that-carried-it-is-gone) · [diverges](#the-search-input-adds-an-outline-none-vendor-does-not-have) |
| [`splitSearchTerms()` / `shouldSplitSearchTerms()`, `searchUsing()` / `hasSearchUsingCallback()` / `callSearchUsing()`, `applyExtraSearchConstraints()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [has-table.md](has-table.md#search-is-the-global-term-only) · [semantics](#extra-searchable-columns-take-closures-that-return-their-expression) · [diverges](#the-default-search-comparison-assumes-a-string-typed-field) · [diverges](#a-search-term-is-not-escaped-for-like) |
| [`getSearchIndicator()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [has-table.md](has-table.md#the-contract-is-eighteen-methods-of-filaments-forty-six) · [detail](#the-unported-search-surface-splits-three-ways) |
| [`persistSearchInSession()` / `persistsSearchInSession()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [has-table.md](has-table.md#no-table-state-is-persisted-in-the-session) |
| `persistColumnSearchesInSession()`, `isSearchableByColumn()`, `hasSearch()`, `getColumnSearchIndicators()` | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | todo | — | [detail](#the-unported-search-surface-splits-three-ways) |
| `getExtraSearchableColumnRelationship()` | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | skipped | — | It answers *is this dotted name's head a relationship*, so Filament's caller can switch to `whereRelation`. Here [one resolution](#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) answers that inside `SearchConstraint::forColumn()` for every tier at once, so an extra searchable column crosses an association with nothing to ask first — there is no caller left to hold the branch this member exists to decide |
| [`deferLoading()` / `isLoadingDeferred()` / `isLoaded()`](../../../packages/tables/src/Table/Concerns/CanDeferLoading.php) + [`poll()` / `getPollingInterval()` / `getPollingIntervalMilliseconds()`](../../../packages/tables/src/Table/Concerns/CanPollRecords.php) | [`CanDeferLoading`](../../../vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php), [`CanPollRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPollRecords.php) | done | — | [detail](#the-deferred-window-is-server-gated-and-client-closed) · [diverges](#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers) · [has-table.md](has-table.md#the-contract-is-eighteen-methods-of-filaments-forty-six) |
| [`filters()` / `pushFilters()` / `getFilters()` / `getFilter()` / `getFiltersFormSchema()` / `deferFilters()` / `hasDeferredFilters()` / `filtersLayout()` / `getFiltersLayout()` / `hasFiltersDropdown()` / `hasFiltersModal()` / `hasFiltersTrigger()` / `hasFiltersAboveContent()` / `hasFiltersBelowContent()` / `hasCollapsibleFilters()` / `getFiltersModalId()` / `filtersFormColumns()` / `getFiltersFormColumns()` / `filtersFormMaxHeight()` / `getFiltersFormMaxHeight()` / `filtersFormWidth()` / `getFiltersFormWidth()` / `filtersResetActionPosition()` / `getFiltersResetActionPosition()` / `isFilterable()` / `getActiveFiltersCount()` / `isFiltered()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) + [`FiltersLayout`](../../../packages/tables/src/Enums/FiltersLayout.php), [`FiltersResetActionPosition`](../../../packages/tables/src/Enums/FiltersResetActionPosition.php) | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php), [`Enums/FiltersLayout`](../../../vendor/filament/tables/src/Enums/FiltersLayout.php), [`Enums/FiltersResetActionPosition`](../../../vendor/filament/tables/src/Enums/FiltersResetActionPosition.php) | in progress | the four before/after-content layouts, and the width-from-columns match | [detail](#an-unshipped-filters-layout-is-refused-where-the-chrome-reads-it) · [detail](#the-filters-form-columns-follow-the-layout-the-width-does-not) |
| [`hiddenFilterIndicators()` / `getFilterIndicators()`](../../../packages/tables/src/Table/Concerns/HasFilterIndicators.php) | [`Table\Concerns\HasFilterIndicators`](../../../vendor/filament/tables/src/Table/Concerns/HasFilterIndicators.php) | in progress | its column-search arm — the `getColumnSearchIndicators()` spread, with per-column search | [indicator.md](indicator.md#removal-is-structured-not-a-click-handler-string) · [has-table.md](has-table.md#search-is-the-global-term-only) |
| [The three chrome actions — `filtersTriggerAction()` / `getFiltersTriggerAction()` / `filtersApplyAction()` / `getFiltersApplyAction()` / `filtersRemoveAllAction()` / `getFiltersRemoveAllAction()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | done | — | [diverges](#the-trigger-opens-its-chrome-from-the-client-and-the-count-stays-a-template-chip) |
| The filters form seam — `getFiltersForm()` / `filtersFormSchema()` | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | todo | — | — |
| [`persistFiltersInSession()` / `persistsFiltersInSession()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | done | — | [has-table.md](has-table.md#no-table-state-is-persisted-in-the-session) |
| [`deselectAllRecordsWhenFiltered()` / `shouldDeselectAllRecordsWhenFiltered()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | done | — | [semantics](#one-filter-setting-drives-three-selection-behaviours) |
| [The query-string identifier — `queryStringIdentifier()` / `getQueryStringIdentifier()`](../../../packages/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | [`HasQueryStringIdentifier`](../../../vendor/filament/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | done | — | [has-table.md](has-table.md#no-table-state-is-bound-to-the-url) |
| [`recordActions()` / `pushRecordActions()` / `getRecordActions()` / `getFlatRecordActions()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) | [`Table\Concerns\HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | in progress | — | [detail](#record-actions-register-without-the-per-action-modifier-hook) |
| [`recordActionsPosition()` / `getRecordActionsPosition()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) + [`RecordActionsPosition`](../../../packages/tables/src/Enums/RecordActionsPosition.php) | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php), [`Enums/RecordActionsPosition`](../../../vendor/filament/tables/src/Enums/RecordActionsPosition.php) | in progress | — | [detail](#the-position-default-has-no-content-arm-to-choose-between-yet) · [diverges](#the-position-setter-drops-filaments-string-arm) · [diverges](#the-four-rendered-positions-collapse-to-two) |
| [`getVisibleRecordActionsByRecordKey()` — per-record clones, record-bound, hidden dropped](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) | [`index.blade.php`'s `$reduceVisibleRecordActions` / `$recordActionsByRecordKey`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-visible-action-map-is-a-table-method-and-is-not-short-circuited) |
| [The two flat action maps — `getAction()` / `getBulkAction()` / `hasAction()` / `hasBulkAction()` / `getFlatActions()` / `getFlatBulkActions()` / `cacheAction()` / `mergeCachedFlatActions()` / `removeCachedActions()`](../../../packages/tables/src/Table/Concerns/HasActions.php) | [`Table\Concerns\HasActions`](../../../vendor/filament/tables/src/Table/Concerns/HasActions.php) | in progress | — | [detail](#the-flat-action-map-keeps-one-trim-and-its-caller) · [semantics](#the-bulk-split-is-by-class-and-eviction-is-by-identity) |
| `modifyUngroupedRecordActionsUsing()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | todo | — | [detail](#record-actions-register-without-the-per-action-modifier-hook) |
| Record-action presentation — `recordActionsColumnLabel()`, `recordActionsAlignment()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | todo | — | [detail](#the-actions-header-cell-is-always-the-unlabelled-arm) |
| The deprecated v3 aliases — `actions()`, `pushActions()`, `actionsPosition()`, `actionsColumnLabel()`, `actionsAlignment()`, `getActions()`, `getActionsPosition()`, `getActionsColumnLabel()`, `getActionsAlignment()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | skipped | — | The project has never ported a deprecated Filament surface; each is a one-line delegate to the `record*` name above |
| [`toolbarActions()` / `pushToolbarActions()` / `getToolbarActions()`](../../../packages/tables/src/Table/Concerns/HasToolbarActions.php) | [`Table\Concerns\HasToolbarActions`](../../../vendor/filament/tables/src/Table/Concerns/HasToolbarActions.php) | done | — | [detail](#the-header-and-toolbar-setters-are-destructive-because-the-flat-bulk-map-is-the-checkbox-switch) |
| [`headerActions()` / `pushHeaderActions()` / `getHeaderActions()` / `headerActionsPosition()` / `getHeaderActionsPosition()`](../../../packages/tables/src/Table/Concerns/HasHeaderActions.php) + [`HeaderActionsPosition`](../../../packages/tables/src/Enums/HeaderActionsPosition.php) | [`Table\Concerns\HasHeaderActions`](../../../vendor/filament/tables/src/Table/Concerns/HasHeaderActions.php), [`Actions/HeaderActionsPosition`](../../../vendor/filament/tables/src/Actions/HeaderActionsPosition.php) | in progress | — | [detail](#the-header-and-toolbar-setters-are-destructive-because-the-flat-bulk-map-is-the-checkbox-switch) · [diverges](#headeractionsposition-files-under-enums-where-filament-keeps-it-under-actions) |
| [The two-group toolbar — the start actions group, its three reserved slots and the widened render gate](../../../packages/tables/templates/partials/Table/index.html.twig) + [`header-toolbar.css`](../../../packages/tables/assets/src/styles/components/header-toolbar.css) | [`index.blade.php`'s `fi-ta-header-toolbar`](../../../vendor/filament/tables/resources/views/index.blade.php), [`container.css`](../../../vendor/filament/tables/resources/css/container.css) | in progress | grouping · the column manager — two slots stand empty | [diverges](#the-toolbars-start-group-carries-its-own-class-and-renders-only-when-it-has-content) · [diverges](#a-bulk-only-trigger-is-hidden-by-a-wrapper-span) · [diverges](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) |
| [The selection chrome — the checkbox cells, the tri-state page checkbox and the selection banner](../../../packages/tables/templates/partials/Table/index.html.twig) + [`selection.css`](../../../packages/tables/assets/src/styles/components/selection.css) | [`index.blade.php`'s selection cells and `fi-ta-selection-indicator`](../../../vendor/filament/tables/resources/views/index.blade.php), [`support/…/input/checkbox.css`](../../../vendor/filament/support/resources/css/components/input/checkbox.css) | in progress | grouping — the group checkbox | [diverges](#the-selection-banner-flattens-vendors-second-wrapper-and-renders-supports-link) · [diverges](#the-selection-cells-carry-no-header-cell-class-and-their-checkbox-skin-is-supports) · [tables-bundle.md](tables-bundle.md#the-selection-controller-diverges-from-tablejs-in-four-places) |
| [`recordUrl()` / `openRecordUrlInNewTab()` / `hasCustomRecordUrl()` / `getRecordUrl()` / `shouldOpenRecordUrlInNewTab()` / `extraRecordLinkAttributes()` / `getExtraRecordLinkAttributes()`](../../../packages/tables/src/Table/Concerns/HasRecordUrl.php) | [`HasRecordUrl`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | done | — | — |
| `getExtraRecordLinkAttributeBag()` | [`HasRecordUrl`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | skipped | — | The bag wrapper has no consumer here: the row anchor spreads `getExtraRecordLinkAttributes()` through `attrs()`, which builds the bag itself |
| [`recordAction()` / `getRecordAction()`](../../../packages/tables/src/Table/Concerns/HasRecordAction.php) | [`HasRecordAction`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordAction.php) | done | — | — |
| [The row chrome — the clickable marker, the stripe and the selected tint](../../../packages/tables/templates/partials/Table/index.html.twig) + [`table.css`](../../../packages/tables/assets/src/styles/components/table.css), [`table_controller.ts`](../../../packages/tables/assets/src/controllers/table_controller.ts) | [`index.blade.php`'s `<tr>` class list](../../../vendor/filament/tables/resources/views/index.blade.php), [`row.css`](../../../vendor/filament/tables/resources/css/row.css) | in progress | grouping — the stripe's group reset and the collapsed row (M4 · 6) | — |
| [The cell wrapper — `<a>` / `<button>` / `<div>` chosen per record](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `$columnWrapperTag` match](../../../vendor/filament/tables/resources/views/index.blade.php) | in progress | the column-level arms — `Column::url()` / `action()` / `disabledClick()` / `hasStateBasedUrls()` (M4 · 4) | — |
| [The reorder chrome — the toolbar trigger, the drag handle column, the sortable mount, the row markers and the indicator](../../../packages/tables/templates/partials/Table/index.html.twig) + [`table.css`](../../../packages/tables/assets/src/styles/components/table.css), [`table_controller.ts`](../../../packages/tables/assets/src/controllers/table_controller.ts) | [`index.blade.php`'s `fi-ta-reorder-*`](../../../vendor/filament/tables/resources/views/index.blade.php), [`sortable.js`](../../../vendor/filament/support/resources/js/sortable.js) | done | — | — |
| [The empty state block — the replacement short-circuit, the demoted heading and the actions, beside the loading block the not-yet-loaded arm splits off into](../../../packages/tables/templates/partials/Table/index.html.twig) + [`table.css`](../../../packages/tables/assets/src/styles/components/table.css) | [`index.blade.php`'s `fi-ta-empty-state`](../../../vendor/filament/tables/resources/views/index.blade.php), [`empty-state.css`](../../../vendor/filament/tables/resources/css/empty-state.css) | done | — | [detail](#the-deferred-window-is-server-gated-and-client-closed) |
| [The selection settings — `bulkActions()` / `pushBulkActions()` / `groupedBulkActions()` / `selectable()` / `isSelectionEnabled()` / `selectCurrentPageOnly()` / `selectsCurrentPageOnly()` / `checkIfRecordIsSelectableUsing()` / `checksIfRecordIsSelectable()` / `isRecordSelectable()` / `trackDeselectedRecords()` / `canTrackDeselectedRecords()` / `recordCheckboxPosition()` / `getRecordCheckboxPosition()` / `getAllSelectableRecordsCount()`](../../../packages/tables/src/Table/Concerns/HasBulkActions.php) + [`RecordCheckboxPosition`](../../../packages/tables/src/Enums/RecordCheckboxPosition.php) | [`Table\Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Table/Concerns/HasBulkActions.php), [`Enums/RecordCheckboxPosition`](../../../vendor/filament/tables/src/Enums/RecordCheckboxPosition.php) | in progress | — | [detail](#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed) · [semantics](#one-filter-setting-drives-three-selection-behaviours) · [has-table.md](has-table.md#the-selection-is-server-canonical-and-re-derived-on-every-read) |
| Grouping and summaries — `groups()`, `defaultGroup()`, the summarizers | [`CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php), [`CanSummarizeRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSummarizeRecords.php) | todo | — | — |
| [Reordering — `reorderable()` / `isReorderable()` / `isReordering()` / `getReorderColumn()` / `getReorderDirection()` / `authorizeReorder()` / `isReorderAuthorized()` / `reorderRecordsTriggerAction()` / `getReorderRecordsTriggerAction()` / `beforeReordering()` / `afterReordering()` / `callBeforeReordering()` / `callAfterReordering()`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php) + [`HasReorderAnimationDuration`](../../../packages/support/src/Components/Concerns/HasReorderAnimationDuration.php) | [`CanReorderRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanReorderRecords.php), [`Support\Concerns\HasReorderAnimationDuration`](../../../vendor/filament/support/src/Concerns/HasReorderAnimationDuration.php) | done | — | — |
| [`heading()` / `description()`](../../../packages/tables/src/Table/Concerns/HasHeader.php) + [`rootHeadingLevel()` / `getHeadingLevel()` / `getHeadingTag()`](../../../packages/tables/src/Table/Concerns/HasHeadings.php) | [`Table\Concerns\HasHeader`](../../../vendor/filament/tables/src/Table/Concerns/HasHeader.php), [`Table\Concerns\HasHeadings`](../../../vendor/filament/tables/src/Table/Concerns/HasHeadings.php) | in progress | `header()` — the custom-header arm takes a Blade `View`, and its Twig analogue waits for a consumer | [detail](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) |
| [`striped()` / `isStriped()`](../../../packages/tables/src/Table/Concerns/CanBeStriped.php) | [`CanBeStriped`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStriped.php) | done | — | — |
| [`recordClasses()` / `getRecordClasses()`](../../../packages/tables/src/Table/Concerns/HasRecordClasses.php) | [`HasRecordClasses`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordClasses.php) | done | — | — |
| Presentation — `stackedOnMobile()`, `content()` | [`CanBeStriped`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStriped.php) and its siblings | todo | — | — |
| `HasArguments`, `HasExtraAttributes`, `HasDefaultDataFormattingSettings` | [`Table.php`'s remaining composed traits](../../../vendor/filament/tables/src/Table.php) | todo | — | — |

The twenty-three **done** rows are `make()`, the host binding, the data-source
declaration, the query-lane declaration and the two members that read off it
(`hasQuery()`, `getRecordClass()`), the host-delegating `getRecords()`,
`getPaginator()` and `getRecordKey()`, the morph-stable row and cell keys built
on the last of those, the empty-state heading and icon, the closure-injection
wiring, the per-record visible-action map, the pagination surface, the
sort-resolution surface and the default-sort family beside it, the three search
rows — the declaration, the chrome, and the query half · 12 landed — the
toolbar action set and the filter setting the whole selection story reads, and
the partial that renders all of it; the column set, the empty-state description
and the four remaining action rows are partly in, one step behind them.

The relation arm · 20 added to the lane has no row of its own here, and that is
the [one-row-per-file](../README.md#one-row-per-file-one-file-per-thing) rule
holding: its surface belongs to the **column** ([column.md](column.md)), and the
`Query/` helpers behind it are Sylament-only engines with no Filament file to
diff, the way `ColumnPathResolver` and `SearchConstraint` already are. What lands
here is what the lane decided — the [resolution order](#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused),
the [search shape](#a-relation-search-is-a-correlated-exists-at-every-cardinality),
the [sort shape](#a-relation-sort-joins-where-filaments-orders-by-a-correlated-subquery)
and the [aggregate delivery](#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)
— in the sections below.

The PHP surface is covered by
[`TableTest`](../../../packages/tables/tests/Unit/TableTest.php) — `make()`
binding the host, `records()` storing and returning the closure identity, the
host-delegating `getRecordKey()`, the empty-state defaults, `columns()` keying by
name and binding the table on each member while a second call resets the set, and
both injection paths at once
(`fn ($liveComponent)` resolved by the class's own
[`resolveDefaultClosureDependencyForEvaluationByName()`](../../../packages/tables/src/Table.php),
`fn (Table $table)` by the `EvaluatesClosures` fallback beneath it — by *name*,
not by type: the parameter matches `$evaluationIdentifier`, the disjunct that
short-circuits ahead of the `is_a($this, $parameterType)` arm beside it, which a
differently named parameter would be needed to reach) — and, for `getRecords()`,
by the host-side
[`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php)
it delegates into.

The browsing configuration is covered in the same file, built through a real
`TableHost` so every assertion runs against a configured `Table` rather than a
bare trait object. Pagination: the untouched defaults as one case (paginated,
`[5, 10, 25, 50]`, default `10`, no extreme links), `paginated(false)`,
`paginated([12, 24])` routing its array into the page options *and* moving the
default to `12`, the ten-preference against a list where ten is not first, the
first-option fallback where there is no ten, and an explicit
`defaultPaginationPageOption()` winning over both. Sorting: one case gives the
table a sortable and a plain column and asserts `getSortableVisibleColumn()`
returns a `Column` for the first and null for both the second and a name that
does not exist — the three arms of the resolver the header cell and
[`sortTable()`](has-table.md#sorttable-ignores-a-column-the-table-did-not-declare-sortable)
both read. Search: `isSearchable()` derived from the columns (globally searchable
→ true, individually-only → false, plain → false), then `searchable()` overriding
it in both directions with `searchable(['author.name'])` also filling the extra
columns; the chrome getters as one case; and the debounce translation as four
values — the `null` default, `1s`, a bare `250`, and unparseable garbage falling
back to 500. The three query-half members are asserted where they are consumed,
in the host's
[`CanSearchRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanSearchRecordsTest.php):
`shouldSplitSearchTerms()` on all three arms (the default `true`, an explicit
`false`, a closure), `hasSearchUsingCallback()` beside the `searchUsing()` case
it gates, and `applyExtraSearchConstraints()` through a table whose columns are
**unsearchable**, so a match can only have come from the extra list — one term
reaching a record through the string member and another only through the closure
member, a third proving the null return adds nothing, and a fourth proving a
returned builder is skipped while the constraint it applied itself still lands.

The query lane is covered by
[`HasQueryTest`](../../../packages/tables/tests/Unit/Table/Concerns/HasQueryTest.php),
against a real in-memory SQLite `EntityManager` rather than a mock — an assertion
about clone isolation or a DQL string is only worth making against a builder
Doctrine itself would accept.
[`EntityManagerFactory`](../../../packages/tables/tests/Fixtures/Doctrine/EntityManagerFactory.php)
builds one per case, schema included, over four fixture entities:
`Song` and `Artist` carry the `ManyToOne` / `OneToMany` pair, `Book`'s identifier
is `code` rather than `id`, and `CompositePair` has two. The last two exist for
what comes later — a record key or a sort that assumes an `id` column passes
against `Song` and fails against them. The eight cases are clone-per-read (two
reads, neither the stored builder, same DQL), a caller mutation not leaking into
the next read, a closure declaration evaluated once and re-evaluated after a
fresh `query()`, two scopes applied in registration order with the `void` one
still counted, the `isResolvingRecord` flag reaching a scope as `[false, true]`,
`getQueryEntityManager()` answering the manager and null, `hasQuery()` across a
bare table and a `records()` set and cleared, and `getRecordClass()` reading
`Song`, caching, answering `null` with no query and re-reading after a new one.

The rendered chrome is covered by the Integration
[`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php),
against a fixture host that is sortable, searchable and paginated **at once** —
one render pass with all three on, because the failure mode worth catching is a
toolbar or footer that only renders when nothing else does. Its assertions are
Crawler selections, per the DOM-contract precedent above: a sortable `<th>`
carrying `aria-sort="none"` around a `button.sy-ta-header-cell-sort-btn` whose
`data-live-action-param` is `sortTable` and `data-live-column-param` the column
name; an unsortable `<th>` with **no** button and **no** `aria-sort` attribute at
all (the [null-not-false](#aria-sort-is-dropped-with-null-because-false-would-render-as-a-string)
arm); the search input bound `debounce(500)|tableSearch`; and the footer's
overview text, its single gap, its active item's `aria-current`, the page-2
button's live-action parameters and the per-page `<select>` bound to
`tableRecordsPerPage` with the right option selected. Two negative cases carry
the rest of that host: a plain table renders neither `sy-pagination` nor
`sy-ta-header-toolbar` anywhere in its markup, and a page **past the end** renders
the empty state *and* the footer together — which is the only way back for a user
who paged past the data, and the case a naive `{% if records %}` wrapper around
the footer would break. Three more cases drive a second host whose `records()`
closure returns a carrier with **no total**, which is where the null arm of the
footer condition is pinned: a middle page keeping prev/next while the window and
the total drop out of the
[simple footer](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both),
an empty first page rendering no footer at all, and an empty page past the data
keeping one.

The action rows split their coverage by tier.
[`HasRecordActionsTest`](../../../packages/tables/tests/Unit/Table/Concerns/HasRecordActionsTest.php)
drives everything through a real `Table`: one registration flattening a group
into the same name-keyed map as an ungrouped action, a second `recordActions()`
call resetting the set where `pushRecordActions()` appends to it, the
`Size::Small` + link-template defaults losing to an explicit `size()`/`button()`,
the table bound onto both an ungrouped action and a group member, the
`AfterColumns` default with the two dropdown placements it picks by side, a
closure position evaluated with the table injected, and the `InvalidArgumentException`
a non-action member earns. `getVisibleRecordActionsByRecordKey()` is covered from
the render side instead, in the Integration file below — the map's whole
contract is what the partial does with it, and a unit assertion on a list of
clones would restate the method body.
[`HasActionsTest`](../../../packages/tables/tests/Unit/Table/Concerns/HasActionsTest.php)
covers the map on its own, through an object composing the trait alone — the
`shouldOverwriteExistingAction: false` arm of `cacheAction()` has no public
caller yet, so nothing on `Table` reaches it.

The partial is covered by the Integration
[`TableTest`](../../../packages/tables/tests/Integration/TableTest.php), which
renders a real table through the booted kernel's Twig: the header labels and
cell values for object records, one `sy-ta-row` per record (the per-cell
`{% do column.record(record) %}` rebinding), the default empty state displacing
the `<table>` entirely with its `heroicons:x-mark` fallback rendered as an
`<svg>` and no description paragraph, a fully configured empty state, a `Markup`
icon reaching the raw-passthrough arm of
[`generateIconHtml()`](../../../packages/support/src/Twig/Extension/IconExtension.php),
and a `<script>` in a record value coming back escaped.

Two of its cases pin the **structure** rather than the strings, because this is
the DOM contract the tables stylesheet and the demo's browser tests are written
against. They read the render back through a `DomCrawler` with the child
combinator — `div.sy.sy-ta > div.sy-ta-ctn > table.sy-ta-table`,
`tr.sy-ta-row > td.sy-ta-cell > div.sy-ta-col > div.sy.sy-ta-text`, and
`div.sy-ta-empty-state[role="status"] > div.sy-ta-empty-state-content` holding
the icon background's `<svg>`, the `<h3>` heading and the `<p>` description — so
a flattened wrapper or a renamed class fails here rather than silently
downstream. A substring assertion passes through both of those changes, which is
why the counts are selected and not grepped.

Five more read the same way, for the row identity and the actions column. The
morph keys are asserted by *reading the host's id back* and selecting
`tr[id="{hostId}.table.records.7"]` and
`td[id="{hostId}.table.record.7.column.title"]`, so the two spellings and the
record key that fills them are pinned as one string rather than as a substring.
The trailing default asserts the header `<th>` by all three of its
class/`aria-label` markers *and* a header count of exactly two, which is what
catches a duplicated or otherwise extra header cell — the three-marker selector
already fails on a column rendered in the body but not the head, but it counts a
second stray `<th>` just as happily as one. It then pins the **side** on both
halves, `th:last-child` and `td:last-child`, because every other selector in
that case is side-blind: presence, the child nesting, the href and the header
count all hold just as well for a column rendered leading, so without the two
`:last-child` reads a regression that moved the default to the wrong side would
pass a test whose name promises trailing. The `BeforeColumns` case is the mirror
image, selecting `td:first-child` and `th:first-child`. The all-hidden case asserts the absence of `sy-ta-actions`
from the whole render, which is the only assertion that can prove the column was
skipped in the header too. And the clone case gives two records a
record-dependent `url()` and demands both hrefs, which fails if the actions are
bound once and reused — the single bug the per-record clone exists to prevent.

## Details

Specifics for rows that are not finished — what is already in, what is left, and
what the next step is. Delete a subsection when its row flips to **done**; the
table row and any divergence it earned stay behind.

### The pagination mode ships two cases, and two setters wait on their features

`paginationMode()` selects between Laravel's length-aware, simple and cursor
paginators, all three built by the query lane's
[`paginateTableQuery()`](has-table.md#the-page-state-has-its-query-lane-and-no-session-or-url-half)
— on the closure lane the userland closure returns whatever it built, so the enum
selects nothing there, which is why it arrived with the lane rather than with the
rest of the pagination configuration. Two of its three cases ship. **Default**
pages against a true total, counted by Doctrine's Tools paginator. **Simple**
runs no count: the lane probes one record past the page and support's
[`Paginator`](../support/pagination.md) takes the null total, the footer
[branching on it](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both)
— which a `records()` closure could already reach, so what the enum added was the
query lane's fetching. **Cursor** still needs a keyset carrier the toolkit does
not ship, and is refused where the mode is consumed rather than where it is set:
a `Closure` mode has no value at set time, so `paginateTableQuery()` throws a
`LogicException` naming the missing carrier and the two modes that work. The
enum is Filament's verbatim, three pure cases in declaration order; the getter is
typed `PaginationMode` where Filament declares `?PaginationMode` over a body that
cannot return null.

`paginatedWhileReordering()` reads on a table that is being reordered, and
reordering is unported (its row is above). `scrollToTopOnPageChange()` is an
Alpine `$el.scrollIntoView()` in Filament's view; the port has no scroll
affordance and it belongs with the
[loading affordances](#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them)
rather than with pagination configuration. Each returns with the feature it
drives.

### The unported search surface splits three ways

Filament's `Table\Concerns\CanSearchRecords` is 300 lines; the port keeps the
declaration, the chrome and — since · 12 — the query half. What the surface
divides into stays worth naming, because two of the three arms are still out.

**Session persistence** — `persistSearchInSession()` and
`persistColumnSearchesInSession()` — is the
[toolkit-wide omission](has-table.md#no-table-state-is-persisted-in-the-session)
recorded on the host side, and returns with that decision rather than with
search.

**The query lane's members landed with · 12** and have a **done** row of their own:
`splitSearchTerms()`, which governs how the term is broken up *before* being
turned into constraints, `searchUsing()` with its
`callSearchUsing()`/`hasSearchUsingCallback()` pair — a closure handed the
builder, which bypasses column assembly entirely — and
`applyExtraSearchConstraints()`, which is what finally consumes
`getExtraSearchableColumns()`: a string member goes through
[`SearchConstraint::forColumn()`](../../../packages/tables/src/Query/SearchConstraint.php)
— the same dispatch a column's own search columns take, so it joins the word's
`OR` group as a `LIKE` or as a correlated `EXISTS` depending on what the name
[resolves to](#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused)
— and a closure member
[returns its expression](#extra-searchable-columns-take-closures-that-return-their-expression)
the way a column's `searchable(query:)` does. So a table declaring
`searchable(['author.name'])` searches by it — across the association since · 20,
where · 12 needed the join declared in `query()` — rather than merely reading as
searchable. The composition around them —
[one group per word, `AND`-ed](has-table.md#search-is-the-global-term-only) — is
the host's.

One member of the group is **skipped** rather than pending:
`getExtraSearchableColumnRelationship()`, which walks a dotted name to decide
whether its head is an Eloquent relationship so the constraint can become a
`whereRelation`. Here that question is answered one layer down and for every
tier at once — `SearchConstraint::forColumn()` resolves the name
[against the mapping](#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused)
and returns either a `LIKE` or a correlated `EXISTS` — so
`searchable(['author.name'])` searches across the association with nothing
declared, and no caller is left holding the branch this member exists to decide.

**Everything else waits on a neighbouring feature.** `isSearchableByColumn()`
answers whether any column wants its own input, which is
[per-column search](has-table.md#search-is-the-global-term-only); `hasSearch()`
delegates to a host method that arrives with it; `getColumnSearchIndicators()`
returns one `Filters\Indicator` per column-scoped term and waits on that same
per-column search. Only the global one ships: `getSearchIndicator()` is a
nullable delegate to the host's `getTableSearchIndicator()` — the
[tenth contract method](has-table.md#the-contract-is-eighteen-methods-of-filaments-forty-six)
— where the null return carries what Filament's separate `hasSearch()` gate
says, so no companion method is needed.

### Loading affordances are deferred with the JavaScript that would drive them

`deferLoading()` and `poll()` were held back until support had a loading
affordance to render, and M4 · 3 is where they land: the deferred window renders
support's `LoadingIndicator` at `2xl` inside a `sy-ta-table-loading-ctn`, and the
poll interval is the table controller's `setInterval` over `component.render()`.
The mechanics are the two sections below.

What is still deferred is the **in-flight** affordance — the indicator Filament
shows *during* a request, driven by Alpine's `wire:loading`/`x-show` over a
`LoadingIndicator` contract and a `loading-indicator` view. Nothing here renders
one: a polling table repaints on arrival with no interim state, the search
field's prefix holds its icon alone, and the selection banner has no spinner
beside its count. That one waits on the in-flight mechanics rather than on the
component, which support now ships.

### The deferred window is server-gated and client-closed

Filament's `wire:init="loadTable"` has no UX LiveComponent counterpart (R6 §2.2),
so the window is ours end-to-end. The host carries `$isTableLoaded`, a
non-writable `LiveProp` mutated only by the `loadTable()` action, and
`isTableLoaded()` short-circuits to true on a table that does not defer — so
`Table::isLoaded()` answers for every table and the flag is inert unless
`deferLoading()` is on. The partial reads it once and gates **four** reads on the
result: the preamble `getRecords`, the `getPaginator` read, the
`getVisibleRecordActionsByRecordKey` map and the selectable-count query. Every
one of them reaches the database, and each is pinned by
[`TableDeferredLoadingTest`](../../../packages/tables/tests/Integration/TableDeferredLoadingTest.php)'s
zero-read case: the fixture's data source counts its calls, so deleting any one
gate fails there rather than only under a profiler.

The body branch splits three ways where vendor's splits two. `records is null`
is the loading block; `records is empty` is the empty state; anything else is the
table. Before this, one `records is empty` carried both null and the empty list —
Twig's `is empty` is true for `null` — which is exactly the conflation vendor
avoids with `$records === null`. The selection banner gains the same gate, which
is vendor's own `$isLoaded` guard on it.

The controller closes the window: on connect it reads the flag back off the live
store and, when the table defers and the flag is false, fires `loadTable` once. A
reconnect after the load reads true and fires nothing, so the once-guard is the
store rather than a controller flag. What it reads there is the **method's**
answer, not the raw property — the prop and the getter share a name, so
`PropertyAccessor` prefers `isTableLoaded()` when the hydrator dehydrates
([the general mechanic](has-table.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary),
which `$isTableReordering` carries too). That is the value the client wants: a
table that does not defer publishes `true` however its flag stands, so the
controller never fires an action into a window that was never open.

### The poll interval parses strictly and carries no Livewire modifiers

`wire:poll` reads a Livewire root, and the table partial does not own one —
stamping the *page* would re-render everything on the page rather than the table
(R6 §2.1/2.3). So `poll()` is server-side configuration and the interval is
client-side machinery: `getPollingIntervalMilliseconds()` hands the controller a
number and the controller owns the `setInterval`. The attribute is emitted only
while the table is not being reordered, matching vendor's `(! $isReordering)`
guard, and regardless of the deferred state, because vendor polls through the
deferred window too.

Owning the timer is what makes that guard cost something Livewire's does not.
`wire:poll` is re-read whenever the attribute is morphed, so removing it stops
the loop; the table partial's root carries no id, key or `data-live-ignore`, so
a live re-render morphs it **in place** and Stimulus never re-runs `connect()` —
a timer started there would keep firing `render()` through a drag, morphing the
rows out from under SortableJS, which is exactly what the suppression exists to
prevent. So the loop is (re)started from `pollIntervalValueChanged()` rather than
only at connect: Stimulus fires that callback when the attribute is added,
changed, and removed (with the value's default), which stops the timer on the
way into reorder mode, restarts it on the way out, and picks up a new interval
from a `poll()` closure whose answer changed. `#startPolling()` is idempotent
(it stops first) and returns unless the host is resolved, so the callback
Stimulus fires *before* `connect()` — the value observer starts first — leaves
the start to `connect()`.

Two divergences ride on that. The parse is **strict**: `10s` and `500ms` parse,
and anything else — a bare number, a unit Filament never had, an empty string —
throws `InvalidArgumentException` where Livewire silently falls back to 2000 ms.
A table quietly polling at the wrong rate is invisible until it costs somebody a
database, which is the fail-fast house rule's whole case. And Livewire's
`.visible` / `.keep-alive` modifiers are **not** ported: both are properties of
`wire:poll` itself, and the interval this port hands the browser is a number with
nowhere to carry them.

### Record actions register without the per-action modifier hook

`recordActions()`, `pushRecordActions()`, `getRecordActions()` and
`getFlatRecordActions()` are ported whole: the reset-then-push shape, the
`Size::Small` + `LINK_TEMPLATE` defaults stamped through
[`defaultSize()`/`defaultTemplate()`](../actions/action.md) so an explicit
`size()`/`button()` still wins, the group flattening into the shared flat map,
the table bound onto every member, and the by-side dropdown placement
`getRecordActions()` re-stamps on each read.

Two things Filament does here are not in. `modifyUngroupedRecordActionsUsing()`
— a closure `pushRecordActions()` evaluates against every ungrouped action with
`['action' => $action]` injected, which Filament's docs reach through a
[`Table::configureUsing()`](../support/component-manager.md) registration to
restyle every table's ungrouped actions at once — has no consumer in the port
yet and is **todo** on a row of its own. And the
`->defaultDropdownTeleport(true)` Filament chains onto the placement stamp is still
not made here, though it is now *callable*: the teleport family landed on the action
side with the toast that needed it
([semantics](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase)),
so the stamp is one call away rather than blocked. It is a **live gap**, not a
deferral of convenience — `.sy-ta-ctn` applies `overflow-x-auto`, and a
non-`visible` overflow on one axis makes the UA adjust the other to `auto` as well,
so the table container clips a row-action panel exactly the way the toast card did.
Whoever next touches record actions should add the call and verify it in the demo;
this file is not that lane.

### The actions header cell is always the unlabelled arm

Filament's header renders one of two `<th>`s for the actions column: a normal
`fi-ta-header-cell` carrying `$recordActionsColumnLabel` when the table set one,
and otherwise an empty `fi-ta-actions-header-cell fi-ta-empty-header-cell` whose
`aria-label` is the pluralised *"Actions"* translation. Sylament's partial
renders only the second, with the English literal `aria-label="Actions"`.

Both halves of the first arm are missing rather than declined:
`recordActionsColumnLabel()` is the **todo** row above, and there is no label to
read; the pluralisation `trans_choice(..., $flatRecordActionsCount)` needs the
[i18n layer](#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships)
nothing in the port has, the same reason every fixed string is a literal. The setter and the `{% if %}`
that reads it land together, at which point the literal becomes the fallback of
the second arm rather than the only arm.

`recordActionsAlignment()` is the other half of that row and it is a CSS
question, not a template one: Filament expresses it as `fi-align-*` modifier
classes on `fi-ta-actions`, and the
[ported stylesheet block](../../../packages/tables/assets/src/styles/components/table.css)
deliberately stops at the base rule — the alignment and wrapping arms come back
with the setter that can emit them.

### The position default has no content arm to choose between yet

Filament's `getRecordActionsPosition()` falls back through *two* defaults: with
no content grid and no columns layout it answers `AfterColumns`, and otherwise
`AfterContent` — the actions move below the card when the table renders records
as a grid instead of as rows. Sylament answers `AfterColumns` unconditionally.

Nothing is dropped in principle. `getContentGrid()` and `hasColumnsLayout()` are
both unported (the `Presentation` and `Column grouping and layouts` rows above),
so there is no state either branch could read — a two-way fallback would be a
constant with extra steps. The `AfterContent` case of the enum ships anyway,
because the enum is a verbatim port and a partial one would be a worse lie. The
second arm returns in the same change as the content grid.

### The flat action map keeps one trim, and its caller

The map is Filament's whole surface now — `getAction()`, `getBulkAction()`,
`hasAction()`, `hasBulkAction()`, `getFlatActions()`, `getFlatBulkActions()`,
`cacheAction()` with its `instanceof BulkAction` split, `mergeCachedFlatActions()`
and `removeCachedActions()`. The bulk half and the eviction landed together with
[`BulkAction`](../actions/bulk-action.md) and the two action-set traits that call
them, which is what the earlier version of this section was waiting for.

One trim is deliberate rather than incidental.
`mergeCachedFlatActions()` drops Filament's `$shouldOverwriteExistingActions`
passthrough, while `cacheAction()` keeps its own
`$shouldOverwriteExistingAction`. That looks lopsided until you find who passes
`false`: only `pushEmptyStateActions()`, whose whole point is that an empty-state
action must **not** displace a same-named record or header action already in the
map. Both members exist to serve that one caller, which is
[blocked](#surface) — so the parameter stays where a caller will reach it and
the passthrough returns with the caller that needs it. Every caller this epic
added overwrites, so nothing here changed when the bulk half landed.

### The header and toolbar setters are destructive, because the flat bulk map is the checkbox switch

`toolbarActions()` and `headerActions()` both walk the set they are about to
replace, hand each member (a group flattened first) to `removeCachedActions()`,
empty their own ordered list, and only then push the new set. Filament's comment
explains the shape and it is worth keeping as a statement rather than a
narration: the flat **bulk** map is what
[`isSelectionEnabled()`](#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed)
reads, so an appending setter would leave a replaced bulk action switching the
checkbox column on forever, and clearing the whole map instead would take the
record actions down with it. Only identity eviction hits exactly the members the
set being replaced owns.

Two consequences a reader would otherwise have to derive. Re-registering under a
name another set already cached is **last writer wins** in the flat map, and
evicting the winner then leaves the map without the loser — the maps are keyed by
name, the eviction is by identity, and nothing restores a shadowed entry. And
`pushToolbarActions()` / `pushHeaderActions()` do **not** evict, by design: they
are the append half the destructive setters delegate into.

Two spelling differences from vendor, both no-ops. `pushHeaderActions()` loops
`getFlatActions()` calling `cacheAction()` per member where `pushToolbarActions()`
calls `mergeCachedFlatActions()`; the port uses the merge helper in both, since
that helper *is* the loop. And `$action->table($this)` is stamped inside each
`instanceof` branch rather than ahead of them, matching
[`pushRecordActions()`](#record-actions-register-without-the-per-action-modifier-hook)
and turning a bad member into the `InvalidArgumentException` the branch already
throws instead of a fatal on an undefined method.

### The selection settings ship as a subset, and the rest are listed

Ported whole from `Table\Concerns\HasBulkActions`: the two deprecated aliases
`bulkActions()` / `pushBulkActions()` (five lines each, forwarding to the toolbar
setters, annotation included — they carry the API's household name and a
migrating user greps for it in both directions), `groupedBulkActions()`,
`selectable()` / `isSelectionEnabled()`, `selectCurrentPageOnly()` /
`selectsCurrentPageOnly()`, `checkIfRecordIsSelectableUsing()` /
`checksIfRecordIsSelectable()` / `isRecordSelectable()`,
`trackDeselectedRecords()` / `canTrackDeselectedRecords()`,
`recordCheckboxPosition()` / `getRecordCheckboxPosition()` and
`getAllSelectableRecordsCount()`, which delegates to the host.

Three of those getters carry behaviour a signature cannot: `isSelectionEnabled()`
returns an explicit `selectable()` bool when one was set and otherwise answers
*is any visible bulk action registered*, so a table turns its own checkboxes on;
`selectsCurrentPageOnly()` is `evaluate(…) || ! hasQuery()`, so a `records()`
table is page-only whatever the setter says; and `canTrackDeselectedRecords()` is
force-false under `deselectAllRecordsWhenFiltered(false)`
([why](#one-filter-setting-drives-three-selection-behaviours)).
`checksIfRecordIsSelectable()` is a **presence** test and never evaluates the
callback — every reader uses it to decide whether the per-record walk is worth
running at all, so evaluating it there would cost the very thing it guards.

`isRecordSelectable()` hands the callback the record twice: as the `record` named
injection and as a typed injection keyed by `$record::class`. The typed half is
**exact-class** matching, because support's `Invoker` compares class-strings
rather than walking `is_a()`, so a Doctrine **proxy** would key under its proxy
class and miss a `fn (Song $record)` hint. Nothing reaches that today — a root
query's results are hydrated entities, never proxies — and the shape is shared
with every other `::class`-keyed typed injection in the toolkit, so it is
recorded here rather than worked around at this one call site: it would be fixed
once, in the `Invoker`, if a proxy ever arrives.

Not ported, each for a stated reason rather than by omission:

- `maxSelectableRecords()` / `getMaxSelectableRecords()` — and with them
  Filament's per-universe asymmetry, a `LogicException` on the records lane
  against a silent SQL `limit()` on the query lane. A cap wants one meaning
  before it ships.
- `disabledSelection()` / `isSelectionDisabled()` — it suppresses the banner's
  two links only, which is chrome the banner has to render first.
- `selectGroupsOnly()` / `selectsGroupsOnly()` and the group checkbox — they need
  record grouping, which is a separate unported feature.
- `resolveSelectedRecordsUsing()` — the records-lane override for how a selection
  resolves; the built-in `only`/`except` over the page collection is the only
  resolution here so far.
- `currentSelectionLivewireProperty()` — **skipped**, not deferred: it entangles
  the Alpine selection into a host property, which is exactly what the port's
  [writable props](has-table.md#the-selection-is-server-canonical-and-re-derived-on-every-read)
  already are.

### `columns()` takes columns only, and does not push

Filament's `columns()` accepts three kinds of member —
`Column`, `ColumnGroup` and a `Columns\Layout\Component` — resets six
properties, and delegates to `pushColumns()`, which sorts each member into the
flat `$columns` map, the `$columnsLayout` list and the `$columnGroups` map,
then walks the result to cache each column's action. Sylament's takes
`array<Column>`, resets one property, and keys the map by
[`getName()`](column.md).

Everything trimmed is a tier that does not exist yet: groups and layouts are
unported ([column.md](column.md) tracks both), and so are column actions — the
record-action lane put actions on the *row*, not in a cell — so the
action-caching walk has nothing to cache.
What is kept is the shape that matters — `columns()` **resets** rather than
appends, and binds itself to each member as it goes, so a second call replaces
the set and every column can reach the table and, through it, the host. Both are
pinned by [`TableTest`](../../../packages/tables/tests/Unit/TableTest.php).
`pushColumns()` returns as the public append seam when the grouping tier lands.

### An unshipped filters layout is refused where the chrome reads it

[`FiltersLayout`](../../../packages/tables/src/Enums/FiltersLayout.php) ships all
ten of Filament's cases, and the chrome consumes six. `Dropdown` puts the filters
form behind the toolbar's trigger; `Modal` behind a trigger that opens support's
`Modal`; `AboveContent` and `AboveContentCollapsible` render a panel between the
indicator row and the table, the collapsible one inside a `<details>`;
`BelowContent` renders it after the table and before the pagination; and `Hidden`
renders no filter chrome at all while the filters themselves still constrain the
query, nothing in the application path consulting the layout. The other four —
`BeforeContent`, `BeforeContentCollapsible`, `AfterContent`,
`AfterContentCollapsible` — need chrome the toolkit has not built: vendor's is a
sidebar from `lg` up and a floating-ui dropdown below it, one hybrid over two
behaviours (R3 §2.3).

The enum ships whole rather than trimmed, because the case *names* are the API a
migrating user greps for and a missing case is a fatal
`Error: undefined constant` at the call site with nothing to explain it. What
the port refuses instead is the *consumption*, and it does so in
`getConsumableFiltersLayout()` — the private probe every layout question in
`HasFilters` runs through, and the toolbar reaches at least one of them for any
filterable table — with a `LogicException` naming the case and the six that work.
`getFiltersLayout()` stays total: it evaluates the closure arm and returns
whatever was set, so a reader, a test or `getFiltersFormColumns()`'s fallback can
ask what the table wants without tripping the refusal.

That split is [`PaginationMode::Cursor`'s](#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features)
precedent applied a second time — refuse where the value is consumed, not where
it is set — and the reason is the same: a `Closure` layout has no value at set
time, so a setter-side check would be unenforceable for exactly the callers most
likely to need it. Each refused case lifts as its chrome lands; nothing about
the enum changes when it does.

The declaration **order** differs: ours leads with the shipped cases, Filament
leads with `AboveContent` and declares `Dropdown` eighth. The cases are pure — no
backing values, no interface — so the only thing that can observe it is
`FiltersLayout::cases()`, which nothing reads today. Left as declared rather than
re-sorted to vendor.

### The filters form columns follow the layout, the width does not

`getFiltersFormColumns()` carries Filament's match whole: a configured count
wins, and the fallback is `['sm' => 2, 'lg' => 3, 'xl' => 4, '2xl' => 5]` for
`AboveContent`, `AboveContentCollapsible` and `BelowContent`, `1` for everything
else. The responsive arm is not a general default — it is the full-width layouts'
default, and it is what makes an above-content filter bar look like a bar instead
of a column. The match runs over `getFiltersLayout()` rather than the consumable
probe, so a refused layout still answers `1` instead of throwing from a sizing
getter.

`getFiltersFormWidth()` stays trimmed in the other direction: Filament's derives
a `Width` enum case from the column count for the dropdown panel, and support
ships no `Width` enum, so the getter is the plain evaluated value with a `null`
default — a width the caller states or does not. The modal layout falls back to
`'md'` in the template for the same reason. That match returns if and when a
`Width` enum lands in support.


## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### One filter setting drives three selection behaviours

`deselectAllRecordsWhenFiltered()` reads as a filter setting and files with the
filters, Filament's placement kept — but nothing in filtering consults it. Its
three readers are all selection, and only the first is the one the name suggests:
the host clears the selection from the filter and search
[update tails](has-table.md#the-selection-is-server-canonical-and-re-derived-on-every-read);
`getSelectedTableRecordsQuery()` re-applies the filters and the search to the
selected-records query **only while it is on**; and `canTrackDeselectedRecords()`
returns false outright when it is off.

The three hang together. Under the default, any selection that survives is inside
the current filters by construction, so constraining the query is safe and closes
the select-a-row, filter-it-away, bulk-delete-it hole. Turn it off and the
selection deliberately outlives the filters, so constraining would silently drop
records the user still has selected — and an inverted "everything except these"
selection has no meaning at all once the set it inverts can drift underneath it,
which is why tracking goes with it.

### The bulk split is by class, and eviction is by identity

`cacheAction()` sorts on `instanceof BulkAction`, not on `isBulk()`. So an
`Action::make('x')->bulk()` — bulk on the wire, bulk in
[`getContext()`](../actions/action.md) — lands in the **record** map, and a bulk
mount reaches it only through the resolver's fall-through from the bulk map to
the record one. It works; it just does not take the direct path, and it does not
enable the checkbox column, since
[`isSelectionEnabled()`](#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed)
walks `getFlatBulkActions()`. Filament behaves identically; use `BulkAction` when
the class matters.

`removeCachedActions()` filters both maps with `in_array(…, strict: true)` —
identity, never name. That is what lets the
[destructive setters](#the-header-and-toolbar-setters-are-destructive-because-the-flat-bulk-map-is-the-checkbox-switch)
evict their own members without touching a same-named action some other
registration owns.

### The query lane is on exactly while no records closure is set

`hasQuery()` returns `$this->dataSource === null` — it never asks whether a query
was declared. A table with neither is on the query lane, and a table with **both**
is on the records lane, because `records()` wins outright. That is Filament's
precedence, carried over as-is: `hasQuery()` is `! $this->dataSource` there, and
the host branches on it to decide whether to run the closure or assemble a query.

The shape reads backwards until the two lanes are seen as one axis rather than
two flags. The records closure is the escape hatch — a user who supplies one has
said *I will produce the rows myself*, and a query left behind from an earlier
configuration pass must not silently resurface. So the predicate asks the escape
hatch, not the default. `records(null)` puts the table back on the query lane,
which is what makes the setter a true reset rather than a one-way switch.

The corollary is that "on the query lane" does not mean "has a query": a bare
`Table` answers `true` and `getQuery()` answers `null`. Anything assembling a
query has to handle that null itself, and cannot read `hasQuery()` as a
guarantee.

### Every read clones a memoised base query

`getQuery()` never hands out the stored builder. It resolves the *base* query
once — `getBaseQuery()` memoises into `$cachedBaseQuery`, so a `query(Closure)`
declaration that yields a builder is evaluated on the first read and not again —
then returns `applyQueryScopes(clone $baseQuery, …)`, a fresh clone per read. A
caller may therefore mutate what it gets back (paginate it, sort it, add a
`where`) without the next reader seeing any of it, and `modifyQueryUsing()`
scopes run against that private copy rather than accumulating on a shared one.

Both halves are load-bearing and neither is obvious from the signature.
Doctrine's `QueryBuilder::__clone()` deep-clones the DQL parts and the parameter
collection, so the clone is genuinely independent — but the builder is mutable
and every `->andWhere()` returns `$this`, so without the clone a single
`getQuery()` call site would permanently narrow the table. And without the
memoisation a closure declaration would be re-run per read: the record class, the
paginator's count query and the page query are three reads of the same table, and
a closure doing real work — a repository call, an ACL scope — would run three
times. `query()` resets both the base cache and the record-class cache, so a
re-declared query re-evaluates.

The memo holds a builder and never a `null`, so a declaration evaluating to null
— no `query()` at all, or a closure that returns one — is re-evaluated on every
read. It is a branch rather than a `??=` because the read has a second job: it
records which lane answered, in the flag
[`isRelationshipBaseQuery()`](#the-mappings-order-is-the-relation-scoped-tables-default-sort)
reports. That is exactly the case the value-plus-flag
pair [`getRecordClass()` keeps](#the-record-class-is-read-off-the-base-querys-root-entity)
exists to cover on its side, and it is deliberately not kept here, because the
null means *this table has no query*: every reader branches on it at once and
none of them re-enters this getter, so the repeat costs one closure call rather
than a getter per row. The shape to know about is a closure doing real work
before returning null — it pays that call per read.

Filament clones too (`$query->clone()`) but memoises nothing: its `getQuery()`
calls `$this->evaluate($this->query)` on every read, and `getModel()` keeps a
cache of its own because it is the one reader hit per row. The port memoises one
level lower, which subsumes that cache and covers the other readers with it.

Two members carry the rest of the lane. `applyQueryScopes()` is public and takes
the builder to scope, so a caller that built its own query — a count query, a
relation-scoped one — can put the table's scopes on it; scopes run in
registration order, and one returning nothing (a `void` closure mutating in
place) leaves the query it was handed, which is what makes both closure shapes
valid. It has no such caller yet, and one shipped query deliberately does not
become one: the
[aggregate batch](#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)
builds its own builder over the record class and is *not* scoped, because it is
already narrowed to the keys of a page the scopes produced — running them again
would re-filter a set that has been filtered, and a scope reading the search term
would filter it by a term the aggregated relation has no field for.
`getQueryEntityManager()` is Sylament-only: an Eloquent builder carries
its connection and every Filament consumer reaches the model statically, where
Doctrine's read side needs the `EntityManagerInterface` explicitly — so the
entity manager behind the base query needs a seam, and on the `query()` lane it
is read off the builder the caller handed over rather than injected. The
relationship lane is the case that stance did not foresee: there is no builder to
read from until something has *built* one, and building it is a mapping read. So
`Table` composes
[`CanAccessAssociations`](../../../packages/schemas/src/Components/Concerns/CanAccessAssociations.php)
— the reader arrives the way it reaches every schemas component, through a
`#[Required]` setter on the container-built table, with an entity-manager-less
fallback for one built outside the container that refuses at use rather than at
construction. A `Table` therefore carries exactly one container dependency for
this, and only the relationship lane consults it.

### A dotted name is resolved against the mapping before it is refused

Every query-side consumer of a column name — `Column::applySort()`, the host's
`defaultSort` branch, `Column::applySearchConstraint()`, the table's extra
searchable columns and `applyEagerLoading()` — resolves it the same way, in one
fixed order, and the order is what keeps · 12's behaviour byte-for-byte intact
while adding the relation arm on top of it:

1. **A dotless name** is `{rootAlias}.{name}`, exactly as before.
2. **A dotted name whose head is an alias the builder already declares** is
   returned verbatim, exactly as before — so a join written by hand in `query()`
   still wins, under whatever alias it was given, and a table that worked
   yesterday cannot be re-resolved out from under it.
3. Otherwise
   [`RelationPathResolver`](../../../packages/tables/src/Query/RelationPathResolver.php)
   walks the name against `ClassMetadata` from the root entity, and what it
   answers decides the arm.
4. Only if that resolves nothing does
   [`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php)
   throw — with a message naming all three resolutions it tried.

The walk itself has one rule worth stating, because it is not the obvious one:
at each step the **whole remaining path** is tested as a field name *before* the
head is tested as an association. That is what makes `address.street` resolve —
a Doctrine embeddable's fields are mapped under their dotted names, so
`hasField('address.street')` is true on the root and the path never becomes a
hop. It is also Filament's own precedence, arrived at differently: their
`hasRelationship()` asks `$record->hasAttribute()` before `$record->isRelation()`
for the same reason, an attribute that happens to share a relation's name wins.
The result is a `ResolvedRelationPath` — a list of hops and the field remainder —
with **zero** hops for the embeddable case (rendered `{rootAlias}.{fieldPath}`)
and one or more for a real relation path.

A path that runs out of segments standing **on** an association —
`sortable(['artist'])`, where `artist` is the relation itself and no field
follows — is the one resolution the two entry points answer differently, and the
split is deliberate. `analyseQueryPath()` **throws**, naming the missing field
segment, because sorting or searching *by an entity* is undefined and a silent
no-op there would read as a broken sort. `tryAnalyseQueryPath()` returns null,
and eager loading uses that one: a column named after an association is a
perfectly good display column (the cell renders the entity, or a
`formatStateUsing()` closure turns it into something), so the fetch-join arm has
no business refusing what the render will happily do. The failure contract is
therefore **strict on the query seams and lenient on the display seam**, drawn at
exactly one call site rather than at a `catch`.

### A relation search is a correlated `EXISTS` at every cardinality

Where a relation **sort** has to join, a relation search must not: a to-many join
multiplies the root's rows, and a search that turns one matching row into three
would break the count, the page arithmetic and the rendered list at once. So
`RelationConstraint::existsLike()` builds a subselect re-rooted on the *same*
entity, joins it along the path, correlates it back by identifier and puts the
`LIKE` on the far end:

```
EXISTS (SELECT sy_x0.id FROM Song sy_x0 JOIN sy_x0.genres sy_x0_0
        WHERE sy_x0.id = s.id AND sy_x0_0.name LIKE :sylament_search_0)
```

Filament reaches the same SQL shape from the other direction — `whereRelation`
is `whereHas`, which Eloquent compiles to `exists (select …)` — so this is a
faithful port rather than a divergence, and it earns a note only because three
of its details look like choices and are constraints. The subselect is re-rooted
on the **root entity** rather than started at the association, because DQL's
`FROM` cannot name an association path (the installed parser answers "NOT YET
IMPLEMENTED"). The correlation is **identifier equality** rather than entity
equality, which sidesteps comparing objects across the two scopes. And the
`LIKE` parameter is registered on the **outer** builder, because one DQL query
has one parameter space no matter how deeply a subselect nests — which is also
why the subselect's own aliases are seeded from the outer parameter count, so two
searchable relation columns in one word's `OR` group cannot collide.

Everything the search assembly does around it is unchanged: one `Orx` per word,
this predicate joining it like any other, no joins and no state left behind — so
it is idempotent across the per-word loop by construction, and the count clone,
which applies search but not sort, gets identical predicates and counts
correctly.

### The record class is read off the base query's root entity

`getRecordClass()` answers the entity class the table's rows are instances of —
`array_first($this->getBaseQuery()?->getRootEntities() ?? [])`, so a query with no
`from()` and a table with no query both answer `null`. It is Filament's
`getModel()` under the [record vocabulary](../renames.md#renames), and the read
underneath is the one thing that changed: Eloquent's builder holds a model
*instance* to take `::class` from, where Doctrine's holds the FROM clause's
class-strings, plural because DQL admits several roots. The first is the table's;
a second root is a join expressed as a cartesian FROM, and it describes the query
rather than the record.

The cache is a value plus a flag (`$cachedRecordClass`, `$hasCachedRecordClass`)
rather than a `??=`, because `null` is a real answer and must not re-read on
every call — Filament's shape, kept. The flag is what `query()` clears alongside
the base-query cache. The null stays a null rather than becoming a throw: it
means *this table is not on a Doctrine query*, which is a state every caller has
to branch on anyway.

### `getRecords()` delegates to the host and fetches nothing

`Table::getRecords()` is one line — `$this->getLiveComponent()->getTableRecords()`
— and that is Filament's shape too, not a stub. The `Table` owns the
*declaration* (`records()` stores a closure) while the host owns the
*resolution*: it evaluates the closure, normalises the result, and caches it for
the request. Reading `getRecords()` as the fetch point and adding caching or
evaluation here would duplicate — and diverge from — the host's copy in
[`HasTable::getTableRecords()`](has-table.md). The direction of the call is the
thing to keep: table → host, never the reverse.

`getRecordKey($record)` is the same one line against
[`getTableRecordKey()`](has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key),
and reads the same way: the *convention* for identifying a record is the host's,
and the table is the seam a template or a column reaches it through. That is why
the contract grew `getTableRecordKey()` rather than the table growing a resolver
of its own — a second implementation of the `id` read would be one more place to
keep in step with
[`InteractsWithRecord::resolveRecordKey()`](../actions/action.md).

### `getRecords()` unwraps the paginator, and `getPaginator()` is the only seam to it

`Table::getRecords()` returns `array` — never a `Paginator` — even when the
host's `getTableRecords()` returned one: it unwraps with
`$records instanceof Paginator ? $records->getRecords() : $records`. So the row
loop is written once and reads the same shape on a paginated and an unpaginated
table, and `records is empty` in the partial means *this page is empty*, which
is what the empty state should say.

Filament has no such method because its `getRecords()` **is** the paginator —
Laravel's implements `IteratorAggregate`, so `@foreach ($records as $record)`
walks the page while `$records->links()` renders the footer off the same object.
Sylament's [`Paginator`](../support/pagination.md) is iterable too, so that shape
was available; splitting it is what keeps the template's two readers honest about
what they need. The counts a footer wants are reached through `getPaginator()`,
which returns null on an unpaginated table — so the partial's `footerVisible` is
the carrier's presence plus a gate on what it counts, and it needs no second
`isPaginated()` read. With a known total the gate is
`getTotalRecordCount > 0`, suppressing the chrome for a table with no records at
all, and the total is what it tests rather than the page: a page **past the end**
is empty and still renders the footer, which is the only way back for a user who
paged past the data. A
[simple carrier](../support/pagination.md#one-carrier-expresses-both-shapes-rather-than-a-length-aware-and-a-simple-sibling)
has no total to gate on, so the null arm asks what it does know —
`count > 0 or not onFirstPage or hasMorePages` — which suppresses exactly one
shape, the first page of an empty simple table, and keeps the past-the-end footer
for the same reason the total arm does. Treating a null total as visible on its
own would put an empty footer under every empty simple table.

The trap to avoid when extending either: `getRecords()` must not start returning
the carrier "when there is one", and `getPaginator()` must not start building one
when there is not. Both would put the same information behind two shapes.

### The default sort is a secondary order, and its direction rules the tiebreaker

`defaultSort()` does not mean *the order when nothing else applies*. It is
appended **after** whatever the user clicked, so a table with both sorts by the
user's column first and breaks its ties with the default — and only when the
default names the very column the user is sorting by is it skipped, which is what
keeps `defaultSort('title')` from ordering by `title` twice. Filament composes it
exactly this way; the port keeps the composition, and
[`applySortingToTableQuery()`](has-table.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)
is where it is assembled.

The direction is the second surprise. `defaultSort()` takes one and defaults it
to `'asc'`, and that declared direction — not the direction the user clicked —
is what the default sort **and** the `defaultKeySort()` tiebreaker are applied
in: `getDefaultSortDirection() ?? getTableSortDirection()`, with anything but
`'desc'` read as `'asc'`. So under a `title:desc` user sort, a table declaring
`defaultSort('plays')` orders `title desc, plays asc, id asc`. The user's own
column keeps the direction they clicked; nothing after it does. A table that
never calls `defaultSort()` has a null default direction, so the tiebreaker
follows the user instead.

The `'desc'` comparison is the host's and it is case-sensitive, which is why
`getDefaultSortDirection()` lowercases what it evaluates (Filament's
`Str::lower()`) before handing it over: `defaultSort('plays', 'DESC')` means
descending, and dropping that one call would silently turn every mixed-case
declaration ascending. It is the only transformation on this side of the family,
and the [sorting suite](has-table.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)
pins it.

`getDefaultSort()` is declared to return `string|QueryBuilder|null` — wider than
the `string|Closure|null` property behind it, because the closure arm may hand
back a builder to sort **and replace** the query with, which is the one way a
default sort can also filter. The declaration is the contract the host branches
on, and it needs no evaluation seam: PHPStan reads the declared return, not the
narrower type `evaluate()` infers from the property. Filament widens the same
return and then cannot use it — every caller of `applySortingToTableQuery()`
discards the builder the arm produces — so *replacing* the query is the one
behaviour on this row that is
[ours rather than the original's](has-table.md#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded).

### A table is searchable when a column says so, and `searchable()` overrides in both directions

`isSearchable()` reads three sources in order and the order is the API: an
explicit `searchable(bool)` wins outright, then any extra searchable columns,
then any column that is **globally** searchable. So a table with a
`->searchable()` column renders a search field without the table itself being
configured at all — which is how a migrating user expects it to work — while
`->searchable(false)` on the table suppresses the field even when a column asks
for it.

The subtlety is that `$isSearchable` is `?bool`, not `bool`: null means *nobody
said*, which is what makes the column-derived answer reachable, and it is why
`searchable(false)` also clears `extraSearchableColumns` — leaving them would
make the second source contradict the explicit false. A column marked
`searchable(isGlobal: false, isIndividual: true)` deliberately does **not** make
the table searchable, because it asked for its own input, not for the global
field; that field arrives with
[per-column search](has-table.md#search-is-the-global-term-only). All of it is
Filament's, expressed the same way.

### Extra searchable columns take closures that return their expression

`searchable(['author.name', fn () => …])` takes closures beside names, and they
join the search group the same way a column's `searchable(query:)` closure does —
by **returning** an expression, not by mutating a nested builder. The contract is
one and the same, argued once in
[column.md](column.md#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group):
a returned `Expr` node or raw DQL string is added to the word's `Orx`, a closure
that returns nothing adds nothing, and a closure that returns the **builder** —
which `andWhere()` does, so `fn (QueryBuilder $query) => $query->andWhere(…)`
returns one whether or not the author meant to — is skipped rather than added,
because its constraint is already on the query, outside the `OR` group. That
skip is explicit in both places: `QueryBuilder` implements `Stringable`, so the
`string|Stringable` test that admits raw DQL would otherwise hand it to
`Orx::add()`, which refuses anything outside its `Comparison`/`Func`/`Andx`/`Orx`
allow-list with an `InvalidArgumentException`.

The injections are Filament's three — `$query`, `$search`, and the `$searchQuery`
alias for it — and a blank member is skipped before any of it, so a list built
conditionally (`['title', $extra ?? '']`) does not resolve an empty path.
Filament's `blank($column)` guard is spelled `array_filter(…, filled(…))` over
the list instead: the helper's `@phpstan-assert-if-false !null` makes a direct
`blank()` call on a non-nullable member read as an impossible check at level max,
where the same helper as an `array_filter` callback narrows the list and keeps
the semantics exactly (a whitespace-only member is blank too).

### `aria-sort` is dropped with null, because `false` would render as a string

The header cell's `aria-sort` is computed as
`isColumnSortable ? (… 'ascending' : 'descending' : 'none') : null` — the
unsortable arm is `null` and not `false`, which reads like an arbitrary choice
between two falsy values and is not. `false` drops an attribute in
[`ComponentAttributeBag::toScalarAttributes()`](../support/component-attribute-bag.md)
*except* for an `aria-` name, which the bag converts to the **string** `"false"`
first — ARIA states being string-valued, where `aria-hidden="false"` genuinely
differs from an absent attribute. So the `false` arm would emit
`aria-sort="false"` on every unsortable column: an invalid token for that
particular attribute, whose vocabulary is `ascending`/`descending`/`other`/`none`.

The general rule the rest of the partial relies on is unchanged — `null` and
`false` both drop — and this is the one family where they differ. Reach for
`null` whenever the intent is *no attribute*, and keep `false` for a genuine
boolean attribute (`disabled`) outside the `aria-` namespace.

### The row and cell morph keys disagree on `record` vs `records`

A row's key is `{hostId}.table.**records**.{recordKey}` and a cell's is
`{hostId}.table.**record**.{recordKey}.column.{columnName}` — plural on the row,
singular on the cell, for the same record and the same host. It reads like a
typo in one of the two.

It is Filament's, copied deliberately:
[`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php)
writes `.table.records.` on the `<tr>` (twice) and `.table.record.` on the `<td>`.
The strings are opaque — nothing parses them, they only have to be *stable* and
*distinct* — so the asymmetry costs nothing, and "correcting" it would silently
break any consumer that matched Filament's shape, which is the one thing porting
the value verbatim buys. Keep both spellings if this template is ever
restructured; they are not two spellings of one constant.

### The host binding is required, where the schemas one is optional

`Table::make()` takes a non-optional `InteractsWithTable`, and
`BelongsToLiveComponent::$liveComponent` here is a plain non-nullable property
with no `getLiveComponentOrNull()` peek — deliberately unlike the same-named
schemas trait
([`belongs-to-live-component.md`](../schemas/belongs-to-live-component.md)),
whose property is nullable because a `Schema` can be built standalone for a
preview render. A table cannot: it reaches its records only through the host, so
an unbound table has nothing to render and the binding is a constructor-time
requirement rather than a later optional attachment. Both packages ship a trait
called `BelongsToLiveComponent` with different nullability — check the namespace
before assuming which one you have. This matches Filament, whose
`Table\Concerns\BelongsToLivewire` is likewise non-nullable against schemas'
nullable one.

### The empty-state texts read the record labels

`getEmptyStateHeading()` falls back to `"No {plural record label}"` and
`Table::setUp()` — the first `setUp()` the class declares — stores a default
`emptyStateDescription()` closure answering *"Create a `<record label>` to get
started."* when the table carries an action named `create`, `null` otherwise.
Both are Filament's, minus the translation layer: Filament reads
`__('filament-tables::table.empty.heading', ['model' => …])`, and no message
catalogue ships in this port, so the interpolation happens in PHP and the string
is English. The i18n pass turns both into catalogue lookups without changing what
they interpolate.

Two consequences worth naming. The heading says `No records` only where the table
has no record class to read — a `records()` table, or one configured with
neither; a `query()` table over `Song` says `No songs`, through
[`plural()`](../support/helpers.md). And the description is a *stored closure*
rather than a getter fallback, which is what makes `emptyStateDescription(null)`
clear it: the setter overwrites the closure, so a caller can turn the default
off, exactly as in Filament. `hasAction('create')` reads the
[shared flat map](#the-flat-action-map-keeps-one-trim-and-its-caller), so a
`create` action declared as a record, header or toolbar action all count.

### The record title reads the attribute through the accessor and falls back to the label

`getRecordTitle()` tries the `recordTitle()` closure first — the record injected
by name and by type — then `recordTitleAttribute()` read through schemas'
[`PropertyAccessor::getFromRecord()`](../schemas/property-accessor.md), then the
record label. The attribute arm swallows an `AccessException` and treats a blank
or non-scalar value as absent, so a missing property, a null column and an object
column all answer the label rather than throwing or rendering an empty title —
the body panels' `Resource::getRecordTitle()` already carries, now shared by
the table.

The return type is therefore `string`, never null: the label always answers. That
is what makes actions' unconditional table hop
([action.md](../actions/action.md#a-table-bound-action-titles-and-labels-through-its-table))
terminal — a table-bound action never reaches its host's title hook, because the
table's answer is always filled.

### The relationship query is the base query, rooted at the target

`relationship()` is the third data lane, and it lands in one slot:
`getBaseQuery()` — the single memo every reader funnels through — falls back from
the declared `query()` to
[`AssociationReader::createRelationshipQuery()`](../schemas/association-reader.md).
Precedence is `records()` › `query()` › `relationship()` › the refusal, and
`hasQuery()` is unmoved: it still asks only whether a `records()` closure is set,
because a relationship table is a query table.

Everything downstream picks the lane up with no change of its own, which is the
point of putting it there. `getRecordClass()` reads the first root entity and the
root is the **target**, always — the many-to-many arm narrows by a subselect over
the owner's own association rather than by joining back through an inverse side,
and the one-to-many arm narrows by the target's `mappedBy` field — so the record
class is `Genre` for `Song.genres`, `getQueryEntityManager()` answers the
reader's manager, columns resolve their paths from the target, and search, sort,
pagination, the aggregate batch and the selection lane compose over the
constraint instead of around it. `getTableRecord($key)` is scoped for free and no
ownership check exists anywhere: the constraint lives in the base query, which
record resolution narrows *further* by key, so a key outside the owner's
collection resolves to `null` — which is also what makes a smuggled key harmless
on the two by-key bulk arms.

The relationship closure is evaluated **once** per table instance and memoised
behind a `hasCachedRelationship` flag, because it usually resolves the owner and
that is a database read; `relationship()` resets that memo along with the base
query and the record-class memo, so re-declaring the lane re-reads it. A closure
answering `null` leaves the table with no base query at all, and that is not a
quieter empty state: `hasQuery()` is still true — no `records()` closure is set —
so the render reaches the three-lane refusal, `Table [...] must have [records()],
[query()] or [relationship()]`. A host whose owner may be missing therefore
declares a `records()` fallback of its own (`->records(fn (): array => [])`)
rather than relying on the relationship closure answering `null`.

One rig caveat, because it decides where a delete test may live: the tables
fixture SQLite enforces no foreign keys, so a by-key delete of a record a join
table still references leaves the join row dangling here where a real Postgres
would refuse the statement. That is why the relation-scoped delete pins delete
`Song`s through `Artist.songs`, whose rows own their own foreign key, and never
`Genre`s through `Song.genres`, whose join table is `Song`'s mapping.

### The mapping's order is the relation-scoped table's default sort

Doctrine applies `#[ORM\OrderBy]` when it hydrates a collection, so every other
read of `Artist::$songs` comes back title-ascending. A relation-scoped table that
ignored it would be the one view of that association ordered differently from all
the rest, so `getDefaultSort()` falls back — when the table declares no
`defaultSort()` and the relationship's target carries an `orderBy` — to the
`QueryBuilder` arm of its own contract, `addOrderBy()`ing one qualified field per
mapping entry and returning the builder.

The fallback is gated on the relationship being the lane actually in force, which
`isRelationshipBaseQuery()` answers off the flag `getBaseQuery()` sets as it
picks a lane. A table declaring both `query()` and `relationship()` is a query
table, and appending the mapping's fields to *that* builder would qualify the
target's columns with the query's own root — invalid DQL against a different
entity — so the fallback answers `null` there and the relationship closure, a
database read, stays unevaluated on the render path.

That arm is why nothing else had to move: the host applies the *user's* sort
first and the default's parts after it
([the composition](has-table.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)),
so a clicked header wins and the mapping order degrades to a tiebreaker beneath
it, exactly as a hand-written `defaultSort(fn ($query) => …)` would. The
direction is read off the mapping rather than off the table, and anything that is
not `desc` is `asc` — Doctrine's own docblock says `'asc'|'desc'` while its
metadata hands `'ASC'` through untouched, so the comparison is
case-insensitive. `Artist::$albums` is declared `DESC` for exactly that reason:
it is the only fixture mapping whose direction disagrees with the caller's, so
without it the map would be indistinguishable from a hardcoded `'asc'`.
Filament has no counterpart: an Eloquent relation carries no readable default
order, so this is an addition rather than a divergence.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### The inverse relationship is read off the mapping, not derived

Filament's relation-scoped table carries three members the port does not:
`inverseRelationship()`, `getInverseRelationship()` and
`getInverseRelationshipFor()`. They exist because Eloquent cannot be asked what
the other side of a relation is called, so Filament *guesses* it — the parent
class's basename, singularised and camel-cased — reached through a magic dynamic
call on the relation object, and `inverseRelationship()` is the setter that
overrides the guess when it is wrong.

Doctrine already carries the answer: an association mapping names its counterpart
(`mappedBy` on an inverse side, `inversedBy` on an owning one) and
[`AssociationTarget`](../../../packages/schemas/src/State/AssociationTarget.php)
surfaces both. So the two jobs Filament needs the inverse name for are already
done without it — the write side reaches the target's owning field through
`mappedBy`, and the candidate query excludes already-related records with a
negated subselect that needs no inverse mapping at all. A setter with nothing
left to override would be an API that does nothing, which is worse than an
absence; the guess itself is exactly the kind of internal
[principles §1](../../principles.md) rejects. Three `— not ported` rows in
[renames.md](../renames.md) carry the names.

Reversal is cheap but unmotivated: it needs a *reader* to want the inverse name
by itself, and the only known caller — Filament's own
`RelationManager::makeTable()` — reaches it from a `@deprecated` static property
this port will not carry either.

### `HeaderActionsPosition` files under `Enums/`, where Filament keeps it under `Actions/`

Filament's `HeaderActionsPosition` is the only file its tables package keeps in
`src/Actions/`, and it is an enum rather than an action class. The port files it
in `Enums/` beside `RecordActionsPosition`, `RecordCheckboxPosition`,
`FiltersLayout`, `FiltersResetActionPosition` and `PaginationMode`, so the six
enums are in one place. Cases, name and defaults are Filament's; only the
namespace segment moves, which is the
[namespace translation](../renames.md#namespace-segments) rule rather than a
rename. `RecordCheckboxPosition` is **not** a divergence of any kind — Filament
already keeps that one in `Enums/`.

### The ten-per-page default is matched strictly

`getDefaultPaginationPageOption()` prefers `10` when the page options contain it,
and Filament tests that with a loose `in_array(10, $options)` where this port
passes `strict: true`. The two disagree on one input:
`paginationPageOptions(['10', 25])` yields the **string** `'10'` here (the loose
match having failed, the first option wins) and the **int** `10` in Filament — a
value that is not in the option list at all, which the host's
[validating getter](has-table.md#per-page-is-validated-in-the-getter-on-every-read)
would then reject in favour of… this same method.

Strict is the arm to keep: the returned value is compared against the option list
again on every read, so a default that is not literally one of the options is a
loop. It is also the house rule — `in_array()` without `strict: true` is a
PHPStan-flagged smell at level max — and the input that distinguishes them
(numeric strings mixed with ints in one option list) has no reason to exist. The
`'all'` option is a string on purpose and is unaffected: it is matched by value,
not by this branch.

### `isPaginated()` has no groups-only arm

Filament's is `$this->evaluate($this->isPaginated) && (! $this->isGroupsOnly())` —
a table showing only group headers has no rows to page through. `isGroupsOnly()`
belongs to record grouping, which is unported (its row is above), so the second
conjunct has nothing to read and the getter is the evaluation alone. It returns
in the change that lands grouping, and this row is listed as a divergence rather
than a gap because the *shipped* getter is complete for every table that can
exist today.

### `getSortableVisibleColumn()` has no hidden arm to check

Filament's rejects three things — an unknown column, a **hidden** one, and an
unsortable one. The port drops the middle check because column visibility is
unported (`hidden()` / `visible()` / `toggleable()` are a **todo** row in
[column.md](column.md)), so `isHidden()` does not exist to call. The name keeps
the word *visible* deliberately: it is Filament's, the check returns with the
concern, and renaming the method for the duration would break the API a migrating
user greps for.

The host's [global search loop](has-table.md#search-is-the-global-term-only) has
the same omission for the same reason: Filament skips a hidden column before
asking whether it is globally searchable, and the port asks only the second
question. Both arms return in the change that ports visibility, and neither can
be written before it — there is no third behaviour here, only a check that has
nothing to call.

### `getSearchDebounceMilliseconds()` is a Sylament-only translation of Filament's duration string

Filament stores `searchDebounce('500ms')` and interpolates the string straight
into the Blade attribute `wire:model.live.debounce.{{ $searchDebounce }}`, where
Livewire parses it. ux-live-component's `data-model` modifier is
`debounce(N)` with **N in milliseconds and nothing else**, so the string has to be
parsed on the PHP side; `getSearchDebounceMilliseconds()` does that and is the
value the partial renders.

`searchDebounce()` and `getSearchDebounce()` keep Filament's exact shape — the
setter takes and the getter returns the duration string, defaulting to `'500ms'`
— so a migrating call site is unchanged; the new member sits beside them as a
derived read. It accepts `500ms`, `1s`, a bare number (read as milliseconds), and
returns 500 for anything it cannot parse rather than throwing: a debounce is a
comfort setting, and a typo'd one should not take a page down. Recorded in the
[rename table](../renames.md#names-with-no-filament-source) as a name with no
Filament source. Permanent — the string is the API, the integer is the wire
format.

### `searchOnBlur()` renders `on(change)`, because ux-live-component has no blur event

The sibling translation, one modifier over. Filament's `searchOnBlur()` swaps
the input's binding to `wire:model.live.blur`, which commits the field on
**every** blur. The partial renders `on(change)|tableSearch` instead:
ux-live-component's `on()` modifier accepts `input` or `change` and throws on
anything else — the whitelist is literal in
`vendor/symfony/ux-live-component/assets/dist/live_controller.js`, in the
`modelDirective.modifiers` switch — so there is no blur event to name. The
delta is real rather than cosmetic: `change` fires when a **changed** value
leaves the field, or on Enter, so blurring an untouched input commits nothing
where Livewire would have committed it.

The setter and its getter keep Filament's shape (`searchOnBlur()` /
`isSearchOnBlur()`), so a migrating call site is unchanged and the intent the
name states — *do not search while I type* — is served exactly. Schemas already
made this translation for `lazy()`
([has-state-binding-modifiers.md](../schemas/has-state-binding-modifiers.md#livewire-modifier-array-grammar-becomes-a-single-getdatamodel-data-model-string)),
and it holds here for the same reason it holds there: the two client runtimes
have incompatible binding grammars, and this one has no third option.

One consequence of the binding to keep in view: the search `<input>` renders no
`value` attribute at all. That is safe today — the morph preserves a modified
model field's value, and no server-side path ever *clears* `tableSearch` — but
the day a "reset search" action lands, it will null the prop while the DOM input
keeps its stale text, because there is no attribute for idiomorph to reset
against. The fix is the one [`TextInput`](../forms/text-input.md) already
carries: render `value` explicitly and **empty rather than absent** (`?? ''`).
Do it in the same change as the writer, not before — an unconditional `value`
today would only re-render what the morph already preserves.

### The default search comparison assumes a string-typed field

[`SearchConstraint::like()`](../../../packages/tables/src/Query/SearchConstraint.php)
emits `path LIKE :param` — or `LOWER(path) LIKE :param` on the case-insensitive
arm — and nothing else. As of M3·4 the implementation itself lives in
[`Sylament\Schemas\Query\SearchConstraint`](../schemas/search-constraint.md),
with tables' class delegating; the reach described below is unchanged. Filament's `generate_search_column_expression()` does
more on Postgres: it double-quotes each segment of the name and appends `::text`,
because Postgres refuses `integer LIKE text` outright where MySQL and SQLite
coerce silently. So a `searchable()` integer column is portable in Filament and
is not here: against Postgres it fails loudly at execution, while the same table
against SQLite matches.

There is no DQL counterpart to lift. `::text` is a Postgres cast written in SQL,
and a DQL string is parsed by Doctrine before any platform sees it; the portable
spelling would be a DQL function, and both candidates are wrong here.
`CONCAT(path, '')` changes what an index can serve, and `CAST` is not a DQL
function at all — the parser's built-in string list is `concat`, `substring`,
`trim`, `lower`, `upper` and `identity`, anything else arriving through
`Configuration::addCustomStringFunction()`, which the toolkit cannot assume a
consumer has called. A platform-conditional emission would also have to happen
*before* the parser runs, which is exactly where `SearchConstraint` already reads
the platform for its case-insensitivity default — so a cast arm can be added
there the day one is worth its dependency.

Two escape hatches exist meanwhile, both first-class API rather than workarounds:
`searchable(query:)` on the column hands the closure the builder and takes back
whatever expression it wants (a registered `CAST`, a `LOWER(CONCAT(…))`, a
platform-specific literal), and `searchUsing()` on the table replaces the whole
assembly. The **column-name** half of Filament's helper — the quoting and the
JSON-path spelling — has no counterpart by construction: a DQL path is
`alias.field` against mapped metadata, never a quoted table name, and JSON access
is not in the grammar.

### A search term is not escaped for `LIKE`

The bound parameter is `'%' . $search . '%'`, so `%` and `_` typed by the user
stay wildcards: searching `a_c` matches `abc`, and searching `%` matches every
row with a non-null value in any searchable column. This is Filament's behaviour
exactly — its `generate_search_term_expression()` lowercases and returns, and the
call site interpolates `"%{$search}%"` — and the port keeps it rather than
quietly changing what a migrating table matches.

It is a usability quirk and not an injection risk: the term is a **bound
parameter** on both sides, never concatenated into DQL, so the worst a crafted
term does is match too much. Escaping it properly needs a `LIKE … ESCAPE` clause
(supported by the DQL grammar) plus a per-platform decision about the escape
character, and it changes matching for every existing table — so if it lands it
lands as an opt-in on the same setter that already governs case
(`forceSearchCaseInsensitive()`'s neighbour), not as a silent fix.

### A dotted path into a JSON column is refused, where Filament's Eloquent pushes it through

Filament's `getJsonSafeColumnName()` replaces every dot in a name that is **not**
a relationship with the driver's JSON arrow (`data.theme` → `data->theme`), and
it does so unconditionally — no cast is consulted, no column is looked up — so a
dotted name that survives the relationship check sorts and searches straight into
the document, and a typo'd one becomes a JSON path that quietly matches nothing.
Here the same name is refused: the resolver asks Doctrine's mapping, which knows
the column's type is `json` and nothing whatever about its contents, so no field
and no association matches and `ColumnPathResolver` throws — with the JSON case
named in the message, so the refusal reads as a decision rather than a missing
feature.

The cell **display** side is unaffected and this is the asymmetry to keep in
mind: `getStateFromRecord()` walks a decoded array by key, so a `data.theme`
column renders perfectly and only refuses when someone marks it `sortable()` or
`searchable()`.

What it would take to close it is the reason it is not closed: DQL has no JSON
path operator, so the query would need a registered custom DQL function per
platform (`JSON_EXTRACT` on MySQL/SQLite, `#>>` on PostgreSQL) plus a way for the
resolver to know which prefix is a JSON column and which is a path inside it —
a per-application registration, not something a port can infer from mapping
metadata. Until then the escape hatches are the ones the message names:
`sortable(query:)` and `searchable(query:)`, which hand the user the builder and
let them write the platform's own expression.

### A relation sort joins, where Filament's orders by a correlated subquery

Sorting by `artist.name`, both codebases starting from a name with no join
declared anywhere, reaches the same rows by opposite mechanics. Filament hands
the name to `RelationshipOrderer::buildSubquery()`, which builds the relation's
own query, selects the sorted column off it, correlates it to the outer row and
`limit(1)`s it, then orders by that subquery. Sylament's
[`ColumnPathResolver::resolveOrderable()`](../../../packages/tables/src/Query/ColumnPathResolver.php)
`LEFT JOIN`s each hop through
[`RelationJoiner`](../../../packages/tables/src/Query/RelationJoiner.php) and
orders by `{alias}.{field}`.

The join is the better shape *here* for a reason that is Doctrine's rather than
a preference: the same join is what
[eager loading](column.md#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality)
wants anyway, so a column that is both sorted and displayed costs exactly one
`LEFT JOIN` and no extra query — where Eloquent's subquery buys nothing for the
display side, its eager load being a separate query regardless. Three
consequences worth knowing:

- **LEFT, unconditionally.** An `INNER` join would drop every row whose relation
  is null the moment a user clicks a sortable header — rows vanishing on a
  *sort* is a data change, not an ordering change. Filament's correlated subquery
  yields `null` for the same rows, so LEFT is also what matches its outcome.
- **The join is reused, including a user's own.** `ensureJoined()` scans
  `getDQLPart('join')` for a join whose expression is exactly
  `{parentAlias}.{property}` and adopts its alias if one is there. That
  deliberately adopts a join the user wrote in `query()` **whatever its type** —
  an `INNER JOIN` or one carrying a `WITH` condition included. The alternative is
  worse in both directions: minting a second join over the same association
  doubles the rows the user's own join was there to shape, and refusing to reuse
  it would make a hand-written `innerJoin` a hard error. So a table that
  inner-joins its author in `query()` and then sorts by `author.name` keeps its
  own narrowing, which is what it asked for by writing the join.
- **Minted aliases are deterministic** — `sy_{property}`, nested as
  `sy_{parent}_{property}`, numerically suffixed only on a real collision with
  `getAllAliases()` — so the DQL a table produces is stable across renders and
  reads in a log.

A **to-many** hop anywhere in the chain is refused with a `LogicException`
naming both ways out (`sortable(query:)`, or an aggregate column). That is
Filament's own behaviour, not a limitation of this port: their
`RelationshipOrderer::validateRelationshipType()` throws an
`InvalidArgumentException` for anything that is not `BelongsTo`, `HasOne`,
`MorphOne`, `BelongsToThrough` or `HasOneThrough`. Ours is a `LogicException`
for consistency with the lane's other refusals, and it fires **before** anything
is joined, so a refused sort leaves the query untouched.

### An aggregate is a batched side query and a HIDDEN subselect, never a select on the page query

Filament's aggregates ride the page query: `withCount()` and its five siblings
add a correlated sub-select per declaration, Eloquent hydrates the result onto
each model as an attribute, and the cell reads it from there. That is one query
for rows and values together, and it is unavailable here for a reason that is
structural rather than aesthetic — a non-`HIDDEN` scalar in a Doctrine `SELECT`
switches the whole result to **mixed hydration** (`[0 => Entity, 'sy_value' => 3]`
per row), which breaks record keying, cell state, the row loop and
`Paginator::getIterator()`, all of which expect entities.

So the value takes a second path. After the page's records are resolved and
keyed, the host runs one batched query per aggregate column —
`SELECT {root}.{id} AS sy_key, {FN}({t}.{col}) AS sy_value … WHERE {root}.{id} IN
(:keys) GROUP BY {root}.{id}` — and drops the result into the table's
[side store](../../../packages/tables/src/Table/Concerns/HasAggregateValues.php),
where the cell reads it ahead of walking the record. The page query stays a plain
entity read, the count query is untouched, and the cost is +1 query per aggregate
column per render.

Two details of that batch are contracts rather than incidentals. Its joins are
**inner**, so a key whose related set is empty produces no group and is simply
absent from the result — the host fills the gap with SQL's own semantics (`0` for
a count, `false` for an existence check, `null` for the rest), which keeps the
defaults in one place instead of asking the database to invent them. And the key
narrowing is applied **after** the scope closure has run, because Doctrine's
`where()` *replaces* where Eloquent's `where()` *adds*: a scope written with the
Filament reflex would otherwise silently drop the `IN (:keys)` and aggregate the
whole table into one arbitrary key's cell. (A scope calling `groupBy()` still
replaces the grouping — the same hazard, one layer along, and unfixed rather than
unnoticed.)

**Sorting** cannot use the store, since the database has to do the ordering, so
it takes a third shape: the same aggregate as a correlated subselect added to the
sorted clone `AS HIDDEN sy_agg{n}`, with `addOrderBy()` naming that alias.
`HIDDEN` is what keeps hydration pure — the scalar is projected for the SQL's
benefit and dropped before the result is built — and it survives both paginator
walkers (`LimitSubqueryOutputWalker` un-hides it into the inner SQL by design,
`CountOutputWalker` wraps the whole statement), which is the property the whole
approach rests on. Only the sorted clone carries it; the count clone never
applies sort at all.

The cost shape is worth stating plainly, because "correlated subselect" sounds
worse than the alternative and is in fact the *same* alternative: Laravel's
`withAggregate()` is also a correlated sub-select in the SELECT list, so both
codebases pay per candidate row when ordering by an aggregate. What differs is
that Filament pays it **once** for value and order together, while this port pays
the subselect on the sorted clone *and* a batch query for the values. One extra
query per aggregate column, in exchange for a page of clean entities. An
`exists()` column sorts by the raw `COUNT` rather than by the boolean it
displays: the zero/non-zero boundary is identical, so the two partitions land the
same way round, and only the order *within* the true group differs — by count,
not by tiebreak. With no `castToBool` in the ordering there is nothing to
collapse them.

### A composite-identifier root refuses relation search and aggregates

Both of the mechanisms above correlate a subquery to the outer row by a **single**
identifier column — `WHERE sub.id = root.id` for the search `EXISTS`, `WHERE
root.id IN (:keys)` and `GROUP BY root.id` for the aggregate batch. A record
class whose identifier is composite has no such column, so
`getSingleIdentifierFieldName()` throws `MappingException` and both sites catch
it and re-throw a `LogicException` naming the limitation and the class.

That is louder than the rest of the lane treats the same shape, and deliberately
so. Record keying
[refuses composite identifiers](has-table.md#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key)
at the same point, and the identifier tiebreaker
[no-ops rather than throwing](has-table.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)
— a missing tiebreak degrades a page boundary, which is survivable, where a
search that silently matched nothing or an aggregate that silently filled every
cell with the same value is a wrong answer presented as a right one. A
composite-identifier table can still render, sort by plain and to-one relation
paths, and page; what it cannot do is type into the search box against a relation
column, or declare an aggregate. Composite identifiers are out of the relation
layer on the schemas side too, which is where the stance was set.

### A `Markup` header label does not switch the sortable header to a `<span>`

Filament's header cell renders its sortable trigger as a `<button>` **unless**
the column label is an `Htmlable`, in which case it emits a `<span>` with the
same click handler instead — the escape hatch for a label that already contains
interactive markup and would nest a control inside a button. Sylament's partial
always renders a `<button>`.

Twig has no type test for `Markup` — `label is instanceof('Twig\Markup')` needs
`twig/extra`'s `instanceof` test, which the port does not ship and does not want
for one branch, and the alternative (a `Column::hasMarkupLabel()` getter existing
solely to answer a template's type question) puts a rendering concern on the
column. Against that, the case is currently unreachable in a useful way: a
`Markup` label containing a control would already be broken inside the button,
and no ported column produces one. YAGNI wins.

Provisional, and the reversal is cheap when a real case appears — the branch is
one `{% if %}` around the trigger element, and the accessible name is the same
either way. Note what does **not** reverse it: a `Markup` label with no
interactive content renders correctly inside the button today.

### The copy is English and hardcoded, because no i18n layer ships

Filament's `getEmptyStateHeading()` falls back to
`__('filament-tables::table.empty.heading', ['model' => $this->getPluralModelLabel()])`
— a *translated* string interpolating the table's plural model label. Sylament
interpolates the same label
([semantics](#the-empty-state-texts-read-the-record-labels)) and emits English
directly.

Only the translation half is missing, and it is not a table concern: **i18n is
not wired anywhere in the port** — no `translation` component is a dependency of
any bundle and no message catalogue ships, so there is nothing for a translation
key to resolve against. Every fixed string the toolkit renders sits in the same
position, which is why this section is the anchor the other packages' copy rows
cite.

The literals read correctly in English rather than making an API claim. They
reverse in one step: an i18n pass turns each into a catalogue lookup. Until then
a consumer wanting other copy sets it explicitly, which is fully ported
everywhere it applies.

### The empty state icon getter returns null, and the fallback lives in the template

Filament types `getEmptyStateIcon(): string | BackedEnum` — never null — because
the getter itself chains two fallbacks: `FilamentIcon::resolve(TablesIconAlias::EMPTY_STATE)`
for the registered alias override, then `Heroicon::OutlinedXMark`. Sylament types
it `string|BackedEnum|Markup|null` and returns only what the user set.

Only the **nullability** differs. The settable union is Filament's
`string | BackedEnum | Htmlable | Closure | null` under the standard
`Htmlable` → [`Twig\Markup`](../renames.md#idiom) translation, matching support's
own [`HasIcon`](../../../packages/support/src/Components/Concerns/HasIcon.php)
— so a raw `<svg>` handed to `emptyStateIcon()` survives the getter untouched
rather than being escaped or rejected. Filament's own getter return type
(`string | BackedEnum`) is narrower than its property union and could not
actually carry the `Htmlable` arm it accepts; the getter here admits `Markup`
so the two agree.

The chain is not dropped, it moves: the alias hook and the `heroicons:x-mark`
default sit next to each other in the table template, which is where the rest of
the toolkit already puts them — the same shape support's `Modal` close button
uses. Keeping them in PHP would split one two-step fallback across a getter and
a template, and would make the getter lie about whether the user configured
anything. A caller asking *"did the user set an icon?"* gets a straight answer
here; a caller wanting the rendered icon reads the template's resolution. This is
permanent, and it is the general policy for icon getters in this port rather than
a table-specific choice.

### The partial emits no header scaffolding and no content wrapper

Filament's `index.blade.php` nests the table five deep — `fi-ta` > `fi-ta-ctn` >
`fi-ta-main` > `fi-ta-content-ctn` > `table.fi-ta-table` — and the two header
containers inside `fi-ta-main` (`fi-ta-header-ctn`, `fi-ta-header-toolbar`) are
emitted **unconditionally**, hidden with `x-cloak` + `x-show` when the table has
no heading, description or toolbar action. Sylament's partial is three deep —
`sy sy-ta` > `sy-ta-ctn` > `table.sy-ta-table` or `div.sy-ta-empty-state` — with
no `sy-ta-main`, no `sy-ta-content-ctn`, no `sy-ta-header-ctn`, and no
`aria-label` on the `<table>`; the two bands that do render — `sy-ta-header`
(heading, description and the header actions, landed with the heading rows) and
`sy-ta-header-toolbar` (toolbar actions, search, the filters trigger) — render
only when something would go in them, where Filament emits both unconditionally
behind `x-cloak` + `x-show`.

The wrappers are not markup for their own sake in Filament: they exist so Livewire's
DOM morph finds the same nodes before and after a toolbar's visibility flips,
which is why they render empty rather than being wrapped in an `@if`. Live
morphs survive the conditional rendering here because the whole partial
re-renders server-side. The `sy-ta-header` band landed with the heading and
description rows, `header.css` behind it, and `HeaderActionsPosition::Adaptive`
now has its CSS — the adaptive class rides the band when header actions are
present. The `aria-label` half of the old prediction dissolved on inspection:
vendor 5.7.6 puts no `aria-label` on its `<table>` at all — the
`{{ $pluralModelLabel }}` label sits on the `role="list"` wrapper of the
unported grid-content layout, so it waits there, not here.

Provisional, and the reversal is mechanical: each container comes back in the
change that lands the first feature it holds, at which point its emptiness is
transient rather than permanent. The **class names** are already reserved by the
Filament originals, so nothing about the eventual DOM depends on inventing them
later.

The `ms-auto` stopgap on `.sy-ta-search-field` left with the toolbar's second
group, and the prediction that replaced it has since been paid off in full. It
expected filters to land in **slot 1** beside the search field, and they did not:
Filament's toolbar has exactly two children — a start-aligned actions group
(reordering, toolbar actions, the grouping selector) and an end group holding the
search field, the filters trigger and the column manager — so filters joined the
search field in the *same* child, and the positional `& > :nth-child(2)` gave way
to the named `& > .sy-ta-header-toolbar-end { ms-auto }`. Naming the group rather
than counting it is what made
[the start group](#the-toolbars-start-group-carries-its-own-class-and-renders-only-when-it-has-content)
a one-rule addition when it landed, with no second reversal anywhere. The toolbar
is pinned in every state — search only, filters only, actions only, all of them,
none at all — by
[`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php).

### The toolbar's start group carries its own class and renders only when it has content

Filament's start-aligned toolbar group *is* `fi-ta-actions`, the same class its
record-action cell wrapper carries, and it renders unconditionally so the
positional `& > :nth-child(1)` / `:nth-child(2)` rules always find two children.
Neither half survives the port. The class is `sy-ta-header-toolbar-actions`,
because `.sy-ta-actions` already means *the record-actions cell wrapper* here and
its rule is `justify-end gap-3` — a start-aligned toolbar group under that name
would be right-aligned and differently gapped — and because
[`TableTest`](../../../packages/tables/tests/Integration/TableTest.php)'s
all-hidden-actions case asserts the *absence* of the string `sy-ta-actions` from
the rendered table, which a toolbar group of that name would falsify. The group
is then gated on holding a header or toolbar action, which costs the end group
nothing precisely because the two groups are named rather than counted
([above](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper)): an
absent first child leaves `& > .sy-ta-header-toolbar-end { ms-auto }` doing the
same work. The stylesheet addition is one rule, `max-w-full shrink-0` — vendor's
`fi-ta-actions` pair minus everything the toolbar's generic
`& > * { flex min-h-9 items-center gap-x-4 }` already supplies.

The group's contents keep Filament's order exactly — [reorder trigger]
[toolbar actions] [grouping selector] — with the first and last as comment slots
(M4 · 3 and M4 · 6), and the end group reserving a third for the column manager
(M4 · 5). Getting that order wrong is what forces a restructure two epics later,
so the comments are load-bearing rather than decorative. Header actions rendered
here as a stopgap while no `sy-ta-header` band existed; they moved into the band
(`sy-ta-header-actions` — named, not `fi-ta-actions`, for the same
record-actions-collision reason as the start group) when the heading rows
landed, which is Filament's own placement, and each list wraps its bulk-only
members the same way wherever it renders.

### A bulk-only trigger is hidden by a wrapper span

Filament reveals a bulk trigger with `x-cloak` + `x-show="getSelectedRecordsCount()"`
written straight onto the trigger element. The port cannot: the trigger is
rendered by the action's own template, and the only thing the table partial
reaches into it is `getExtraAttributes()` — an unconditional map, which is where
[`BulkAction`](../actions/bulk-action.md#bulk-only-visibility-is-a-data-marker-the-table-controller-flips-not-an-alpine-expression)
stamps `data-sy-table-bulk-only`, `BulkActionGroup` stamping the same marker on
its dropdown root. That marker is what the selection controller flips `hidden` on,
so the client half needs nothing further; the **server** half — `hidden` on the
first paint, before the controller connects — has nowhere to go on an element the
partial does not write. So each bulk-only item is wrapped in a `<span>` carrying
both the marker and `hidden` while nothing is selected, the shape
`CheckboxList`'s two swapped bulk-toggle spans already use. Wrapper and trigger
carry the same marker and flip together.

Which items get the wrapper is read off the item itself — `isBulk` for an
`Action`, and for an `ActionGroup` the marker's presence in
`getExtraDropdownAttributes()`, which is the same declaration the controller
selects on rather than a second predicate free to drift from it. The gap that
leaves is Filament's `ActionGroup::hasNonBulkAction()`, deliberately unwritten
here: a **plain** `ActionGroup` holding only bulk actions is unmarked, so it
renders and stays visible with nothing selected. `BulkActionGroup` — what
`groupedBulkActions()` builds, and what the demo uses — is marked and behaves.
The gap closes with that method.

### The selection banner flattens vendor's second wrapper and renders support's `Link`

Filament's banner is one `fi-ta-selection-indicator` holding two children, so its
`& > :nth-child(1)` / `:nth-child(2)` rules can address them: the first a `<div>`
around the loading indicator and the count text, the second a `<div>` wrapping
`fi-ta-selection-indicator-actions-ctn` — a wrapper that exists only because two
render hooks bracket it. There are no render hooks here, so the second wrapper is
dropped and `.sy-ta-selection-indicator-actions-ctn` **is** the second child.
That makes vendor's own `& .fi-ta-selection-indicator-actions-ctn { flex gap-x-3 }`
redundant with the `& > *` rule above it, so it is not ported either and the
class survives as the DOM hook the tests and the controller select on. The
loading indicator inside the first child goes with
[the loading affordances](#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them)
the toolkit has not ported at all.

Both links are `<twig:SylamentSupport:Link tag="button">`, which is exactly what
`<x-filament::link tag="button">` is on the other side — so `primary` and
`danger` come from the colour policy rather than a banner-local rule, and
[`selection.css`](../../../packages/tables/assets/src/styles/components/selection.css)
carries no link styling at all. "Select all" ships a server `hidden` and is
revealed by the controller only where a ceiling is known and not everything is
selected; "Deselect all" is visible whenever the banner is. The count is a
controller-filled empty `<span>` seeded from two literal value strings
(`1 record selected`, `:count records selected`) — Filament's `|`-separated
Laravel plural string run through its own `window.pluralize` global, which the
port does not ship; i18n takes both literals when it lands. The controller
formats the number it interpolates with `Intl.NumberFormat`, so it reads
_"1,200 records selected"_, while the ceiling in the server-rendered
_"Select all N"_ label is the bare integer. The two are deliberately not aligned:
`Intl.NumberFormat()` groups by the **browser's** locale and Twig's
`number_format` by the template's, so matching them would trade a visible
inconsistency for a locale-dependent one. It resolves with i18n, on the same
pass that takes the two count literals.

The banner is server-gated on `isSelectionEnabled()` and client-gated on the
count, as vendor's is, and it renders on an overflow (empty) page too: a
selection outlives a page that lost its rows. Of vendor's two extra server gates
one is ported — the banner does not render while a deferred table's window is
still open — while `maxSelectableRecords(1)` stays unported. So is the
`fi-selected` row tint and its leading primary bar — that
rides a class Filament's Alpine binds onto each `<tr>`, and the port stamps no
per-row class from the client; the ticked checkbox carries the state visually
until the row-click work of M4 · 3 revisits it.

### The selection cells carry no header-cell class, and their checkbox skin is support's

The `<th>` and `<td>` wrapping a checkbox carry `sy-ta-selection-cell` — never
`sy-ta-header-cell`. That is Filament's own split, and it is load-bearing
downstream: every header-label assertion in this repo and in the demo reads
`th.sy-ta-header-cell` as *the column headers*, and a selection column would
otherwise push an unlabelled cell into that list. The one deliberate trim is
`sy-ta-cell` on the `<th>`, which vendor does stamp there: our `.sy-ta-cell` rule
is written for a body cell (`p-0`, plus a `:has(.sy-ta-actions)` arm), so the
header would import padding the selection rule then has to override. The body
cell keeps both classes, and `.sy-ta-selection-cell` is authored *after*
`.sy-ta-cell` in
[`table.css`](../../../packages/tables/assets/src/styles/components/table.css)
so its `px-3 py-4` outranks the base `p-0` on source order at equal specificity.

A record the table refuses renders the cell **empty** rather than a disabled
input — Filament's shape, and behavioural rather than cosmetic: the controller's
page-key walk reads `.sy-ta-record-checkbox` values off the DOM, so a record with
no input is excluded from "select page" by construction.

The `<input>` is inline in the template with no support Twig component behind it,
again Filament's shape, and its skin is the code-less `.sy-checkbox` class
support gained in the same change, beside the `sy-ta-page-checkbox` /
`sy-ta-record-checkbox` hooks. Moving the skin there is what gave the page
checkbox its `:indeterminate` arm, which forms' copy never carried because
nothing in forms can set that property
([forms' styles.md](../forms/styles.md#the-checkbox-skin-moved-to-support-when-tables-became-its-second-consumer)).

The two ARIA labels are Filament's own strings with one word translated —
"Select/deselect all records on this page for bulk actions." and
"Select/deselect record :key for bulk actions." against vendor's
"Select/deselect all items for bulk actions." and
"Select/deselect item :key for bulk actions." *Record* is this toolkit's word for
what vendor's translation calls an item, and the page checkbox says which rows it
covers because our header cell is the only place that label appears.


### Every rule in `table.css` is scoped under the `.sy-ta` root, one class above vendor's

Filament ships no `.fi-ta` rule at all — `.fi-ta-ctn`, `.fi-ta-table`,
`.fi-ta-header-cell`, `.fi-ta-cell`, `.fi-ta-row`, `.fi-ta-actions` and
`.fi-ta-empty-state` are each a top-level selector in a file of its own.
[`table.css`](../../../packages/tables/assets/src/styles/components/table.css)
wraps all seven in one `.sy-ta { … }` block. That is what lets the seven collapse
into a single stylesheet — six of them absorbed into the file whose basename
already matched — and it is what matches the `div.sy.sy-ta` root the partial
always emits: deliberate scoping, not a nesting habit.

It costs exactly one class of specificity on every rule the file ships, and the
shift is uniform: `.fi-ta-cell` at (0,1,0) becomes `.sy-ta .sy-ta-cell` at
(0,2,0), `.fi-ta-ctn` becomes `.sy-ta > .sy-ta-ctn`, and each nested arm moves
with its parent — `.fi-ta-cell .fi-ta-col` at (0,2,0) becomes
`.sy-ta .sy-ta-cell .sy-ta-col` at (0,3,0). Nothing *inside* the file changes,
because every rule gains the same class; what changes is what an outside rule has
to beat.

So a consuming app overriding a table rule with a single class —
`.sy-ta-cell { … }`, (0,1,0) — loses to the shipped rule wherever its stylesheet
sits in source order, where the same override against Filament would win on order
alone. It has to match or beat (0,2,0): re-scope it under `.sy-ta`, or double the
class (`.sy-ta-cell.sy-ta-cell`).

This is `table.css` alone. The package's other three stylesheets —
`header-toolbar.css`, `filters.css` and `text-column.css` — declare every one of
their roots at top level exactly as vendor does, so a consumer overriding a
toolbar, a filter or a column rule pays nothing extra and only the table chrome
carries the surcharge. The other failure mode the extra class could
introduce — a rule that silently never applies because the scope is absent — is
closed at the render: the partial emits `sy sy-ta` unconditionally, and the
Integration [`TableTest`](../../../packages/tables/tests/Integration/TableTest.php)
selects `div.sy.sy-ta > div.sy-ta-ctn > table.sy-ta-table` with the child
combinator, so a flattened or renamed root fails there rather than as an
unstyled table.

### The search field flattens support's input wrapper into two classes

`fi-ta-search-field` carries no rule in Filament at all — it is a bare hook on a
`<div>`, and everything visible comes from the `x-filament::input.wrapper`
component it contains: `.fi-input-wrp` paints the chrome, an inner
`.fi-input-wrp-prefix.fi-inline` holds the icon, and
`input.fi-input.fi-input-has-inline-prefix` sits in a
`.fi-input-wrp-content-ctn` beside it. Support ships that component now
([input-wrapper.md](../support/input-wrapper.md)), but this field predates it
and has not been migrated onto it in this epic — a follow-up, not done here —
and the field itself is [ported inline into the partial](README.md) rather than
as a component, so four vendor elements collapse to two — `<label
class="sy-ta-search-field">` holding the icon and the input directly — and three
vendor rules collapse into `header-toolbar.css`'s two.

The metrics carry over unchanged, which is worth spelling out because one of them
reads like a drift and is not. Ours puts `gap-x-2` between the icon and the
input; vendor's `gap-x-3` separates *multiple* prefix children and never shows
here, because the search field's prefix holds one visible child — the icon, with
a `wire:loading` indicator beside it that is `display: none` at rest and so
creates no gap (and which this port does not render at all, loading affordances
being [deferred](#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them)).
The icon-to-input distance vendor actually computes is the inline prefix's `pe-2`
plus the input's `ps-0` — 0.5rem, which is what `gap-x-2` is. The leading `ps-3`
is the prefix's, the trailing `pe-3` is the `pe` half of the input's `px-3`,
`min-w-0` is lifted off `.fi-input-wrp-content-ctn`, and the `@supports
(-webkit-touch-callout: none)` Safari-zoom block is vendor-verbatim, comment
included. The input's six `disabled:` / `dark:disabled:` arms — including the
`[-webkit-text-fill-color:…]` pair iOS needs — are carried too: the element is a
real `<input>`, so they are expressible on the flat DOM whether or not this call
site ever disables it.

Two vendor rules cannot come across, and they are the remainder rather than a
choice. `.fi-input-wrp`'s disabled chrome (`&.fi-disabled { bg-gray-50
dark:bg-transparent }`) and its focus guard
(`&:not(.fi-disabled):not(:has(.fi-ac-action:focus))`, which suppresses the
wrapper ring while a prefix action holds focus) both key off DOM this partial
does not emit — no `sy-disabled` on the field, and no action mounted inside it.
Both return once this field migrates onto the input-wrapper component, along
with the two-layer structure that migration would restore.

### `items-center` is lifted onto the search field, because the prefix that carried it is gone

Vendor's `.fi-input-wrp` is a bare `flex`, so its `align-items` stays `stretch`;
the `items-center` sits one level in, on
[`.fi-input-wrp-prefix`](../../../vendor/filament/support/resources/css/components/input/wrapper.css)
(`hidden items-center gap-x-3 ps-3`), the box holding the icon.
[`header-toolbar.css`](../../../packages/tables/assets/src/styles/components/header-toolbar.css)'s
`.sy-ta-search-field` is `flex items-center`: the class that ports the *wrapper*
gained it, and the class that declared it has no port at all, because the element
it styles is not emitted.

The lift is what keeps the icon where Filament draws it, not a restyle. Vendor
centres the icon *inside* a prefix box that stretches to the field's full height.
Flatten the prefix away and the icon becomes a direct child of the wrapper, where
`stretch` degrades to `flex-start` for any item with a definite cross size — and
the icon has one, `.sy-icon`'s `size-5`, 1.25rem against a field 2.25rem tall
(the input's `leading-6` plus its `py-1.5`). Without the lift the magnifying
glass renders 0.5rem above vendor's.

The one element the two arrangements can disagree about is the input, and today
they cannot. Vendor's stretches inside `.fi-input-wrp-content-ctn`; ours is
centred as a direct child. Both are no-ops while the input is the tallest thing
in the field — it is what sets the height — so they part only if the field is
ever given a height of its own or a taller sibling, at which point vendor's input
would grow and ours would stay at its natural height, centred. Nothing sets
either, and the divergence closes with the same migration onto the
input-wrapper component the rest of the flattening waits on.

### The search input adds an `outline-none` vendor does not have

`input.fi-input` suppresses the UA outline in the focused state only —
`focus:ring-0 focus:outline-none`. `.sy-ta-search-input` carries both of those
**and** a bare `outline-none`, which Tailwind v4 compiles to a resting
`outline-style: none`. No vendor declaration is behind it; the arm is ours.

It buys nothing visible. A UA draws an input's outline while the element is
focused, and `:focus-visible` is a subset of `:focus`, so vendor's `focus:` arm
already reaches every state the resting one could.

The suppression **is** paired, and the replacement is vendor's own rather than
something added to compensate for the extra arm: focus lands on the input and
`.sy-ta-search-field:focus-within` paints `ring-2 ring-primary-600`
(`dark:ring-primary-500`) around the whole field, exactly as `.fi-input-wrp`'s
`focus-within:ring-2` does over there. The field keeps a visible keyboard-focus
indicator, so this is not the accessibility regression an unpaired
`outline-none` would be — in neither codebase was the indicator ever the input's
own outline.

What it does change is the *reach* of the suppression. Vendor leaves every
non-focused state alone, so an outline an application draws there — a
`:user-invalid` ring, a forced-colors affordance — lands unopposed; here it has
to beat `.sy-ta-header-toolbar .sy-ta-search-field .sy-ta-search-input`, (0,3,0).
Nothing in the toolkit draws one, so the arm is inert in both directions today:
it removes no outline anyone renders and blocks no rule anyone writes.

Recorded rather than reverted, and provisional on exactly that inertness — it is
an addition with no vendor counterpart and no reader, so it goes in the change
that first wants a resting outline on this input, with nothing to migrate.

### The visible action map is a `Table` method, and is not short-circuited

Filament builds its per-record action map **in the Blade template**: a
`$reduceVisibleRecordActions` closure clones each registered action, binds the
record, drops the hidden ones, and a preamble loop calls it per record filling
`$recordActionsByRecordKey`. Sylament's
[`getVisibleRecordActionsByRecordKey()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php)
is the same computation on the `Table`, and the partial only reads the result.

The move is the ordinary Blade → Twig translation — Twig has no `@php` block and
a closure-building preamble is not idiom — but it changes one thing on purpose.
Filament's preamble **breaks** out of the record loop the moment one record
yields a visible action, because all it needs from that loop is the boolean
`$hasRecordActionsForAnyRecord`; the rows it never reached compute their actions
lazily, inside the row loop, through the same closure. Sylament computes every
record's entry up front and returns the whole map.

That is the cheaper shape here, not the more expensive one. The row loop needs
each record's list anyway, so Filament's early break saves work only for the
records between the first visible action and the end of the page — and it pays
for the saving with a second call site and a per-record cache to keep the two in
step. Computing all of it once, in one place, is the same total work in the
common case (every record has the same actions) and one code path instead of
two. The template's own `hasRecordActions` is then a `filter(...)|length > 0`
over the finished map.

One consequence to keep: a record whose actions are *all* hidden keeps an
**empty list** in the map rather than being absent, so the template can tell
"this record has no visible actions" from "this record is not in the map" — the
first still renders its (empty) actions cell so the row keeps its column count.
The whole map is empty only when nothing was registered at all, which is the
early return at the top of the method.

### The morph key is an `id` attribute, not `wire:key`

Filament stamps `wire:key="{{ $this->getId() }}.table.records.{{ $recordKey }}"`
on every `<tr>` and `wire:key="....table.record.{{ $recordKey }}.column.{{ $column->getName() }}"`
on every `<td>`, so Livewire's morph pairs a row with the same record across a
re-render instead of matching by position. Sylament stamps the *same two strings*
as plain `id` attributes.

The value is identical down to the separators, and the
[`{{ $this->getId() }}` prefix](has-table.md#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id)
has a port of its own; only the attribute differs, because the two morph
libraries key on different things. Livewire's morphdom reads `wire:key`;
UX LiveComponent uses **idiomorph**, which pairs nodes by `id` and has no
per-framework key attribute to read. Emitting `wire:key` here would be a string
no library looks at, and idiomorph would fall back to positional matching — the
exact failure the key exists to prevent, where deleting row 1 morphs row 2's DOM
state onto row 3's record.

Permanent, and it is the same substitution any Livewire → UX template port makes.
The `id` is a real document id, so it is also addressable from CSS and tests,
which `wire:key` never was — the Integration `TableTest` reads the rows back by
`tr[id="…"]` for exactly that reason.

### The four rendered positions collapse to two

`RecordActionsPosition` has five cases, and the partial now branches on four of
them separately: `BeforeCells` and `BeforeColumns` are two distinct insertion
points around the selection cell, as are `AfterColumns` and `AfterCells`, while
`AfterContent` renders nothing at all. What still collapses is the *output*, and
only on a table with no selection — with the checkbox column absent there is
nothing between the two leading points, or between the two trailing ones, so each
pair lands the same cell in the same place.

In Filament the pairs are distinct because there is a third thing in the row.
`*Cells` sits outside the columns *and* outside the selection checkbox and the
reorder handle; `*Columns` sits between them and the data columns — so
`BeforeCells` puts the actions before the checkbox and `BeforeColumns` after it.
M4 · 1 landed the checkbox and split the branches back apart at exactly those two
points; reordering is the remaining absentee, and its handle joins the row at the
same two points when M4 · 3 lands it.

`AfterContent` renders nothing on purpose rather than by omission: it is the
columns-*layout* position, where records render as cards rather than rows and the
actions go below the card body. That layout is unported
([column grouping and layouts](#surface)), so a table asking for `AfterContent`
gets no actions column at all — matching Filament, whose row-mode markup has no
`AfterContent` arm either.

Half resolved, half still provisional: the branch split is permanent, the
coinciding output is not — it ends for any given table the moment selection is
on — and `AfterContent` gains its arm with the columns layout.

### The position setter drops Filament's `string` arm

Filament types both `recordActions()`'s `$position` and `recordActionsPosition()`
as `RecordActionsPosition | string | Closure | null`, but
`getRecordActionsPosition(): RecordActionsPosition` returns whatever
`evaluate()` produced without converting — so a `string` accepted by the setter
is a `TypeError` at the getter, every time. The `string` arm is a v3 leftover
from when positions were string constants, and nothing in Filament converts it.
Sylament types both `RecordActionsPosition|Closure|null` and rejects the string
at the call site instead.

The consequence is the reverse of the usual compatibility argument: keeping the
arm would preserve an API that cannot be used successfully, while dropping it
turns a runtime crash deep in the getter into a type error at the setter the
caller wrote. A migrating user passing `'after-columns'` gets told immediately,
at the line that is wrong. Permanent — it reverses only if Filament ever adds
the conversion the arm implies, at which point this becomes a real feature
rather than a dead union member.

### The trigger badge is composed in the table partial, because `IconButton` has no slot

Filament badges its trigger by calling `->badge($activeFiltersCount)` on the
`openFilters` **action**, which the action's own view renders inside the button.
There is no action object here — the v1 chrome is a fixed template — and
support's `IconButton` renders exactly one thing, `generateIconHtml()`, with no
slot and no children. So the count is composed one level out: a
`.sy-ta-filters-trigger` wrapper holds the icon button and, when the count is
above zero, a `.sy-ta-filters-trigger-badge-ctn` span holding an `xs` `Badge`.
A slot on `IconButton` is a possible later support enhancement; it is not built
for this.

That container is
[`fi-icon-btn-badge-ctn`](../../../vendor/filament/support/resources/css/components/icon-button.css)
ported metric for metric — `absolute z-[1] flex w-max -translate-x-1/2
-translate-y-1/2 rounded-md bg-white dark:bg-gray-900`, and the badge inside it
at Filament's `badgeSize` default of extra-small, which is what keeps a
two-digit count off the 1.25rem funnel underneath it. Only the two anchor
offsets are re-derived, and the composition is why: vendor's container is a
child of the icon button, so `start-full top-1` measures against the painted
2.25rem box, while ours is a sibling inside a wrapper that is the button's
**margin** box — `.sy-icon-btn`'s `-m-2` insets it 0.5rem on every edge — so the
same two points are `start-full` + `ms-2` and `-top-1`. The wrapper also carries
`pointer-events-none`, which vendor needs no equivalent of: a click on a badge
nested *inside* the button is a click on the button already.

### The trigger opens its chrome from the client, and the count stays a template chip

`getFiltersTriggerAction()`, `getFiltersApplyAction()` and
`getFiltersRemoveAllAction()` are Filament's three, each with its
`filters*Action(?Closure)` seam taking the single `action` injection and falling
back to the untouched action. Three things differ.

The trigger wires **no click of its own** — `liveComponentClickHandlerEnabled(false)`,
vendor's `livewireClickHandlerEnabled(false)` — because the surrounding chrome
owns the opening: support's `Dropdown` for the dropdown layout, and for the modal
layout an `command="show-modal"` / `commandfor` pair the builder stamps on
through `extraAttributes()`. Vendor reaches the same place through Alpine, and
carries `modalSubmitAction(false)` plus an `extraModalFooterActions()` pair
besides, because its modal *is* the action's own modal; ours is a plain
`<twig:SylamentSupport:Modal>` the template renders, so the eighteen
modal-customization settings vendor hand-copies off the trigger have nothing to
copy onto and are **not** ported. A caller wanting a different filters modal
changes the layout, not the trigger.

The **active-count badge stays a template chip**. Vendor calls
`$filtersTriggerAction->badge($activeFiltersCount)` at each render site; our
`Action::badge()` takes no value — it selects the badge *template variant* — and
the `badge($value)` family is unported, so porting it here would mean smuggling
a whole actions feature into a tables epic. The chip therefore keeps its
[existing composition](#the-trigger-badge-is-composed-in-the-table-partial-because-iconbutton-has-no-slot)
one level out from the trigger, and the actions trail keeps the row.

The icons resolve through a closure the action evaluates
(`static fn (?IconManager $icons = null) => …`) rather than an injected manager
field, because [`CanReorderRecords`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php)
already declares a `#[Required] setIconManager()` on `Table` and a second trait
declaring the same method is a fatal collision. The parameter is nullable with a
`null` default so the Invoker's container arm answers inside DI and its
default-value arm answers outside it, which keeps the fallback glyph readable
from a unit fixture.

### The collapsible panel is a `<details>` element, and the modal opens through an invoker command

Vendor's `AboveContentCollapsible` is an Alpine `areFiltersOpen` flag toggled by
a trigger rendered *below* the form, inside the same container. Ours is a native
`<details>`, the panel its content and the toggle its `<summary>` — zero
JavaScript.

The server-rendered `open` is a **seed, not the state**: it is
`activeFiltersCount > 0`, and being server-rendered is what makes it survive a
morph *the user has not toggled through*. What carries a manual toggle across
one is UX Live's `ExternalMutationTracker`, the same mechanism
[the searchable select's id handoff](../forms/select.md#the-hidden-select-lends-its-id-to-the-combobox-button)
and [Tabs' active-class writes](../schemas/tabs.md#the-active-tab-is-server-rendered-and-then-owned-by-the-controller)
already ride. The browser's own write of `open` is an attribute mutation on a
node inside the host, so it is recorded
([`live_controller.js:1197`](../../../vendor/symfony/ux-live-component/assets/dist/live_controller.js))
and replayed onto the *incoming* node before idiomorph syncs attributes off it
(`:917`, applied at `:1043`) — a user's collapse therefore outlives every later
round trip, the `deferFilters(false)` re-render fired by each field edit
included, and a re-toggle back to the seeded value cancels the record rather
than pinning it (`ChangingItemsTracker::setItem()`, `:955`). Without that replay
the seed would be a defect rather than a default: idiomorph's own
`beforeAttributeUpdated` is a no-op (`:422`), so `syncNodeFrom()` (`:546`) would
copy the server's opinion straight back over the user's.

The residue is support's
[accepted gap](../support/dropdown.md#the-open-state-is-a-controller-field-re-asserted-through-a-mutationobserver),
inherited on the same terms: an element idiomorph *replaces* rather than morphs
carries no record forward, and the `<details>` has no id forcing a match. No belt
is added here — the dropdown could afford one because its controller owned the
open state anyway, while a disclosure whose whole point is zero JavaScript would
have to grow a controller to hold it.

The summary's funnel is a **hand-built** `IconButton` with `tag="span"`, not the
trigger action. A `<button>` inside a `<summary>` becomes the event's activation
target, so the `<details>` would never toggle — the disclosure is the summary's
job and nothing interactive may sit inside it. `getFiltersTriggerAction()` is
therefore consumed by the dropdown and modal layouts only, which is also where
vendor's `$hasFiltersTrigger` puts it once the before/after layouts are out of
scope.

The `Modal` layout opens the same way — from the client, with no server round
trip — through the HTML invoker command pair `command="show-modal"` /
`commandfor`, pointing at the dialog id `getFiltersModalId()` mints under the
table host id. The dialog is support's `Modal` rendered inline in the partial;
`showModal()` puts it in the top layer, so the modal controller's open-attribute
observer finds it already `:modal` and returns, and Escape, click-away and focus
restoration are the platform's. That keeps the whole layout inside this lane's
templates with no controller of its own. Vendor's body is the **raw** filters
form rather than the `Filters` component, and ours follows it: the component
would put a second "Filters" heading and a second reset under the modal's own,
so apply and reset sit in the modal footer instead.

**`command`/`commandfor` is Baseline newly-available** — Chrome 135, Firefox 141,
Safari 26, September 2025 — and it degrades **silently**: on anything older the
trigger is an ordinary `<button>` that does nothing, with no error and no
fallback path. That is the price of a layout with no JavaScript of its own; the
alternative is one line in the table controller dispatching support's
`sylament-open-modal` window event, which is where this goes if the supported
floor ever has to drop below that.

The dialog itself is rendered **inside** the morphed tree, so its open state
rides the same `ExternalMutationTracker` replay the `<details>` above does:
`showModal()` sets the `open` content attribute, that write is recorded, and the
morph re-applies it — the dialog is never de-rendered mid-edit, which is what a
`deferFilters(false)` modal (a live request per field change) and the Reset
button (a live action fired from inside the open dialog) both depend on. It does
**not** inherit the `<details>`' replacement gap: `getFiltersModalId()` gives it a
stable `id`, and idiomorph matches on the id set first, so the dialog is always
morphed in place. Were the attribute ever lost anyway, support's modal controller
could not recover it — `#normalizeModality()` returns on `!this.element.open` and
its only other open path is the `open` prop this template never sets — which is
the half-closed state [that controller's docblock](../support/modal.md) describes.

### The filters panel teleports, because the scroll container wraps the toolbar too

The mount passes `:teleport="true"`; Filament's does not. The reason is one level
below the markup, in which element scrolls. Filament's horizontal scroll is on
`fi-ta-content-ctn` — the wrapper around the table *content*, a sibling **below**
the toolbar (`content.css:2`, `@apply relative divide-y … overflow-x-auto`) — and
its outer `fi-ta-ctn` clips only when the table has no header at all
(`container.css:4`, `&:not(.fi-ta-ctn-with-header) { @apply overflow-hidden; }`),
which a filtered table never is: `$hasHeader` is computed with `|| $hasFilters`
(`index.blade.php:124`). So no ancestor of Filament's filters panel clips it, and
its dropdown needs no escape. Ours puts `overflow-x-auto` on `.sy-ta-ctn`, the
one container holding toolbar, indicators, table and footer alike — so the panel
of a short or narrow table was clipped by the box the table itself is scrolled
in.

The fix is the remedy Filament applies to the dropdowns that *are* inside its
clipping container: `HasRecordActions` stamps every record `ActionGroup` with
`->defaultDropdownTeleport(true)`
([`HasRecordActions.php:107`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php)).
Teleport is [a positioning strategy, not a DOM move](../actions/action-group.md#dropdownteleport-is-a-positioning-strategy-not-a-dom-move-in-either-codebase)
in both codebases — the panel stays exactly where it was rendered and switches to
`position: fixed`, which no `overflow` ancestor can clip — so the open-state
machinery that survives a live morph (the `hidden` `MutationObserver` and
`panelTargetConnected`, [dropdown.md](../support/dropdown.md#the-open-state-is-a-controller-field-re-asserted-through-a-mutationobserver))
is untouched by it, and nothing about the panel's contents or its live wiring
changes.

Two things bound the claim. A fixed element is still clipped by an ancestor that
creates a containing block for it — a `transform`, `filter`, `backdrop-filter`,
`perspective`, `will-change` or `contain` — and of everything Sylament ships only
`.sy-modal-window` has one, so a filtered table rendered *inside a modal* keeps
the old behaviour. Filament inherits exactly that caveat, its teleport being the
same strategy. And the structural alternative stays open: porting
`fi-ta-content-ctn` would move the scroll off the container and remove the reason
for teleporting at all, which is the shape to reach for if the table ever grows
the content wrapper [the partial deliberately omits](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper).

### The panel is fixed chrome, with no action objects behind it

Filament's `filters.blade.php` takes an `applyAction` object and reads
`isVisible()` off it; the header reset is a `link` with `tag="button"` built
through `prepare_inherited_attributes()`, the footer reset a `button`. Ours takes
both an `applyAction` and the `applyVisible` boolean that gates the footer, and
renders the action through `renderComponent()` — Filament's shape, once the
[three chrome actions](#the-trigger-opens-its-chrome-from-the-client-and-the-count-stays-a-template-chip)
landed. The two resets stay hand-built support `Link`/`Button`s wired with
`stimulus_action('live', 'action', …)`, exactly as vendor's are;
`filtersFormSchema()` — the whole-form override closure — is the one seam still
deferred. `Hidden` renders no trigger and no panel while the filtering itself
stays applied: the layout is read in the chrome branches and nowhere in the query
or indicator lanes, so a hidden filter still constrains the query and still
produces its indicators — and the
[indicators row](#the-chip-is-one-badge-and-the-delete-affordance-is-supports)
renders them, because it is gated on the indicator list alone.

Two smaller shapes belong to the same panel. `getFiltersFormSchema()` returns its
`Group`s **keyed** by filter name where Filament ends on `array_values()` — the
keys are what its evaluate call would have handed the dropped override, they
survive into `Schema::getComponents()` untouched, and they make one filter's
group a direct read rather than a scan. And the header's
`.sy-ta-filters-header-actions` wrapper is a named hook carrying no rule of its
own, matching the bare `<div>` Filament has at that spot.

### The chip is one `Badge`, and the delete affordance is support's

Each chip is a single `<twig:SylamentSupport:Badge>` carrying the indicator's
label and colour, with the remove control rendered **inside** it — Filament's
shape exactly, once support's `Badge` grew the
[delete button](../support/badge.md#the-delete-button-is-three-props-not-an-attribute-carrying-slot)
it had been missing. The one translation is where the payload rides: Blade's
`<x-slot name="deleteButton" :wire:click="…">` carries its attributes on the
slot, a Twig block carries none, so the chip passes `:deletable`, `deleteLabel`
and `:deleteAttributes` instead — and what it puts in that bag is
`stimulus_action('live', 'action', parameters: …)` built from the
[`FilterIndicator` carrier](indicator.md#removal-is-structured-not-a-click-handler-string),
where Filament interpolates a `removeLivewireClickHandler()` expression string.
The X is drawn under `SupportIconAlias::BADGE_DELETE_BUTTON`, so the chip's icon
is overridable through the same alias a Filament app would use.

The remove-all button follows Filament exactly — the `removeAllFilters` action,
an x-mark icon button under the `FILTERS_REMOVE_ALL_BUTTON` alias, rendered only
when at least one chip is removable, and the row's one icon button. Its
`.sy-ta-filter-indicators-remove-all-btn` hook rides on the action's
`extraAttributes()` rather than a template class, since `renderComponent()` takes
no attributes from its call site. The row is gated on the indicator list
being non-empty and on nothing else, so a `Hidden` filters layout still shows its
chips. Five cases in
[`TableRenderingTest`](../../../packages/tables/tests/Integration/TableRenderingTest.php)
pin it: the label and its two chips — each a badge with no badge nested inside
it, the second carrying **no** `data-live-field-param` attribute at all, which is
what [dropping the null](indicator.md#removal-is-structured-not-a-click-handler-string)
buys — a keyed chip carrying both `filterName` and `field`, the search chip on
`resetTableSearch`, a non-removable chip with neither a delete button nor a
remove-all beside it, and the remove-all appearing the moment one chip is
removable.

No chip class carries a CSS rule any more: `.sy-ta-filter-indicator` rides on the
badge as a named hook a consuming stylesheet can reach — the same shape as the
panel's `-header-actions` wrapper — and everything it used to paint (the capsule
tint, ring and end padding that made a badge-beside-a-button read as one control)
is the badge's own. `.sy-ta-filter-indicator-remove-btn` is gone with the button
it named; the delete control is support's `.sy-badge-delete-btn`.
`.sy-ta-filter-indicators-remove-all-btn` stays, still rule-less, still taking
its whole appearance from `IconButton`.

`filters.css` re-derives the vendor rules it ports
([`container.css`](../../../vendor/filament/tables/resources/css/container.css)
`:98-135` and `:187-205`) rather than copying them: the header gains `gap-x-3`,
the actions row `flex-wrap items-center`, and the dropdown panel a `20rem` base
width vendor expresses one level up, as the table's own `getFiltersFormWidth()`
(a `Width` enum defaulting to `Width::ExtraSmall`) handed to the dropdown's
`:width`. The indicators row loosens with it — `px-3 py-1.5` to `px-4 py-3`,
`gap-x-3` to a wrapping `gap-3`, `items-start` to `items-center`, and a label
without `leading-6 whitespace-nowrap` — but its tint is vendor's again
(`bg-gray-50 dark:bg-white/5`), which had moved onto the chip only to make the
composed capsule legible and had no reason to stay off the row once the capsule
went.

_See also: the global [rename table](../renames.md) — `BelongsToLivewire` →
`BelongsToLiveComponent`, `livewire()` → `liveComponent()` and `getLivewire()` →
`getLiveComponent()` are the Livewire → UX LiveComponent idiom translation
recorded there, not divergences of this component._
