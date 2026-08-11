# Table (tables)

[`Sylament\Tables\Table`](../../../packages/tables/src/Table.php) — the root
object of the tables package, the counterpart to schemas'
[`Schema`](../schemas/schema.md): a
[`ViewComponent`](../../../packages/support/src/Components/ViewComponent.php) a
host builds through [`HasTable`](has-table.md), carrying the record data source
and the empty-state copy. It ports Filament's
[`Filament\Tables\Table`](../../../vendor/filament/tables/src/Table.php) together
with the `Table\Concerns\` traits it composes — twelve today
([`BelongsToLiveComponent`](../../../packages/tables/src/Table/Concerns/BelongsToLiveComponent.php),
[`HasRecords`](../../../packages/tables/src/Table/Concerns/HasRecords.php),
[`HasQuery`](../../../packages/tables/src/Table/Concerns/HasQuery.php),
[`HasEmptyState`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php),
[`HasColumns`](../../../packages/tables/src/Table/Concerns/HasColumns.php),
[`HasActions`](../../../packages/tables/src/Table/Concerns/HasActions.php),
[`HasRecordActions`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php),
[`CanPaginateRecords`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php),
[`CanSortRecords`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php),
[`CanSearchRecords`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php),
[`HasFilters`](../../../packages/tables/src/Table/Concerns/HasFilters.php),
[`HasFilterIndicators`](../../../packages/tables/src/Table/Concerns/HasFilterIndicators.php)),
thirty-one in Filament. The twelve ported are folded into the surface table below
rather than earning rows upstairs, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing); the
nineteen still unported keep a per-file row in the package
[README](README.md#features), the way
[`actions`](../actions/README.md#features) tracks the concerns `Action` has not
reached yet — as do `HasQuery`, ported but keeping its row for the relation arm
it still owes, and the two filter concerns, keeping theirs for the form seam and
the chrome actions they do not carry yet. The rows below group those files by feature, which is what the
per-file rows upstairs point at.

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
| [`records()` / `getDataSource()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::records()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [has-table.md](has-table.md#the-records-injections-stop-at-six-of-filaments-eight) |
| [`getRecords()` — always the current page's keyed array](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecords()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) · [semantics](#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) |
| [`getPaginator(): ?Paginator`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | — (Sylament-only; Filament's `getRecords()` **is** the paginator) | done | — | [pagination.md](../support/pagination.md) · [semantics](#getrecords-unwraps-the-paginator-and-getpaginator-is-the-only-seam-to-it) |
| [`emptyStateHeading()` / `getEmptyStateHeading()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [diverges](#the-empty-state-heading-hardcodes-no-records) |
| [`emptyStateDescription()` / `getEmptyStateDescription()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | in progress | — | [detail](#the-create-action-default-description-waits-on-the-model-label) |
| [`emptyStateIcon()` / `getEmptyStateIcon()`](../../../packages/tables/src/Table/Concerns/HasEmptyState.php) | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | done | — | [diverges](#the-empty-state-icon-getter-returns-null-and-the-fallback-lives-in-the-template) |
| [`$evaluationIdentifier = 'table'` + the `$liveComponent` name injection](../../../packages/tables/src/Table.php) | [`Table::resolveDefaultClosureDependencyForEvaluationByName()`](../../../vendor/filament/tables/src/Table.php) | done | — | — |
| [`$template`](../../../packages/tables/src/Table.php) + [the partial it names](../../../packages/tables/templates/partials/Table/index.html.twig) | [`$view = 'filament-tables::index'`](../../../vendor/filament/tables/src/Table.php), [`index.blade.php`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-partial-emits-no-header-scaffolding-and-no-content-wrapper) · [diverges](#a-markup-header-label-does-not-switch-the-sortable-header-to-a-span) · [semantics](#aria-sort-is-dropped-with-null-because-false-would-render-as-a-string) |
| `emptyState()` (a whole replacement view) | [`HasEmptyState::emptyState()`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | todo | — | — |
| `emptyStateActions()` / `pushEmptyStateActions()` | [`HasEmptyState`](../../../vendor/filament/tables/src/Table/Concerns/HasEmptyState.php) | todo | — | — |
| Record labels — `modelLabel()`, `pluralModelLabel()`, `recordTitle()`, `recordTitleAttribute()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | todo | — | [diverges](#the-empty-state-heading-hardcodes-no-records) |
| [Record identity — `getRecordKey()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getRecordKey()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#getrecords-delegates-to-the-host-and-fetches-nothing) |
| [Morph-stable row and cell keys](../../../packages/tables/templates/partials/Table/index.html.twig) | [`index.blade.php`'s `wire:key` stamps](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-morph-key-is-an-id-attribute-not-wirekey) · [semantics](#the-row-and-cell-morph-keys-disagree-on-record-vs-records) |
| Pivot record identity — `hasPivotRecordKeys()`, `allowDuplicates()` | [`HasRecords`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | skipped | — | The `BelongsToMany` pivot arm — it stays unported with the [relation-scoped table surface](#the-relation-scoped-table-arm-travels-with-relation-managers) it belongs to, and a Doctrine association table carrying payload is an entity of its own rather than a pivot |
| [`columns()` / `getColumns()` / `getColumn()`](../../../packages/tables/src/Table/Concerns/HasColumns.php) | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | in progress | — | [column.md](column.md) · [detail](#columns-takes-columns-only-and-does-not-push) |
| Column grouping and layouts — `pushColumns()`, `getColumnsLayout()`, `hasColumnGroups()` | [`Table\Concerns\HasColumns`](../../../vendor/filament/tables/src/Table/Concerns/HasColumns.php) | todo | — | [detail](#columns-takes-columns-only-and-does-not-push) |
| The column manager — user-toggleable column visibility | [`HasColumnManager`](../../../vendor/filament/tables/src/Table/Concerns/HasColumnManager.php) | todo | — | — |
| [The `query()` lane — `query()` / `getQuery()` / `modifyQueryUsing()` / `applyQueryScopes()` / `getQueryEntityManager()`](../../../packages/tables/src/Table/Concerns/HasQuery.php) | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | done | — | [semantics](#every-read-clones-a-memoised-base-query) |
| [`hasQuery()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::hasQuery()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [semantics](#the-query-lane-is-on-exactly-while-no-records-closure-is-set) |
| [`getRecordClass()`](../../../packages/tables/src/Table/Concerns/HasRecords.php) | [`HasRecords::getModel()`](../../../vendor/filament/tables/src/Table/Concerns/HasRecords.php) | done | — | [rename](../renames.md#renames) · [semantics](#the-record-class-is-read-off-the-base-querys-root-entity) |
| The relation-scoped table — `relationship()`, `inverseRelationship()`, `getRelationship()`, `getRelationshipQuery()`, `getInverseRelationship()`, `getInverseRelationshipFor()`, `selectPivotDataInQuery()` | [`Table\Concerns\HasQuery`](../../../vendor/filament/tables/src/Table/Concerns/HasQuery.php) | blocked | relation managers | [detail](#the-relation-scoped-table-arm-travels-with-relation-managers) |
| [`paginated()` / `isPaginated()` / `paginationPageOptions()` / `getPaginationPageOptions()` / `defaultPaginationPageOption()` / `getDefaultPaginationPageOption()` / `extremePaginationLinks()` / `hasExtremePaginationLinks()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | done | — | [diverges](#the-ten-per-page-default-is-matched-strictly) · [diverges](#ispaginated-has-no-groups-only-arm) |
| [`paginationMode()` / `getPaginationMode()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) + [`PaginationMode`](../../../packages/tables/src/Enums/PaginationMode.php) | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php), [`Enums/PaginationMode`](../../../vendor/filament/tables/src/Enums/PaginationMode.php) | in progress | its `Cursor` arm — a keyset carrier ([pagination.md](../support/pagination.md)) | [detail](#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) · [has-table.md](has-table.md#the-page-state-has-its-query-lane-and-no-session-or-url-half) |
| `paginatedWhileReordering()`, `scrollToTopOnPageChange()` | [`Table\Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPaginateRecords.php) | todo | — | [detail](#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features) |
| [`getSortableVisibleColumn()` / `getSortColumn()` / `getSortDirection()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [diverges](#getsortablevisiblecolumn-has-no-hidden-arm-to-check) |
| [`defaultSort()` / `getDefaultSort()` / `getDefaultSortDirection()` / `defaultKeySort()` / `hasDefaultKeySort()`](../../../packages/tables/src/Table/Concerns/CanSortRecords.php) | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | done | — | [semantics](#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker) · [has-table.md](has-table.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) · [has-table.md](has-table.md#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded) |
| `defaultSortOptionLabel()` / `getDefaultSortOptionLabel()` | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | skipped | — | It labels the "unsorted" entry of the mobile sort **dropdown**, which the port does not render and nothing else reads; it belongs to that layout rather than to sorting, and lands only if the layout does |
| `persistSortInSession()` / `persistsSortInSession()` | [`Table\Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSortRecords.php) | todo | — | The session half of sorting, and the [toolkit-wide omission](has-table.md#no-table-state-is-persisted-in-the-session) recorded on the host side — it returns with that decision rather than with sorting, the way the `persistSearchInSession()` row below does |
| [`searchable()` / `isSearchable()` / `getExtraSearchableColumns()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [semantics](#a-table-is-searchable-when-a-column-says-so-and-searchable-overrides-in-both-directions) |
| [The search chrome — `searchPlaceholder()`, `searchDebounce()`, `searchOnBlur()` and their getters](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [diverges](#getsearchdebouncemilliseconds-is-a-sylament-only-translation-of-filaments-duration-string) · [diverges](#searchonblur-renders-onchange-because-ux-live-component-has-no-blur-event) |
| [`splitSearchTerms()` / `shouldSplitSearchTerms()`, `searchUsing()` / `hasSearchUsingCallback()` / `callSearchUsing()`, `applyExtraSearchConstraints()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [has-table.md](has-table.md#search-is-the-global-term-only) · [semantics](#extra-searchable-columns-take-closures-that-return-their-expression) · [diverges](#the-default-search-comparison-assumes-a-string-typed-field) · [diverges](#a-search-term-is-not-escaped-for-like) |
| [`getSearchIndicator()`](../../../packages/tables/src/Table/Concerns/CanSearchRecords.php) | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | done | — | [has-table.md](has-table.md#the-contract-is-ten-methods-of-filaments-forty-six) · [detail](#the-unported-search-surface-splits-three-ways) |
| `persistSearchInSession()`, `persistColumnSearchesInSession()`, `isSearchableByColumn()`, `hasSearch()`, `getColumnSearchIndicators()` | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | todo | — | [detail](#the-unported-search-surface-splits-three-ways) |
| `getExtraSearchableColumnRelationship()` | [`Table\Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSearchRecords.php) | skipped | — | It answers *is this dotted name's head a relationship*, so Filament's caller can switch to `whereRelation`. Here [one resolution](#a-dotted-name-is-resolved-against-the-mapping-before-it-is-refused) answers that inside `SearchConstraint::forColumn()` for every tier at once, so an extra searchable column crosses an association with nothing to ask first — there is no caller left to hold the branch this member exists to decide |
| Deferred loading and polling — `deferLoading()`, `poll()` | [`CanDeferLoading`](../../../vendor/filament/tables/src/Table/Concerns/CanDeferLoading.php), [`CanPollRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanPollRecords.php) | todo | — | [detail](#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) |
| [`filters()` / `pushFilters()` / `getFilters()` / `getFilter()` / `getFiltersFormSchema()` / `deferFilters()` / `hasDeferredFilters()` / `filtersLayout()` / `getFiltersLayout()` / `hasFiltersDropdown()` / `filtersFormColumns()` / `getFiltersFormColumns()` / `filtersFormMaxHeight()` / `getFiltersFormMaxHeight()` / `filtersFormWidth()` / `getFiltersFormWidth()` / `filtersResetActionPosition()` / `getFiltersResetActionPosition()` / `isFilterable()` / `getActiveFiltersCount()` / `isFiltered()`](../../../packages/tables/src/Table/Concerns/HasFilters.php) + [`FiltersLayout`](../../../packages/tables/src/Enums/FiltersLayout.php), [`FiltersResetActionPosition`](../../../packages/tables/src/Enums/FiltersResetActionPosition.php) | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php), [`Enums/FiltersLayout`](../../../vendor/filament/tables/src/Enums/FiltersLayout.php), [`Enums/FiltersResetActionPosition`](../../../vendor/filament/tables/src/Enums/FiltersResetActionPosition.php) | in progress | — | [detail](#an-unshipped-filters-layout-is-refused-where-the-chrome-reads-it) · [detail](#the-filters-form-columns-default-to-one-without-filaments-responsive-branch) |
| [`hiddenFilterIndicators()` / `getFilterIndicators()`](../../../packages/tables/src/Table/Concerns/HasFilterIndicators.php) | [`Table\Concerns\HasFilterIndicators`](../../../vendor/filament/tables/src/Table/Concerns/HasFilterIndicators.php) | in progress | — | [indicator.md](indicator.md#removal-is-structured-not-a-click-handler-string) |
| The filters form seam and its chrome actions — `getFiltersForm()`, `filtersFormSchema()`, `filtersTriggerAction()`, `filtersApplyAction()`, `filtersRemoveAllAction()` and their getters | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | todo | — | — |
| `persistFiltersInSession()` | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | todo | — | The session half of filtering, and the [toolkit-wide omission](has-table.md#no-table-state-is-persisted-in-the-session) recorded on the host side — it returns with that decision rather than with filters, the way the `persistSortInSession()` and `persistSearchInSession()` rows above do |
| `deselectAllRecordsWhenFiltered()` | [`Table\Concerns\HasFilters`](../../../vendor/filament/tables/src/Table/Concerns/HasFilters.php) | blocked | table bulk actions | It clears the row **selection** when a filter changes, and there is [no selection to clear](#the-flat-action-map-has-no-bulk-half-and-no-removal) |
| The query-string identifier — `queryStringIdentifier()` | [`HasQueryStringIdentifier`](../../../vendor/filament/tables/src/Table/Concerns/HasQueryStringIdentifier.php) | todo | — | — |
| [`recordActions()` / `pushRecordActions()` / `getRecordActions()` / `getFlatRecordActions()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) | [`Table\Concerns\HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | in progress | — | [detail](#record-actions-register-without-the-per-action-modifier-hook) |
| [`recordActionsPosition()` / `getRecordActionsPosition()`](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) + [`RecordActionsPosition`](../../../packages/tables/src/Enums/RecordActionsPosition.php) | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php), [`Enums/RecordActionsPosition`](../../../vendor/filament/tables/src/Enums/RecordActionsPosition.php) | in progress | — | [detail](#the-position-default-has-no-content-arm-to-choose-between-yet) · [diverges](#the-position-setter-drops-filaments-string-arm) · [diverges](#the-four-rendered-positions-collapse-to-two) |
| [`getVisibleRecordActionsByRecordKey()` — per-record clones, record-bound, hidden dropped](../../../packages/tables/src/Table/Concerns/HasRecordActions.php) | [`index.blade.php`'s `$reduceVisibleRecordActions` / `$recordActionsByRecordKey`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-visible-action-map-is-a-table-method-and-is-not-short-circuited) |
| [The flat action map — `getAction()` / `hasAction()` / `getFlatActions()` / `cacheAction()` / `mergeCachedFlatActions()`](../../../packages/tables/src/Table/Concerns/HasActions.php) | [`Table\Concerns\HasActions`](../../../vendor/filament/tables/src/Table/Concerns/HasActions.php) | in progress | — | [detail](#the-flat-action-map-has-no-bulk-half-and-no-removal) |
| `modifyUngroupedRecordActionsUsing()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | todo | — | [detail](#record-actions-register-without-the-per-action-modifier-hook) |
| Record-action presentation — `recordActionsColumnLabel()`, `recordActionsAlignment()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | todo | — | [detail](#the-actions-header-cell-is-always-the-unlabelled-arm) |
| The deprecated v3 aliases — `actions()`, `pushActions()`, `actionsPosition()`, `actionsColumnLabel()`, `actionsAlignment()`, `getActions()`, `getActionsPosition()`, `getActionsColumnLabel()`, `getActionsAlignment()` | [`HasRecordActions`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordActions.php) | skipped | — | The project has never ported a deprecated Filament surface; each is a one-line delegate to the `record*` name above |
| Header and toolbar actions, `recordAction()`, `recordUrl()` | [`HasHeaderActions`](../../../vendor/filament/tables/src/Table/Concerns/HasHeaderActions.php), [`HasToolbarActions`](../../../vendor/filament/tables/src/Table/Concerns/HasToolbarActions.php), [`HasRecordAction`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordAction.php), [`HasRecordUrl`](../../../vendor/filament/tables/src/Table/Concerns/HasRecordUrl.php) | todo | — | — |
| Bulk actions — `bulkActions()`, `groupedBulkActions()`, `selectable()`, `isRecordSelectable()` | [`HasBulkActions`](../../../vendor/filament/tables/src/Table/Concerns/HasBulkActions.php) | blocked | table bulk actions | [detail](#the-flat-action-map-has-no-bulk-half-and-no-removal) |
| Grouping and summaries — `groups()`, `defaultGroup()`, the summarizers | [`CanGroupRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanGroupRecords.php), [`CanSummarizeRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanSummarizeRecords.php) | todo | — | — |
| Reordering — `reorderable()` | [`CanReorderRecords`](../../../vendor/filament/tables/src/Table/Concerns/CanReorderRecords.php) | todo | — | — |
| Presentation — `striped()`, `stackedOnMobile()`, `content()`, `header()`, the headings, `recordClasses()` | [`CanBeStriped`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStriped.php) and five siblings | todo | — | — |
| `HasArguments`, `HasExtraAttributes`, `HasDefaultDataFormattingSettings` | [`Table.php`'s remaining composed traits](../../../vendor/filament/tables/src/Table.php) | todo | — | — |

The twenty-one **done** rows are `make()`, the host binding, the data-source
declaration, the query-lane declaration and the two members that read off it
(`hasQuery()`, `getRecordClass()`), the host-delegating `getRecords()`,
`getPaginator()` and `getRecordKey()`, the morph-stable row and cell keys built
on the last of those, the empty-state heading and icon, the closure-injection
wiring, the per-record visible-action map, the pagination surface, the
sort-resolution surface and the default-sort family beside it, the three search
rows — the declaration, the chrome, and the query half · 12 landed — and the
partial that renders all of it; the column set, the empty-state description and
the three remaining action rows are partly in, one step behind them.

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

### The relation-scoped table arm travels with relation managers

Filament's `HasQuery` is two declarations, not one: `query()` hands over a
builder, and `relationship()` hands over a closure returning an Eloquent
`Relation`, from which `getRelationshipQuery()` derives the builder instead. The
first arm is ported; the second is not, and its seven members
(`relationship()`, `inverseRelationship()`, `getRelationship()`,
`getRelationshipQuery()`, `getInverseRelationship()`,
`getInverseRelationshipFor()`, `selectPivotDataInQuery()`) are the row above.
`getQuery()` loses the second of its two arms with them: Filament falls back to
the relationship query when no builder was declared, and here a declared query is
the only source.

**This is the whole-table face of relations, and it is a different feature from
the column one.** The [relation arm](../roadmap.md#pulled-forward) shipped the
column face — a name that reads, sorts, searches and counts *through* an
association — deliberately without touching this, because `relationship()` does
not resolve a path: it makes the table's entire data source one parent record's
collection, which is a **data lane** decision. Adopting it means a third case
beside `records()` and `query()` in `hasQuery()` and in both "must have"
messages, an inverse side to write back through, and a parent record to bind —
none of which a column path needs and all of which a relation manager supplies.
So the destination is now named rather than open: **relation managers**, listed
out of scope for the panel milestone and unordered beyond it.

Two of the seven exist only to work around Eloquent's own shapes —
`getRelationshipQuery()` is mostly the `HasOneOrManyThrough`
`select($table . '.*')` patch and the `withCasts()` call on the pivot class, and
`selectPivotDataInQuery()` is pivot-column aliasing whole — and neither has a
Doctrine referent: a Doctrine association is reached by joining from the owning
entity, and an association table with payload is an entity of its own rather than
a pivot with casts. `getInverseRelationship()` is likewise Eloquent-shaped,
guessing the inverse accessor's name by singularising or pluralising the parent's
class basename, where Doctrine already carries the inverse side on the mapping
(`inversedBy` / `mappedBy`) and a guess would be strictly worse than a read — and
that read is [`AssociationReader`](../schemas/association-reader.md), shipped and
reusable, which is why nothing here waits on machinery any more, only on the
feature that needs it. The likely shape stands as the earlier draft left it: a
`QueryBuilder` with the join already applied, which `query()` accepts today,
leaving `relationship()` to add only the inverse-side knowledge a nested create
needs. Filament's `hasPivotRecordKeys()` / `allowDuplicates()` pair is already
**skipped** in the surface table on the same grounds.

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
[tenth contract method](has-table.md#the-contract-is-ten-methods-of-filaments-forty-six)
— where the null return carries what Filament's separate `hasSearch()` gate
says, so no companion method is needed.

### Loading affordances are deferred with the JavaScript that would drive them

`deferLoading()` renders the table shell immediately and fetches records on a
second request; `poll()` re-fetches on an interval. Both are **todo** rather than
blocked — nothing structural is missing, and UX LiveComponent can express each
(`data-live-action` on connect, `data-poll`).

What is missing is the affordance around them, and that is the reason to defer
rather than ship them cheaply: a deferred table shows a loading skeleton, a
polling one shows an in-flight indicator, and Filament's are Alpine-driven
(`wire:loading`, `x-show`) with a `LoadingIndicator` contract and a
`loading-indicator` view behind them — none of which is ported in support (both
are **todo** rows in its
[README](../support/README.md#components)). Shipping `poll()` without the
indicator gives a table that silently changes under the reader, which is worse
than one that does not poll. They land together, once support has a loading
affordance to render.

### The create-action default description waits on the model label

Filament's `Table::setUp()` registers a default `emptyStateDescription()` closure
that returns *"Create a `<model label>` to get started"* — but only when the
table has an action named `create`, and `null` otherwise. The action half is now
answerable: [`hasAction('create')`](#the-flat-action-map-has-no-bulk-half-and-no-removal)
reads the flat map a `recordActions()` call fills. The **model label** is still
missing, with the rest of the
[label machinery](#the-empty-state-heading-hardcodes-no-records), so there is no
string to interpolate. Sylament's `Table` therefore does not override `setUp()`
at all, and `getEmptyStateDescription()` returns `null` until a user sets one.
The default returns in the same change as the label machinery. Note what
`hasAction('create')` will read by then: the flat map is shared across every
registration lane, so a `create` action declared as a header action counts too —
that lane is [still blocked](#surface), and today only `recordActions()` fills
the map.

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
[i18n layer](#the-empty-state-heading-hardcodes-no-records) nothing in the port
has, the same reason `'No records'` is a literal. The setter and the `{% if %}`
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

### The flat action map has no bulk half and no removal

`getAction()`, `hasAction()`, `getFlatActions()`, `cacheAction()` and
`mergeCachedFlatActions()` are Filament's, minus everything that mentions a bulk
action: `getBulkAction()`, `getFlatBulkActions()`, `hasBulkAction()`, the
`$flatBulkActions` property, and the `instanceof BulkAction` branch that sorts a
cached action into one map or the other. There is no `BulkAction` class in the
port — [`action.md`](../actions/action.md) tracks it — so the branch has nothing
to test and the second map nothing to hold. The bulk half returns with that
class.

`removeCachedActions()` is likewise out: its only Filament callers are
`HasHeaderActions` and `HasToolbarActions`, which re-register a named action and
evict the one they replace — both unported, so nothing can evict anything yet.

One smaller trim is deliberate rather than incidental.
`mergeCachedFlatActions()` drops Filament's `$shouldOverwriteExistingActions`
passthrough, while `cacheAction()` keeps its own
`$shouldOverwriteExistingAction`. That looks lopsided until you find who passes
`false`: only `pushEmptyStateActions()`, whose whole point is that an empty-state
action must **not** displace a same-named record or header action already in the
map. Both members exist to serve that one caller, which is
[blocked](#surface) — so the parameter stays where a caller will reach it and
the passthrough returns with the caller that needs it.

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
ten of Filament's cases, and the epic renders two of them. `Dropdown` puts the
filters form behind the toolbar's trigger; `Hidden` renders no filter chrome at
all while the filters themselves still constrain the query, nothing in the
application path consulting the layout. The other eight —
`Modal`, `AboveContent`, `AboveContentCollapsible`, `BelowContent`,
`BeforeContent`, `BeforeContentCollapsible`, `AfterContent`,
`AfterContentCollapsible` — each need chrome the toolkit has not built: a modal
host for the form, or a content-adjacent panel with its own collapse state.

The enum ships whole rather than trimmed to two, because the case *names* are
the API a migrating user greps for and a missing case is a fatal
`Error: undefined constant` at the call site with nothing to explain it. What
the port refuses instead is the *consumption*, and it does so in
`hasFiltersDropdown()` — the method the toolbar calls to decide whether to
render the trigger and its panel — with a `LogicException` naming the case and
the two that work. `getFiltersLayout()` stays total: it evaluates the closure
arm and returns whatever was set, so a reader, a test or a future layout can ask
what the table wants without tripping the refusal.

That split is [`PaginationMode::Cursor`'s](#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features)
precedent applied a second time — refuse where the value is consumed, not where
it is set — and the reason is the same: a `Closure` layout has no value at set
time, so a setter-side check would be unenforceable for exactly the callers most
likely to need it. Each refused case lifts as its chrome lands; nothing about
the enum changes when it does.

### The filters form columns default to one, without Filament's responsive branch

Filament's `getFiltersFormColumns()` falls back to
`['sm' => 2, 'lg' => 3, 'xl' => 4, '2xl' => 5]` for the `AboveContent`,
`AboveContentCollapsible` and `BelowContent` layouts and to `1` for everything
else. The port keeps the `1` and drops the match.

The responsive arm is not a general default — it is the full-width layouts'
default, and it reads as one only because the layout is in scope. All three
layouts it names are [unported](#an-unshipped-filters-layout-is-refused-where-the-chrome-reads-it),
so the branch as written would be dead code choosing between two answers where
only one is reachable. `getFiltersFormWidth()` is trimmed on the same grounds and
in the other direction: Filament's derives a `Width` enum case from the column
count for the dropdown panel, and support ships no `Width` enum, so the getter is
the plain evaluated value with a `null` default — a width the caller states or
does not.

Both arms return with the layouts they belong to — the column match with the
above/below-content chrome, the width match if and when a `Width` enum lands in
support.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

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

The memoisation is a `??=`, so it can hold a builder and cannot hold a `null`: a
declaration evaluating to null — no `query()` at all, or a closure that returns
one — is re-evaluated on every read. That is exactly the case the value-plus-flag
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
entity manager behind the declared query needs a seam, and it is read off the
base query rather than injected, which keeps a `Table` free of container
dependencies it would otherwise carry only for this.

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

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

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
arm — and nothing else. Filament's `generate_search_column_expression()` does
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

### The empty state heading hardcodes "No records"

Filament's `getEmptyStateHeading()` falls back to
`__('filament-tables::table.empty.heading', ['model' => $this->getPluralModelLabel()])`
— a translated string interpolating the table's plural model label, which is
itself derived from the Eloquent model class when not set explicitly. Sylament
returns the literal `'No records'`.

Two prerequisites are missing, and neither is a table concern. The **label
machinery** (`modelLabel()` / `pluralModelLabel()` / `getRecordTitle()`) needs a
record-class → human-label derivation that the record vocabulary has not grown
yet, plus a pluralizer — Filament reaches `Str::plural()` behind a
`locale_has_pluralization()` guard, and the Symfony equivalent
(`symfony/string`'s inflector) is a decision of its own. **i18n** is not wired
anywhere in the port: no `translation` component is a dependency of any bundle
and no message catalogue ships, so there is nothing for a translation key to
resolve against.

The literal is therefore a placeholder that reads correctly in English rather
than an API claim, and it is the only user-visible string the tables package
hardcodes. It reverses in two steps: the label machinery makes the heading say
*"No articles"*, and the i18n pass turns it into a catalogue lookup. Until then a
consumer wanting either writes `->emptyStateHeading(...)` explicitly, which is
fully ported.

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
`aria-label` on the `<table>`; `sy-ta-header-toolbar` is the one container that
does render, and only when the table is searchable.

The wrappers are not markup for their own sake in Filament: they exist so Livewire's
DOM morph finds the same nodes before and after a toolbar's visibility flips,
which is why they render empty rather than being wrapped in an `@if`. Nothing
here can flip yet — the heading, description, toolbar actions, filters and
pagination they host all sit in **todo** or **blocked** rows above, and the
`<table>`'s `aria-label` needs the [record labels](#the-empty-state-heading-hardcodes-no-records)
that the same rows wait on. A wrapper emitted now would be a permanently empty
node with a class no stylesheet targets.

Provisional, and the reversal is mechanical: each container comes back in the
change that lands the first feature it holds, at which point its emptiness is
transient rather than permanent. The **class names** are already reserved by the
Filament originals, so nothing about the eventual DOM depends on inventing them
later.

The `ms-auto` on `.sy-ta-search-field` is a stopgap for the same thinness — the
ported `& > :nth-child(2)` rule pushes the toolbar's *second* child to its end
and today's toolbar has exactly one child, so the search field would otherwise
render start-aligned where Filament shows it at the end — and it drops when the
slot-1 controls (filters, grouping) land beside it.

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

`RecordActionsPosition` has five cases and the partial branches on two:
`BeforeCells` and `BeforeColumns` both render a **leading** `<td>`,
`AfterColumns` and `AfterCells` both render a **trailing** one, and
`AfterContent` renders neither.

In Filament the pairs are distinct because there is a third thing in the row.
`*Cells` sits outside the columns *and* outside the selection checkbox and the
reorder handle; `*Columns` sits between them and the data columns — so
`BeforeCells` puts the actions before the checkbox and `BeforeColumns` after it.
Neither selection nor reordering is ported (both are **todo** rows above), so
there is nothing between the two insertion points and the pairs coincide
exactly. Rendering them as four distinct branches today would be four branches
producing two outputs.

`AfterContent` renders nothing on purpose rather than by omission: it is the
columns-*layout* position, where records render as cards rather than rows and the
actions go below the card body. That layout is unported
([column grouping and layouts](#surface)), so a table asking for `AfterContent`
gets no actions column at all — matching Filament, whose row-mode markup has no
`AfterContent` arm either.

Provisional and mechanical: the branches split back apart in the change that
lands selection, and `AfterContent` gains its arm with the columns layout.

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

_See also: the global [rename table](../renames.md) — `BelongsToLivewire` →
`BelongsToLiveComponent`, `livewire()` → `liveComponent()` and `getLivewire()` →
`getLiveComponent()` are the Livewire → UX LiveComponent idiom translation
recorded there, not divergences of this component._
