# HasTable / InteractsWithTable (tables)

The host axis of the tables package: the trait
[`Sylament\Tables\Concerns\HasTable`](../../../packages/tables/src/Concerns/HasTable.php)
a UX Live Component mixes in to own a [`Table`](table.md), and the contract
[`Sylament\Tables\Contracts\InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php)
that names what the table may call back into. Together they port Filament's
[`Concerns\InteractsWithTable`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php)
(trait) and
[`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php)
(interface) — the labels are swapped, the same documented interface⇄trait switch
schemas and actions already carry, recorded in the
[rename table](../renames.md#interface-and-trait-names-are-swapped). ⚠️ Reading
either name across the two codebases means checking which side it is on first.

`HasTable` itself is twenty-four methods wide: `table()`, `getTable()`,
`makeTable()`, the three filtered-query seams `getFilteredTableQuery()`,
`filterTableQuery()` and `getFilteredSortedTableQuery()`, `getTableRecords()`,
the `resolveTableRecords()` half it caches and the two keying halves
`keyDataSourceRecords()` and `keyTableRecords()` — the second reading
`isFieldKeyedRecord()` to tell an array record's own key column from a list
index — `getTablePaginator()`, `getTableRecordKey()` with its
`resolveQueryRecordKey()` helper that reads an entity's identity off the mapping
metadata and its `toRecordKey()` helper that decides which scalars are printable
as one, `getTableRecord()` with its `resolveQueryTableRecord()` helper that
re-queries for a single one, `getAllTableRecordsCount()`,
`fillTableAggregateValues()`, `flushCachedTableRecords()`,
`getTableHostId()` and, since M4 · 3, the three query-string members
`getTableUrlMapping()` (the per-prop
[URL opt-in](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page)),
`getTableQueryStringIdentifier()` and `getIdentifiedTableQueryStringPropertyNameFor()`.
It builds the table once per request, resolves its data source once per
request — a `records()` closure, or
the Doctrine query those three seams assemble — and hands back an array **keyed
by record key** — or a [`Paginator`](../support/pagination.md) carrying such an
array — plus a by-key lookup that reads fresh whenever the render cache is cold
and, on the query lane, every single time, the count of everything behind that
page, the flush that empties the cache, and the per-host id every row and cell is
stamped with.

Around that core it composes ten host concerns, three of them landed by the
table upgrades epic:
[`CanPaginateRecords`](../../../packages/tables/src/Concerns/CanPaginateRecords.php)
(the page and per-page state plus the `gotoTablePage()` live action),
[`CanSortRecords`](../../../packages/tables/src/Concerns/CanSortRecords.php)
(the `column:direction` string and the `sortTable()` cycle) and
[`CanSearchRecords`](../../../packages/tables/src/Concerns/CanSearchRecords.php)
(the search term) — plus
[`HasFilters`](../../../packages/tables/src/Concerns/HasFilters.php) (the two
filter bags, the filters form and the five filter live actions), from the filter
layer,
[`HasBulkActions`](../../../packages/tables/src/Concerns/HasBulkActions.php) (the
three selection props, the two resolution seams and the `selectAllTableRecords()`
live action), from M4 · 1, and the three M4 · 3 added:
[`CanPersistTableState`](../../../packages/tables/src/Concerns/CanPersistTableState.php)
(the session key, the read hook and the five per-prop writers),
[`CanReorderRecords`](../../../packages/tables/src/Concerns/CanReorderRecords.php)
(the reorder mode flag, its toggle and the `reorderTable()` write) and
[`CanDeferLoading`](../../../packages/tables/src/Concerns/CanDeferLoading.php)
(the deferred window's flag and the `loadTable()` action that closes it), and the
two the column epics added:
[`HasColumns`](../../../packages/tables/src/Concerns/HasColumns.php) (an editable
cell's write-back, a column action's call and the request-scoped refusal bag both
render through), from M4 · 4, and
[`HasColumnManager`](../../../packages/tables/src/Concerns/HasColumnManager.php)
(the applied and draft column bags, their mount and hydrate hooks, and the apply
and reset live actions), from M4 · 5. Each is
a per-file row below rather than a folded one, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). What
Filament's nineteen-trait host does beyond them — grouping and summaries — landed
with M4 · 6 and is folded here rather than tracked as two rows upstairs, with the
features themselves in [`grouping.md`](grouping.md) and
[`summarizer.md`](summarizer.md).

The trait presumes no host property beyond the fifteen `#[LiveProp]`s it and its
concerns declare: `?string $tableHostId` (the persisted half of
[`getTableHostId()`](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id)),
`int $tablePage`, `?string $tableSort`, `array $tableColumns` (the column
manager's *applied* set, non-writable so the client reaches it only through the
manager's own action), the two mode flags
`bool $isTableReordering` and `bool $isTableLoaded` — each mutated only by its own
`#[LiveAction]`, so both stay checksummed — and the nine **writable** props the
chrome binds to: `?string $tableRecordsPerPage`, `?string $tableSearch`,
`array $tableColumnSearches` (the
[per-column terms](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept),
nested and seeded),
`array $tableDeferredColumns` (the manager's unapplied draft), the
two [filter bags](#the-filter-bags-are-arrays-never-null-and-carry-no-getters),
`array $tableFilters` and `array $tableDeferredFilters`, and the three
[selection bags](#the-selection-is-server-canonical-and-re-derived-on-every-read),
`array $selectedTableRecordKeys` (whose wire name stays Filament's
`selectedTableRecords`), `array $deselectedTableRecords` and
`bool $isTrackingDeselectedTableRecords`.
Writability is the security line and it is
[deliberate on each prop](#per-page-is-validated-in-the-getter-on-every-read):
page, sort and the two mode flags move only through a `#[LiveAction]` — and a
seed, where the prop is
[bound to the URL](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page)
or read back from the session, which is why a seeded page is
[floored and capped in the getter](#gototablepage-replaces-livewires-paginator-trio)
and a seeded sort is
[run through `sortTable()`'s own check at mount](#a-seeded-sort-is-healed-at-mount-where-filaments-is-trusted-to-the-query) —
while the four a `data-model` writes are re-validated on every read; and the
per-page and search props each dehydrate
[in the vocabulary of the element bound to it](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary)
rather than through a validating getter, which is also why
[neither bag may grow one](#the-filter-bags-are-arrays-never-null-and-carry-no-getters).
A host can still mix the trait in beside
[`HasSchemas`](../schemas/has-schemas.md) and
[`HasActions`](../actions/has-actions.md) — the property names are disjoint,
which is what keeps them composable.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Concerns/InteractsWithTable.php`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php)
  — the host trait, reduced here to its build-and-fetch core
- [`tables/src/Concerns/HasRecords.php`](../../../vendor/filament/tables/src/Concerns/HasRecords.php)
  — the host-side record resolution `getTableRecords()` ports
- [`tables/src/Concerns/CanPaginateRecords.php`](../../../vendor/filament/tables/src/Concerns/CanPaginateRecords.php)
  — the host's page and per-page state, plus the query-lane `paginateTableQuery()`
- [`tables/src/Concerns/CanSortRecords.php`](../../../vendor/filament/tables/src/Concerns/CanSortRecords.php)
  — `sortTable()`, the `column:direction` parse, and `applySortingToTableQuery()`
- [`tables/src/Concerns/CanSearchRecords.php`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php)
  — the global search term, the per-column searches, and their query application
- [`tables/src/Concerns/HasBulkActions.php`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php)
  — the host-side selection state, its two resolution seams and the key enumeration
- [`tables/src/Contracts/HasTable.php`](../../../vendor/filament/tables/src/Contracts/HasTable.php)
  — the 46-method interface this reduces to thirty-five

## Surface

Every point of this trait and its contract. _Blocked by_ names what has to land
first. _Detail_ carries up to three links: **detail** → the write-up in
[Details](#details), deleted once the row is **done**; **diverges** → the
permanent note in [Divergences](#divergences); **semantics** → the permanent
note in [Semantics](#semantics). A **skipped** row states its reason inline
instead.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`table(Table $table): Table` — the identity default a host overrides](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::table()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | — |
| [`getTable(): Table` — build once, cache](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::getTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#the-table-is-built-lazily-on-first-gettable-not-in-a-booted-hook) |
| [`makeTable(): Table`](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::makeTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#maketable-seeds-nothing-from-deprecated-host-getters) |
| [`getTableRecords()` — resolve the data source once, eager-load, re-key, fill the aggregate store, cache](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | in progress | — | [detail](#the-records-injections-match-filaments-eight) · [semantics](#a-records-closure-resolves-public-container-services-by-type) · [diverges](#gettablerecords-returns-a-keyed-array-or-a-paginator-never-a-collection) · [diverges](#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone) |
| [`getTablePaginator(): ?Paginator` — the carrier, unwrapped for the footer](../../../packages/tables/src/Concerns/HasTable.php) | — (Sylament-only; Filament's records **are** the paginator) | done | — | [pagination.md](../support/pagination.md) · [semantics](#the-paginator-is-a-return-value-not-a-state-holder) |
| [`InteractsWithTable` — thirty-five methods](../../../packages/tables/src/Contracts/InteractsWithTable.php) | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | in progress | — | [detail](#the-contract-is-thirty-five-methods-of-filaments-forty-six) |
| [`flushCachedTableRecords()`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::flushCachedTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | on the contract since M4 · 2 — actions' `callMountedAction()` calls it in a `finally` after any mounted action runs on a table host. [detail](#the-contract-is-thirty-five-methods-of-filaments-forty-six) |
| [`getTableHostId()` + the `$tableHostId` `LiveProp`](../../../packages/tables/src/Concerns/HasTable.php) | [`Livewire\Component::getId()`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) |
| [`getTableRecordKey(object\|array $record): string`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecordKey()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key) · [semantics](#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec) · [diverges](#a-query-lane-override-of-gettablerecordkey-the-codec-cannot-read-back-is-refused-where-filaments-misses-in-silence) |
| [`getTableRecord(?string $key)` with its `resolveQueryTableRecord()` query arm](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecord()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#gettablerecord-reads-fresh-when-the-render-cache-is-cold) · [diverges](#record-resolution-applies-no-limit-1-where-filaments-find-does) |
| [`getAllTableRecordsCount(): int`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getAllTableRecordsCount()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#the-all-records-count-answers-four-ways-where-filaments-answers-two) · [diverges](#an-output-walker-of-the-querys-own-is-refused-by-name-only-where-doctrines-fallback-cannot-serve-a-keyed-root) |
| [The query lane — `getFilteredTableQuery()`, `filterTableQuery()`, `applyFiltersToTableQuery()`, `getFilteredSortedTableQuery()`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [semantics](#filtertablequery-applies-the-filters-then-the-search) · [diverges](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder) |
| `getTableQueryForExport()` | [`Concerns\HasRecords::getTableQueryForExport()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | blocked | the export surface | It bypasses the seams above, re-reading `getTable()->getQuery()` and re-applying filters, search and sorting itself, to assemble a query for Filament's export action — a surface with no counterpart here and on no milestone, the same one [`ExportColumn`](column.md#hascellstate-is-relocated-into-the-column-tier) waits on. It lands with that action or not at all |
| [`CanPaginateRecords` — `$tablePage`, `$tableRecordsPerPage`, `gotoTablePage()`, `getTablePage()`, `getTableRecordsPerPage()`, `dehydrateTableRecordsPerPage()`, `updatedTableRecordsPerPage()`, `paginateTableQuery()`](../../../packages/tables/src/Concerns/CanPaginateRecords.php) | [`Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Concerns/CanPaginateRecords.php) | in progress | — | [detail](#the-page-state-has-its-query-lane-and-no-session-or-url-half) · [diverges](#gototablepage-replaces-livewires-paginator-trio) · [diverges](#per-page-is-validated-in-the-getter-on-every-read) · [diverges](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) · [diverges](#the-recordsperpage-injection-is-nullable) · [diverges](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) · [semantics](#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column) · [diverges](#an-output-walker-of-the-querys-own-is-refused-by-name-only-where-doctrines-fallback-cannot-serve-a-keyed-root) · [diverges](#a-single-scalar-identifier-fetch-joined-page-ordered-by-a-to-one-association-is-doctrines-limit-and-stays-one) |
| [`CanSortRecords` — `$tableSort`, `sortTable()`, `healSeededTableSort()`, `getTableSortColumn()`, `getTableSortDirection()`, `applySortingToTableQuery()`](../../../packages/tables/src/Concerns/CanSortRecords.php) | [`Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Concerns/CanSortRecords.php) | in progress | — | [detail](#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) · [diverges](#sorttable-ignores-a-column-the-table-did-not-declare-sortable) · [diverges](#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded) · [diverges](#the-identifier-tiebreaker-is-suppressed-only-by-the-qualified-key) · [semantics](#a-column-name-containing-a-colon-cannot-be-cycled) · [diverges](#a-seeded-sort-is-healed-at-mount-where-filaments-is-trusted-to-the-query) · [diverges](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`CanSearchRecords` — `$tableSearch`, `updatedTableSearch()`, `getTableSearch()`, `dehydrateTableSearch()`, `applySearchToTableQuery()`, `applyGlobalSearchToTableQuery()`, `extractTableSearchWords()`](../../../packages/tables/src/Concerns/CanSearchRecords.php), the last delegating to [`Query\SearchTermSplitter`](../../../packages/tables/src/Query/SearchTermSplitter.php) | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | in progress | — | [detail](#search-is-the-global-term-and-one-term-per-column) · [diverges](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) · [diverges](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) · [table.md](table.md#the-two-hidden-column-arms-landed-with-column-visibility-an-epic-after-their-loops) |
| [Table state in the URL — the `getTableUrlMapping()` opt-in hook, the six `modifyTable*Prop()` `LiveProp` modifiers, `getTableQueryStringIdentifier()` and `getIdentifiedTableQueryStringPropertyNameFor()`](../../../packages/tables/src/Concerns/HasTable.php) | [`getIdentifiedTableQueryStringPropertyNameFor()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php), the six-attribute `#[Url]` block on [`ListRecords`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) and Livewire's own [`queryStringHandlesPagination()`](../../../vendor/livewire/livewire/src/Features/SupportPagination/HandlesPagination.php) for `page` | done | — | [diverges](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) · [diverges](#the-query-string-identifier-is-live-on-the-table-and-dead-in-the-modifiers) |
| The `reordering` URL bind | [`#[Url(as: 'reordering')]` redeclared on `ListRecords`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) | skipped | — | A transient UI mode rather than browsing state, and binding it would leave a permanent `reordering=0` in every URL under [the residue constraint](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page). The `grouping` bind beside it is a different answer: it [ships](grouping.md#a-bookmarked-grouping-beats-a-selectable-default-group), because a grouping is browsing state |
| [Table state in the session — `CanPersistTableState`: the `RequestStack` setter, `getTableSessionKey()`, `persistTableStateToSession()`, `readTableSessionState()`, `forgetTableSessionState()`, the five per-prop writers and the `#[PostMount(priority: 10)]` read hook](../../../packages/tables/src/Concerns/CanPersistTableState.php) | [`Concerns\InteractsWithTable::bootedInteractsWithTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [Per-column search — the `$tableColumnSearches` bag with its `hydrateTableColumnSearches()` / `dehydrateTableColumnSearches()` pair, `mountTableColumnSearches()`, `afterTableColumnSearchesHydrated()`, `getTableColumnSearch()` / `getTableColumnSearches()`, `applyColumnSearchesToTableQuery()`, `handleTableColumnSearchUpdates()`, `getTableColumnSearchIndicators()`, `persistTableColumnSearchesInSession()` and the `resetTableColumnSearch()` / `resetTableColumnSearches()` actions](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [detail](#search-is-the-global-term-and-one-term-per-column) · [diverges](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept) · [diverges](#the-per-column-change-is-read-off-a-first-call-hydration-snapshot) · [diverges](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [Deferred loading — `$isTableLoaded`, `loadTable()`, `isTableLoaded()`](../../../packages/tables/src/Concerns/CanDeferLoading.php) | [`CanDeferLoading`](../../../vendor/filament/tables/src/Concerns/CanDeferLoading.php) | done | — | the trait's fourth member, `isTableLoadingDeferred()`, is `@deprecated Override the table() method` and is not ported — what it fronts is [`Table::deferLoading()`](table.md#the-deferred-window-is-server-gated-and-client-closed). [table.md](table.md#the-deferred-window-is-server-gated-and-client-closed) |
| Host polling — `getTablePollingInterval()` | [`CanPollRecords`](../../../vendor/filament/tables/src/Concerns/CanPollRecords.php) | skipped | — | The whole trait is `@deprecated Override the table() method to configure the table.`, one host getter returning null, and the project has never ported a deprecated Filament surface. The interval it fronts is [`Table::poll()`](table.md#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers), which ships |
| [`Concerns\HasFilters` — the `$tableFilters` / `$tableDeferredFilters` bags, `getTableFilterState()`, `getTableFilterFormState()`, `parseTableFilterName()`, `getTableFiltersForm()`, the `#[PostMount]` fill and the `#[PostHydrate(priority: 10)]` re-cache](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | in progress | — | [diverges](#the-filter-bags-are-arrays-never-null-and-carry-no-getters) · [diverges](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) · [diverges](#a-seeded-filter-bag-fills-the-form-instead-of-the-declared-defaults) · [semantics](#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it) · [diverges](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) · [semantics](#the-two-filter-bags-round-trip-differently-and-the-difference-is-the-whole-deferredlive-split) |
| [`applyTableFilters()`, `removeTableFilter()`, `removeTableFilters()`, `resetTableFiltersForm()`, `updatedTableFilters()`, `handleTableFilterUpdates()`](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | done | — | [detail](#filter-mutations-reset-the-page-and-flush-the-record-cache) · [diverges](#the-live-mode-filter-hook-is-a-per-field-afterstateupdated-not-the-props-onupdated) |
| [`resetTableSearch()`](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords::resetTableSearch()`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [detail](#filter-mutations-reset-the-page-and-flush-the-record-cache) |
| The deprecated `*TableAction` host shims — `mountTableAction()`, `callMountedTableAction()`, `getMountedTableActionRecord()`, `unmountTableAction()`, `getTableRecordAction()`, … | [`Concerns\HasActions`](../../../vendor/filament/tables/src/Concerns/HasActions.php), [`HasRecordAction`](../../../vendor/filament/tables/src/Concerns/HasRecordAction.php) | skipped | — | Every method in both files is a one-line deprecated delegate, and the project has never ported a deprecated Filament surface. What they front is built: the host resolves a table-context mount through actions' [`resolveTableAction()`](../actions/has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) and binds the record through `getTableRecord()` |
| [`HasBulkActions` — the three selection props, `isTableRecordSelected()`, `hasSelectedTableRecords()`, `getSelectedTableRecords()`, `getSelectedTableRecordsQuery()`, `getAllSelectableTableRecordKeys()`, `getAllSelectableTableRecordsCount()`, `selectAllTableRecords()`, `deselectAllTableRecords()`](../../../packages/tables/src/Concerns/HasBulkActions.php) | [`Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | in progress | — | [detail](#the-selection-is-server-canonical-and-re-derived-on-every-read) · [semantics](#a-selection-outlives-a-page-and-a-sort-and-not-a-filter-or-a-search) · [diverges](#deselectalltablerecords-clears-the-server-state-where-filaments-only-dispatches-an-event) · [diverges](#select-all-across-pages-is-a-state-writing-live-action-not-an-answered-call) |
| [Grouped selection — `getGroupedSelectableTableRecordKeys()`](../../../packages/tables/src/Concerns/HasBulkActions.php), the group checkbox | [`Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | done | — | [grouping.md](grouping.md#the-whole-universe-arm-costs-one-query-per-group-on-the-page) · [diverges](grouping.md#both-selection-lanes-key-through-getstringkey-where-filaments-records-lane-reads-the-column-raw) |
| The deprecated `*TableBulkAction*` host shims — `mountTableBulkAction()`, `callMountedTableBulkAction()`, `replaceMountedTableBulkAction()`, `getMountedTableBulkAction()`, `shouldSelectCurrentPageOnly()`, `getTableBulkActions()`, … | [`Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | skipped | — | Every one is a v4→v5 bridge delegating to the unified action API, and the project has never ported a deprecated Filament surface. What they front is built: a bulk mount is `mountAction($name, context: ['table' => true, 'bulk' => true])`, exactly what `mountTableBulkAction()` forwards to |
| [Grouping — the `$tableGrouping` prop, `groupTable()`, `mountTableGrouping()`, `getTableGrouping()`, the two string readers, `getTableRecordGroupBoundaries()` and `applyGroupingToTableQuery()`](../../../packages/tables/src/Concerns/CanGroupRecords.php) | [`Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php) | done | — | [grouping.md](grouping.md) — `updatedTableGroupColumn()` apart, a Livewire hook named for a property Filament no longer has, whose page reset is [inline in `groupTable()`](grouping.md#a-grouping-change-resets-the-page-and-flushes-the-record-cache) here |
| [Summaries — `getAllTableSummaryQuery()`, `getPageTableSummaryQuery()`, `getGroupedTableSummaryQuery()`, `getTableSummarySelectedState()` and `shouldRenderTrailingGroupedTableSummary()`](../../../packages/tables/src/Concerns/CanSummarizeRecords.php) | [`Concerns\CanSummarizeRecords`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | in progress | — | [summarizer.md](summarizer.md) — the four query members are on [the contract](#the-contract-is-thirty-five-methods-of-filaments-forty-six) because the partial reads each one; the probe stays off it, since only the boundary map calls it. `$modifyQueryUsing` and the batched `GROUP BY` it served are [not ported](summarizer.md#the-per-group-figure-is-one-scoped-query-per-group-not-one-group-by) |
| [Reordering — `$isTableReordering` / `toggleTableReordering()` / `isTableReordering()` / `reorderTable()`](../../../packages/tables/src/Concerns/CanReorderRecords.php) | [`Concerns\CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | done | — | [semantics](#reordertable-is-gated-by-reorderable-never-by-the-reorder-mode) · [semantics](#a-refused-key-leaves-a-gap-in-the-numbering-and-a-smuggled-one-does-not) · [diverges](#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update) · [table.md](table.md#the-drag-control-mounts-per-mode-where-vendors-instance-outlives-every-render) |
| `makeTableReorderColumnExpression()` — the mass `CASE` expression | [`Concerns\CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | skipped | — | [diverges](#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update) |
| `reorderTable()`'s `BelongsToMany` pivot-column arm | [`Concerns\CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | skipped | — | It reorders a position column that lives on the **pivot** rather than on the record, keyed by the pivot class under `allowsDuplicates()`. The pivot surface is skipped whole on Doctrine grounds — an association table carrying payload is an entity of its own, so there is no pivot query to reorder and no pivot class to key by ([table.md](table.md#surface), [renames.md](../renames.md#renames)) — and a join table with a position column is reordered here as the entity it is |
| The deprecated host reorder shims — `isTablePaginationEnabledWhileReordering()`, `getTableReorderColumn()` | [`Concerns\CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) | skipped | — | Both are documented "Override the `table()` method" bridges, and the project has never ported a deprecated Filament surface; what they front is [`Table\Concerns\CanPaginateRecords::isPaginatedWhileReordering()`](../../../packages/tables/src/Table/Concerns/CanPaginateRecords.php) and [`Table\Concerns\CanReorderRecords::getReorderColumn()`](../../../packages/tables/src/Table/Concerns/CanReorderRecords.php) |
| The deprecated host presentation shims — `isTableStriped()`, `getTableEmptyState()`, `getTableEmptyStateActions()`, `getTableEmptyStateDescription()`, `getTableEmptyStateHeading()`, `getTableEmptyStateIcon()` | [`Concerns\CanBeStriped`](../../../vendor/filament/tables/src/Concerns/CanBeStriped.php), [`Concerns\HasEmptyState`](../../../vendor/filament/tables/src/Concerns/HasEmptyState.php) | skipped | — | Both files carry `@deprecated Override the \`table()\` method` on the trait *and* on every member, and the project has never ported a deprecated Filament surface. What they front ships on the table object — `striped()` and the whole empty-state family, [both **done** there](table.md#surface) |
| [The column manager — the two column bags, `mountTableColumnManager()`, `afterTableColumnManagerHydrated()`, `applyTableColumnManager()`, `resetTableColumnManager()`, `getDefaultTableColumnState()`, `isTableColumnToggledHidden()`, `getTableColumnManagerItems()`](../../../packages/tables/src/Concerns/HasColumnManager.php) | [`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php) | in progress | — | [column-manager.md](column-manager.md) · [detail](#the-column-state-is-re-derived-against-the-definition-on-every-read) |
| `HasContent`, `HasHeader` | [`Concerns\HasContent`](../../../vendor/filament/tables/src/Concerns/HasContent.php), [`Concerns\HasHeader`](../../../vendor/filament/tables/src/Concerns/HasHeader.php) | todo | — | — |
| Translatable content driver — `getActiveTableLocale()`, `makeFilamentTranslatableContentDriver()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | todo | — | [has-schemas.md](../schemas/has-schemas.md#the-translatable-content-driver-is-not-ported) |

Nineteen rows are **done**. Ten are the trait's own core — the build-once pair with
`makeTable()`, the record key, the by-key lookup with its flush, the all-records
count, the three filtered-query seams, the host id, and the paginator peek — and
nine arrived with the state layer since: the filter mutations and `resetTableSearch()`
from the filter epic, M4 · 6's [grouping](grouping.md) and the
[grouped selection](grouping.md#the-whole-universe-arm-costs-one-query-per-group-on-the-page)
beside it — whose own summaries half stays **in progress**, against
[`Count::icons()`](summarizer.md#counticons-ships-as-api-and-refuses-to-summarize) —
and M4 · 3's
[URL binding](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page),
[session persistence](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it),
[deferred loading](table.md#the-deferred-window-is-server-gated-and-client-closed)
and [reordering](#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update),
and M4 · 5's
[per-column search](#search-is-the-global-term-and-one-term-per-column) — the
column manager beside it being the one member of that epic still **in progress**,
against [`ColumnGroup`](column-manager.md#the-columngroup-half-is-the-whole-of-what-is-left).
Those four carry Integration suites of their own —
[`TableUrlBindingTest`](../../../packages/tables/tests/Integration/TableUrlBindingTest.php),
[`TableSessionPersistenceTest`](../../../packages/tables/tests/Integration/TableSessionPersistenceTest.php),
[`TableDeferredLoadingTest`](../../../packages/tables/tests/Integration/TableDeferredLoadingTest.php)
and [`TableReorderingTest`](../../../packages/tables/tests/Integration/TableReorderingTest.php),
beside the Unit
[`CanReorderRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanReorderRecordsTest.php)
that pins the write itself — because each is a `#[PostMount]` or a `#[LiveAction]`
whose behaviour only exists once a component is really mounted. The reorder half
needed one rig change to be testable at all: the
[`Song`](../../../packages/tables/tests/Fixtures/Doctrine/Entity/Song.php) fixture
gained a **nullable** `int $position` field with its accessor pair, so the drag
write has a mapped column to assign onto and a fresh row starts unnumbered — every
other fixture entity is unchanged, and no existing case reads the field. The core
ten are
covered by
[`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php)
via the
[`TableHost`](../../../packages/tables/tests/Fixtures/Concerns/TableHost.php)
fixture, which takes the host's `table()` body as a constructor closure: the
cache is proved by identity (`getTable()` returns the same instance) *and* by a
configuration counter that must read exactly `1`, so a second build would fail
even if it happened to produce an equal object. `getTableRecords()` is covered on
every path in the same file — the fetch-once counter, both data-source
`LogicException`s, the object arm keyed by `id`, the array and `Traversable` arms
with their `__key` synthesis (from the source key, and an explicit entry winning
over it), the string cast, and the last-writer-wins collision. `getTableRecordKey()`'s
three refusals sit beside them: an object whose `id` is null, one with no `id`
property at all — the `AccessException` arm — and, called directly because
resolution always synthesises one, an array with no key entry. Its **return** is
pinned by calling it directly rather than reading a map key, which PHP would
coerce back to an `int`: an integer `id` and a `Stringable` one both come back as
the `string` the contract promises.

The query lane is covered in the same file, against the in-memory SQLite
[`EntityManagerFactory`](../../../packages/tables/tests/Fixtures/Doctrine/EntityManagerFactory.php)
rig rather than a mocked builder, so every case runs through Doctrine's own
hydration. Resolution is pinned end to end — two seeded `Song`s coming back as
entities under their identifiers, and a `modifyQueryUsing()` scope narrowing the
same query to one — and so is the precedence between the lanes: a table declaring
`records()` *and* `query()` resolves the closure, keys its entities by the `id`
convention, and never evaluates the query at all, which a counter on the query
closure proves. A second case reads the same both-configured table through the
seams instead, where `getFilteredTableQuery()` and
`getFilteredSortedTableQuery()` must both answer null — the assertion that keeps
the [lane gate](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder)
from drifting back to "is there a builder to be had". The metadata keying has
eight cases of its own, none of them reachable by that convention: a `Book`,
whose identifier is `code` and which has
no `id` anywhere, keying by `sy-001`; a `Shortcut`, whose identifier is two
strings, keying three rows that between them carry a dot, a slash, a space, the
separator itself and non-ASCII — `plain~about` beside
`www=2Eexample=2Ecom~=2Fblog=2F2026=2Fall=20the=20way=20down` and
`example=2Ecom~=2Fclanky=2F=C5=BDofia=7EN=C3=A1vratov=C3=A1`; a `Residency`,
whose identifier is a `Venue` association plus a season, keying
`main-hall~2026-spring` — the association component flattened to the target's
own key through the target's own DBAL type; the two composite refusals, a
`Shortcut` whose second component is still blank and a `Residency` whose
association half was never set; an anonymous subclass of `Shortcut` keying
`example=2Ecom~double` by the **table's** record class rather than its own —
the one pin on the class `RecordKey::of()` takes as its third argument, since an
anonymous class has no mapping to read; and
the two identifier refusals, one blank and one never initialised. The fallback is
pinned from the other side, an anonymous object carrying a public `$id` handed to
`getTableRecordKey()` on a query-lane table and coming back `'7'` — the case that
fails the moment the metadata read stops asking whether the record is one of the
table's own entities. Both keying cases assert array *keys*, and they disagree on
type for a reason: `Book`'s survive as strings while `Song`'s `'1'` and `'2'` come
back as `int`s, the same PHP coercion the closure lane's `id` cases already
record.

`getTableRecord()` is pinned on both cache arms by an evaluation counter, because
the difference between them is invisible in the returned record: the **cold**
case asserts one evaluation for the lookup and then a *second* on the following
`getTableRecords()`, which is what proves the lookup left the cache empty rather
than filling it; the **warm** case reads after a render and asserts the counter
never moves. Its two null arms — a `null` key and an unknown one — are asserted
in one case. `flushCachedTableRecords()` is covered by the same counter across a
read-flush-read cycle, which reaches `2` only if the flush actually emptied the
cache.

Its **query** arm is pinned by what a cached page cannot answer. The
fresh-resolution case renders a page, deletes the second `Song` through a DQL
`DELETE` and clears the identity map, then asserts `getTableRecord('2')` is null
while the still-warm page keeps both keys — the one shape in which "reads the
cache" and "re-queries" differ visibly. Three more reach past the page the render
built: a record on page 3 of a `paginated([2])` table, a record the active search
term excludes, and a fetch-joined `Artist` coming back with **all three** of its
songs — the case that fails the moment a `setMaxResults(1)` joins the resolution
query, which is
[the limit this lane does not apply](#record-resolution-applies-no-limit-1-where-filaments-find-does).
The scope flag is read through a `modifyQueryUsing()` closure recording its
`isResolvingRecord` argument across a render and then a lookup, asserting exactly
`[false, true]`. Five narrower answers sit beside them: an unknown key coming
back null; a bare table naming its three lanes; a `Shortcut` and a `Residency` each
resolving by their own key through the per-field narrowing, which is a different
method from the keying above and is reached without it; five keys that are
malformed for the class (`a~b~c`, `plain` and `=2` failing the arity — `=2` is
one component, so it never reaches the escape check — `plain~=61` escaping a
safe byte, `plain~=2f` spelling its hex lowercase) answering null with **no
statement run at all**, next to a canonical
`nope~nope` that costs exactly one and finds nothing — which is what separates
"refused before the bind" from "queried and missed"; and the single-identifier
lane held to the same rule, where `abc`, `05`, ` 5`, `5abc`, a `7` trailed by a
newline and `1~2~3` all answer null before any bind. Three of them coerced to an
integer on the way into the old untyped bind: `05` and ` 5` resolved song 5
before M7 · 1, and the newline-trailed `7` coerced just as readily — it missed
only because the rig seeds no song 7.

`getAllTableRecordsCount()` has one case carrying all four arms. Seven seeded
songs are counted through the query with nothing rendered; the same table with
`alpha` in the search box counts **two**, which is what proves the count reads
the [filtered](#filtertablequery-applies-the-filters-then-the-search) query rather
than the declared one; a records-lane array counts its own length; a records-lane
carrier of two records declaring a total of `23` answers `23`, which is what
tells the carrier arm apart from a plain `count()`; and a null-total carrier
throws. Two more cases cover the arms those four cannot reach, both on a
**rendered** query-lane table. One pins the order: seven songs paged at two, a
DQL `DELETE` behind the render, and the count answers `7` from the carrier the
page was built with — then `6`, and only after `flushCachedTableRecords()`, which
is what proves the known total is preferred to a second `COUNT` rather than
merely agreeing with one. The other is the same table in `Simple` mode, whose
carrier carries no total at all: the count falls through to the query and still
answers `7`, where reading the cached total without the null check would hand a
`null` back through an `int` return.

`getTableHostId()` is pinned from the render side rather than here, in two
places. The Integration
[`TableTest`](../../../packages/tables/tests/Integration/TableTest.php) reads the
id off the host and asserts the `<tr>` and `<td>` the partial emitted carry
exactly that prefix. The Integration
[`HasTableTest`](../../../packages/tables/tests/Integration/Concerns/HasTableTest.php)
covers the half that prefix is *for*: that the id in `data-live-props-value` is
the id stamped on the rows, and that a live round trip comes back with the same
one. Both fail if the
[getter/property pairing](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id)
is broken, which nothing else would catch.

The same file carries the pin for the *other* face of that pairing — the
[wire vocabulary](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary)
of the two writable props. It renders
[`PaginatedSongsTable`](../../../packages/tables/tests/Fixtures/Twig/Components/PaginatedSongsTable.php),
a live host that paginates and searches at once, and asserts the props blob
against the two elements bound to it: `tableRecordsPerPage` is the **string**
`'10'` and equals the `value` of the `<option>` the footer marked selected, and
`tableSearch` is `''` against a search `<input>` that renders no `value`
attribute at all. A new fixture rather than a widened old one — the only other
live host, `RecordActionsTable`, is unpaginated and belongs to the action tests,
and the paginated `BrowsableSongsTable` is a plain Twig component that emits no
props blob. **A functional test could not have caught this**: UX's
`TestLiveComponent::call()` posts `args` and nothing else, and the only method
that sends an `updated` payload at all is `set()`, which sends the one prop the
test names. The client echoing a dirty model *of its own accord* — the round trip
the mismatch actually breaks — therefore has no expression in a PHP test, in this
suite or in the demo's. Asserting the dehydrated blob against the rendered
element is the whole regression pin.

The three browsing concerns are covered in the same Unit
[`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php).
Sorting is driven through `sortTable()` rather than by writing `$tableSort`: the
three-step cycle on one column (asc → desc → unsorted), a second column starting
ascending, an explicit direction winning, the numeric-column string cast, and one
case asserting that **all four** refusals leave the state untouched — an
undeclared column, an unknown one, a null one, and an invalid direction. The
parse is read the other way round, by setting `$tableSort` directly to a bare
column and to `title:bogus`, which is the only way to reach the two arms
`sortTable()` cannot produce. Pagination covers the seeded default (the getter
answering `10` while the prop stays `null`, which is what proves it never
writes), the unpaginated `null`, and the four normalisation arms — a valid
option, `'all'`, an out-of-range number, and outright garbage, the last two
falling back to the table default rather than to the forged value. Search covers
the trim (with an NBSP), the blank-as-null read, and the all-whitespace case.
One case ties the three together: page 4 is set, then each of sort, per-page and
search is changed in turn, and the page must be back at `1` every time — each
change a *real* one, since an echoed value is now
[a deliberate no-op](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary).

The [wire bridges](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary)
are pinned in the same file, called the way the dehydration pass calls them —
with the getter's value, since that is what `PropertyAccessor` hands over.
`dehydrateTableRecordsPerPage()` answers the **string** `'10'` for the seeded
default, `'all'` for the selected all-option, and `null` for a table whose page
options are empty; `dehydrateTableSearch()` answers `''` where its getter answers
null, and the untrimmed `'  ok computer '` where its getter answers
`'ok computer'` — the two assertions that fail the moment either bridge starts
mirroring its getter again. Both `onUpdated` guards have a case apiece, each
carrying both arms against a fetch counter: an echoed value leaves the page at 4
**and** the record cache warm, and the next real change resets to page 1 and
flushes it.

Two cases pin the halves that only exist together. The **injection** case
declares a `records()` closure taking all six names, mutates sort, page and
search first, and asserts the exact array the closure saw — so a renamed
injection or a stale read fails on the value, not on an arity error. The
**Paginator** case returns one from the closure and asserts the re-keyed clone
comes back from `getTableRecords()`, `getTablePaginator()` and
`getTable()->getPaginator()` alike, with `getTableRecord('1')` reaching into it —
which is what proves the carrier is unwrapped for lookups rather than special-cased
at each call site. A closure returning an `int` is asserted to name `Paginator` in
its `LogicException`, so the widened return type cannot silently accept garbage.

The **query half** of sorting is the one browsing concern with a file of its own,
[`CanSortRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanSortRecordsTest.php)
— the state half above stays with the rest of the host state, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). Every case
drives the public lane against three seeded songs whose title order, play order
and identifier order all disagree, so an ordering that is silently ignored cannot
pass: each asserts the composed `ORDER BY` off
`getFilteredSortedTableQuery()->getDQL()` **and** the record order the database
answered with. They are the user sort with its tiebreaker; the default sort
alone, in the direction it declared; a mixed-case `'DESC'` arriving lowercased,
which is the only transformation the declaration side performs; the default
appended under a user sort; everything after the user's column taking the
*declared* direction rather than the clicked one; the same-column default applied
exactly once (a count of occurrences, since the failure being caught is a
duplicate rather than an absence); the raw-path arm with no matching column; a
default-sort closure ordering the query it was handed; another replacing it with
a builder that also filters, asserted by the two records left, and that same
replacement under a user sort, where the clicked ordering goes with the builder
it was applied to; a third replacing it with a builder over a **different
entity**, where the tiebreaker must read `book.code` — `Book`'s identifier is not
named `id`, so a tiebreaker taken from the table's own record class would compose
`book.id` and the case asserts the assembled DQL *parses* as well as reading
right; the closure arms of both setters, one naming a column and one switching
the tiebreaker off; both tiebreaker suppressions, the qualified key and the
joined `artist.id` one; `defaultKeySort(false)` leaving no key ordering at all;
and a `query()`-authored `orderBy` staying in front of everything the table adds.
Three more pin the composite tiebreak. A sorted `Shortcut` query appends
`ORDER BY shortcut.label ASC, shortcut.host ASC, shortcut.path ASC` — every
identifier field the user's order did not already name, in mapping order, at the
sort's own direction. A query that already orders `shortcut.path DESC` gets only
`shortcut.host ASC` appended, with `shortcut.path` named exactly once, which the
case asserts by counting occurrences. And a `Residency` query appends
`ORDER BY residency.venue ASC, residency.season ASC` — the **plain association
path** rather than `IDENTITY(residency.venue)`, which is what keeps the dedupe
working by string equality against the spelling
`ColumnPathResolver::resolveOrderable()` emits — then executes, so Doctrine
emitting the foreign-key column is proven rather than asserted. Before M7 · 1 a
composite root composed no `ORDER BY` at all.

The **query half** of search has a file of its own for the same reason,
[`CanSearchRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanSearchRecordsTest.php),
driving the public lane against three seeded songs (`Love Song`, `Silent Love`,
`Noise`, with disagreeing play counts) whose titles overlap on purpose, so an
`OR` that silently became an `AND` — or a group that silently became a second
`OR` — changes which records come back. Every case asserts the records **and**
the composed `WHERE`, because SQLite matches `LIKE` case-insensitively for ASCII
and the case arms would pass on rows either way: one word matching two records
through the title column and a second word matching a third only through the
`plays` column (the `OR` across columns); two words narrowing to the one record
carrying both, and a two-word term matching nothing though each word matches
something (the `AND` across groups); `splitSearchTerms(false)` binding
`%love song%` as one phrase; a quoted `"love song"` binding the same thing while
splitting stays on (the `str_getcsv` arm); a term joined by the two Hangul
fillers splitting into two words, which is the whole point of the normalising
regex — neither character is `\s`, so without it the pair is one word matching
nothing; `searchUsing()` reaching the closure with the term, replacing the
assembly and leaving no `LIKE` in the DQL at all; the extra-column arms above; a
searchable table with nothing searchable on it composing **no** `WHERE`; an
all-whitespace term leaving the DQL identical to an unsearched one and binding no
parameters; the two case arms read as DQL and bound values (`song.title LIKE`
with `%Love%` against `LOWER(song.title) LIKE` with `%love%`); and four
parameters named `sylament_search_0`…`_3` across two words and two columns, with
`getSQL()` called on the result so the whole assembly is proved to parse.

The **query half** of pagination is the third such file,
[`CanPaginateRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanPaginateRecordsTest.php),
driving seven seeded songs through `->paginated([2, 5])` so the default option is
`2` and four pages exist. It asserts the true total and the sliced keys on page 1
*and* page 3 (reached through `gotoTablePage()`, which is also what proves the
flush); a page past the end holding no records and the total anyway; the `'all'`
option returning everything with `recordsPerPage` `'all'` and a last page of `1`;
`Simple` reporting a **null** total with `hasMorePages()` true on a full first
page and false on the short last one, which only a probe answers correctly
without a count; `Simple` plus `'all'` degenerating to a known total; `Cursor`
throwing; and `paginated(false)` handing back a plain array with
`getTablePaginator()` null. One case is the fetch join the Tools paginator exists
for: three artists with three songs each, `leftJoin('artist.songs', 'songs')`
with `addSelect()`, page size 2 — a raw `LIMIT 2` would answer two *rows* of one
artist, so the case asserts two `Artist` records, a total of `3`, and a
collection of three songs on the first of them. A keyed twin sits beside it
since M7 · 2 — a root whose identifier is an association plus a scalar,
fetch-joining a collection two hops out, the gigs of the venue that is half
its own identifier — which takes
[the two-step read](#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column)
instead and asserts the same shape on **both** of its pages: the sliced keys,
the true total, the last page and a whole collection on a record of each,
because the point of that read is that a keyed page does not differ. Because a
query-lane table is paginated by default, the sorting and search suites reach
their records through
`getRecords()` on the carrier rather than treating the return value as an array.

The `#[LiveProp]` and `#[LiveAction]` attributes carry no runtime behaviour a
render test would catch, so they are pinned by reflection in
[`HasTableLivePropsTest`](../../../packages/tables/tests/Unit/Concerns/HasTableLivePropsTest.php):
each state prop by writability, `onUpdated` hook name, `dehydrateWith` bridge
name and — since per-column search — `hydrateWith` name, and each live action by
its `#[LiveArg]`-marked parameter names. Every name a prop carries is also
asserted to **exist** as a method, because the attribute holds them as strings: renaming the method without
the attribute leaves it pointing at nothing, and UX raises that only at runtime.
For the bridge that is a `LogicException` from `dehydrateValue()`, which the
Integration render above would catch too; for the hook it is
`ensureOnUpdatedMethodExists()`, which nothing here reaches, since no test sends
an `updated` payload. Writability is a
security property — a prop that silently became writable is exactly the failure
[the validating getter](#per-page-is-validated-in-the-getter-on-every-read)
exists to survive, and this is the test that would notice.

## Details

Specifics for rows that are not finished — what is left and what the next step
is. Delete a subsection when its row flips to **done**; the row and any
divergence stay behind.

### The column state is re-derived against the definition on every read

The applied bag `$tableColumns` and the client-writable draft `$tableDeferredColumns`
are both **derived** values, never sources of truth. The definition — the columns
`table()` declared — owns identity and toggleability; the stored state owns only
the toggle, and the order too while
[`reorderableColumns()`](table.md#surface) is on. `syncTableColumnState()` runs
that merge at every entry point: the mount hook, the hydrate hook and both live
actions. So a column the definition dropped disappears from the state, a column
it added arrives at its declared place, a column that cannot be toggled is forced
on however the state reads, and a column whose stored sentinel says it was not
toggleable when it was stored is re-seeded from the definition's current default
rather than left at a stale choice.

Everything arriving from outside goes through `normalizeTableColumnState()`
first — the session, whose contents an earlier version of this table wrote, and
the writable draft, which is a client value like any other. It refuses a non-list
or an item without a string `type` and `name` **whole**, and the caller then falls
back to the definition's own state rather than to a partially-trusted one; a
non-boolean toggle reads as on. The stored shape carries a `type` at all because
Filament's manager lists column *groups* beside columns — `group` is honoured on
read so a state a later version writes still loads, and is never produced here.

The state exists for any column the manager could toggle or move, whether or not
the chrome that edits it is rendered: `hasColumnManager()` gates the trigger
alone, so `columnManager(false)` over toggleable columns still honours their
declared `toggledHiddenByDefault()`. The chrome that edits it — the manager's two
Twig components, its Stimulus controller, its stylesheet and the partial's two
branches — landed in the same epic, and is tracked with the rest of the feature
on [`column-manager.md`](column-manager.md).

The other direction of that gate is a **divergence**, and a deliberate one.
`hasTableColumnState()` asks only whether a column could be toggled or moved, so
an explicit `columnManager(true)` over columns that are neither seeds no state
and lists no rows — a manager over nothing editable is an empty panel here, where
Filament's `initTableColumnManager()` gates on `hasColumnsLayout()` alone and
would list every column with its toggle off. Widening the gate to
`hasColumnManager()` would cost every **plain** table two extra walks of
`isToggleable()` over every column, on both hooks, which is the O(N) closure cost
the [visible set](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again)
already pays twice; the chrome guards on the row count instead.

Two costs are carried rather than optimised away, both Filament-exact. The mount
gate walks **every** column's `isToggleable()` closure, since
`hasToggleableColumns()` is `array_any()` over the declared set and no cheaper
gate was substituted for it. And Filament memoises its reorderable answer once
per host (`hasReorderableTableColumns()`, a `??=` on a host property); the port
asks `Table::hasReorderableColumns()` afresh at each of its four sites, one of
them inside the per-column default-state build, so a
`reorderableColumns(fn () => …)` closure is evaluated O(N) times per request
instead of once. The memo is droppable because it has no invalidation seam of its
own and the table is built once per request; the cost is recorded here rather
than paid for with a second uninvalidated memo beside the two `setTableColumns()`
already drops.

The session **read** is ungated while the **write** is gated, unlike the three
sibling flags on [`CanPersistTableState`](#surface), which gate each read. This
is Filament's own shape — vendor's `loadTableColumnsFromSession()` is called
unconditionally and only `persistTableColumns()` consults the flag — so
`persistColumnsInSession(false)` means "stop writing", not "stop reading", and a
state stored before the flag was turned off keeps applying. Kept for parity and
pinned by a case that seeds a key for the opted-out fixture and reads it back;
gating the read is a behaviour change rather than a bug fix, and belongs with
whoever wants it.

The two hooks take priorities the rest of the package has to leave free:
`#[PostMount(priority: 4)]`, below `CanPersistTableState`'s read at 10 and above
the filters mount at 0, because 5 is
[`CanSortRecords::healSeededTableSort()`](#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)'s
and UX sorts equal priorities with a stable `arsort()`, which would resolve a tie
by reflection order over flattened traits; and `#[PostHydrate(priority: -4)]`,
which re-derives both bags before any read of either.

### The `records()` injections match Filament's eight

Filament evaluates the data source with eight named injections —
`columnSearches`, `filters`, `page`, `recordsPerPage`, `search`, `sort`,
`sortColumn`, `sortDirection` — each a closure reading the corresponding piece of
host state, so a userland `records()` closure can paginate, sort and filter its
own result set. Sylament passes **all eight**, in the same shape (a `fn ()` per
name, so the state is read only if the closure asks for it). Every one is
Filament's name, Filament's arity and Filament's value — `sort` is the same
`[$column, $direction]` pair, redundant with the two singular names and kept
because a migrating closure destructures it, and `columnSearches` is the same
flat, dotted, trimmed map `getTableColumnSearches()` hands the query lane, so
`['artist' => ['name' => ' x ']]` reaches the closure as `['artist.name' => 'x']`
exactly as it reaches Filament's.

Each arrived with the layer that gave it state, which is why the list was
incomplete for two epics: `filters` came with the filter bags, `columnSearches`
with [per-column search](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept).
Two values differ in **type** rather than in content. `filters` is typed `array`
where Filament types it `?array`: our prop is
[an array and never null](#the-filter-bags-are-arrays-never-null-and-carry-no-getters),
so a migrating `fn (?array $filters)` still matches and a `fn (array $filters)`
needs no null guard. And `recordsPerPage` is
[nullable here](#the-recordsperpage-injection-is-nullable) where Filament types
it `int|string`.

Everything the evaluation path resolves without a named injection resolves as
before: `$table` — by the parameter name, which is `Table`'s
`$evaluationIdentifier`, or by a `Table` type hint — `$liveComponent` by name
through [`Table`'s resolver override](table.md), any explicit named or typed
injection a caller passes to `evaluate()`, and a public container service
[by type](#a-records-closure-resolves-public-container-services-by-type).

### The page state has its query lane, and no session or URL half

`Concerns\CanPaginateRecords` is the page number, the per-page option, the
reset-to-page-one hook, the two getters the `records()` closure is handed, and —
since · 12 — `paginateTableQuery()`, the slice the query lane takes. Two groups
of Filament members are still not in it.

The query lane is a gate in `resolveTableRecords()`: an **unpaginated** table
resolves its whole query into the keyed array it always did, and a **paginated**
one returns [support's carrier](../support/pagination.md) instead, records keyed
the same way, so the shape a query-lane table hands back is decided by
[`Table::isPaginated()`](table.md#surface), which since record grouping landed
is also false under `groupsOnly()`. Filament
calls `$query->paginate()` / `simplePaginate()` / `cursorPaginate()` on an
Eloquent builder and returns Laravel's paginator. Here the page is
`setFirstResult()` / `setMaxResults()` on the builder **before** `getQuery()`,
which is what transfers both onto the `Query`, and the fetch goes through
Doctrine's own
[`Tools\Pagination\Paginator`](../../../vendor/doctrine/orm/src/Tools/Pagination/Paginator.php)
— imported as `DoctrinePaginator`, support's carrier having the same short name.
Wrapping it is why the slice is not a bare `LIMIT`: on a fetch-joined query a
limit over the *joined rows* would hand back half a record's collection, where
the Tools paginator limits a subquery over identifiers first and re-reads the
whole rows. **Which joins earn that treatment narrowed with · 20.** Until then
the test was `getDQLPart('join') !== []` — any join at all, deliberately broader
than needed on the grounds that an extra subquery is cheaper than silently wrong
rows. Then the relation arm started joining *for* the user, and "broader than
needed" stopped being free: a to-one join added because someone clicked a
sortable header would have flipped every such table into id-subquery mode for a
join that multiplies no rows. So the heuristic became cardinality-aware —
[`JoinCardinality::joinsCollection()`](../../../packages/tables/src/Query/JoinCardinality.php)
walks each join expression back to its class through the root entities and the
joins before it, and answers true only for a **collection-valued** association.
The original trade survives where it still applies: anything the mapping cannot
classify — an arbitrary-class join, a join off an alias this builder never
declared, a join on a property that is no association — counts as collection
fetching, because there the two directions still cost differently, an extra
subquery against silently truncated collections.

A **third** strategy sits beside those two, and the join heuristic is only half
of what picks it. Two shapes of identifier defeat Doctrine's paginator on that
path, for two different reasons. A record class keyed by **two or more fields**
has no single identifier column at all, and `getSingleIdentifierFieldName()`
answers with a `MappingException` from inside a getter mid-render. A class
keyed by a **single association** does have one, and Doctrine's walkers do
serve it — right up to the identifier tiebreak's `ORDER BY <root>.<association>`,
which the limit walker cannot re-emit on any platform outside its `ROW_NUMBER`
branch. Since M7 · 2 either root, on a query that joins a collection, is paged
by
[a two-step read tables runs itself](#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column);
every other table, single-**scalar**-`id` roots included, still goes through
the Tools paginator exactly as before. The routing predicate reads metadata
alone and never the platform, so both shapes take the two-step read on
PostgreSQL too, where the second would have worked either way. One private
method makes that choice — `readTableRecordWindow()`, which
[the trailing-group look-ahead](summarizer.md#the-trailing-group-is-followed-past-the-page-break-with-a-record-not-a-scalar)
calls for its window of one, so the page and the probe cannot disagree about
the same query.

The three [modes](table.md#the-pagination-mode-ships-two-cases-and-two-setters-wait-on-their-features)
differ in what they pay for the total, and the mode is read here, where it is
consumed, rather than in the setter, because a `Closure` mode has no value at set
time. `Default` reads `DoctrinePaginator::count()` — a `COUNT` subquery that
ignores the first/max already set — so the carrier carries a true total and a
page past the end comes back **empty with that total intact** rather than clamped
to the last page, which is what leaves the footer a way back. `Simple` runs no
count at all: it asks for one record more than the page holds, drops the extra,
and reports `hasMoreRecords` from whether it arrived, support's carrier
[taking a null total for exactly this](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both).
`Cursor` throws, naming the keyset carrier that would answer it. The `'all'`
option bypasses the Tools paginator on every mode — a plain `getResult()`, the
total being how many records came back — which makes `Simple` **plus** `'all'`
degenerate to a *known* total, the one shape in which a simple paginator has one.
A page size is an `int` in every ordinary case and a numeric **string** in the
one the [strict ten-match](table.md#the-ten-per-page-default-is-matched-strictly)
allows, so it is cast before the offset arithmetic rather than trusted.

The **session** half (`getTablePerPageSessionKey()`, the `session()->put()` in
the updated hook) is a
[deliberate omission](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it). The
**query-string** page name (`getTablePaginationPageName()`, which feeds Livewire's
`WithPagination`) is the [URL binding](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) that
has a **todo** row of its own, and it is the one argument every Filament arm
passes that `paginateTableQuery()` has nothing to pass: no page here is named in
a query string. The two `@deprecated` host getters
(`getTableRecordsPerPageSelectOptions()`, `isTablePaginationEnabled()`) follow the
project-wide rule and are not ported at all.

### Sorting composes the user's order, the default, and the identifier tiebreaker

`applySortingToTableQuery()` is the query lane's half of the trait, and it
composes three orderings onto whatever the declared query already carries — a
user-authored `orderBy()` in `query()` stays in front of all of them. First the
user's: `getTableSortColumn()` is resolved through
[`getSortableVisibleColumn()`](table.md#the-two-hidden-column-arms-landed-with-column-visibility-an-epic-after-their-loops)
and the column applies **itself** through
[`Column::applySort()`](column.md#sort-columns-apply-in-declared-order-where-filament-reverses-them),
so a `sortable(query:)` closure or a multi-column `sortable([…])` list decides
what its own name means. Then the
[default sort](table.md#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker),
skipped when it names the column the user is already sorting by, applied through
the same column resolution when it names a declared sortable column and through
[`ColumnPathResolver`](../../../packages/tables/src/Query/ColumnPathResolver.php)
when it names a raw path instead — which is how `defaultSort('plays')` works on a
table with no `plays` column. A default-sort **closure** is handed `query` and
`direction`; ordering the builder it was given is the usual arm, and returning a
builder replaces the query outright, filters and all — **including the user's
sort**, which was applied to the builder that just got replaced, so a table whose
default sort closure returns one is ordered by that builder plus the tiebreaker
and by nothing the user clicked. Filament writes the same replacement line and
throws the result away at every call site, so the arm is live here and dead
there — [its own divergence](#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded).

Last is `defaultKeySort()`, on by default: the root alias's identifier field,
read from the mapping metadata rather than assumed to be `id`, appended in the
same direction the default sort ran in. It is what makes a page boundary stable
when the sorted column has ties. Exactly one thing suppresses it: an existing
ordering whose path **is** the qualified key. Filament's second suppression —
any ordering whose path merely *ends* in the same field name — shipped with · 12
and was [removed by · 20](#the-identifier-tiebreaker-is-suppressed-only-by-the-qualified-key),
because the relation arm made `artist.id` an ordering the table can now produce
by itself. A **composite** identifier is tiebroken by all of its fields rather
than by one.
[`IdentifierConstraint::orderBy()`](../schemas/record-key.md#the-operations-and-their-dql)
appends each identifier field the existing order does not already name, in
mapping order, at the same direction — so a two-field key adds two parts, a
derived identity orders by the association path itself (`residency.venue`, which
is the one join column it has), and a single-field key adds exactly the one part
this section describes. It still no-ops when the builder names no root entity to
read metadata from, which is now the only case where there is no key to tiebreak
by.

Both halves of that qualified key are read off the builder **actually in hand** —
its root entity, its root alias, and the `EntityManager` the metadata comes from,
never the table's memoised
[record class](table.md#the-record-class-is-read-off-the-base-querys-root-entity)
or its declared query. That is Filament's reading too
(`$query->getModel()->getQualifiedKeyName()`), and it is what a replacement
builder needs: a default-sort closure returning a builder over a different entity
would otherwise get that entity's alias carrying the *declared* entity's
identifier field — `book.id` for a `Book` keyed by `code` — which is not a
mismatch a reader would spot, because it composes cleanly and fails only when
Doctrine parses it. The table-level read stays where it belongs: keying a row is
the record class's job, ordering this builder is this builder's.

That is one rule across the lane rather than two habits. An **identity** read —
[keying a record](#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key)
and [resolving one back](#gettablerecord-reads-fresh-when-the-render-cache-is-cold)
— sources its `EntityManager` and record class off the **table's declaration**,
because a key has to mean the same thing on both sides of a round trip and the
table is the only thing that outlives the builder a single read built. An
**ordering** read — the tiebreaker here — sources both off the **builder in
hand**, because an `ORDER BY` only has to be valid for the query it is appended
to. Neither direction is a convenience: swapping them gives you row keys that
change with a scope, or a qualified key that does not parse.

Filament's method opens with two early returns and one of them now ships.
`isTableReordering()` replaces every ordering — the user's sort, the default sort
and the identifier tiebreaker alike — with the reorder column, resolved through
[`ColumnPathResolver::resolveOrderable()`](../../../packages/tables/src/Query/ColumnPathResolver.php)
rather than appended raw the way Filament's `orderBy($column)` is, because DQL
needs the alias a bare property name does not carry. The other, `isGroupsOnly()`,
skips sorting entirely — every ordering, the tiebreaker included — and landed with
[record grouping](grouping.md#the-php-gates-of-groupsonly-are-unconditional-where-the-templates-are-not),
above the reorder branch, which is Filament's own order. On the records lane none of this runs at all: the `sortColumn` /
`sortDirection` injections are the whole application there, and the userland
closure orders its own data.

`updatedTableSort()` and `updatedTableSortDirection()` collapse into the body of
`sortTable()` here, because both exist to run the two things it already does —
persist to the session (a [deliberate omission](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it))
and reset the page. Filament needs them as separate members because Livewire
calls them for a *property write*; `$tableSort` is not writable here, so nothing
else can change it. The three `@deprecated` host getters are not ported.

### Search is the global term, and one term per column

Both halves of Filament's trait are ported, and they are two features rather than
two settings: the global term is one input over every globally searchable column,
where **per-column search** is an input per column, with a UI of its own (a row
under the header) and a bag of its own. `applySearchToTableQuery()` is where they
meet — it applies the column terms first and the global term second, the order
Filament applies them in — which is what it existed as a one-line delegate to
prepare for.

The per-column half is the [seeded bag](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept)
and its [first-call hydration snapshot](#the-per-column-change-is-read-off-a-first-call-hydration-snapshot),
both recorded below; its query pass mirrors the global one word for word — the
column's own `splitIndividualSearchTerms()` overriding the table's
`splitSearchTerms()` wherever it answers non-null, an `Orx` per word,
`andWhere()`-d only if the column joined it — over exactly the **individually**
searchable columns the developer has not hidden, so a term left on a column the
developer later hid, or on one that is only globally searchable, constrains
nothing. A column the *user* toggled off still constrains, exactly as it still
renders an input. It is what the `columnSearches` injection reads.

The global half composes like this. A blank term is a no-op — `getTableSearch()`
already answers null for it, so nothing is applied and the query is returned as
it came. A table with a `searchUsing()` closure hands it the builder and the term
and stops there, columns and extra columns alike unread; that closure is the
whole assembly. `callSearchUsing()` returns `void`, Filament-exact: the closure
constrains the builder it was handed, and a *replacement* builder returned from
it is discarded — unlike
[a `defaultSort()` closure](table.md#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker),
whose returned builder wins. Otherwise the term is broken into **words** —
`splitSearchTerms()` is on by default, and `extractTableSearchWords()` is a
one-line delegate to
[`Query\SearchTermSplitter::split()`](../../../packages/tables/src/Query/SearchTermSplitter.php),
which normalises the Unicode blanks Filament names (`\x{3164}`, `\x{1160}`) to
spaces before `str_getcsv()` splits on them, so a `"quoted phrase"` survives as
one word — and each word gets **its own `Orx`**: every globally searchable column applies
[itself](column.md#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group)
into it, the table's
[extra searchable columns](table.md#extra-searchable-columns-take-closures-that-return-their-expression)
follow, and the group is `andWhere()`-d only if something joined it. So the
composition is `OR` within a word and `AND` between words — *every* word must
match *something* — and a table with nothing searchable adds no `WHERE` at all
rather than an empty parenthesis or a false predicate. The loop skips a **hidden**
column before asking whether it is globally searchable, Filament's own order — an
arm that could not be written until column visibility landed, and that arrived
[with its sorting sibling](table.md#the-two-hidden-column-arms-landed-with-column-visibility-an-epic-after-their-loops).

Two members of Filament's do not port. The `generate_search_term_expression()` /
`generate_search_column_expression()` pair is Eloquent-and-driver-specific
assembly, replaced by
[`SearchConstraint::like()`](../../../packages/tables/src/Query/SearchConstraint.php)
and its [PostgreSQL text cast](table.md#a-non-string-searchable-field-is-cast-to-text-on-postgresql).
As of M3·4, `like()` itself lives in
[`Sylament\Schemas\Query\SearchConstraint`](../schemas/search-constraint.md)
— tables' class delegates — so forms' relationship search reuses it without
depending on `sylament/tables`. The word splitter took the same shape one tier
down for the same reason: the body sat in a **host trait**, so the only way to
reach it was to be a table host, and panels' global search is not one. Hoisting
it into `Query\SearchTermSplitter` — a pure static, the tier tables' other query
helpers already live at — makes it callable from a panel resource, and leaves the
trait method in place at its own visibility so no caller and no override moved.
Of the search **indicators**, the global one ships —
`getTableSearchIndicator()` mints the `Filters\Indicator` chip for the current
term, labelled `Search: <term>` (Filament's
`filament-tables::table.fields.search.indicator` at its English value,
[hardcoded like the rest of the copy](table.md#the-copy-is-english-and-hardcoded-because-no-i18n-layer-ships)),
and is the [tenth contract method](#the-contract-is-thirty-five-methods-of-filaments-forty-six).
[`Table::getFilterIndicators()`](indicator.md#removal-is-structured-not-a-click-handler-string)
pairs it with the `resetTableSearch` live action and puts it first in
[the row](table.md#the-chip-is-one-badge-and-the-delete-affordance-is-supports),
so the chip's X clears the term and resets the page.
`getTableColumnSearchIndicators()` fills the slot beside it, second and ahead of
the filters' own chips: one `<label>: <term>` chip per **visible**, individually
searchable column holding a non-blank term, keyed by column name so
`getFilterIndicators()` can pair each with `resetTableColumnSearch` and the
`column` argument it takes. Two things differ from Filament's, both consequences
of keying rather than listing: the chips follow the bag's key order rather than
declaration order, and the label carries the **trimmed** term, since the map they
are built from is the trimmed one. On the records lane none of this runs:
the `search` injection hands the closure a string and the closure decides what
matching means.

### The contract is thirty-five methods of Filament's forty-six

`Contracts\HasTable` declares 46 methods — the full host API a Filament table,
column, filter, action, or test helper may call back into. Sylament's
`InteractsWithTable` declares the thirty-five the shipped surface actually calls:
`getTable()` (the template and the column layer need the table from the host),
`getTableRecords()` (which [`Table::getRecords()`](table.md#getrecords-delegates-to-the-host-and-fetches-nothing)
delegates to), and three that arrived with the record-action lane (M1 · 6) —
`getTableRecord()`, because a row-scoped action carries a record *key* across the
request boundary and nothing else, so the actions layer resolves the record
through the contract rather than reaching into the trait,
`getTableRecordKey()`, its inverse, which
[`Table::getRecordKey()`](table.md#getrecords-delegates-to-the-host-and-fetches-nothing)
delegates to so a template can address the row it is rendering, and
`getTableHostId()`, which the partial reads directly off the host to prefix its
row and cell ids. The table upgrades epic added the next four, every one of them
because a **template** reads it: `getTableSortColumn()` and
`getTableSortDirection()` (which `Table::getSortColumn()` / `getSortDirection()`
delegate to, so a header cell can render its own `aria-sort`),
`getTablePaginator()` (`Table::getPaginator()`'s delegate, the footer's data) and
`getTableRecordsPerPage()`, which the partial reads **off the host directly** to
mark the selected `<option>`. That last one is the reason
[`TemplateHostCallsTest`](../../../packages/tables/tests/Architecture/TemplateHostCallsTest.php)
exists: `component.getLiveComponent` is typed `InteractsWithTable`, so any method
a template reads off it that lives only on the trait renders fine for a
trait-using host and fatals in Twig for any other host satisfying the published
interface. The test scans the shipped templates for `getLiveComponent.<method>`
and fails unless the contract declares it — which is how that method was found
missing after the first pass declared eight.

The filter layer added the tenth, `getTableSearchIndicator()`, and it is the
first member no template reads. `Table::getSearchIndicator()` — what the
indicators row calls for the global-search chip — delegates to the host, and
`Table::getLiveComponent()` is typed `InteractsWithTable`, so at PHPStan level
max the delegate cannot compile against a method the interface does not declare.
It is the minimum that buys that: the return is **nullable**, which is what lets
Filament's separate `hasSearch()` gate stay off the contract — null *is* "nothing
is searched for" — and the writable `$tableSearch` prop stays off it too, since
the host mints the chip rather than exposing the term. The raw state a host holds
is never the contract's business; what a shipped consumer calls is.

The host-state layer added the eleventh, `getTableFiltersForm()`, for the same
reason the tenth arrived and one more.
[`Filters\Concerns\HasSchema::getSchema()`](base-filter.md#getschema-is-unguarded-where-the-state-readers-beside-it-are-not)
reads the form off a `getLiveComponent()` typed as this interface, and at level
max that call cannot compile until the method is declared here — which is why
the two shipped in the same change, and why the filter-side reader was held back
a commit rather than guarded. The second reason is the one that found the
ninth: the filters panel reads the form off `getLiveComponent` in a template,
which is exactly what `TemplateHostCallsTest` scans for, so a contract-only host
would fatal in Twig. That half has since shipped — the partial's dropdown branch
is the reader — and it landed without touching the interface, because the member
it needs was already declared. The two state bags stay off the contract on the
same grounds as `$tableSearch`: a consumer reads a *slice* through
`getTableFilterState()` / `getTableFilterFormState()`, never the whole bag, and
[no getter for either exists at all](#the-filter-bags-are-arrays-never-null-and-carry-no-getters).
Those two readers are on Filament's contract and are not on ours yet, because
today's only caller is `Filters\Concerns\BelongsToTable`, which reaches them
through a `method_exists()` check that names `HasTable` in its failure — a
deliberate hold, not an oversight: the check predates this trait and flipping it
to a contract call is the filters panel's change to make, alongside the rest of
the applying half.

M4 · 1's selection lane added six at once, the largest single growth so far, and
each one still names a consumer outside the trait. Three are **template** reads,
so `TemplateHostCallsTest` enforces them the way it enforced the ninth:
`isTableRecordSelected(string $key)` (each checkbox's server-rendered `checked`),
`hasSelectedTableRecords()` (the server-side `hidden` on the selection banner and
on every bulk-only trigger) and `getAllSelectableTableRecordsCount()` (the
banner's ceiling, and the Stimulus value the controller counts against). Three
are **cross-package** reads, and they are the reason the guarded soft reference
stays honest: a bulk action in `sylament/actions` resolves its records through
`getSelectedTableRecords()`, its query through `getSelectedTableRecordsQuery()`
and clears the selection through `deselectAllTableRecords()` — always after an
`instanceof Sylament\Tables\Contracts\InteractsWithTable` check against the
**published interface**, never through a `method_exists()` probe on a foreign
trait.

Two members of the same lane deliberately stayed off.
`getAllSelectableTableRecordKeys()` is called only by the `selectAllTableRecords()`
live action beside it, and that action is called only over the wire — neither has
a consumer outside the trait, and declaring members without consumers is exactly
what this rule forbids. `getAllTableRecordsCount()` stayed off too, although the
selection [now calls it](#the-selection-is-server-canonical-and-re-derived-on-every-read)
for the ceiling a total-less paginator cannot give: that call is trait-internal,
and the template reads the selection count rather than the record count.

M4 · 2 added the eighteenth, `flushCachedTableRecords()`, a **cross-package** read
of the same shape as the selection's three: actions' `callMountedAction()` calls
it in a `finally` on any host that `instanceof`s this interface, so a table never
re-renders from the page of records a mounted action's write just invalidated.
It is the first member that is already public on `HasTable` before it is
declared here — the trait has cached and flushed since the query lane — so
declaring it cost every trait-using host nothing and `BareTableHost` one more
stub. Its end-to-end pin is a case in `RecordActionsResolutionTest` over the
query-lane fixture `QueryRecordActionsTable`, whose record action reads
`getTableRecords()` before it writes — and the write **adds** a row rather than
mutating the bound one, because the identity map hands the query lane the very
instance an action mutated, so only a changed *set* of records tells a stale page
from a re-queried one; the action confirms first because a modal-less action runs
during the mount, whose render is a request of its own and therefore a fresh host.

M4 · 3 added the nineteenth and twentieth together, `isTableReordering()` and
`isTableLoaded()`, and both are the **tenth's** shape rather than the selection's:
no template reads either off `getLiveComponent`, but
[`Table::isReordering()`](table.md#surface) and
[`Table::isLoaded()`](../../../packages/tables/src/Table/Concerns/CanDeferLoading.php)
delegate to them off a `getLiveComponent()` typed as this interface, so at level
max the delegates cannot compile until the interface declares them. What the
partial reads is the two `Table` methods — once each, into a `{% set %}` the body
then branches on: reordering gates the handle column, the drag mount, the
suppressed sort headers, the emptied action sets, the dropped checkbox cells, the
selectable-count query (whose every consumer is suppressed with them) and the poll
attribute, while loaded gates the four record-touching reads. Both are on
Filament's contract too (`HasTable.php:96` and `:98`), so this is the growth order
catching up rather than a divergence — and, like the selection's three, what is
published is the **question**: the raw `$isTableReordering` / `$isTableLoaded`
props stay off, so a host answering `false` to either without composing
`CanReorderRecords` or `CanDeferLoading` renders correctly.

The contract grows a method at a time as the
features that need it land — a host must be able to satisfy it, so declaring the
pagination, filter and bulk-selection halves before those exist would force every
consumer to stub methods nothing calls. The Doctrine query lane added **none**:
every member it shipped — the three filtered-query seams, the query-lane record
resolution, `getAllTableRecordsCount()` — is called from inside the trait, from a
test, or not yet at all, and no template reads one off `getLiveComponent`. So
that lane left the count where it found it, and `getAllTableRecordsCount()` is
the clearest case of the rule at work: Filament's contract declares it, and
[ours will](#the-all-records-count-answers-four-ways-where-filaments-answers-two)
when a consumer here calls it. Growth is nearly free for implementors, because
`HasTable` supplies every member: every tables fixture that mixes the trait in,
and the demo's two implementors — `TableShowcaseCard` and the actions page's
`DispatchAnywhere` — take an addition without an edit. One fixture is
deliberately outside that, and costs a stub per growth —
`BareTableHost` implements the interface **by hand**, to prove a host can satisfy
the contract without the trait, so each new member lands there as an explicit
`null` stub. This is a **growth order**, not a
divergence: the end state is the same set of members, minus whatever the
Eloquent-only rows above turn out not to need.

The column catalog added the twenty-first, `getTablePage()`, on the first of
those grounds: `TextColumn::rowIndex()` turns a row's position on the page into
its position in the whole set, so it multiplies the page by the per-page count,
and both reads go through a `getLiveComponent()` typed as this interface. The
host already answered it — [`CanPaginateRecords`](../../../packages/tables/src/Concerns/CanPaginateRecords.php)
has carried the getter since the table-upgrades epic — so the change is the
declaration alone, which is the growth order this whole section describes: a
member joins when a shipped consumer calls it, never in anticipation.

The same epic added the twenty-second, `getTableColumnStateError()`, on those
same grounds one tier down: an editable column's partial paints the refusal a
write left against its own cell, and it asks for it through
[`CanUpdateState::getUpdateStateError()`](../../../packages/tables/src/Columns/Concerns/CanUpdateState.php),
which reaches the host through a `getLiveComponent()` typed as this interface.
The bag it reads lives on [`HasColumns`](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client)
and has no Filament counterpart at all — vendor holds that error on the client —
so this member is Sylament's own rather than one of the forty-six.

The column manager added the twenty-third, `isTableColumnToggledHidden()`, on the
tenth's grounds again: [`CanBeToggled::isToggledHidden()`](column.md#surface) asks
the host whether the user has switched a column off, through a
`getLiveComponent()` typed as this interface, and
[`Table::getVisibleColumns()`](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again)
is what calls it. What is published is again the **question** rather than the
state: `$tableColumns` and `$tableDeferredColumns` stay off the contract, so a
host that answers `false` without composing `HasColumnManager` renders every
column and is correct.

The manager's chrome added the twenty-fourth, `getTableColumnManagerItems()`,
and this one is there for a **template** rather than for a column: the partial
renders one row per item, so it reads the list off the host exactly as it reads
`getTableRecordsPerPage()`, and
[`TemplateHostCallsTest`](../../../packages/tables/tests/Architecture/TemplateHostCallsTest.php)
would fail the moment it did so without the declaration. It publishes the rows
rather than the bag they are derived from, so the two column props stay off the
contract as before.

Per-column search added the twenty-fifth and twenty-sixth together, one on each
of the two grounds. `getTableColumnSearch(string $column)` is a **template**
read — the search row binds each input's `value` to it — so
`TemplateHostCallsTest` enforces it the way it enforced the ninth.
`getTableColumnSearchIndicators()` is the tenth's shape:
[`Table::getColumnSearchIndicators()`](table.md#surface) delegates to it off a
`getLiveComponent()` typed as this interface, so at level max the delegate
cannot compile against a method the interface does not declare. What is
published is again the finished answer rather than the state — the writable
`$tableColumnSearches` bag stays off, on the same grounds `$tableSearch` does,
and so do the two reset actions, which are called only over the wire.

M4 · 6 added the last **nine**, the largest growth the contract has taken, and
every one of them on the template's grounds rather than on a delegate's — the
partial reads all nine off `getLiveComponent`, so `TemplateHostCallsTest` is
what would fail if any were dropped. [Grouping](grouping.md) brings five:
`getTableGrouping()` (the resolved `Group` the preamble branches on),
`getTableGroupingColumn()` and `getTableGroupingDirection()` (the selector's two
`selected` attributes and the direction field's `hidden`),
`getTableRecordGroupBoundaries()` (one lookup per row, in place of the trackers
Filament's Blade loop carries) and `getGroupedSelectableTableRecordKeys()` (the
group checkbox's key list, written into the markup so a tick costs no request).
[Summaries](summarizer.md) bring four: `getAllTableSummaryQuery()`,
`getPageTableSummaryQuery()` and `getGroupedTableSummaryQuery()` — the three
datasets a band row is over — and `getTableSummarySelectedState()`, the one
batched read each row hands to its summarizers.

One member of the same lane deliberately stayed off, for the reason
`getAllSelectableTableRecordKeys()` did: `shouldRenderTrailingGroupedTableSummary()`
has exactly one caller — `Concerns\CanGroupRecords::closesTrailingTableRecordGroup()`,
the boundary map's own gate — and it reaches it through a host it already holds.
The band reads the map rather than the probe. It is public on `HasTable` and absent from the
interface, which is the rule working rather than an omission —
`getTableGroupingColumn()` is the counter-example that proves the same rule, since
it was added a commit later than its four siblings, when the selector's `selected`
attribute turned out to read it.

### The selection is server-canonical, and re-derived on every read

Three writable `LiveProp`s hold the whole selection —
`$selectedTableRecordKeys` (wire name `selectedTableRecords`),
`$deselectedTableRecords` and `$isTrackingDeselectedTableRecords` — and none of
them is trusted. The `updated` bag a live request carries is **not** checksummed
(only the originals are), so a key in either list proves nothing about the record
being on the page, extant, visible under the current filters, or selectable at
all. Every read re-derives:

- `normalizedSelectedTableRecordKeys()` runs on **every** read of either bag,
  dropping anything that is not a non-bool scalar, casting the rest to `string`,
  and re-listing with `array_values()`. A nested array, a `true`, a keyed map —
  all gone before a key is compared. The props are typed `list<string>` because
  that is what they are *meant* to hold; the normalizer is what makes the type
  true of client input.
- `isTableRecordSelected()` and `hasSelectedTableRecords()` are tracking-aware:
  while tracking, the answer inverts and reads the deselection bag, and
  "something is selected" is true by definition — an inverted empty exclusion set
  means *everything*.
- `getSelectedTableRecords()` re-applies the table's filters and search to the
  query it builds, and re-applies `checkIfRecordIsSelectableUsing()` to whatever
  comes back, in both universes. The
  [filter half is conditional](table.md#one-filter-setting-drives-three-selection-behaviours);
  the selectability half never is.
- `tracksDeselectedTableRecords()` re-derives the **flag** itself, which is the
  one client value whose forgery *widens* the reach: an inverted selection with
  an empty exclusion bag means every row, so a `true` posted onto a table whose
  UI never offers tracking would resolve a bulk write to the whole filtered set —
  or, where `deselectAllRecordsWhenFiltered(false)` also turns the filter
  re-application off, to the whole unfiltered base query. The controller offers
  the inverted selection only when `canTrackDeselectedRecords()` and not
  `selectsCurrentPageOnly()`, and the reader is that same gate on the server:
  a table that cannot track at all, and a **query-lane** table that confines
  selection to its page, both read a forged flag as no tracking and fall back to
  the selected-keys bag. A `records()` table is page-only by construction and is
  deliberately left alone — its tracking arm resolves off the page collection the
  user was shown, so there is nothing to widen. Filament trusts the flag; this is
  a divergence, and it costs nothing, because every path our own chrome can take
  passes the gate.

Two universes, as everywhere else on this trait. On the **records lane** the
selection resolves off the current page collection — `array_intersect_key()` for
the keys, `array_diff_key()` for the deselections, Filament's `only`/`except`
spelled in PHP — so a key selected on page 1 does not resolve from page 2, the
same asymmetry [`getTableRecord()`](#gettablerecord-reads-fresh-when-the-render-cache-is-cold)
already has. On the **query lane** it is a fresh query, narrowed by
[`IdentifierConstraint::in()`](../schemas/record-key.md#the-operations-and-their-dql)
or, while tracking, `notIn()`, both built the way `resolveQueryTableRecord()`
builds its single-key constraint. A single-identifier class emits exactly the
`IN (:keys)` / `NOT IN (:deselected)` over the qualified identifier this lane
always emitted — now with the list **typed** by the identifier's own DBAL type —
and a composite one emits the balanced OR of per-field ANDs that DQL's missing
row-value `IN` forces. A key that is malformed for the class is **dropped**
rather than refused, so one stale entry in a selection bag cannot fail a bulk
action over the rest of it, and the keys are de-duplicated on the way. An empty
bag means opposite things on the two arms, and the port has to say so, because
Doctrine says neither: DBAL expands an empty list parameter to `NULL`, so **both**
`IN (NULL)` and `NOT IN (NULL)` match nothing. On the `IN` arm that is the right
answer — an empty selection selects no rows — and `getSelectedTableRecords()`
never reaches the query anyway, since it opens with the empty-and-not-tracking
early-out and returns `[]` without touching the database. On the `NOT IN` arm it
is the exact inversion of the truth: an empty exclusion set means **every** row,
and it is the commonest state there is, since the default select-all flips
tracking on and empties both bags. So the constraint is **omitted** rather than
emitted while the deselection bag is empty. Laravel gives Filament that for free
— its grammar compiles `whereKeyNot([])` to `1 = 1` — and a literal port loses
it: a bulk delete after select-all would delete nothing at all, silently.

`getSelectedTableRecordsQuery(false)` — the arm a bulk action takes when it means
to write without hydrating — carries one extra constraint the fetching arm does
not need. Because that arm never hydrates, the selectability callback has nowhere
to run afterwards, so it runs **first**: the query is cloned, walked, filtered
through `isRecordSelectable()`, and the surviving keys are added as a second
`IN (…)`. Filament's `constrainQueryToSelectableTableRecords()` does the same, and
without it a DQL bulk delete would happily delete rows the table refuses to let
the user select — the client is the only source of keys on that path.

The resolved set is memoised in `$cachedSelectedTableRecords` for the rest of the
request, and the two mutators (`selectAllTableRecords()`,
`deselectAllTableRecords()`) null it, so a same-request read after a clear sees
the clear. Filament's cache is never invalidated, because Filament's clear
[never touches the server state](#deselectalltablerecords-clears-the-server-state-where-filaments-only-dispatches-an-event).
The memo does **not** key on `$shouldFetchSelectedRecords`, Filament's exactly:
call it once fetching and once not in the same request and the second call
returns the first call's shape — records where keys were asked for, or the
reverse. No shipped caller does, since an action reads its selection once.

Two of Filament's moves are deliberately absent from the query. It applies
`applySortingToTableQuery()` to the selected-records query; ours does not,
because the ordering changes no membership and the non-fetch arm's builder is
handed to a bulk action that may turn it into a DQL `DELETE`, which an
`ORDER BY` would make invalid. And the `maxSelectableRecords` `limit()` is absent
with the setting itself.

`getAllSelectableTableRecordsCount()` is the banner's ceiling, and the one member
here typed to answer **null**. With no selectability callback it is free — the
page count where selection is page-only, the paginator's total otherwise. With a
callback set it is Filament's documented performance cliff: the whole filtered
set is hydrated and filtered, once per render. The one shape whose paginator
carries no total is simple-mode pagination, and there it takes Filament's last
arm and counts the filtered query, through
[`getAllTableRecordsCount()`](#the-all-records-count-answers-four-ways-where-filaments-answers-two)
— which cannot throw on that path, since everything past the page-only branch has
a query (`selectsCurrentPageOnly()` is true for every `records()` table, so the
lane that has no total to count is also the lane that never gets there). A
simple-paginated table with selection on therefore pays one `COUNT` per render,
exactly as Filament's does; the alternative was a ceiling-less banner on a table
that can perfectly well be counted.

So the `?int` is contract slack rather than a case this trait reaches: it is
typed nullable because [the contract](#the-contract-is-thirty-five-methods-of-filaments-forty-six)
lets a host satisfy it without this trait, and because the chrome has to render
*something* for a host that answers null — the banner falls back to counting the
page, and the select-all-across-pages link, which needs a ceiling, does not
appear.

Not ported, with the settings they belong to: chunked resolution
(`chunkSelectedRecords()`, `lazyById()` — no shipped action asks for it and
Doctrine's iteration story differs), the `maxSelectableRecords` enforcement in
both universes, `resolveSelectedRecordsUsing()`, and the grouped key enumeration.
Each has a [row](#surface) or a
[table-side row](table.md#the-selection-settings-ship-as-a-subset-and-the-rest-are-listed).

### A composite selection larger than one batch is read in batches

Since M7 · 7, `getSelectedTableRecords()` reads a selection over a record class keyed by several fields in batches of `IdentifierConstraint::batch()`, at most 500 keys a statement — 500 for two to four identifier fields, fewer above that ([the chunk rule](../schemas/record-key.md#the-chunk-rule)) — instead of one statement over the whole bag. The bags are client input, and one statement over them cost time quadratic in their length. A forged 10 000-key `selectedTableRecords` held a request for about 23 s, where a single-identifier table answered in about 23 ms. On PostgreSQL the tracked arm's `NOT (… OR …)` crossed the JIT threshold and ran for minutes. Three shapes follow:

- **The selected bag** is batched. Each batch runs the query `getSelectedTableRecordsQuery()` would, narrowed to its keys.
- **A tracked deselection** that fits one batch stays one `notIn()` statement. A
  larger one is subtracted in PHP from a pluck of every key the table admits
  (one statement binding no key), and the rest is read in batches, since an
  exclusion cannot be split across statements. The one-batch exclusion compares
  each deselected key decoded and bound by its field's type, so a spelling the
  codec accepts but no row mints — a decimal `1.0` for `1`, a case variant under
  a case-insensitive collation — excludes the row; the larger bag is subtracted
  by string, as the checkbox compares, and does not. Such a spelling never comes
  from a rendered checkbox, so only a forged bag sees the two arms disagree, and
  the only row it can affect is one rendered ticked, which the exclusion spares.
  The one-statement exclusion is a sequential scan on PostgreSQL 18, and from
  roughly 40 000 rows a 101–500-key one crosses `jit_above_cost`, where JIT adds
  about 130–280 ms to it ([measured](../schemas/record-key.md#the-chunk-rule)).
- **`fetchSelectedRecords(false)` beside `checkIfRecordIsSelectableUsing()`** hydrates each batch once and keys the selectable records in PHP. `getSelectedTableRecordsQuery(false)` binds them back as a second key list instead.

The hydrated records come back in table order within a batch, and batch after batch across them; Filament's own chunked read comes back in id order, not the table's. Each record comes back once: two spellings of one key that the database compares equal — a decimal `1` and `1.0`, or a case variant under a case-insensitive collation — can land in different batches and hydrate the same managed record twice, so the batches' records are de-duplicated by identity before the selectability filter, as the single statement returned such a row once. `getSelectedTableRecordsQuery()` is unchanged: it returns one builder, a custom bulk action's `$selectedRecordsQuery`, so it binds every selected key. A bulk action over a large composite selection reads `$selectedRecords` instead. A single-identifier selection is untouched: one statement, one array parameter.

### Filter mutations reset the page and flush the record cache

Every filter mutation ends in the same two lines — `tablePage = 1` and
`flushCachedTableRecords()` — because a narrowed result set makes the current
page number meaningless and the render cache stale. That is
`handleTableFilterUpdates()`, and it is the tail of `updatedTableFilters()`, of
`removeTableFilter()`, `removeTableFilters()` and `resetTableFiltersForm()` in
live mode, and of `applyTableFilters()` in every mode. Filament's tail is the
same reset plus two things that do not exist here — the session write and
`deselectAllTableRecords()` — and it omits the flush because a Livewire request
starts with a null cache; ours is one object across the whole request, so the
flush is what the page reset would otherwise lie about.

The deferred branch is the fork: `removeTableFilter()`, `removeTableFilters()`
and `resetTableFiltersForm()` call `applyTableFilters()` instead of the tail
when the table defers, which copies the draft bag over the applied one. So a
chip removal on a deferred table also **applies whatever else was pending in
the panel** — Filament's semantics exactly, and the reason removal writes
through the schema rather than the arrays: `resetTableFilterState()` resolves
the filter's `Group` through `Schema::getComponentByStatePath()` (built from
the form's own root path plus the filter name, since our lookup takes an
absolute path), walks its child schema's flat components for `Field` instances
— Filament reads the same set off a `getFlatFields()` that
[schemas has not ported](../schemas/schema.md#hascomponents-still-lacks-the-lookup-layer)
— and calls `state()` on each. Whichever bag the form's state path names
receives the write.

Per field the new value is `getResetState()[$fieldName]` when the filter names
one, and otherwise derived from the current state's type: an array resets to
`[]`, a bool to `false`, anything else to `null`. "Names one" is
`array_key_exists()` here and `$filterResetState[$fieldName] ?? …` upstream, so a
reset state that names a field **with a null value** is honoured here and falls
through to the type-derived arm there — a fix rather than a mirror, and the one
that makes `SelectFilter`'s `['value' => null]` do what it says.

The bool arm consults
[`hasNullableBooleanState()`](../forms/field.md#hasnullablebooleanstate-has-exactly-one-consumer)
first and writes `null` instead of `false` when it answers true — which
`Select`, `Radio` and `ToggleButtons` all do. This is the predicate's only
consumer in either tree, so restoring the branch is what porting it was *for*.

The arm is narrower than it looks either way, because the fallback runs only for
a field the filter's `getResetState()` does not name, and `SelectFilter` seeds
`['value' => null]` in `setUp()` — as Filament's does, at `SelectFilter.php:148`
— so every `SelectFilter` and every `TernaryFilter` takes the explicit-null left
side and clears before the `match` is reached. `Checkbox`/`Toggle` are the other
side of it: both cast through a non-nullable `BooleanStateCast`, where `false`
genuinely is the resting value, and neither overrides the predicate, so both
still reset to `false`.

What the branch actually decides is a hand-built nullable boolean inside a custom
filter schema — `Filter::make('x')->schema([Select::make('f')->boolean()])` —
which declares no reset state. That now clears to `null` rather than to `0`; the
`0` it used to write was a *false* filter to any ternary-shaped query arm, not a
cleared one, so the flattening had been a real behaviour difference and not only
a typing one. Both arms are pinned in
[`HasFiltersTest`](../../../packages/tables/tests/Unit/Concerns/HasFiltersTest.php)
— the nullable field clearing to `null`, the `Checkbox` beside it still clearing
to `false`.

A named `$field` narrows the reset to that one field only when the group
actually carries it — an unknown name resets the whole group, which is
Filament's `array_key_exists` guard and keeps a stale chip from becoming a
no-op.

`removeTableFilters()` removes a filter only when **every** one of its
indicators is removable; a filter with no indicators is vacuously removable and
is reset. It then clears the global search through `resetTableSearch()`, the
host action the search chip's X also targets — `tableSearch = ''`, page one,
flush — which is why that method lives on `CanSearchRecords` beside
`updatedTableSearch()` rather than in the filters concern that calls it.

Two signature-level deltas fall out of that pair. Filament's `removeTableFilter()`
carries a third parameter, `bool $isRemovingAllFilters = false`, which
`removeTableFilters()` passes to borrow the body without the tail; ours splits the
body into the private `resetTableFilterState()` instead, so the public action's
signature is exactly the two `#[LiveArg]`s a chip sends and no caller can ask it
to skip its own tail. And Filament's `resetTableSearch()` delegates
(`$this->tableSearch = ''; $this->updatedTableSearch();`) where ours inlines the
same three lines, because our `updatedTableSearch()` carries a `$previousValue`
no-op guard Filament's hook does not — delegating would work only by the accident
that the defaulted `null` never equals `''`. Behaviour is identical today; the
cost is that a host overriding `updatedTableSearch()` does not see its override
run from the reset path.

`resetTableFiltersForm()` fills the form with **defaults**, not reset state:
`Schema::fill()` with no argument runs `hydrateState(applyDefaults: true)`, so
a `Filter::make('featured')->default()` comes back checked where a chip removal
would have unchecked it. The two are deliberately different operations. Every
other field clears to null, including one whose filter declares no `default()` —
a `SelectFilter`'s `value` is the live case, and the browser pass that found it
standing after a reset is what
[restored the defaults-fill null-write](../schemas/component.md#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default)
in the schemas state layer.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### `filterTableQuery()` applies the filters, then the search

The name is Filament's and it is now accurate: the body is
`applySearchToTableQuery($this->applyFiltersToTableQuery($query))` — filters
first, then [the global search](#search-is-the-global-term-and-one-term-per-column), Filament's
order. `applyFiltersToTableQuery()` runs two passes over the one builder, every
visible filter's `applyToBaseQuery()` before every visible filter's `apply()`,
and returns the same builder both times. The composition around it is Filament's
as well: `getFilteredTableQuery()` reads
[`Table::getQuery()`](table.md#every-read-clones-a-memoised-base-query) — a
scoped clone, so what comes back is always a builder the caller may mutate —
[gated on the lane](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder)
and null when there is no query to read, hands it to `filterTableQuery()`, and
`getFilteredSortedTableQuery()` adds
[the sorting](#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)
and nothing else.

Record resolution takes the same filters and not the rest:
`resolveQueryTableRecord()` runs `applyFiltersToTableQuery($query, isResolvingRecord: true)`
before narrowing on the identifier, so a row action cannot reach a record
outside the filtered set — and the `isResolvingRecord` flag skips the *apply*
pass of any filter that called `excludeWhenResolvingRecord()`, never the base
pass, which is where a filter's joins live. Search, sorting and pagination stay
unapplied there per the epic-12 decision.

One thing Filament wraps around both passes has no port and needs none. On a
deferring table its `applyFiltersToTableQuery()` re-roots the filters form at
`tableFilters`, flushes the schema's cached absolute state paths and child
schemas, and puts the path back to `tableDeferredFilters` in a `finally`, so
anything resolving state *through the form* during the pass reads the applied bag
rather than the draft. Nothing here resolves it that way:
[`getTableFilterState()`](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk)
names the applied bag directly, whatever the form happens to be bound to, so
there is no path to flip and no cache to invalidate — the seam is the two loops
and nothing else.

Both of Filament's remaining applications have since filled these seams, each a
body inside a seam rather than a new call site: **per-column** search slotted into
`applySearchToTableQuery()` ahead of the global half (M4 · 5), and grouping wraps
the sorted seam from outside (M4 · 6) —
`applySortingToTableQuery($this->applyGroupingToTableQuery($query))`, so the group
order is appended first and every sort after it
[breaks ties inside a group](grouping.md#the-group-order-is-appended-first-so-every-later-sort-breaks-ties-inside-a-group).
That is why the seams were written public and whole before the layers that fill
them: anything needing the lane could ask for it without waiting for its own
half.

Filament runs two more inside this seam that this port deliberately runs
elsewhere. Its column loop calls `applyRelationshipAggregates()` and
`applyEagerLoading()` here, so both land on the filtered query — which is the
same clone the **count** takes. Here the aggregate value never touches a query
clone at all ([why](table.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)),
and eager loading runs one step later, on the
[page clone alone](#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone),
so a `COUNT` never carries joins added purely to fill cells.

### A `records()` closure resolves public container services by type

Closure evaluation reaches the container here, which is not obvious from the
trait: `$table->evaluate($dataSource, [...])` passes seven named injections and
nothing else, yet a `records()` closure type-hinting a repository is handed one.
`SylamentTablesBundle::loadExtension()` registers `Table` as an autowired,
autoconfigured service, so `Table::newInstance()` resolves through
`ComponentResolver`'s locator, the `#[Required] setInvoker()` setter fires, and
the table evaluates through the container's `Invoker` instead of the fallback one
built around an empty container.
[`SylamentTablesBundleTest`](../../../packages/tables/tests/Integration/SylamentTablesBundleTest.php)
pins both halves: the table's `Invoker` is the container's, and a closure
type-hinting a fixture repository receives the container's shared instance.

One boundary comes with it, and it is a support-layer rule rather than a tables
one: the `Invoker` holds `service_container`, and `Container::has()` on a compiled
container answers for **public** ids only — so a **private** service, which is
Symfony's default and what most of Sylament's own services are, still does not
resolve by type. That is the same "only types the container `has()` resolve"
constraint
[component.md](../support/component.md#closure-resolution-lives-in-a-service)
records, and it is why the fixture repository that test injects is
`#[Autoconfigure(public: true)]`.

### The paginator is a return value, not a state holder

`getTablePaginator()` looks like it owns paging state and does not: it calls
`getTableRecords()` and returns the result only when it happens to be a
[`Paginator`](../support/pagination.md), null otherwise. On the **records** lane
the carrier is whatever the userland closure returned — the host neither builds
one nor slices anything — so a table is "paginated" for rendering purposes
exactly when its closure chose to return one, regardless of what
`Table::isPaginated()` says. `isPaginated()` governs the *inputs* (whether
`recordsPerPage` is injected at all); the footer renders off the *output*. A
closure that ignores its injections and returns a plain array on a paginated
table therefore renders with no footer, correctly: there are no counts to render.

On the **query** lane the two answers agree, because there the host does the
slicing: [`paginateTableQuery()`](#the-page-state-has-its-query-lane-and-no-session-or-url-half)
builds a carrier exactly when `isPaginated()` is true and the lane returns a
plain keyed array when it is not, so a query-lane footer *is* `isPaginated()`.
This method is unchanged by that — it still answers off whatever came back, never
asking which lane produced it — which is why one footer condition serves both.

The one place this shape matters is caching. Both methods read the same
`$cachedTableRecords`, so asking for the paginator does not fetch a second time,
and a template reading `component.getPaginator` before the row loop costs
nothing.

### A column name containing a colon cannot be cycled

`$tableSort` encodes the pair as `"{$column}:{$direction}"` and
`getTableSortColumn()` splits on the first colon, so a column literally named
`a:b` round-trips as column `a`, direction `b` — and `sortTable('a:b')` writes a
string whose column half no longer matches the name it was given, which means the
same-column branch never fires and the column can never cycle past ascending.

This is Filament's encoding, kept verbatim, and the edge is Filament's too
(`str($this->tableSort)->before(':')`). It is recorded rather than fixed because
the fix costs more than the edge: a column name is a record property path, where
a colon is not legal PHP and not a Doctrine field name, so the case is reachable
only through an array record with a hand-written key. Escaping the separator
would change the wire format for every table to guard a name nobody can write in
the common case. If it ever needs fixing, both halves — the writer in
`sortTable()` and the two readers — move together.

### `updatedTableFilters()` fires for a whole-prop write, never for a field inside it

`$tableFilters` carries `onUpdated: 'updatedTableFilters'`, and it is easy to
read that as *"any client write under this prop calls the hook"*. It does not,
and the mechanism is worth stating because the live-mode page reset turns on it.

`LiveComponentHydrator::processOnUpdatedHook()` normalises a **string** hook to
`[LiveProp::IDENTITY => $method]`, then walks the map. The `IDENTITY` branch
`continue`s unless `$dehydratedUpdatedProps->hasPropValue('tableFilters')`, and
`DehydratedProps::hasPropValue()` is `array_key_exists($propName, $this->propValues)`
— an exact key lookup, no prefix matching. A client write to a field inside the
bag arrives under its own key, `tableFilters.status.value`, which is not
`tableFilters`, so the identity hook does not fire. The array form of
`onUpdated` is what covers nested paths, and it takes **fixed** path strings —
which dynamic filter names, keyed by whatever the user called the filter, cannot
supply.

So the hook covers exactly the direct whole-prop writes: a test helper's
`set('tableFilters', [...])`, and the applying half's own PHP-side assignment,
which is inside our code and calls `handleTableFilterUpdates()` outright rather
than relying on a hydration hook to notice. Live-mode per-field resets ride the
schema's `afterStateUpdated` walk instead — the `-10` `PostHydrate` in
[`HasSchemas`](../schemas/has-schemas.md), which is also why this trait rebuilds
its cache-only form at priority `10`.

### The two filter bags round-trip differently, and the difference is the whole deferred/live split

Deferred is the default, and in that mode the filters schema is rooted at
`tableDeferredFilters` while the query reads `tableFilters`. A field write
therefore lands in the draft bag and changes nothing: the rows, the page and the
indicator chips all still describe the applied bag, and `applyTableFilters()` is
the single copy that promotes draft to applied and runs
`handleTableFilterUpdates()` — page to 1, record cache flushed. There is no
"unapplied changes" affordance, matching Filament: the trigger badge and the
chips count applied indicators, and the apply button is simply always visible
while deferring. Filament's deferred branch also calls
`Schema::partiallyRender()`, which stamps a `wire:partial` on the rendered schema
so Livewire can re-render that subtree alone; there is no port and nothing to
port it to — a ux-live-component request re-renders the whole component and
idiomorph diffs the result — so the branch here is the `statePath()` and nothing
more.

Live mode roots the schema at `tableFilters` itself with a root `->live()`, so a
field write *is* the mutation. Which mechanism carries the page reset and the
flush depends on the write's shape, and the panel only ever sends one of them: a
**nested** write at the field's own model fires no `onUpdated` hook at all (the
identity hook is an exact key match — see the note above), so it reaches
`handleTableFilterUpdates()` through the field's `afterStateUpdated` instead,
which is why this trait rebuilds its cache-only form at `#[PostHydrate(priority:
10)]` — above the `-10` walk that fires those hooks. A **whole-prop** write is
the shape `updatedTableFilters()` exists for, and no rendered element produces
it.

The observable contract is the same at the end of either path, which is why one
suite covers all three: `FiltersLiveTest` drives the deferred path through the
panel's field models and the apply action, the live path through the panel's
field models after paging off page 1 — so the reset is observable, and a broken
link anywhere in that chain renders the empty state — and the hook through the
one whole-prop write it keeps for that purpose.

Three consequences of removal being a **schema-state** mutation rather than an
array edit are pinned there too. A chip's X resets the field its indicator was
keyed to and leaves the sibling field of the same filter standing, because
`removeTableFilter()` narrows the group's `Field` walk to that one name. In
deferred mode a chip removal also applies any pending draft edits, because its
tail *is* `applyTableFilters()` — Filament's semantics, inherited deliberately.
`resetTableFiltersForm()` re-fills from the field **defaults**, not from
`getResetState()`, so a filter carrying `default()` comes back set rather than
empty.

Record resolution runs the same filters with `isResolvingRecord: true`, so a row
action cannot reach a record outside the filtered set; a filter that opts out
with `excludeWhenResolvingRecord()` is skipped in that pass only, and both arms
are pinned by mounting the same action against the same filtered-out key.

One shape is worth stating because nothing rejects it: a **whole-prop** write to
either bag replaces it outright, and a filter whose key the replacement omits is
handed an empty slice — which
[`isActive`'s opt-out polarity](base-filter.md#isactive-is-an-opt-out-not-an-opt-in)
reads as *active*, while the indicator gate's opposite polarity shows no chip for
it. The panel never produces that shape, because each field writes at its own
`<bag>.<filter>.<field>` model and the `#[PostMount]` fill seeded every key
before it; custom chrome that assigns the bag wholesale has to seed them itself.
`FiltersLiveTest` drives the field models in both modes for exactly this reason,
and keeps a single whole-prop write, on the live host, purely as the
`updatedTableFilters()` pin.

The mirror image is a **nested** write straight at
`tableFilters.<filter>.<field>` while deferring — equally unrejected, and
equally not a shape the shipped chrome emits, since the panel binds the deferred
bag there — which reaches the applied bag having fired nothing at all: the
identity `onUpdated` wants the exact `tableFilters` key, deferred mode attaches
no `afterStateUpdated` to its fields, and the `-10` walk is watching a form
rooted at `tableDeferredFilters`. So the page never resets, the draft bag is
never mirrored back from the write, and the next `applyTableFilters()` overwrites
it with the stale draft.

### A selection outlives a page and a sort, and not a filter or a search

Which state changes clear the selection is not derivable from the code that
clears it, because the clearing lives in two `updated*` tails rather than
anywhere a reader would look. The rule, ported from Filament verbatim: a
**filter** change and a **search** change deselect everything (when
`deselectAllRecordsWhenFiltered()` is on, the default); a **sort**, a **page**
change and a **per-page** change do not. The hook points are
`handleTableFilterUpdates()` — already the single tail of all five filter
mutations — and `updatedTableSearch()`, in both cases immediately before the
existing page reset.

A third site is Sylament's own: `resetTableSearch()`, the clear button's
`#[LiveAction]`, deselects on the same terms. Filament has no such action — its
search input is a live model write, so clearing it goes through
`updatedTableSearch()` like any other edit — and without the splice the port
would have two channels to one state change disagreeing about the selection.

Nothing is deliberately absent from that list: `sortTable()`,
`gotoTablePage()` and `updatedTableRecordsPerPage()` reset the page and flush the
record cache without touching the three props, so a selection made on page 1 is
still a selection on page 3. That is the whole reason the query lane resolves by
key rather than from the page collection.

Two consequences worth stating. A selection can outlive the page that showed it —
including onto an overflow page with no rows at all, since a page past the end
[renders empty with the total intact](#the-page-state-has-its-query-lane-and-no-session-or-url-half)
— so the chrome that clears it has to stay reachable from an empty page. And on
the records lane a surviving cross-page selection resolves to **nothing**,
because that lane resolves off the current page collection; the keys are still
held, still round-trip, and still count in the banner, but
`getSelectedTableRecords()` returns only those on the page in front of you.

### `reorderTable()` is gated by `reorderable()`, never by the reorder mode

The live action's only guard is `$table->isReorderable()` — it does **not** ask
`isTableReordering()`, so a client that posts an order without ever flipping the
mode still writes. That reads like a missing check and is Filament-exact: vendor's
`reorderTable()` opens with the same single `if (! $this->getTable()->isReorderable())`
and nothing else. The reason it is safe is that the mode flag is chrome, not
authority — `reorderable()` is where a table says reordering is allowed at all, and
`authorizeReorder()` is the arm that will carry a policy once authorization lands.
Adding the mode to the gate would buy nothing an attacker could not get by calling
`toggleTableReordering()` first.

The flag is re-gated from the other direction, and that half is ours:
`isTableReordering()` answers `$this->getTable()->isReorderable() && $this->isTableReordering`,
so a mode left standing on a table that has since lost its reorder column reports
false and every branch reading it — the handle column, the drag mount, the
suppressed sort headers, the emptied action sets — goes back to normal without a
write. Filament's `isTableReordering()` composes the same two, so this is parity
too; what is worth saying is that the flag alone is never trusted anywhere.

### A refused key leaves a gap in the numbering, and a smuggled one does not

`reorderTable()` numbers by array position — `$index + 1` over the submitted order,
reversed first when `getReorderDirection()` is `desc` — and the two ways a key can
fail to produce a record land on opposite sides of that numbering.

A key the **query does not answer for** (deleted since the drag, filtered out by a
`modifyQueryUsing()` scope, or simply invented) is skipped inside the loop, so its
slot is consumed and the positions either side of it differ by two. Filament's mass
`CASE` update behaves identically for the same reason: its `WHEN` arms are built
from `$recordIndex + 1` over the same array, and the arm for a key with no row
matches nothing. A gap is therefore parity, not a defect, and reordering is
position-ordered rather than position-contiguous by design — the next drag renumbers
from 1 regardless.

A key that is **not a printable scalar** never reaches the loop: the bag is filtered
(`is_scalar($key) && ! is_bool($key)`) and `array_values()`-compacted *before*
numbering, so a smuggled array or object shifts every later key up one and leaves no
gap. That compaction is ours — vendor hands `$order` straight to
`$connection->escape()` — and it is the shape the `#[LiveArg] array $order` edge
needs, since the keys are client-supplied.

### A host trait is analysed only through a class that composes it

PHPStan skips a trait no analysed class uses, and the repo ignores
`trait.unused` globally, so the skip is silent. This trait sat in exactly that
position from the day it landed until panels' `ListRecords` became the first
shipped class to `use` it; composing it turned on both the standalone and the
per-using-class analysis at once and surfaced **eleven** latent level-max errors
here, plus eight in [`HasActions`](../actions/has-actions.md#a-host-trait-is-analysed-only-through-a-class-that-composes-it).
Four were real runtime gaps rather than narrowing noise, and all four are fixed:
the two unguarded `(string)` casts on a record key, the unchecked row type
behind an `is_iterable()` PHPStan had proved unreachable, and the
half-implemented shape check on a
[filter slice](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk).
The rest were type-flow noise — a `blank()` call ordered after the narrowing
that makes its `@phpstan-assert-if-false` vacuous, and `evaluate()`'s generic
binding `T` to `Closure` when the value handed to it is statically a closure.

The durable half is the rule, not the incident: **a host trait carries no
static-analysis coverage of its own** — it inherits whatever its consumers give
it. Two consequences when extending this trait or writing another host one. Only
a class inside an analysed `src/` path turns a trait on:
[`phpstan.neon.dist`](../../../phpstan.neon.dist)'s `paths` stop at
`packages/*/src`, so the test fixture host
[`TableHost`](../../../packages/tables/tests/Fixtures/Concerns/TableHost.php)
composed this trait for its whole life and contributed nothing — which is
exactly why the eleven errors stayed latent until a class landed in `src/`. And
a scoped run over the package that *owns* the trait cannot see the errors that
exist only in a using class's context, which is why the whole set arrived at
once in a lane that had touched neither package — only the unscoped
`composer test:static` sees them.

### Two `TableColumnState` vocabularies meet on one host, and both are Filament's

`updateTableColumnState()` writes **one editable cell's value** —
[`has-columns.md`](has-columns.md#surface) owns it, and the state it names is a
record's property. `getDefaultTableColumnState()`, `syncTableColumnState()`,
`normalizeTableColumnState()`, `isReorderedTableColumnState()`,
`hasTableColumnState()` and `setTableColumns()` all concern
[the column manager's visibility-and-order bag](#the-column-state-is-re-derived-against-the-definition-on-every-read),
which is about which columns render at all. The two have nothing to do with each
other, they flatten into the same host class, and the phrase *table column state*
means a different thing in each.

The collision is **inherited, not minted**. Vendor's
[`Concerns\HasColumns`](../../../vendor/filament/tables/src/Concerns/HasColumns.php)
declares `updateTableColumnState(string $column, string $record, mixed $input)`
and vendor's
[`Concerns\HasColumnManager`](../../../vendor/filament/tables/src/Concerns/HasColumnManager.php)
declares `getDefaultTableColumnState()` and `setTableColumns()`; Filament ships
both vocabularies on the same host, so preserving its public API means
preserving the ambiguity. Renaming the four coinages this port added
(`normalizeTableColumnState`, `isReorderedTableColumnState`,
`hasTableColumnState`, `syncTableColumnState` — the last collapsing Filament's
`syncStaticColumnsFromTableColumnState()` and
`syncReorderableColumnsFromDefaultTableColumnState()`) away from that stem would
leave them inconsistent with the two Filament names they sit beside, which reads
worse than the ambiguity does.

The disambiguator is the **argument**, not the name: the editable write takes a
column name *and a record key*, and every manager member takes a state array or
nothing. A reader arriving at either family from the other should start there.

### A keyed root that joins a collection pages in two statements, because Doctrine's walkers key by one column

`Query\FetchJoinPageQuery::read()` is the window of a query that joins a
collection under a root Doctrine's paginator cannot page: two or more
identifier fields, which its `getSingleIdentifierFieldName()` sites refuse
outright, or a single one that is an association, which it pages until the
identifier tiebreak's order item. It answers in
**two** statements, which is the shape Doctrine's own `Paginator` uses and for
the same reason — a to-many join multiplies rows, so a `LIMIT` over them slices
a record in half. A keyed root whose query joins **no** collection is not
routed here at all: it keeps Doctrine's paginator, exactly as a single-scalar
one does.

The first statement reads the page's **identifiers** through Doctrine's own
`LimitSubqueryOutputWalker`, set as a hint on a clone of the builder: that
walker projects every identifier column, where the tree walkers behind it
project the one `getSingleIdentifierFieldName()` answers. The second reads the
**records** off a second clone, window cleared and
[`IdentifierConstraint::in()`](../schemas/record-key.md#the-operations-and-their-dql)
narrowing it to those keys, hydrated normally — so every collection comes back
whole and the order is the builder's own, never the first statement's. What
comes back is a **list**: the records are `array_values()`d on the way out,
because a query with `INDEX BY` on its root hydrates a string-keyed array and
the callers here read `[0]`. An empty
first page answers `[]` without running the second. Both steps are clones: the
builder handed in is never windowed, narrowed or reordered, so a caller may
read a second window off it. The **total** is unchanged, a `Paginator::count()`
through `CountOutputWalker`, which is composite-safe.

Three things a reader would otherwise have to re-derive.

**The walker's rows are remapped positionally and minted through
`KeySelection::keyOfHydrated()`.** The walker names its own result columns — an
identifier association under its bare property name carrying the **raw** driver
value, a scalar field under `<alias>_<field>` and converted — in identifier
order and exactly as wide as the identifier, so the row is written onto the
aliases this class asked `select()` for, in order, and handed to the selection
that authored them. Since [M7 · 11](../roadmap.md#m7--composite-identifiers) it is
keyed through `keyOfHydrated()`, which converts the raw association halves alone:
`keyOf()` read the already converted scalar fields through their types a second
time, which minted another key for a custom type whose reader does not read its
own PHP value back unchanged
([the second reader](../schemas/record-key.md#a-hydrated-row-is-keyed-without-reading-its-fields-a-second-time)).
`RecordKey::fromValues()` is the wrong call here and fails
**silently**: it prints without converting, so against a type whose stored form
differs from its printed one it mints a key the decode refuses, `in()` renders
that as `1 = 0`, and the page comes back empty with no error anywhere. A row of
any other width is a `LogicException` naming the class — a tripwire on
Doctrine's row shape, not a recoverable case.

That tripwire guards **width**, and width only. Every key here is minted
server-side and then strictly decoded again by `in()`, so an identifier type
outside
[the decode contract](../schemas/record-key.md#parameter-naming-and-what-an-empty-or-malformed-key-means)
— one whose printed PHP form is a spelling its own `convertToPHPValue()`
refuses and, since [M7 · 5b](../roadmap.md#m7--composite-identifiers), its
`convertToDatabaseValue()` refuses too,
[a type refusing it on read only](../schemas/record-key.md#a-type-that-refuses-its-printed-form-on-read-only-decodes-it-through-its-own-writer)
being read back through its own writer — has every key of the page dropped, and
the page comes back empty the same silent way, beside a correct total. That is the by-key seam's systemic
property rather than this read's: selection, row actions and the aggregate
batch mint and re-decode the same way, so a loud guard belongs where the decode
contract lives, in schemas' `Identity\` seam, and not in one of its consumers.
It is written down here because the page is where it shows least.

**An order item that is exactly `<root>.<field>`, where the field is an
owning-side to-one association with one join column, is swapped — in the
identifier statement only — for a hidden `IDENTITY()` result variable.**
`LimitSubqueryOutputWalker` rewrites an order item's SQL from the **field**
mappings alone (`generateSqlAliasReplacements()`), so an association path is
re-emitted as `<table alias>.<fk>` against a derived table carrying only the
aliased identifier columns — a driver error, and one
[the identifier tiebreak](#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)
walks into on every derived-identity page **outside that walker's `ROW_NUMBER`
branch**, since it orders by the association itself. So the item becomes
`addSelect('IDENTITY(root.assoc) AS
HIDDEN sy_order_<n>')` with `ORDER BY sy_order_<n>` — a result variable, which
is the route the walker does support and the one the hidden aggregate sort
already travels. Joining the target and ordering by its identifier field would
work for a plain derived identity and not for a **chained** one, whose target's
own identifier is another association; the result variable needs neither the
join nor the recursion. It is applied unconditionally rather than per platform,
because the rewritten item is a plain result variable that the `ROW_NUMBER`
branch orders by just as happily, and it never reaches the record statement,
whose order stays the builder's. `isAssociationWithSingleJoinColumn()` is the
one condition the swap tests, and it is enough on its own: only an owning-side
to-one mapping carries `joinColumns` at all, so the test already implies the
to-one.

Anything else in the order list is left as written, and the four shapes are not
left alone for the same reason. Two of them — an inverse-side to-one, and a
to-one whose target is itself keyed by two columns — Doctrine's own `SqlWalker`
refuses as an `ORDER BY` path expression in **every** statement it walks, paged
or not (`associationPathInverseSideNotSupported` and
`associationPathCompositeKeyNotSupported`). So no rewrite of them could ever
ship a half-key-ordered page: the record statement keeps the caller's own order
and would throw one statement later even if the identifier step had been
rewritten. What the condition buys is that the refusal arrives in front of the
**first** statement, and the case that shows it is an **empty** window — there a
rewrite would answer `[]` and a full page would still throw, so the empty one is
what `FetchJoinPageQueryTest` pins. The other two are genuinely the walker's
own: an item reached off a **joined** alias, which fails in the driver exactly
as it does today, and a comma list inside a single `orderBy()` part
(`->orderBy('r.position DESC, r.venue', 'DESC')` is one part, of which only the
head is read), which tables' own sort API cannot produce — it adds one item per
`addOrderBy()` — but a hand-written query builder can. Two details of the swap
carry no test at all and are recorded rather than pinned: `setCacheable(false)`
on the identifier step, which mirrors Doctrine's own paginator, and the
`<root>.` prefix test that keeps a joined alias's item out.

**The two statements are a race, inherited rather than introduced.** A record
deleted between them shortens the page — exactly as it does under Doctrine's
paginator, which reads its identifiers and its rows in two statements for the
same reason.

One older line becomes load-bearing on this lane without changing:
`HasBulkActions::pluckTableRecordKeys()` selects the identifier off the
table's own query, fetch join and all, so the join multiplies its rows the way
it multiplies the page's. The `array_unique()` around its key map is what
turns five rows back into two keys — a need as old as the fetch join, since a
single-identifier root multiplies its rows the same way — and the keyed
fetch-joined select-all is the round trip that exercises it.

One thing the shape makes unreachable in practice: a summary and a fetch join
cannot share a table, because
[summarizing a to-many join is refused](summarizer.md#a-to-many-join-on-the-summarized-query-is-refused-where-filament-inflates-in-silence)
rather than silently inflated. So the look-ahead's keyed arm is reached only
under a `Summarizer::using()` summarizer, which is the escape that refusal
names.

### An overridden `getTableRecordKey()` keys the rows, and the query lane reads keys back through the codec

`getTableRecordKey()` is public and on the contract, so a host may override it,
and what an override moves is one side of a round trip. It is what a row is
**keyed** by: `keyTableRecords()` keys every object row through it, so the row
loop, each checkbox's `value`, every mount context and `Table::getRecordKey()`
carry its answer. On the query lane nothing reads that key **back** through it.
`getTableRecord()` — and with it every row action, column action and editable
cell — the selection's `in()`/`notIn()`, the key pluck behind select-all, the
reorder's re-resolution, the aggregate batch and the page summary's narrowing
all decode a key through [`RecordKey`](../schemas/record-key.md) and
`IdentifierConstraint`, so the only key they can read back is the one
`RecordKey::of()` mints. On the `records()` lane an object row is keyed **and**
read back by the method alone, so an override there is whole; an array row
never asks it, being keyed by its `__key` entry.

An override answering any other key on the query lane therefore misses
everywhere. Executed against a host keying three songs as `S1`…`S3`, before the
refusal below:

- a row action, a column action or an editable cell on `S1` resolved nothing
  and did nothing, with no error;
- an explicit selection of `S1` and `S3` narrowed to `1 = 0`, so the bulk action
  acted on no row;
- select-all filled the selection with the codec's keys, which no rendered
  checkbox matched, so every box stayed unticked while the bulk action acted on
  all three;
- **select all, untick `S2`, delete: all three were deleted.** `notIn()` answers
  `null` when no deselected key decodes, and the selection query reads `null` as
  *nothing left to exclude*, so the unticked row went with the others — on both
  arms of `fetchSelectedRecords()`;
- a reorder wrote nothing, an aggregate cell showed its default, and the page
  summary narrowed to `1 = 0`.

**Parity.** Resolution, selection, select-all, reorder and the deselection
widening are Filament's too: its `find()`, `whereKey()`, `whereKeyNot()` and
`getKeyName()` never ask the override either, and `whereKeyNot(['S2'])` against
an integer key excludes nothing on SQLite and MySQL. The selectability
constraint, the aggregate batch and the page summary miss only here, because
Sylament reads keys where Filament reads none: Filament's selectability check
re-keys through `getKey()`, its aggregates ride the page query, and its summary
pages with `forPage()`. Before M7 the deselection bind was untyped, which on
PostgreSQL was, by reading, an integer-cast error — a 500 rather than a
deletion; the strict decode made the widening silent there too.

**So the query lane refuses it.** Since
[M7 · 10](../roadmap.md#m7--composite-identifiers), `keyTableRecords()` compares,
for every object row that is an instance of the table's
[record class](table.md#the-record-class-is-read-off-the-base-querys-root-entity),
what `getTableRecordKey()` answers with what `RecordKey::of()` answers, and a
difference throws a `LogicException` naming the host, the row's class, both keys
and the two ways out — answer the codec's key, or list the rows through
`records()`. Every render keys its rows there, so the first render refuses and
none of the symptoms above is reachable. An override answering the codec's key
renders as before, however it computes it; an array row, every `records()` row
and a query-lane row that is no instance of the record class — a `SELECT NEW`
projection, keyed by its `id` — are never compared. The codec's key is minted
once per row, and `getTableRecordKey()` is asked only where the codec answers
null or the host overrides the method — detected by the method's declaring file
being other than `HasTable.php` — so the comparison costs an overriding table
one more mint per row, a few microseconds and no statement, and a table that
does not override costs nothing. Pinned by
[`HasTableRecordKeyOverrideTest`](../../../packages/tables/tests/Unit/Concerns/HasTableRecordKeyOverrideTest.php)
over a fixture host whose override is a closure.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

### The host id is a random `LiveProp`, rather than a framework component id

Filament prefixes every morph key with `$this->getId()` — Livewire's own
component id, a framework-generated string that is part of the component's
snapshot and therefore stable across a re-render for free. UX LiveComponent has
no equivalent a template can read: the id it stamps on the root element is
generated by the frontend controller, not by PHP, so nothing server-side can
address it while rendering.

`getTableHostId()` therefore mints its own — `bin2hex(random_bytes(4))`, lazily
on first read, into a `#[LiveProp] public ?string $tableHostId` so the *same*
eight hex characters come back on every subsequent render of that component
instance. That is the whole requirement: the value is never parsed, never
addressed from JS, and never has to be unique across hosts for correctness — it
only has to be **the same before and after** a round trip, and different enough
between two tables on one page that their row ids do not collide. Four random
bytes give that at a page-realistic number of tables. It is the same shape
actions already ships as
[`getActionsHostId()`](../actions/has-actions.md#the-host-id-is-a-round-tripped-liveprop-not-a-framework-component-id),
for the same missing framework id, and the two are deliberately separate values
rather than one shared id — the packages do not depend on each other in that
direction.

**The getter/property name collision is what makes the lazy fill work, and it is
load-bearing.** `AddLiveAttributesSubscriber::onPreRender`
(`vendor/symfony/ux-live-component/src/EventListener/AddLiveAttributesSubscriber.php`)
dehydrates every `#[LiveProp]` *before* the component's template body renders, so
an id first minted while rendering the table partial would miss that pass
entirely: the next request would hydrate `$tableHostId` back to `null`, mint a
different value, and every row id would change — the exact failure the key
exists to prevent. It does not happen, because the dehydration pass reads the
prop through Symfony's `PropertyAccessor`
(`LiveComponentHydrator::dehydrate()`), which prefers the same-named **getter**
over the property — so the read that dehydrates is itself the read that mints,
and the template later finds the value already there. Renaming either half, or
adding a differently-named getter, breaks morph stability with nothing failing
loudly, which is why the Integration
[`HasTableTest`](../../../packages/tables/tests/Integration/Concerns/HasTableTest.php)
asserts the dehydrated `tableHostId` against the id actually stamped on the rows
rather than trusting the reasoning.

The same preference has a **second face**, and it is a liability rather than a
convenience: on a prop the client also writes, the getter's answer is the wrong
thing to put on the wire, which is what
[the two writable props bridge around](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary).
One mechanism, opposite signs — worth reading both notes before touching either
getter.

Two smaller consequences. The property is `public` because a `LiveProp` must be,
so it is part of the host's public surface and is the one thing this trait adds
to it — a host mixing in `HasSchemas` and `HasActions` alongside is unaffected
only because the three names are disjoint. And a host that renders its table
*without* being a live component (a plain Twig component, or the unit fixtures)
still gets an id: the lazy read works with no hydration behind it, the ids are
simply regenerated per request, which is exactly right for a render that never
morphs.

Permanent in mechanism, provisional in nothing — if UX ever exposes a
server-readable component id, this becomes a one-line delegate and the `LiveProp`
goes away.

### The table is built lazily on first `getTable()`, not in a booted hook

Filament assigns `$this->table = $this->table($this->makeTable())` inside
`bootedInteractsWithTable()` — a Livewire lifecycle hook that fires on every
request, before anything else runs — and `getTable()` is then a plain property
read that would `Error` on an unset typed property if the hook had not run. UX
LiveComponent has no `booted*()` counterpart: its lifecycle attributes
(`#[PostHydrate]`, `#[PreDehydrate]`) fire only for the hydration path, so a
first render — where the component is constructed rather than hydrated — would
leave the property unassigned.

`getTable()` therefore builds on first call and memoises into a nullable
`$cachedTable`, the same lazy shape
[`HasSchemas::$cachedSchemas`](../schemas/has-schemas.md) already uses on the
schemas host. The consequence, and it is the wanted one: the table is never
built for a request that does not touch it. Laziness is no longer *total*,
though — `mountTableFilters()` is a `#[PostMount]` hook and calls `getTable()`
unconditionally, so a **mounted** host builds its table whether or not anything
else asks for it, and only a host reached outside the mount path (a plain
`new`, a hydration-only request) still pays nothing. The one Filament behaviour
lost is the guaranteed build order — Filament can rely on the table existing
before any other hook runs; here anything that needs it calls `getTable()` and
gets it built on the spot. Permanent, unless UX adds a
construct-and-hydrate-alike boot hook.

### `makeTable()` seeds nothing from deprecated host getters

Filament's `makeTable()` is a 38-call `->when()` chain reading `getTableColumns()`,
`getTableFilters()`, `getTableEmptyStateHeading()` and friends off the host —
every one of them marked `@deprecated Override the table() method to configure
the table.` in `Concerns\HasRecords`. It is a v2-era back-compat path Filament
keeps for its own call sites.

Sylament's `makeTable()` is `Table::make($this)` and nothing else. There is no
pre-v4 Sylament code to be compatible with, so the port takes the endpoint
Filament's own deprecation notice points at: a host configures its table by
overriding `table()`. This is the same reasoning that kept
[`schema()` out](../schemas/component.md#the-schema-alias-is-not-ported) as an
alias for `components()`. Permanent.

### The filtered-query seam is gated on the lane, not on the presence of a builder

Filament's `getFilteredTableQuery()` reads `getTable()->getQuery()` and answers
null only when there is no builder to read — the lane question is left to the
callers, and only some of them ask it, branching on `hasQuery()` before the
seam is ever reached. So a Filament table declaring **both** a `records()`
closure and a `query()` resolves the closure for its rows and still hands the
builder back to anything calling the seam directly. Sylament asks the lane
question in the seam itself: no `hasQuery()`,
[which is "no records closure is set"](table.md#the-query-lane-is-on-exactly-while-no-records-closure-is-set),
no query — and `getFilteredSortedTableQuery()` answers null with it.

The gate is there because the seam is public and Filament leaves that question
to whoever calls it — seven call sites across six methods, each answering for
itself, and not alike. `getAllTableRecordsCount()` calls the seam bare and
counts what comes back; `getAllSelectableTableRecordKeys()` calls it a line
*before* it tests `hasQuery()`; `getAllSelectableTableRecordsCount()` manages
both in one body, a bare `->get()` on its selectable branch and a null-safe
`?->count() ?? $this->cachedTableRecords->count()` at its tail;
`getFilteredSortedTableQuery()` and `getAllTableSummaryQuery()` pass the null
on. One of the six settles the lane first:
`getGroupedSelectableTableRecordKeys()` tests `hasQuery()` at its top and
returns out of the records-lane branch entirely, so its seam call below is
reached on the query lane only. `getTableRecords()` settles it the same way and
reaches the seam indirectly, through the sorted one.
(`getTableQueryForExport()` is not among the seven — it bypasses the seam
entirely, re-reading `getTable()->getQuery()` and re-applying filters, search
and sorting itself.) Asking in the seam answers the question once, where the
table is, rather than at each call site — and the caller that needed the answer
is [`getAllTableRecordsCount()`](#the-all-records-count-answers-four-ways-where-filaments-answers-two),
the one Filament reaches with no check at all: the null the gate returns is what
sends a records-lane table to count what its closure returned instead. Handing
back a query a records-lane table will never execute is not a harmless extra —
counted, it runs SQL for rows the render will not show, and a table whose closure
ignores its query would be counted against the wrong data source entirely.

Nothing else changes. `resolveTableRecords()` takes the same branch either way,
because it consults the data source first, so the gate is observable only through
a direct read of the seam — which is exactly what the pinning case in
[`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php)
does. Permanent while both lanes exist.

### `gotoTablePage()` replaces Livewire's paginator trio

Filament's host does not implement paging at all: it composes Livewire's
`WithPagination` trait, which supplies `$page`, `gotoPage()`, `nextPage()`,
`previousPage()` and `resetPage()`, keeps the number in the query string under a
per-table page name, and re-renders on each. UX LiveComponent ships no
counterpart, so the page number is a `#[LiveProp] int $tablePage` here and
`gotoTablePage(#[LiveArg] int $page)` is the one `#[LiveAction]` that moves it,
clamped with `max(1, …)` and flushing the record cache.

The action is not the only writer, so the clamp is not the only guard.
[A URL-bound page](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page)
is seeded straight onto the property, which the action never sees, so
`getTablePage()` carries the read-side pair: `max(1, min($this->tablePage,
intdiv(PHP_INT_MAX, $perPage)))`. The **floor** is there because `?page=` coerces
to `int 0` on a non-nullable prop; the **cap** is there because the same seeding
accepts `PHP_INT_MAX`, and `page × perPage` then overflows to a float that
Doctrine's `setFirstResult()` refuses — a bookmarked `?page=9223372036854775807`
was a 500 until the cap landed. `intdiv()` is computed against the *resolved*
per-page, so it moves with the user's own choice, and the capped number reaches
the wire the same way the floored one does, through
[the getter the dehydration prefers](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary).

One live action covers the trio because the footer already computes its targets:
every button in the [Pagination component](../support/pagination.md) — first,
previous, each windowed number, next, last — knows the page it means, so
`nextPage()` and `previousPage()` would be the same call with arithmetic moved
from the template into PHP. `resetPage()` is likewise not a member: its three
Filament callers (sort changed, per-page changed, search changed) each set
`$this->tablePage = 1` inline here, which is one assignment against a member
whose name would be the only thing it added. The clamp is not in Filament — its
`gotoPage()` accepts what the client sends — and it is here because the page is
handed to a userland closure that will use it as an offset.

Permanent in mechanism. If UX ever ships a pagination trait, this becomes a
delegate; the `records()` closure's `page` injection does not change either way.

### Per-page is validated in the getter, on every read

Filament's `$tableRecordsPerPage` is a plain public Livewire property whose
getter returns it verbatim, with `getDefaultTableRecordsPerPageSelectOption()`
checking the session-restored value against the page options *once*, at seeding
time. Sylament stores the raw client string in a
`#[LiveProp(writable: true)] ?string $tableRecordsPerPage` and re-derives the
answer in `getTableRecordsPerPage()` on **every** call: null passes through,
`'all'` passes through, a numeric string becomes an `int`, anything else becomes
null — and the result must then appear in `getPaginationPageOptions()` or the
table's `getDefaultPaginationPageOption()` is returned instead.

The reason is that the prop is writable, and a writable `LiveProp` is client
input. UX LiveComponent checksums the props it hydrates, but a **writable** one
is exempt by design — that is what writable means — so the `onUpdated` hook is
not a gate: it runs when the client *tells* it the value changed, and a forged
hydration payload never calls it. Validating in the getter makes the forgery
irrelevant instead of trying to catch it: the value reaching a `records()`
closure is always one the table declared, whatever the payload said. That
matters because the closure will use it as a `LIMIT`.

Two consequences, both intended. On the **first** render the prop is `null` and
the getter seeds from the table's default without writing, which is what the
select renders as its selected option and what
[`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php)
asserts by checking the getter answers `10` while the property is still null.
From the second request on the property holds the option string the client sent
back, because that is what
[the wire bridge](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary)
puts on the wire and hydration writes straight onto it — a narrower claim than
"until the user picks something", and one the getter is indifferent to, since it
re-derives its answer either way. And the type is `?string` rather than
Filament's `int|string`, because that is what a `<select>` posts; the
normalisation is the cast, and it lives in one place.

`updatedTableRecordsPerPage()` and `updatedTableSearch()` are kept as `onUpdated`
hooks anyway, for the half validation cannot do: resetting to page 1 and
flushing the record cache when the user changes either. Filament's same-named
methods are Livewire's magic `updated<Property>` hooks; here the binding is
explicit in the attribute, which is the ordinary Livewire → UX translation.

### The writable props dehydrate in their elements' wire vocabulary

Filament needs no counterpart to this: Livewire's snapshot carries the
**property**, and `getTableRecordsPerPage()` is a separate read the view
performs. Here the dehydration pass reads every `LiveProp` through Symfony's
`PropertyAccessor` (`LiveComponentHydrator::dehydrate()`), which prefers the
same-named **getter** — the [second face](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id)
of the preference that mints the host id, and on these two props a liability
rather than a convenience. Unbridged, the wire carried
`getTableRecordsPerPage()`'s resolved option — the `int` `10` — while the
`<select>` bound to the prop held the string `"10"`, and it carried
`getTableSearch()`'s trimmed-or-null value — `null` — while the search `<input>`
held `""`.

A model that disagrees with its element is permanently dirty.
`live_controller`'s `SetValueOntoModelFieldsPlugin` runs on attach and after
every render, and for a non-multiple `<select>` it writes the element's value
back into the store — where `ValueStore.set()` short-circuits on `===` and
otherwise records a **dirty** prop. String `"10"` against `int` `10` is not
`===`, so every subsequent request carried
`updated: {tableRecordsPerPage: "10"}` — a sort click, a page click, cancelling
a row action's modal — which calls `updatedTableRecordsPerPage()` and resets the
table to page 1. That was the shipped defect: on the demo, opening a row's
Delete modal on page 2 and cancelling it snapped the table back to page 1.

Both props therefore declare a `dehydrateWith:` bridge, and each puts on the wire
the value its **element** speaks. `dehydrateTableRecordsPerPage()` string-casts
the resolved option the pass hands it, so the model is exactly the `value` of the
`<option>` the footer rendered as selected (`5 → "5"`, `'all' → "all"`; `null` —
an unpaginated table, or a paginated one with empty page options — stays null and
renders no select at all). `dehydrateTableSearch()` ignores its argument and
returns the property raw, so `""` stays `""` against an `<input>` that renders
with no `value` attribute. Renaming the getters would fix the mismatch too, and
is not available: `getTableRecordsPerPage()` and `getTableSearch()` are the
Filament names migrating users grep for.

The raw wire the search takes is **not** available to the per-page half, which is
worth stating because a reader will otherwise simplify its bridge to
`return $this->tableRecordsPerPage;`. The plugin's store → DOM write is
unconditional for any model that is not `undefined`, and `setValueOnElement()`
marks an `<option>` selected by string comparison — so a raw `null` model matches
no option and the browser's selectedness algorithm picks the **first** one.
Measured on the demo with exactly that bridge in place: the select read `2` while
the table under it showed 10 rows, and the next live action posted
`perPage: "2"`, a value the user never chose. A raw wire is stable only where the
element's vocabulary contains the raw value; for the select that means the
resolved option and nothing else.

Both `onUpdated` hooks also grew a no-op guard — `$previousValue ===` the
property returns without resetting the page or flushing the cache. It is belt and
braces now that the wires agree, and it could not have substituted for the
bridge: before it, `$previousValue` carried the wire's `10` while the property
carried the echoed `"10"`, so the two were never `===`. Its one visible cost is
that a hook called with the value the prop already holds does nothing, which is
what the Unit cases assert from both sides.

Permanent in mechanism: it follows from the `PropertyAccessor` read, and only a
prop the client writes back needs it.

### The `recordsPerPage` injection is nullable

Filament types the injection `fn (): int | string`, but the property it reads is
`public $tableRecordsPerPage = null` and nothing seeds it before the first
render — `getDefaultTableRecordsPerPageSelectOption()` is read by the *view*, not
by the getter — so an unseeded table hands the closure `null` against a
non-nullable declaration. Sylament types it `int|string|null` and means it.

Two paths reach null here, and only one of them is Filament's. An **unpaginated**
table returns null because there is nothing to slice by, which Filament cannot
express at all (its injection is unconditional). A **paginated** table with an
empty `paginationPageOptions([])` returns null because its default page option is
null too. A paginated table with options never does: the getter falls back to the
table's default, so the common case hands the closure an `int` and a closure may
type-hint `int` if it only supports paginated tables — the fixture host does
exactly that, with an `assert(is_int(...))`. Declaring the null is the honest
half of the same contract, and it is what lets a closure branch on
"unpaginated" without asking the table.

### The session key is the host class, so two instances of one page share it

Filament persists five pieces of table state in the Laravel session, each keyed by
`md5(static::class)` plus a suffix: the sort (`persistsSortInSession()`, opt-in),
the search and the column searches (`persistSearchInSession()` /
`persistColumnSearchesInSession()`, opt-in), the filters
(`persistsFiltersInSession()`, opt-in), the
[column manager's state](column-manager.md#the-session-is-written-at-the-end-of-every-mutation-rather-than-once-per-request)
(`persistColumnsInSession()`, opt-**out** — the only *flag* of the five that
defaults on, and the only flag that gates its write without gating its read),
and the per-page option —
**unconditionally**, with no setter to turn it off. M4 · 3 shipped all of it but
the column searches and the column state, which arrived together in M4 · 5 with
[the bag they persist](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept)
and [the bag the manager edits](#the-column-state-is-re-derived-against-the-definition-on-every-read).
All six suffixes go through
[`CanPersistTableState`](../../../packages/tables/src/Concerns/CanPersistTableState.php)'s
two helpers, but only five are read by its `#[PostMount(priority: 10)]` hook: the
sixth, `columns`, is read in the manager's own mount hook at priority 4 instead,
because the state it seeds has to settle before anything asks a column whether it
is visible.
`getTableSessionKey()` is `sprintf('sylament.tables.%s_%s', md5(static::class), $suffix)`,
Filament's shape verbatim, and per-page persists whether or not anything opted in,
so every **paginated** table host with a session starts remembering per-page
choices — the `isPaginated()` gate around that arm is Filament's too.

There is a seventh suffix that does not exist, and its absence is a port of an
absence: **grouping** is persisted by neither codebase. A grep of the vendor tree
for `persists*InSession` returns filters, search, column searches, sort and
columns and nothing else, so M4 · 6 added no writer, no suffix and no setter —
[the URL is where a grouping is kept](grouping.md#nothing-persists-a-grouping-in-the-session-and-filament-does-not-either),
and a case in `TableSessionPersistenceTest` fails the moment a `…_grouping` key
appears.

The key is the divergence, and it is inherited rather than chosen: `md5(static::class)`
names the **class**, so two instances of one component on one page share one
remembered sort, and two different pages never do. The
[host id](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id)
cannot serve instead — it is regenerated per component instance, so it would
remember nothing across a visit, which is the opposite failure. Keeping Filament's
collision is deliberate: the alternative is a table identity vocabulary that does
not exist here either, and a collision that reproduces upstream's behaviour is
easier to reason about than a novel one.

The mechanism is a `#[PostMount(priority: 10)]` hook, and the priority *is* the
precedence rule. UX's own query-string seeding is a `PostMountEvent` listener at
priority 256 and every listener runs before the first `#[PostMount]` method, so a
bound prop already carries the URL's value when the hook reads: the session fills
only what the URL left blank (`blank($this->tableSearch)`, `blank($this->tableSort)`,
`$this->tableFilters === []`, `$this->tableColumnSearches === []`), which is
Filament's own precedence proved from the other end. The per-column bag is read
above [its own seed](#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept)
at priority 3 for the same reason the sort is read above its heal: a stored bag is
filled out by the seed rather than replaced by it. The read runs above the filters mount hook and below the filters'
[query-string seed](#a-seeded-filter-bag-fills-the-form-instead-of-the-declared-defaults),
so a session-restored bag reaches the same normalizer a URL-seeded one does — and
above [the sort heal](#a-seeded-sort-is-healed-at-mount-where-filaments-is-trusted-to-the-query)
at priority 5, so a remembered sort naming a column the table has since dropped is
validated by the same hook a URL-seeded one is, and forgotten in the same pass.

Three narrower divergences ride on it. Every write is a **no-op without a session**
— `getTableStateSession()` returns null for a request with no session at all (a
console command, a sub-request, a hand-mounted test component), where Laravel's
`session()` helper always resolves — so the trait degrades rather than throwing.
A remembered per-page option that is no longer in `getPaginationPageOptions()` is
forgotten at mount rather than left to fail validation on every later visit;
Filament removes it too, but returns `$pageOptions[0]` where ours returns null and
lets [the getter](#per-page-is-validated-in-the-getter-on-every-read) answer. And
the resolved default is **not written back**: Filament's mount assigns the resolved
option onto `$tableRecordsPerPage`, ours leaves the prop null when the session holds
nothing usable, so the session entry is only created by the user's first pick.
`persistTableRecordsPerPageInSession()` early-returns on a null prop, which is what
makes that true on the mount path as well as the update one.

### A seeded sort is healed at mount, where Filament's is trusted to the query

`sortTable()` refuses a column the table does not declare sortable and a direction
that is neither `asc` nor `desc`. Two writers reach `$tableSort` without going
through it — the query string and the session — and Filament checks neither: its
`getTableSortColumn()` is the same raw `before(':')` parse ours is, and the only
thing that looks at sortability is `applySortingToTableQuery()`, which simply does
not sort when the lookup misses.

That is fine on the query lane and wrong on the records one. A `records()` closure
takes a `sortColumn` injection reading `getTableSortColumn()` directly, so a
`?sort=<anything>` handed the closure the raw client string and left it to
userland to decide what to do with a column nobody declared. `healSeededTableSort()`
closes it: a `#[PostMount(priority: 5)]` running the same column-and-direction check
`sortTable()` runs, and nulling the prop when it fails.

The **priority is the point**, and it puts the hook at the bottom of a three-rung
ladder — UX's query-string seeding (a listener at 256), then
[the session read](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it)
at 10, then this at 5. So one check covers both untrusted writers: a URL naming a
column the table never offered, and a session written months ago against a table
that has since dropped it. The cleared value is **written back** to the session
(`persistTableSortInSession()`), so a stale entry is forgotten once rather than
re-read and re-cleared on every later visit — which is also why the heal runs below
the read rather than above it.

Filament needs none of this because its own seeding lands in the same place and it
accepts the consequence; ours is the [entry-point validation](#sorttable-ignores-a-column-the-table-did-not-declare-sortable)
the port already applied to the action, extended to the two writers the action does
not own.

### The URL binding is a per-prop `LiveProp` modifier, opted into by the page

Filament binds seven names to the query string through **two** mechanisms, and the
split matters. Six ride Livewire's `#[Url]` attribute, redeclared on `ListRecords`
with a fixed literal each — `sort`, `search`, `filters`, `tab`, `grouping` and
`reordering`. `page` is not one of them: it rides Livewire's separate pagination
binding, `HandlesPagination::queryStringHandlesPagination()` returning
`['paginators.<name>' => ['history' => true, 'as' => $pageName, 'keep' => false]]`,
where `$pageName` is `getTablePaginationPageName()` — which is why `page` is the
**only** one of the seven that `getIdentifiedTableQueryStringPropertyNameFor()` and
the table's `queryStringIdentifier()` disambiguate per table, and why its
`'history' => true` is the "pagination alone" that constraint 1 below names.
M4 · 3 shipped the five names a routed page needs — `page` (identifier-prefixed),
`sort`, `search`, `filters` and `tab` — through one mechanism UX makes available
and Livewire does not, so the two halves collapse into one hook here, and M4 · 6
added `grouping` as the sixth.

The lever is `modifier:`, not `fieldName:`. Each URL-candidate `LiveProp` names a
two-line modifier (`modifyTablePageProp()`, `modifyTableSortProp()`,
`modifyTableSearchProp()`, `modifyTableColumnSearchesProp()`,
`modifyTableFiltersProp()`, and `HasTabs`'
`modifyActiveTabProp()`), each asking the host's
`getTableUrlMapping(string $prop): ?UrlMapping` and calling `$liveProp->withUrl($mapping)`
when it answers one. `withUrl()` clones the attribute, `LiveComponentMetadata`
re-runs modifiers on every metadata read, and the seed, the write and the
client-attribute paths all honour the result — so the binding is decided per host,
at runtime, which is exactly what an attribute cannot express. The default is
`null`, so a showcase card, a modal-hosted table and a
[relation manager](../panels/relation-manager.md) keep today's behaviour with zero
edits, and only `ListRecords` and `ManageRelatedRecords` override it.
`fieldName:` was rejected because it is also the **wire** name: renaming
`tableSearch` would rename it under three template bindings at once.

Two rules bind an override. It must stay cheap and side-effect-free, because
modifiers run inside `LiveComponentHydrator::hydrate()`; and **no modifier may call
`getTable()`**, because that is *before* `#[PostHydrate]` stamps `_sylament_panel`
onto the request, so a table built there resolves the default panel and memoises
that answer for the rest of the request. That is why the page-parameter prefix
comes from `getTableQueryStringIdentifier()`, a **host** hook a page answers from
a static value, rather than from
[`Table::queryStringIdentifier()`](table.md#surface) — which still ships as parity
surface and is read by `getIdentifiedTableQueryStringPropertyNameFor()` at
non-modifier call sites, an
[inverted deadness](#the-query-string-identifier-is-live-on-the-table-and-dead-in-the-modifiers)
recorded below.

Four constraints of UX's own URL writing are accepted rather than fought, and none
is worked around with JavaScript — the binding ships zero new JS.

1. **replaceState only.** The URL is written server-side through the `X-Live-Url`
   response header and applied with `history.replaceState`, so table state is not
   back-button navigable. Livewire pushes state for pagination alone; there is no
   equivalent hook here.
2. **Residue at defaults.** There is no omit-at-default rule: `http_build_query`
   drops only `null` and `[]`, so after the first live round trip the URL carries
   `?page=1&search=&tab=<key>` even where nothing was changed. A plain first GET
   never rewrites the URL, so a clean link stays clean until the user interacts.
3. **PHP bracket arrays.** A filter bag serializes as `filters[status][value]=x`
   where Livewire writes JSON. The read side matches, so a copied URL round-trips;
   only its shape differs from Filament's.
4. **`onUpdated` does not fire on a seed.** A seeded value is written straight
   through the property accessor, so `updatedTableSearch()` never runs for it —
   which is why `?search=` does not reset the page, and why every check the
   action-side writer runs has to be repeated somewhere the seed passes through.
   `page` is the plainest case: `getTablePage()` both floors and caps it
   (`max(1, min($this->tablePage, intdiv(PHP_INT_MAX, $perPage)))`), because
   `?page=` coerces to `int 0` on a non-nullable prop at one end and accepts
   `PHP_INT_MAX` at the other — where the offset multiplication overflows to a
   float `setFirstResult()` refuses. `sort` is the case that needed a hook of its
   own; `filters` is [handled at its own seed](#a-seeded-filter-bag-fills-the-form-instead-of-the-declared-defaults).

One name is deliberately **not** bound: `reordering`. Filament binds it; here it is
a transient UI mode rather than browsing state, and binding it would leave a
permanent `reordering=0` in the URL under constraint 2 above. `grouping` **is**
bound, since M4 · 6 gave it a prop, and `perPage` and the column state stay
session-or-transient exactly as Filament has them.

### The query-string identifier is live on the table and dead in the modifiers

Filament ships both halves of the name prefix and only one of them is reachable.
`getIdentifiedTableQueryStringPropertyNameFor()` reads
`getTable()->getQueryStringIdentifier()` and nothing else, so the `Table` setter is
the live half; the host shim `getTableQueryStringIdentifier()` is declared on
`InteractsWithTable` returning null and is **read by no caller anywhere in
Filament's source**. Ours inverts exactly that. The host hook is the live half —
`InteractsWithRelationshipTable` overrides it with the host class's own basename,
lower-cased at the first letter, and both routed pages compose the `page` mapping
from it (a plain `ListRecords` answers null and gets a bare `?page=`, so only a
tier reached *through* another one takes a prefix) — while
`getIdentifiedTableQueryStringPropertyNameFor()` reads the
`Table` setter first and the host hook second, and has no production caller of its
own: Filament's one caller is `getTablePaginationPageName()`, which feeds Livewire's
`WithPagination` and has no counterpart here. It ships as parity surface with a
Unit test for its precedence, and nothing else.

The cause is the rule above — a `LiveProp` modifier cannot build a table — and the
consequence is worth stating plainly, because it is a behavioural difference rather
than bookkeeping: a user who calls `->queryStringIdentifier('foo')` inside `table()`
changes what `getIdentifiedTableQueryStringPropertyNameFor()` answers and does
**not** change the `?page=` parameter the page binds, because the mapping was
resolved before the table existed. A page wanting a different prefix overrides
`getTableQueryStringIdentifier()`.

### The reorder write is a unit-of-work flush, not one mass `CASE` update

Filament writes a whole reorder in a single statement: a `DB::transaction()` around
one `UPDATE … SET position = case when key = 'a' then 1 when key = 'b' then 2 … end`
over a `whereIn` of the submitted keys, assembled by `makeTableReorderColumnExpression()`
with the values escaped through the connection's grammar. Ours resolves the keys to
managed entities through the table's own query, writes each position with the
[property accessor](../schemas/property-accessor.md), and calls `flush()` once.

The shape is Doctrine's rather than a preference. A raw mass `UPDATE` bypasses the
unit of work, so entities the request already loaded would keep their stale
positions, `@PreUpdate`/`@PostUpdate` lifecycle callbacks would not fire, and an
optimistic-lock `@Version` column would not increment — three things a Doctrine
application is entitled to assume happen when a mapped field changes. One flush
still sends one transaction, so the atomicity Filament's explicit
`DB::transaction()` buys is not lost; what changes is that N rows cost N `UPDATE`
statements rather than one. N is not the page size: reordering is **unpaginated
by default** (`CanPaginateRecords::$isPaginatedWhileReordering` is `false`, and
`HasTable::resolveTableRecords()` skips the paginator while the table is
reordering), so the submitted order is the whole filtered table unless
`paginatedWhileReordering()` is on. The trade is still worth it, and the read
that resolves those keys back to entities is chunked for the same reason — see
[the chunk rule](../schemas/record-key.md#the-chunk-rule).

Two smaller members go with it. Vendor strips the reorder column to its last dotted
segment (`str($column)->afterLast('.')`) because the pivot arm below needs a bare
column name; ours resolves the *whole* path through the accessor, so
`reorderable('meta.position')` writes into an embeddable or a value object rather
than being silently truncated to `position`. And Filament's second parameter
`$draggedRecordKey` is [not ported](../renames.md#renames) — vendor declares it and
reads it nowhere in the body — so the live action takes the order alone.

### `sortTable()` ignores a column the table did not declare sortable

Filament's `sortTable()` writes whatever column string it is given straight into
`$tableSort`; the check happens much later, in `applySortingToTableQuery()`,
which only applies the sort if `getSortableVisibleColumn()` finds a column — so
an unknown or unsortable name leaves the table *displaying* itself as sorted by a
column it is not sorted by. Sylament checks first and returns without writing:
`getSortableVisibleColumn($column) === null` is a no-op, as is a direction that
is not `asc`, `desc` or null.

The reason is the closure lane. Filament's late check is harmless because the
query builder is the only consumer; here the value is handed to a **userland
closure** through the `sortColumn` and `sortDirection` injections, and that
closure will most likely interpolate it into a query. Filtering at the entry
point is what keeps the injections trustworthy: a name arriving at a `records()`
closure has been matched against a declared-sortable column, so the closure can
use it without re-validating. The direction check is the same argument for the
same reason, and it is why `sortTable()` types its `$column` parameter
`string|int|null` — a `#[LiveArg]` carries whatever JSON the client sent, and an
integer column name (a table sorted by a year) must survive the cast rather than
be refused by the signature before the check runs. Only `$column` carries that
union — `$direction` stays `?string`, because its whole vocabulary is `asc`,
`desc` and null, so there is no legitimate non-string value the widening would
have let through.

Permanent, and the query lane will not loosen it: an ignored request is
recoverable (the user clicks again), a trusted-but-unchecked one is not.

### A default-sort closure's replacement builder reaches the caller, where Filament's is discarded

Both codebases end `applySortingToTableQuery()` with the same two moves — a
`$query = $defaultSort;` when the evaluated default sort came back as a builder,
then the identifier tiebreaker — and the bodies are otherwise line for line. The
divergence is in the **call sites**. This one returns the builder and
[`getFilteredSortedTableQuery()`](../../../packages/tables/src/Concerns/HasTable.php)
consumes the return (`return $this->applySortingToTableQuery($query);`), so the
replacement really becomes the table's query. All four of Filament's callers
invoke the method as a bare statement and then use the builder they passed in —
`Concerns\HasRecords::getFilteredSortedTableQuery()` and
`getTableQueryForExport()`, and two sites in `Concerns\HasBulkActions` — so over
there `$query = $defaultSort` rebinds a local and nothing more. The replacement
never leaves the method; what it does do is redirect the rest of it, so
`getQualifiedKeyName()` and the tiebreaker are read off and appended to the
builder that is about to be thrown away. Filament's observable behaviour for a
`defaultSort()` closure returning a builder is therefore *the original query,
still carrying the user's sort, with no identifier tiebreaker* — the replacement
contributes nothing at all.

The reach is narrow, and worth stating so the divergence is not read as bigger
than it is. A closure written the ordinary way orders the builder it was handed
and returns `$this` or nothing, so `$defaultSort` is either that same builder or
not a builder, and both codebases behave identically on every such input. Only a
closure returning a **different** builder reaches the arm — which is exactly what
[`getDefaultSort()`'s widened `string|QueryBuilder|null` return](table.md#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker)
exists to serve, and it is invisible to a reader diffing the two method bodies,
because the bodies match.

Ours is the reading the declared return states — and Filament declares the same
`Builder` return, so the arm is dead over there rather than absent, which is why
it is kept here rather than un-ported: a value a method computes and hands back
is one the caller uses. The cost is that the replacement is **total**: it brings
its own filters and drops the search and the user's sort along with everything
else applied to the builder it replaced. Three cases in the
[sorting suite](../../../packages/tables/tests/Unit/Concerns/CanSortRecordsTest.php)
pin it — the replacement's `WHERE` reaching the executed query, the user's sort
gone from the DQL, and the tiebreaker read off the replacement (`ORDER BY
book.code ASC` for a `Book` builder returned from a `Song` table's default sort).

Permanent, and it closes on its own if Filament ever consumes the return — the
two agree again with no change here. It is the third arm of this family the port
declines to carry as written, beside
[`searchable()`'s closure condition](column.md#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column)
and [`applySort()`'s reversal](column.md#sort-columns-apply-in-declared-order-where-filament-reverses-them).

### `getTableRecords()` returns a keyed array or a `Paginator`, never a `Collection`

Filament returns a `Collection | Paginator | CursorPaginator`. Sylament returns
`array<array-key, object|array<string, mixed>> | Paginator`, where `Paginator` is
support's [own carrier](../support/pagination.md) rather than Laravel's.

The **keying** is Filament's whole: `mapWithKeys()`'s contract is ported — each
entry re-keyed by the record key, an array record's `__key` entry synthesised
from the source array's own key when absent and put through the same
printable-key rule the object arm uses either way — so
`$records[$key]` is the bare lookup a row-scoped action needs. It applies to both
arms: a returned `Paginator` is re-keyed too, through `withRecords()`, so the
carrier that comes back is a **clone** of the one the closure returned and the
counts around it are untouched. `getTableRecord()` unwraps it before the lookup,
which is why a row-scoped action works identically on a paginated table.

Only the container differs. There is no `Collection` to return: the Laravel
collection pipeline has no port and nothing in the package consumes one, so a
`foreach`-able array carries the same information. `CursorPaginator` has no
counterpart either — cursor paging is a query-lane shape, and the closure lane
has nothing to build a cursor from. The `Traversable` and array arms a closure
can produce both fall out of one `foreach`, which is why nothing calls
`iterator_to_array()` — a generator yielding the same key twice loses the earlier
record either way, and that is the same last-writer-wins rule two records sharing
an `id` already get.

Anything else the closure returns is a `LogicException` naming both accepted
shapes. Filament has no such guard, because Eloquent's builder cannot return
garbage; a userland closure can, and a table that silently renders empty for a
`null` return is the failure worth stopping on.

**The rows inside it are checked the same way**, and that half arrived late —
the `is_iterable()` guard above made the keying loop statically unreachable, so
[nothing analysed it](#a-host-trait-is-analysed-only-through-a-class-that-composes-it)
until a class composed the trait. A row that is neither an object nor an array
used to reach `getTableRecordKey(object|array $record)` and die there on a
`TypeError` naming a parameter the caller never wrote; it now throws a
`LogicException` naming the table and the type that arrived. Two more refusals
sit beside it, both on array rows. An array whose keys are not all strings is
refused: every column read and the `__key` entry address a record by field name,
so a list is not a record, and it used to be accepted and keyed by its position.
And a `__key` entry that is not printable — an array, a non-`Stringable` object,
or blank — is refused where the bare `(string)` cast used to turn it into
`"Array"` plus a PHP warning, or into a fatal `Error`. All three are
garbage-in cases with no green test between them; the failure they replace was
either silent or unattributable.

Permanent for the `Collection` arm; the `Paginator` arm is now shipped rather
than pending.

### Object records are keyed by a readable `id`, rather than an Eloquent key

Filament's non-array arm is `$record->getKey()` — every record is a `Model`, and
a model always answers with the value of whatever column `$primaryKey` names.
Sylament's record vocabulary has no such base class: a record is any `object`,
usually a Doctrine entity but equally a DTO, so there is no `getKey()` to call.
`getTableRecordKey()` therefore reads `id` through schemas'
[`PropertyAccessor::getFromRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php),
which is the same accessor a [`Column`](column.md) reads its state with — so a
public property, a getter, or a `__get` all satisfy it, and `id` is the one
convention the package assumes about a record.

Three consequences worth stating. A `Stringable` id is string-cast before the
scalar check, so a `Symfony\Component\Uid\Uuid` or a value object keys a table
without unwrapping at the call site. An id that is missing, unreadable
(`AccessException`), or [blank](../support/helpers.md) throws a `LogicException`
naming the record class and pointing at the array-record alternative — the key
addresses a record across a request boundary, so a silently empty or positional
one would attach an action to the wrong row. And nothing configures the property
name on this arm: Filament's `$primaryKey` counterpart is a Doctrine mapping
concern, so a record whose identity is not `id` either comes down the query lane
below or uses the array form and its `__key` entry.

**On the query lane the mapping answers instead of the convention.**
`resolveQueryRecordKey()` runs before the `id` read and, when it returns a key,
is the whole answer: for a table with a `query()`, whose
[record class](table.md#the-record-class-is-read-off-the-base-querys-root-entity)
the record is an instance of, the key is Doctrine's own —
`ClassMetadata::getIdentifierValues()`, string-cast through the same `Stringable`
unwrap and blank check. So a `Book` mapped on `code` keys by `code` with nothing
configured, which is exactly what the `id` convention could not do. Everything
else falls through to that convention unchanged: array records, every record on
the closure lane, and — on the query lane — a DTO or any object that is not the
table's entity, so a `SELECT NEW` projection or an object handed straight to
`getTableRecordKey()` is keyed by its `id` exactly as before.
A host that overrides the method must answer the same key on this lane for every
row the table's record class hydrates, or the first render refuses it
([semantics](#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec)).

Two things about that guard. It asks the **table** what its record class is and
tests the record with `instanceof`, rather than asking Doctrine whether the
record's own class is mapped, because the obvious API cannot answer:
`AbstractClassMetadataFactory::isTransient()` returns `false` for an anonymous
class while `getMetadataFor()` **throws** `MappingException` for one, so an
anonymous DTO — the plainest transient object there is — would pass the transient
check and then explode on the metadata read. Reading the declared class also
means an anonymous *subclass* of an entity (a test double) keys by its parent's
mapping instead of blowing up — pinned by `HasTableTest`'s *keys an unmapped
subclass of the record class by the table record class*, because the class now
travels as `RecordKey::of()`'s third argument and no other case notices it
missing. A **composite** identifier is no longer refused:
the read goes through [`RecordKey::of()`](../schemas/record-key.md), which
encodes every identifier field in mapping order into one string — `1~2` for a
scalar pair, `main-hall~2026-spring` for a derived identity, whose association
component is flattened to its target's own key — so a table over such an entity
keys its rows exactly as a single-`id` one does. A class with **one** identifier
field still encodes to the flattened value verbatim, unescaped and unsplit, so
`Book`'s `sy-001` above is untouched; the
[grammar and its alphabet](../schemas/record-key.md#the-grammar) apply to a
composite key only.

The arm keeps two refusals of its own, both `LogicException`: an identifier the
entity carries **none** of — or carries only *part* of, which is the composite
shape of the same fault and needs saying, because `getIdentifierValues()`
silently drops a null component rather than answering it, so counting is the
only honest test — and one that is set but empty or unprintable. Neither is
recoverable: the table would otherwise index rows under a key no action can
resolve.

The action side has the same-shaped twin,
[`InteractsWithRecord::resolveRecordKey()`](../actions/action.md#record-key-resolution-is-hoisted-to-hasactions)
— same `id`-through-the-accessor read, same `Stringable` unwrap, same
`LogicException` for an array record missing its key entry — differing in what
an *unkeyable* record means: it returns `null` where this throws, in both arms.
(The array arm used to disagree in a third way, returning the blank string for a
blank `__key` where the object arm threw; both arms now run one printable-key
rule, so a blank or unprintable key throws wherever it appears.) The asymmetry
is deliberate and matches Filament's own pair. A table
building its row index cannot proceed without a key, so an unkeyable record is
a configuration error worth stopping on; an action assembling a mount context
can simply omit `recordKey` and stay mountable. Keep them in step if the read
ever changes: they are two implementations of one convention, not one method
and its wrapper.

The twin is deliberately **not** given the metadata arm. An action has no table to
ask, and the direction that matters for a table context already runs through the
host: a mounted action carries a *key* back, and
[`resolveTableAction()`](../actions/has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference)
turns it into a record with `getTableRecord()`, this trait's own map. What the
twin still answers for itself is the outbound half — `Action::getContext()`'s
`recordKey` for a row-scoped action — and that half is **no longer the twin's
alone**. The "until" clause this paragraph used to end on arrived with
[M7 · 1](../roadmap.md#m7--composite-identifiers), whose own deliverable is a
row action on a composite entity, and the disagreement was closed the way this
section proposed: when the action has a table and the record is an instance of
the table's
[record class](table.md#the-record-class-is-read-off-the-base-querys-root-entity),
`getContext()` asks the **table** for the key and falls back to
`resolveRecordKey()`'s literal `id` only otherwise. The actions layer was handed
the table's key rather than taught Doctrine —
[action.md](../actions/action.md#getcontext-drops-the-record-model-gate) carries
the other half of this note.

### A query-lane override of `getTableRecordKey()` the codec cannot read back is refused, where Filament's misses in silence

Filament lets a host override `getTableRecordKey()` on an Eloquent table and
then reads every key back through `find()`, `whereKey()` and `whereKeyNot()`,
which never ask it — so an override answering anything but the model key
resolves no record, selects nothing, and after a select-all with a row unticked
acts on the unticked row too, all without an error. Ours failed the same way
and in three places more
([the semantics](#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec)),
and one of the shared failures deletes rows the user deselected, so the query
lane refuses such an override by name at the first render instead. It takes
nothing a migrating user could have relied on: no read-back ever honoured the
override there, so the refusal only names a table that was already broken. The
`records()` lane keeps Filament's freedom, since it reads keys back through the
method itself. Reverse it only if the query lane grows a host-side decode that
pairs with the override.

### `getTableRecord()` reads fresh when the render cache is cold

Filament resolves a record down two lanes that disagree with each other. The
**query** lane runs `$query->find($key)` — a fresh read that never touches
`$cachedTableRecords`. The **records** lane, the one this ports, is
`$this->getTable()->getRecords()[$key] ?? null`, which goes through the host's
`getTableRecords()` and so both fills and reads the render cache.

Sylament gives the records lane the query lane's semantics:
`($this->cachedTableRecords ?? $this->resolveTableRecords())[$key] ?? null` reads
a **warm** cache when one exists, and otherwise resolves fresh **without**
storing the result. The records-lane behaviour is wrong for the case the lookup
exists to serve. A record action resolves its record *before* it mutates
anything, so a cache filled by that resolution is a pre-mutation snapshot; the
re-render that follows the mutation reads that snapshot back and paints a deleted
row as though it were still there. Leaving the cache cold means the render
evaluates the data source itself, after the mutation, and sees it.

The price is one extra data-source evaluation per action round trip — the lookup
evaluates, the render evaluates again — which is exactly what Filament's query
lane pays for the same guarantee. It is also why
[`flushCachedTableRecords()`](../../../packages/tables/src/Concerns/HasTable.php)
is a separate member rather than the whole answer: the cold read covers an action
that resolves before anything has rendered, the flush covers one that mutates
*after* something already has. Permanent — this diverges from a Filament lane we
read as defective rather than from one we have yet to catch up with.

**The query lane has now landed and resolves fresh every time**, exactly as this
note predicted. `resolveQueryTableRecord()` is the whole of it: the branch is
taken on [`hasQuery()`](table.md#the-query-lane-is-on-exactly-while-no-records-closure-is-set),
before the cache is read at all, and what it runs is
`Table::getQuery(isResolvingRecord: true)` — the base query with its
[scopes](table.md#every-read-clones-a-memoised-base-query) applied under that
flag, so a scope can tell a row lookup from a render — narrowed by
[`IdentifierConstraint::equals()`](../schemas/record-key.md#the-operations-and-their-dql)
under `sylament_record_key`, which emits
`{rootAlias}.{identifierField} = :sylament_record_key` for a single identifier,
exactly as this lane always did, and one `= :sylament_record_key_<j>` per field
for a composite one. Every field name is read off the mapping metadata rather
than assumed to be `id`, and every value is **bound** — now typed by its field's
own DBAL type, which is the half of the guard that was missing: an untyped
`'abc'` against an integer column is a `500` on PostgreSQL, because a DBAL
`DriverException` is not an `HttpExceptionInterface` and
`PageController::__invoke()`'s render catch, which rethrows a Twig `RuntimeError`'s
previous exception only when it is an `HttpExceptionInterface`, does not convert
it. The other half is that
a key which is **malformed for the class** — the wrong arity, a non-canonical
escape, a component its field's type cannot hold — answers null *without running
a query at all*, which is what a row action, a column's own `action()` and an
editable cell now get from this method — its three call sites. Selection, the
reorder write and the by-key delete never call it: they narrow through
[`IdentifierConstraint::in()`](../schemas/record-key.md#parameter-naming-and-what-an-empty-or-malformed-key-means)
instead, where a malformed key is **dropped** from the list and a statement
still runs — as `1 = 0` once nothing well-formed is left. The panel's own
`{record}` binding does **not** come through here:
[`Resource::resolveRecordRouteBinding()`](../panels/README.md#a-record-is-resolved-through-the-resources-own-query)
narrows through `IdentifierConstraint::equals()` and reaches the same strict
decode by its own route, so `/admin/articles/abc/edit` is a 404 that runs no
statement rather than a 500. A bare
table names its three lanes, and anything that hydrates to a non-object (a
scalar or partial select) answers null.

Three things it deliberately does **not** apply, all Filament-faithful. No
**search**: a term typed into the search box must not make a row's own action
miss, so the resolution query carries no search term and a record the search
excluded still resolves. No **pagination**: nothing constrains the page, so a
record that left the current page between render and mount resolves anyway. And
no **sorting**, which would only cost an `ORDER BY` on a single-row read. The one
thing Filament's lane applies that this one applies too is **filters**, and they
joined it with the layer that gives them state:
[`applyFiltersToTableQuery($query, isResolvingRecord: true)`](#filtertablequery-applies-the-filters-then-the-search)
runs before the identifier narrowing, so a row action cannot reach a record
outside the filtered set. A fourth thing it does not apply — the row limit
Filament's `find()` carries — is a deviation rather than a faithful omission,
and has
[its own note](#record-resolution-applies-no-limit-1-where-filaments-find-does).

### Record resolution applies no `LIMIT 1` where Filament's `find()` does

Filament resolves with `$query->find($key)`, and Eloquent's `find()` is
`whereKey($id)->first()`, which is `limit(1)->get()->first()` — so its lane does
ship a row limit. `resolveQueryTableRecord()` ends at `getOneOrNullResult()` with
no `setMaxResults(1)`, which is worth stating because it reads as a missing line.

The limit is free in Eloquent because a `with()` eager load is a **second**
query, matched onto the parent models afterwards, so a `LIMIT 1` on the root
query cannot reach a relation. Doctrine's fetch join selects the relation in the
**same** query, so the limit truncates it: the root comes back holding one
element in a collection that reports itself complete — the row-multiplication
trap [the Tools paginator](#the-page-state-has-its-query-lane-and-no-session-or-url-half)
exists to survive on the page query, except that here it corrupts a single
resolved record rather than a page count, silently.

It buys nothing in exchange. The identifier predicate already admits at most one
root, and object hydration returns one entity per identity however many rows the
join produced, so `getOneOrNullResult()` cannot throw from row multiplication. A
query that somehow does return two roots raises Doctrine's
`NonUniqueResultException` — `AbstractQuery::getOneOrNullResult()` throws on a
result count above one — rather than silently answering with the first, which is
the failure we want.

Permanent. The fetch-join resolution case pins it: it asserts all three songs and
fails with "actual size 1 matches expected size 3" the moment the limit comes
back.

· 20 sharpened the *reason* without touching the code. Record resolution now
takes neither the [eager joins](#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone)
nor the aggregate batch, so a query written by a `query()` closure is the only
thing that can fetch-join this clone — which narrows the trap to the case the
test covers and widens the argument for keeping the limit off: the port cannot
know whether the user's own declaration fetch-joins, so the safe reading is that
it might.

### The all-records count answers four ways where Filament's answers two

Filament's `getAllTableRecordsCount()` is two lines: a `LengthAwarePaginator` in
`$cachedTableRecords` answers with its `total()`, and everything else is
`getFilteredTableQuery()->count()` — a call that assumes a builder is there and
fatals on a `records()` table, since that seam
[is not lane-gated](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder)
there either. Sylament's has four arms, and the two extra ones are the two shapes
Filament's method cannot meet: a lane that is not a query at all, and a carrier
whose total is legitimately unknown.

The order is what makes them readable. On the query lane a **cached carrier**
with a known total answers from itself — no second `COUNT` for a number already
paid for — and only that shape short-circuits: a rendered *unpaginated* table
caches a plain array, which carries no total to reuse, so it pays the count like
a cold one. Otherwise the answer is a `COUNT` over
[`getFilteredTableQuery()`](#filtertablequery-applies-the-filters-then-the-search)
through Doctrine's `Tools\Pagination\Paginator`, asked for the
`fetchJoinCollection` treatment on the same
[join heuristic](#the-page-state-has-its-query-lane-and-no-session-or-url-half)
`paginateTableQuery()` uses. That flag is **inert on this call** in the installed
ORM and is passed anyway: `Paginator::count()` never reads it — its count query
is built with `CountWalker::HINT_DISTINCT`, so a fetch-joined table counts
distinct roots either way — and only `getIterator()` branches on it. One
heuristic in one place is worth more than a call site that answers a question
nobody asks, and if the flag ever reaches the count path it will already be
right.

Since M7 · 2 the same call is fronted by one guard: a **composite** root — two
or more identifier fields — whose query carries its own output walker is
[refused by name](#an-output-walker-of-the-querys-own-is-refused-by-name-only-where-doctrines-fallback-cannot-serve-a-keyed-root)
here, as it is in front of the paginator's own total, because
`Paginator::count()` falls back to `CountWalker` there and that walker resolves
the root through one identifier column. A **single derived identity** is not
refused here: one association identifier is a path `CountWalker` does resolve,
and it counts such a table today. Nothing else on this arm moved either — a
keyed root with no such hint counts through `CountOutputWalker` exactly as it
always did, which is the composite-safe path the site ledger records as a
deliberate no-change.

Both filters and search are applied and pagination is not, which is exactly what
"all records" means here: the number a footer's *of N* renders under a filtered
page.

The records lane has no query to count, so the answer is whatever the closure
returned. A plain array counts itself. A carrier answers with its total. A
carrier with a **null** total — support's
[simple-pagination shape](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both)
— throws a `LogicException` naming the limitation, which is the honest answer
rather than a gap: a closure that deliberately skipped counting cannot be asked
for the count afterwards, and `0` or the page size would be a number the caller
would believe. A query-lane table in `Simple` mode is never in that position —
its carrier carries no total either, but the query is still there, so the
fall-through counts it.

Nothing in the package reads the method yet. Filament's two callers are its
`assertCountTableRecords()` test helper and panels' `ExposesTableToWidgets`,
which hands the number to a page's widgets; neither is ported. So it stays off
the [contract](#the-contract-is-thirty-five-methods-of-filaments-forty-six), which grows
only for members a shipped consumer calls, and it ships whole rather than in the
half the closure lane could have answered on its own.

### Eager loading runs over the visible columns, on the page clone alone

Two loops on the page read carry the relation arm's host half, and both differ
from Filament's in where they run.

**Eager loading** is a loop over
[`$table->getVisibleColumns()`](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again),
calling
[`applyEagerLoading()`](column.md#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality)
on each, placed **after** `getFilteredSortedTableQuery()` has returned and before
the query is paginated. Filament loops over the same set inside
`filterTableQuery()`, so the set is no longer a delta — one remains.

It read `getColumns()` until column visibility landed, and that was an absence
rather than a decision: with no `isHidden()` to ask, every column *was* the
visible set. Now a to-one column the developer hid, or the user toggled off,
costs no share of a `LEFT JOIN`. The
[global search loop](table.md#the-two-hidden-column-arms-landed-with-column-visibility-an-epic-after-their-loops)
is the last place still recording the old gap.

M4 · 6 put one gate in front of the whole loop: under
[`groupsOnly()`](grouping.md#the-php-gates-of-groupsonly-are-unconditional-where-the-templates-are-not)
it is skipped entirely, because no record row renders and nothing reads a cell.
Filament has the same skip in the same spirit and a different place — its loop
lives inside `filterTableQuery()`, so the placement is the recorded delta and the
gate is not.

The remaining delta is the **clone**, and it is deliberate. Filament's placement puts the
eager loads on the filtered query, which the count path also reads; ours puts
them one step later, so they reach the page query and nothing else. The count
clone therefore stays join-free — it counts what the search matched, and a join
added to fill cells has no business widening or narrowing it — and record
resolution, which builds its own clone through `getQuery(isResolvingRecord: true)`,
never passes this seam at all. A resolved record lazy-loads what a rendered row
fetch-joined, which is the honest trade: it is one row, and the alternative is
[a fetch join under a row limit](#record-resolution-applies-no-limit-1-where-filaments-find-does).
A test pins both halves by asserting the standalone count and the record
resolution each issue exactly one statement with no `LEFT JOIN` in it.

**The aggregate store** is filled by the second loop, `fillTableAggregateValues()`,
and it runs from `keyTableRecords()` — the one funnel every arm passes through,
paginated and `'all'` alike — because the batch needs the page's keys and those
only exist once the records are keyed. It is a no-op off the query lane (no
entity manager to batch against, and a `records()` closure that returns rows the
port never queried) and a no-op for a page with no keys.

Filament applies its aggregates on **record resolution** too. Ours does not, and
the reason is that nothing there would read them: a resolved record feeds a
row-scoped action, not a rendered cell, and the store is keyed per column for
cells. Revisit with bulk actions or an export surface, which are the two
consumers that would want a resolved record carrying its aggregates.

### The identifier tiebreaker is suppressed only by the qualified key

`defaultKeySort()` appends the root's identifier so a page boundary is stable
under ties, and it stands down when the query is already ordered by that key.
Filament tests that with `afterLast('.')` — an ordering *ending* in the same
field name suppresses it, whichever alias it hangs off — and · 12 ported the
heuristic as written, with the reasoning that a query ordering by a joined key
has almost always chosen its own tiebreaker. · 20 removed the loose half: the
comparison is now against the fully qualified `{rootAlias}.{identifierField}`
(or the bare field name, pre-resolution) and nothing else.

What changed is who can produce the ordering being tested. Under · 12 an
`artist.id` ordering could only come from a user who had written the join *and*
named that column, which is the case the heuristic was charitable about. The
relation arm makes the same ordering something the **table** produces on its own:
a user clicks a sortable `artist.id` column, the arm joins and orders by it, and
the loose comparison would then read the port's own join as the user's chosen
tiebreaker and drop the root key — leaving the page boundary unstable between
rows sharing an artist, which is precisely the failure `defaultKeySort()` exists
to prevent, and one that only shows up as rows repeating or vanishing across a
page change.

It restores Filament's *outcome* rather than departing from it, which is why this
counts as a tightening and not a behaviour change to migrate: their tiebreak scan
only ever meets orderings a user wrote, since their relation sort orders by a
correlated **subquery** object rather than by a dotted string — so a
Filament table sorted by a relation is never suppressed either. The narrow case
that does change is a user who hand-wrote `orderBy('artist.id')` and relied on the
root key dropping out; they now get one extra `ORDER BY` term, which cannot
change the row order they already had.

### `deselectAllTableRecords()` clears the server state, where Filament's only dispatches an event

Filament's body is one line — `$this->dispatch('deselectAllTableRecords')->self()`
— and the PHP props are left holding the old selection. The Alpine component
clears *its* sets and the emptied selection reaches the server on the next
request. So anything reading `$selectedTableRecords` later in the same request —
after the deselect-on-completion hook of a bulk action, say — sees a selection
that has just been cleared.

Ours writes the three props to their empty defaults and nulls the resolution
cache. There is no browser event and no listener, because there is nothing for
one to do: every call site is already inside a live request (the two `updated*`
tails, and an action's deselect-after-completion), so the response's re-render
carries the cleared props, the client store reinitialises from them, and the
table controller repaints from that. This is the epic's central architectural
divergence stated at its smallest: Filament's selection is client-authoritative
with the server reading a copy, ours is server-canonical with the client holding
a working copy.

### Select-all across pages is a state-writing live action, not an answered call

Filament's client, on the fallback select-all path, does
`this.selectedRecords = new Set(await $wire.getAllSelectableTableRecordKeys())` —
a Livewire method call whose **return value** the client stores. UX LiveComponent
has no such channel: a `#[LiveAction]` re-renders, it does not answer.

So the port inverts the direction. `getAllSelectableTableRecordKeys()` still
exists with Filament's four-arm body, but its caller is a
`#[LiveAction] selectAllTableRecords()` on the same trait, which enumerates the
keys and writes them into `$selectedTableRecordKeys` itself. The cost is
identical — one round trip either way — and the props stay canonical, which is the
[whole selection architecture](#the-selection-is-server-canonical-and-re-derived-on-every-read).
The action must end in an ordinary re-render for the client to see the result;
short-circuiting the response would leave the checkboxes showing the old state.

The cheap select-all path does not come here at all. With deselect-tracking
available (the default) the client flips `isTrackingDeselectedTableRecords` and
empties both lists through the value store — three deferred writes, **no request**
— so "all 999 999 selected" costs nothing and no key list ever crosses the wire.
This action is the fallback for the two cases tracking is off in:
`trackDeselectedRecords(false)` (or `deselectAllRecordsWhenFiltered(false)`,
which [forces it](table.md#one-filter-setting-drives-three-selection-behaviours)),
and `selectCurrentPageOnly()`, where the enumeration is the page's keys anyway.

One Filament caution ports with it: that enumeration has **no limit and no
chunking**. On a filtered set of a million rows it returns a million strings and
puts them all in a prop.

### The editable seam is server-canonical too, and the cell controller repaints

The column catalog's write-back is the same inversion one tier down, and it is
worth naming beside select-all because the two are the toolkit's only places
where a client control's value and the server's disagree mid-request.
`updateTableColumnState()` is a `#[LiveAction]` returning `void`: the browser
posts the control's new value, the host validates it, writes it onto the record
and re-renders, and the re-render is the whole reply — a refusal reaches the
screen as a `<p class="sy-ta-cell-error">` the same request paints, from a bag
that is a plain property rather than a `LiveProp`, so it lives for exactly that
one response ([has-columns.md](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client)).
Filament holds that error in Alpine on the client instead, and re-renders
nothing.

The one thing a re-render cannot do here is correct the control itself. UX pins
every input the user has touched through every morph, so the authoritative value
rides the cell **root** — a `div` the tracker ignores — and the
`sylament--tables--editable-cell` controller writes it onto the control after
each render. That is the same mechanism the selection checkboxes use, for the
same reason: after the first morph the client state, not the server-rendered
attribute, is what the DOM shows unless something puts the server's answer back
([has-columns.md](has-columns.md#the-cell-repaints-from-the-root-because-a-touched-control-is-pinned-through-every-morph)).

### The filter bags are arrays, never null, and carry no getters

Filament types both filter props `?array` and defaults them to `null`
(`public ?array $tableFilters = null;`), and its boot hook deliberately writes
`null` back when the bag is empty. Ours are `array` with an `[]` default, both
of them.

The reason is the element on the other end. The filters form binds a field to a
model path like `tableFilters.status.value`, and the client resolves that path
against the props payload on both reads *and* writes — `ValueStore.set()` calls
`get()` first to compare, and `get()` falls through to `getDeepData()` unless a
flat key of that exact dotted name is already sitting in the dirty, pending or
server-sent props. On the render that matters — the first one, where the server
sent the bag under `tableFilters` and nothing has been written yet — nothing is.
`getDeepData()` guards `undefined` and only
`undefined`; the walk beneath it is a bare
`currentLevelData = currentLevelData[parts[i]]` loop. So a `null` parent is not
an empty object to descend into — it is `null['status']`, an uncaught
`TypeError` in the browser, with nothing on the server to catch or report it.
The symptom is a field that silently stops working, not an error anyone can
trace. `[]` walks.

Filament gets away with `null` because Livewire's `data_get`/`data_set` pair
tolerates it on both sides; ux-live-component has no such layer, so this is the
ordinary Eloquent-to-Symfony shape of that difference rather than a judgment
about which default reads better. The one thing Filament's `null` bought — a
tri-state where *"never set"* differs from *"set to nothing"* — is only load-bearing
for the session and query-string lanes deciding whether to seed from a stored bag,
and [neither is ported](#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it).

The second half is a rule rather than a type. **There is no `getTableFilters()`,
and there must never be one**, for either bag. A `LiveProp` is de/hydrated
through Symfony's `PropertyAccessor`, which prefers a same-named getter over the
property — so a `getTableFilters()` would become what goes on the wire, and any
value it returned that differs from the raw property leaves every element bound
under that path permanently dirty, re-firing its update hook on every request.
The same mechanism is [load-bearing one way and a defect the other](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary)
for `$tableHostId` and the two writable props above; the bags are the case where
the answer is simply not to write the getter. Reading a slice is what consumers
want anyway, and that is what
[`getTableFilterState()`](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk)
is for.

### One filter slice is a plain array read, not a `PropertyAccessor` walk

Filament reads a slice with `Arr::get($this->tableFilters, $this->parseTableFilterName($name))`,
a dotted-path accessor. Ours is `$this->tableFilters[$name] ?? null` — a single
array read — and then checks the result is an array before returning it.

The path lookup buys nothing here, because a filter name can never be dotted. It
is the key the table files the filter under *and* the state-path segment its
`Group` binds to, so a dot in one would split the other; the accessor is for
schema state paths resolved against the host object, which is a different job
done by a different layer. What the plain read costs is the `null` a missing key
would have produced anyway, so the arms match.

The shape check is the part that is not just a simplification. Both bags
are **writable** `LiveProp`s, which ux-live-component exempts from the prop
checksum by design — the client can put anything under any key, and a forged or
merely stale payload can leave a string where a filter's state map should be.
Validating on read means the filter layer receives either a state map or
`null`, never a scalar it will index into; the same posture the per-page getter
takes, for the same reason. The check is **field-keyed**, not merely
`is_array()`: a slice whose keys are not all strings is a list the client put
where a map belongs, which is not a state map either, so it reads as `null` too.
That is what the declared `array<string, mixed>|null` return has always claimed
and what the prose above it has always said; the `is_array()` half of the check
went unchallenged for as long as
[nothing composed the trait](#a-host-trait-is-analysed-only-through-a-class-that-composes-it).
A slice that reads as `null` reaches a filter as `[]`, through the same `?? []`
a missing key already took. `parseTableFilterName()` carries a smaller version of
it: Filament's returns `$name::getDefaultName()` unguarded against a `string`
return type, so a `BaseFilter` subclass that never declared a default name is a
`TypeError` there, where ours falls back to the class-string with `?? $name`.

### A seeded filter bag fills the form instead of the declared defaults

`mountTableFilters()` branches on whether the bag arrived with something in it, and
the branch is Filament's exactly. An **empty** bag reaches `fill()` as `null`, which
runs `hydrateState(applyDefaults: true)` and seeds every filter's declared
default. A **non-empty** one — seeded from the query string, read back from the
session, or handed in by a parent passing `tableFilters` — is normalised and then
passed as `fill($seededFilters)`, which runs `hydrateState(applyDefaults: false)`,
so the incoming bag is written as raw state and the schema's own defaults are
skipped whole. Filament reaches the same two arms from the other side: it nulls an
empty bag first (`if (! count($this->tableFilters ?? [])) { $this->tableFilters = null; }`)
and `Schema::fill(null)` is what applies its defaults.

The consequence is a **partial** seed dropping its siblings' defaults, and it is
Filament-identical rather than a port artefact. A table declaring a `featured`
filter and a `plays` filter defaulted to `'100'` answers `?filters[featured][value]=null`
with `plays` at null, not at `'100'`: one named filter in the URL turns the whole
defaults pass off. A user who wants both writes both into the URL, which is what
the URL the table itself writes back always carries.

Normalisation is the second half. A query string carries no types, so
`normalizeTableFilterValuesFromQueryString()` walks the bag recursively and maps the
three scalars that arrive as their own names — `'null'` → null, `'false'` → false,
`'true'` → true — before the fill, because a `TernaryFilter` reading the **string**
`'false'` would take its true arm. Filament runs the same pass, in the same place,
for the same reason (its `filamentphp/filament#7999` comment). One thing follows
that is easy to miss: the pass runs on the bag whatever filled it, so a
**session-restored** bag is normalised too — a text filter the session stored as
the literal string `'true'` comes back typed `true`. That is Filament's behaviour as
well, and it is the reason a session value round-trips differently from the value
that was written.

Two smaller notes on the same hook. The filter bag has a query-string seed of its
own, a `#[PostMount(priority: 20)]` above both the session hook and the fill,
because UX refuses to seed this one prop: it assigns a seeded value only when the
prop's resolved type accepts it, and `property-info` reads `removeTableFilter()` as
a *remover* for a `tableFilter` singular, inferring `list<string>` for the bag —
which no filter state ever is. That seed reads `->query->all()` off the **main**
request only, so a `UrlMapping(mapPath: true)` on filters would bind nothing: a
path-mapped filter bag is not a shape this port reads.

That hook branches on the parameter's **presence**, not on its value, and the
difference is load-bearing. An absent `?filters` is a no-op, so the session is free
to seed the bag afterwards. A **present but malformed** one — `?filters[]=abc`,
where the bag is a list rather than filter-name-keyed — is not declined and left
alone but assigned `[]`, because UX has *already* written the parameter onto the
prop by the time this runs: returning would leave a list in a filter-name-keyed
property, which then reads as a non-empty bag and suppresses every filter's
declared default. Emptying it puts the mount back on the
`fill(null)` arm above, so a hand-edited URL costs the user their filter *values*
and not the table's own defaults. And `Concerns\HasFilters`
now requires `CanPersistTableState` to be composed beside it — it calls
`getTableStateMainRequest()`, `isPersistedTableFilterBag()` and
`persistTableFiltersInSession()` — which `HasTable` does, so the coupling only
matters to a host assembling the concerns by hand.

### The live-mode filter hook is a per-field `afterStateUpdated`, not the prop's `onUpdated`

Livewire calls `updatedTableFilters()` for a write to any path *inside* the bag
— `SupportLifecycleHooks::update()` derives the hook name from the dotted path's
head (`studly()->before('.')`) and hands the remainder over as a second argument
— so Filament's live mode gets its page reset from that hook alone.
Ux-live-component's identity `onUpdated` fires only for a write to the whole
prop — [the mechanism is in Semantics](#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it),
and its array form takes compile-time path strings where ours are the user's
filter names — so the same hook covers strictly less here and live mode needs a
second path.

That path is the schema: the non-deferred branch of `getTableFiltersForm()`
attaches `afterStateUpdated(fn () => $this->handleTableFilterUpdates())` to
every `Field` in the form, and `HasSchemas::callAfterSchemaStateUpdated()` — the
`#[PostHydrate(priority: -10)]` pass that diffs `$oldState` against the hydrated
state — fires them for exactly the fields that changed. The filters form is in
`getCachedSchemas()` by then because
[the re-cache hook runs at priority `10`](../schemas/has-schemas.md). Deferred
mode attaches nothing, since a draft edit is meant not to touch the query; the
pinning cases assert both halves.

`updatedTableFilters()` is kept rather than deleted: it is the whole-bag write
path — a server-side assignment, a test, a `data-model="tableFilters"` binding —
and it is what mirrors an applied write back into the deferred bag.

### A full seed replaces Filament's reserved-key list, and a blank is kept

Filament seeds `$tableColumnSearches` with **26** names only — `at`, `concat`,
`constructor`, `entries`, `length`, `map`, `sort`, `values` and the rest of
`getReservedTableColumnSearchKeys()` — because Alpine reads the bag through a
JavaScript proxy, where a column named `length` would resolve to the array's own
property rather than to the entry. Every other name is left out on the reasoning
its own comment states: *other names already read back as an empty search from a
JSON array*. And on a write it **forgets** a blanked key (`Arr::forget`) unless
the key is reserved, in which case it is reset to `''`.

Neither half ports. UX's client throws `Invalid model name` for a `data-model`
path the published props do not carry, so a key that is missing — or one that was
forgotten on the previous keystroke — is not an empty read but a thrown error on
the next one. `mountTableColumnSearches()` therefore seeds **every**
individually searchable column, `''` by default and a dotted name **nested**
(`artist.name` lands as `['artist' => ['name' => '']]`), and
`resetTableColumnSearch()` writes `''` where Filament would unset. That write is
the one place the divergence costs something, so it is gated: Filament's
`Arr::forget` is destructive only and cannot invent a key, while an always-write
takes a **client-supplied** column name and would add one permanently — a merely
global column's name accumulating in the bag, the session and the wire, and a
dotted one (`title.x`) replacing a real leaf with a map. So the action resolves
the name against the table first and returns silently unless the column is
individually searchable, which is exactly the seed's own predicate: the bag's key
set can only ever be the seed's. The seed runs
again inside
[the post-hydrate hook](#the-per-column-change-is-read-off-a-first-call-hydration-snapshot),
so a column that *became* individually searchable between two requests has its
key before the render that binds an input to it.

The reserved list is then unnecessary rather than merely unported: the bag holds
a key for every searchable column whenever any column is searchable, so it never
serialises as a JSON array in the first place, which is the collision the list
exists to prevent. A fixture column named `entries` — on Filament's list, and
unlike `length` neither a SQL keyword nor a DQL function, so a `Song` field can
carry it — pins that it round-trips like any other name.

Filament's own limit is inherited unchanged: a column named `author` cannot be
individually searchable beside one named `author.name`, because the nesting
collides — the second write turns the first's string into an array, or the other
way about, depending on order.

### The per-column change is read off a first-call hydration snapshot

A `LiveProp`'s `onUpdated` hook fires for a write to the **property**, never for
a write to a path inside it —
[the same fact the filter bag records](#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it)
— and every per-column term is exactly such a nested write. Filament has the
opposite problem and no work to do: Livewire's `updatedTableColumnSearches()`
receives the key that changed.

So the change is read off the bag itself. `hydrateTableColumnSearches()` keeps
the value it was handed **on its first call only** (`??=`), which is the bag as
it arrived, before any update the request carries; `afterTableColumnSearchesHydrated()`
— `#[PostHydrate(priority: -5)]`, below the panel stamp every panels page runs
at `20` and above schemas' state pass at `-10` — compares the hydrated bag against it and runs
`handleTableColumnSearchUpdates()` only when they differ. Both sides are seeded
and cast through the same two helpers before the compare, so a column that became
searchable since the last request (a blank on both sides) and a leaf that arrived
as a number rather than a string (`100` against `'100'`) are both *not* an edit.
The handler is the same choreography the global term's `onUpdated` runs —
deselect if the table says so, page back to `1`, flush the record cache, write
the session — so a per-column keystroke and a global one are indistinguishable
downstream.

### An output walker of the query's own is refused by name only where Doctrine's fallback cannot serve a keyed root

Doctrine falls back to tree walkers the moment a query sets
`HINT_CUSTOM_OUTPUT_WALKER` itself — `LimitSubqueryWalker` for a page,
`CountWalker` for a total — and both resolve the root through
`getSingleIdentifierFieldName()`. Two reads cannot survive that, and each has
a predicate of its own. A **keyed** root — two or more identifier fields, or
one that is an association — whose query *also joins a collection* is read in
two steps, and the first of them needs Doctrine's own limit walker: the user's
would be silently dropped from exactly one of the two statements. Turning
output walkers off is not an escape either: `LimitSubqueryWalker` calls that
same method and then refuses a foreign-key identifier **separately**, with a
`RuntimeException` telling the caller to turn the output walkers back on. The
other read is a **composite** root's total, which is `CountWalker`, and which
answers `getSingleIdentifierFieldName()` with a raw `MappingException` from
inside a getter mid-render.

So those two shapes are refused, with a `LogicException` naming the table, the
record class and the walker, at the three places that meet them: inside the
private window read both paginator sites share, in the branch that takes the
two-step route; in front of the total `paginateTableQuery()` reads for itself;
and in
[`getAllTableRecordsCount()`](#the-all-records-count-answers-four-ways-where-filaments-answers-two).
The first site asks whether the root is keyed, the other two whether it is
composite, and that difference is the whole of the design — the guard takes the
record class its caller resolved rather than resolving one for itself, because
only the caller knows which question it is asking. The hint is read off the
**built** `Query` rather than off the builder, because
`Configuration::setDefaultQueryHints()` reaches only the former — an
application-wide default walker is precisely the shape that would otherwise
surface as a mapping error nobody could place.

What the refusal leaves alone is longer than what it refuses, and deliberately
so. A **single-scalar-identifier** table is untouched — both predicates answer
null for it, so the guard returns before it can fire and the DQL is what it
always was. A keyed root that joins **no** collection reads its page through a
plain windowed query, which honours the user's walker, in default and simple
mode alike; only a **total** behind that window can still refuse. A **single
derived identity** — one association as the whole key — totals through
`CountWalker`, which does resolve an association identifier as a path, so it
is untouched wherever it is counted. Simple mode's page asks for no total at
all. What that leaves is one shape the refusal does reach with no join in the
query: a **composite** root's total — `paginateTableQuery()`'s in default
mode, where the window is read first and the refusal then throws in front of
the total, so no page reaches the caller, and
`getAllTableRecordsCount()`'s, which asks the same question whether the table
pages or not. And the `recordsPerPage: 'all'` **page** is not refused, because
it builds no Doctrine paginator: it resolves the query outright and counts what
came back into support's own carrier, so there is nothing for a user's walker
to collide with. A **cold** `getAllTableRecordsCount()` over that same
composite root still is — it is the count that refuses, not the page, and under
`'all'` a warm one answers from the carrier the page already filled. An earlier, wider
draft of this guard fired for every keyed root and took three of those shapes
down with it, which is why it is drawn exactly at the collision and nowhere
wider.

### A single-scalar-identifier fetch-joined page ordered by a to-one association is Doctrine's limit, and stays one

The order rewrite the
[two-step read](#a-keyed-root-that-joins-a-collection-pages-in-two-statements-because-doctrines-walkers-key-by-one-column)
applies is not applied to the lane that read does not own. A table whose root
has one **scalar** identifier, whose query fetch-joins a collection and whose
order names a to-one association path, meets the same
`LimitSubqueryOutputWalker` rewrite and the same driver error, on every
platform outside that walker's `ROW_NUMBER` branch — SQLite here, MySQL and
MariaDB in production, since no installed DBAL platform defines
`supportsRowNumberFunction()`. Ordinary API reaches it — a column named after
a **bare** to-one association and made `sortable()`, a `defaultSort('artist')`
or a group on that name, all of which resolve to `<root>.<assoc>`, because
only a *dotted* path ending in an association is refused and a dotless name
never reaches the mapping walk at all. What does not reach it is the
identifier tiebreak, which on a single-scalar root orders by a field. It is
Doctrine's,
it predates this milestone, and M7 · 2 did not fix it: routing
single-identifier pages through the keyed read would close it and was rejected,
because a proven lane that nothing asked us to move is worth more than the
closure. A table meeting all three conditions joins the target and orders by
its identifier field instead, or drops the fetch join.

_See also: the global [rename table](../renames.md) — the interface⇄trait label
swap (`InteractsWithTable` trait → `HasTable` trait, `HasTable` interface →
`InteractsWithTable` interface) is recorded there._
