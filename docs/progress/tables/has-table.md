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

`HasTable` itself is seventeen methods wide: `table()`, `getTable()`,
`makeTable()`, the three filtered-query seams `getFilteredTableQuery()`,
`filterTableQuery()` and `getFilteredSortedTableQuery()`, `getTableRecords()`,
the `resolveTableRecords()` half it caches and the `keyTableRecords()` half that
re-keys, `getTablePaginator()`, `getTableRecordKey()` with its
`resolveQueryRecordKey()` helper that reads an entity's identity off the mapping
metadata, `getTableRecord()` with its `resolveQueryTableRecord()` helper that
re-queries for a single one, `getAllTableRecordsCount()`,
`flushCachedTableRecords()` and `getTableHostId()`.
It builds the table once per request, resolves its data source once per
request — a `records()` closure, or
the Doctrine query those three seams assemble — and hands back an array **keyed
by record key** — or a [`Paginator`](../support/pagination.md) carrying such an
array — plus a by-key lookup that reads fresh whenever the render cache is cold
and, on the query lane, every single time, the count of everything behind that
page, the flush that empties the cache, and the per-host id every row and cell is
stamped with.

Around that core it composes four host concerns, three of them landed by the
table upgrades epic:
[`CanPaginateRecords`](../../../packages/tables/src/Concerns/CanPaginateRecords.php)
(the page and per-page state plus the `gotoTablePage()` live action),
[`CanSortRecords`](../../../packages/tables/src/Concerns/CanSortRecords.php)
(the `column:direction` string and the `sortTable()` cycle) and
[`CanSearchRecords`](../../../packages/tables/src/Concerns/CanSearchRecords.php)
(the search term) — plus
[`HasFilters`](../../../packages/tables/src/Concerns/HasFilters.php) (the two
filter bags, the filters form and the five filter live actions), from the filter
layer. Each is a per-file row below rather than a folded one, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). What
Filament's nineteen-trait host does beyond them — bulk actions, selection,
reordering, the column manager, and the whole query-lane half of the three
above — is unported and listed below.

The trait presumes no host property beyond the seven `#[LiveProp]`s it and its
concerns declare: `?string $tableHostId` (the persisted half of
[`getTableHostId()`](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id)),
`int $tablePage`, `?string $tableSort`, and the four **writable** props the
chrome binds to — `?string $tableRecordsPerPage`, `?string $tableSearch` and the
two [filter bags](#the-filter-bags-are-arrays-never-null-and-carry-no-getters),
`array $tableFilters` and `array $tableDeferredFilters`.
Writability is the security line and it is
[deliberate on each prop](#per-page-is-validated-in-the-getter-on-every-read):
page and sort move only through a `#[LiveAction]`, so they stay checksummed,
while the four a `data-model` writes are re-validated on every read — and the
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
- [`tables/src/Contracts/HasTable.php`](../../../vendor/filament/tables/src/Contracts/HasTable.php)
  — the 46-method interface this reduces to eleven

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
| [`getTableRecords()` — resolve the data source once, eager-load, re-key, fill the aggregate store, cache](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | in progress | — | [detail](#the-records-injections-stop-at-seven-of-filaments-eight) · [semantics](#a-records-closure-resolves-public-container-services-by-type) · [diverges](#the-missing-data-source-error-does-not-name-relationship) · [diverges](#gettablerecords-returns-a-keyed-array-or-a-paginator-never-a-collection) · [diverges](#eager-loading-runs-over-every-column-on-the-page-clone-alone) |
| [`getTablePaginator(): ?Paginator` — the carrier, unwrapped for the footer](../../../packages/tables/src/Concerns/HasTable.php) | — (Sylament-only; Filament's records **are** the paginator) | done | — | [pagination.md](../support/pagination.md) · [semantics](#the-paginator-is-a-return-value-not-a-state-holder) |
| [`InteractsWithTable` — eleven methods](../../../packages/tables/src/Contracts/InteractsWithTable.php) | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | in progress | — | [detail](#the-contract-is-eleven-methods-of-filaments-forty-six) |
| [`flushCachedTableRecords()`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::flushCachedTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | — |
| [`getTableHostId()` + the `$tableHostId` `LiveProp`](../../../packages/tables/src/Concerns/HasTable.php) | [`Livewire\Component::getId()`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) |
| [`getTableRecordKey(object\|array $record): string`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecordKey()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key) |
| [`getTableRecord(?string $key)` with its `resolveQueryTableRecord()` query arm](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecord()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#gettablerecord-reads-fresh-when-the-render-cache-is-cold) · [diverges](#record-resolution-applies-no-limit-1-where-filaments-find-does) |
| [`getAllTableRecordsCount(): int`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getAllTableRecordsCount()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#the-all-records-count-answers-four-ways-where-filaments-answers-two) |
| [The query lane — `getFilteredTableQuery()`, `filterTableQuery()`, `applyFiltersToTableQuery()`, `getFilteredSortedTableQuery()`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [semantics](#filtertablequery-applies-the-filters-then-the-search) · [diverges](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder) |
| `getTableQueryForExport()` | [`Concerns\HasRecords::getTableQueryForExport()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | blocked | the export surface | It bypasses the seams above, re-reading `getTable()->getQuery()` and re-applying filters, search and sorting itself, to assemble a query for Filament's export action — a surface with no counterpart here and on no milestone, the same one [`ExportColumn`](column.md#hascellstate-is-relocated-into-the-column-tier) waits on. It lands with that action or not at all |
| [`CanPaginateRecords` — `$tablePage`, `$tableRecordsPerPage`, `gotoTablePage()`, `getTablePage()`, `getTableRecordsPerPage()`, `dehydrateTableRecordsPerPage()`, `updatedTableRecordsPerPage()`, `paginateTableQuery()`](../../../packages/tables/src/Concerns/CanPaginateRecords.php) | [`Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Concerns/CanPaginateRecords.php) | in progress | — | [detail](#the-page-state-has-its-query-lane-and-no-session-or-url-half) · [diverges](#gototablepage-replaces-livewires-paginator-trio) · [diverges](#per-page-is-validated-in-the-getter-on-every-read) · [diverges](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) · [diverges](#the-recordsperpage-injection-is-nullable) · [diverges](#no-table-state-is-persisted-in-the-session) |
| [`CanSortRecords` — `$tableSort`, `sortTable()`, `getTableSortColumn()`, `getTableSortDirection()`, `applySortingToTableQuery()`](../../../packages/tables/src/Concerns/CanSortRecords.php) | [`Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Concerns/CanSortRecords.php) | in progress | — | [detail](#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) · [diverges](#sorttable-ignores-a-column-the-table-did-not-declare-sortable) · [diverges](#a-default-sort-closures-replacement-builder-reaches-the-caller-where-filaments-is-discarded) · [diverges](#the-identifier-tiebreaker-is-suppressed-only-by-the-qualified-key) · [semantics](#a-column-name-containing-a-colon-cannot-be-cycled) · [diverges](#no-table-state-is-persisted-in-the-session) |
| [`CanSearchRecords` — `$tableSearch`, `updatedTableSearch()`, `getTableSearch()`, `dehydrateTableSearch()`, `applySearchToTableQuery()`, `applyGlobalSearchToTableQuery()`, `extractTableSearchWords()`](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | in progress | — | [detail](#search-is-the-global-term-only) · [diverges](#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) · [diverges](#no-table-state-is-persisted-in-the-session) · [table.md](table.md#getsortablevisiblecolumn-has-no-hidden-arm-to-check) |
| Table state in the URL — `#[Url]`-style query-string binding for page, sort and search | [`getIdentifiedTableQueryStringPropertyNameFor()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | todo | — | [diverges](#no-table-state-is-bound-to-the-url) |
| Per-column search — `$tableColumnSearches`, `updatedTableColumnSearches()`, the reserved keys | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | todo | — | [detail](#search-is-the-global-term-only) |
| Deferred loading — `$isTableLoaded`, `loadTable()`, `isTableLoaded()` | [`CanDeferLoading`](../../../vendor/filament/tables/src/Concerns/CanDeferLoading.php) | todo | — | [table.md](table.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) |
| Host polling — `getTablePollingInterval()` | [`CanPollRecords`](../../../vendor/filament/tables/src/Concerns/CanPollRecords.php) | skipped | — | The whole trait is `@deprecated Override the table() method to configure the table.`, one host getter returning null, and the project has never ported a deprecated Filament surface. The interval it fronts is `Table::poll()`, a **todo** row in [table.md](table.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) |
| [`Concerns\HasFilters` — the `$tableFilters` / `$tableDeferredFilters` bags, `getTableFilterState()`, `getTableFilterFormState()`, `parseTableFilterName()`, `getTableFiltersForm()`, the `#[PostMount]` fill and the `#[PostHydrate(priority: 10)]` re-cache](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | in progress | — | [diverges](#the-filter-bags-are-arrays-never-null-and-carry-no-getters) · [diverges](#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) · [diverges](#the-mount-fill-applies-defaults-and-seeds-nothing-from-a-passed-bag) · [semantics](#updatedtablefilters-fires-for-a-whole-prop-write-never-for-a-field-inside-it) · [diverges](#no-table-state-is-persisted-in-the-session) · [semantics](#the-two-filter-bags-round-trip-differently-and-the-difference-is-the-whole-deferredlive-split) |
| [`applyTableFilters()`, `removeTableFilter()`, `removeTableFilters()`, `resetTableFiltersForm()`, `updatedTableFilters()`, `handleTableFilterUpdates()`](../../../packages/tables/src/Concerns/HasFilters.php) | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | done | — | [detail](#filter-mutations-reset-the-page-and-flush-the-record-cache) · [diverges](#the-live-mode-filter-hook-is-a-per-field-afterstateupdated-not-the-props-onupdated) |
| [`resetTableSearch()`](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords::resetTableSearch()`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | done | — | [detail](#filter-mutations-reset-the-page-and-flush-the-record-cache) |
| The deprecated `*TableAction` host shims — `mountTableAction()`, `callMountedTableAction()`, `getMountedTableActionRecord()`, `unmountTableAction()`, `getTableRecordAction()`, … | [`Concerns\HasActions`](../../../vendor/filament/tables/src/Concerns/HasActions.php), [`HasRecordAction`](../../../vendor/filament/tables/src/Concerns/HasRecordAction.php) | skipped | — | Every method in both files is a one-line deprecated delegate, and the project has never ported a deprecated Filament surface. What they front is built: the host resolves a table-context mount through actions' [`resolveTableAction()`](../actions/has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) and binds the record through `getTableRecord()` |
| Bulk actions and selection — `getSelectedTableRecords()`, `getAllSelectableTableRecordKeys()`, `deselectAllTableRecords()` | [`Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | blocked | table bulk actions | — |
| Grouping and summaries | [`CanGroupRecords`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php), [`CanSummarizeRecords`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | todo | — | — |
| Reordering, striping, the column manager, `HasContent`, `HasHeader` | [`CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) and four siblings | todo | — | — |
| Translatable content driver — `getActiveTableLocale()`, `makeFilamentTranslatableContentDriver()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | todo | — | [has-schemas.md](../schemas/has-schemas.md#the-translatable-content-driver-is-not-ported) |

The ten **done** rows are the build-once core, the record key, the by-key
lookup with its flush, the all-records count, the three filtered-query seams, the
host id, and the paginator peek. They are covered by
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
four cases of its own, none of them reachable by that convention: a `Book`, whose
identifier is `code` and which has
no `id` anywhere, keying by `sy-001`; a `CompositePair` query refusing loudly; and
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
`[false, true]`. Three narrower answers sit beside them: an unknown key coming
back null, a bare table naming both lanes, and a `CompositePair` query refusing
loudly on *resolution*, which is a different method from the keying refusal above
and is reached without it.

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
One more pins the composite-identifier guard: a `CompositePair` query composes no
`ORDER BY` at all rather than throwing on a single-identifier read.

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
collection of three songs on the first of them. Because a query-lane table is
paginated by default, the sorting and search suites reach their records through
`getRecords()` on the carrier rather than treating the return value as an array.

The `#[LiveProp]` and `#[LiveAction]` attributes carry no runtime behaviour a
render test would catch, so they are pinned by reflection in
[`HasTableLivePropsTest`](../../../packages/tables/tests/Unit/Concerns/HasTableLivePropsTest.php):
each of the four state props by writability, `onUpdated` hook name *and*
`dehydrateWith` bridge name, and each live action by its `#[LiveArg]`-marked
parameter names. Both names each prop carries are also asserted to **exist** as
methods, because the attribute holds them as strings: renaming the method without
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

### The `records()` injections stop at seven of Filament's eight

Filament evaluates the data source with eight named injections —
`columnSearches`, `filters`, `page`, `recordsPerPage`, `search`, `sort`,
`sortColumn`, `sortDirection` — each a closure reading the corresponding piece of
host state, so a userland `records()` closure can paginate, sort and filter its
own result set. Sylament passes **seven**, in the same shape (a `fn ()` per name,
so the state is read only if the closure asks for it): `filters`, `page`,
`recordsPerPage`, `search`, `sort`, `sortColumn` and `sortDirection`. Every one is
Filament's name, Filament's arity and Filament's value — `sort` is the same
`[$column, $direction]` pair, redundant with the two singular names and kept
because a migrating closure destructures it.

One name is still missing, and it is the same reason all eight were missing
before the table-upgrades epic: `columnSearches` waits on per-column search (its
**todo** row above) and arrives in the change that lands it. `filters` arrived
here, with the layer that gives it state — the raw applied `tableFilters` bag,
read lazily like every other name, so a closure-lane table filters its own data
exactly as it paginates and sorts it. The value is typed `array` where Filament
types it `?array`: our prop is
[an array and never null](#the-filter-bags-are-arrays-never-null-and-carry-no-getters),
so a migrating `fn (?array $filters)` still matches and a `fn (array $filters)`
needs no null guard. One other value differs rather than being absent:
`recordsPerPage` is [nullable here](#the-recordsperpage-injection-is-nullable)
where Filament types it `int|string`.

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
[`Table::isPaginated()`](table.md#ispaginated-has-no-groups-only-arm). Filament
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
[deliberate omission](#no-table-state-is-persisted-in-the-session). The
**query-string** page name (`getTablePaginationPageName()`, which feeds Livewire's
`WithPagination`) is the [URL binding](#no-table-state-is-bound-to-the-url) that
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
[`getSortableVisibleColumn()`](table.md#getsortablevisiblecolumn-has-no-hidden-arm-to-check)
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
by itself. It also no-ops rather than throwing when
the table's records carry a composite identifier, or when the builder names no
root entity to read metadata from — a query that cannot be keyed by one column
cannot be tiebroken by one either.

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

What is still missing from Filament's method is its two early returns.
`isTableReordering()` replaces every ordering with the reorder column, and
belongs to reordering (a **todo** row above); `isGroupsOnly()` skips sorting
entirely, and belongs to grouping (likewise). Each lands with the feature that
gives it meaning. On the records lane none of this runs at all: the `sortColumn` /
`sortDirection` injections are the whole application there, and the userland
closure orders its own data.

`updatedTableSort()` and `updatedTableSortDirection()` collapse into the body of
`sortTable()` here, because both exist to run the two things it already does —
persist to the session (a [deliberate omission](#no-table-state-is-persisted-in-the-session))
and reset the page. Filament needs them as separate members because Livewire
calls them for a *property write*; `$tableSort` is not writable here, so nothing
else can change it. The three `@deprecated` host getters are not ported.

### Search is the global term only

`$tableSearch`, its trimmed getter, and the query application over it are ported.
Filament's trait carries a second, larger half — `$tableColumnSearches`,
`updatedTableColumnSearches()`, `getReservedTableColumnSearchKeys()` and
`applyColumnSearchesToTableQuery()` — which is **per-column search**, a feature
with a UI of its own (an input in each header cell) rather than a variant of this
one. It has its own **todo** row above, it is what the missing `columnSearches`
injection reads, and it is why `applySearchToTableQuery()` exists as a one-line
delegate to `applyGlobalSearchToTableQuery()` rather than being collapsed into
it: the column half becomes a second call inside it — ahead of the global one,
the order Filament applies them in.

The global half composes like this. A blank term is a no-op — `getTableSearch()`
already answers null for it, so nothing is applied and the query is returned as
it came. A table with a `searchUsing()` closure hands it the builder and the term
and stops there, columns and extra columns alike unread; that closure is the
whole assembly. `callSearchUsing()` returns `void`, Filament-exact: the closure
constrains the builder it was handed, and a *replacement* builder returned from
it is discarded — unlike
[a `defaultSort()` closure](table.md#the-default-sort-is-a-secondary-order-and-its-direction-rules-the-tiebreaker),
whose returned builder wins. Otherwise the term is broken into **words** —
`splitSearchTerms()` is on by default, and `extractTableSearchWords()` normalises
the Unicode blanks Filament names (`\x{3164}`, `\x{1160}`) to spaces before
`str_getcsv()` splits on them, so a `"quoted phrase"` survives as one word — and
each word gets **its own `Orx`**: every globally searchable column applies
[itself](column.md#a-search-closure-returns-its-expression-where-filaments-mutates-an-orwhere-group)
into it, the table's
[extra searchable columns](table.md#extra-searchable-columns-take-closures-that-return-their-expression)
follow, and the group is `andWhere()`-d only if something joined it. So the
composition is `OR` within a word and `AND` between words — *every* word must
match *something* — and a table with nothing searchable adds no `WHERE` at all
rather than an empty parenthesis or a false predicate. Filament's loop also skips
a **hidden** column first; the port has no visibility to check and drops that arm
[with its sorting sibling](table.md#getsortablevisiblecolumn-has-no-hidden-arm-to-check).

Two members of Filament's do not port. The `generate_search_term_expression()` /
`generate_search_column_expression()` pair is Eloquent-and-driver-specific
assembly, replaced by
[`SearchConstraint::like()`](../../../packages/tables/src/Query/SearchConstraint.php)
and its [narrower reach](table.md#the-default-search-comparison-assumes-a-string-typed-field).
Of the search **indicators**, the global one ships —
`getTableSearchIndicator()` mints the `Filters\Indicator` chip for the current
term, labelled `Search: <term>` (Filament's
`filament-tables::table.fields.search.indicator` at its English value,
[hardcoded like the rest of the copy](table.md#the-empty-state-heading-hardcodes-no-records)),
and is the [tenth contract method](#the-contract-is-eleven-methods-of-filaments-forty-six).
[`Table::getFilterIndicators()`](indicator.md#removal-is-structured-not-a-click-handler-string)
pairs it with the `resetTableSearch` live action and puts it first in
[the row](table.md#the-chip-is-one-badge-and-the-delete-affordance-is-supports),
so the chip's X clears the term and resets the page.
`getTableColumnSearchIndicators()` still waits — on per-column search rather than
on the indicator class, the same **todo** row above — and that same
`getFilterIndicators()` is where its half slots in when it lands, second, ahead
of the filters' own chips. On the records lane none of this runs:
the `search` injection hands the closure a string and the closure decides what
matching means.

### The contract is eleven methods of Filament's forty-six

`Contracts\HasTable` declares 46 methods — the full host API a Filament table,
column, filter, action, or test helper may call back into. Sylament's
`InteractsWithTable` declares the eleven the shipped surface actually calls:
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

Filament's bool arm consults `hasNullableBooleanState()` first and writes `null`
instead of `false` when it answers true — which `Radio`, `ToggleButtons` **and
`Select`** all do. That predicate is not ported, and the resulting one-arm
difference is out of reach of the fields the shipped filters build for
themselves: the fallback runs only for a field the filter's
`getResetState()` does not name, and `SelectFilter` seeds `['value' => null]`
in `setUp()` — as Filament's does, at `SelectFilter.php:148` — so every
`SelectFilter` and every `TernaryFilter` takes the explicit-null left side and
clears rather than flipping to its false arm. Our `Checkbox`/`Toggle` are the
other side of it: both cast through a non-nullable `BooleanStateCast`, where
`false` genuinely is the resting value.

What is left is a hand-built nullable boolean inside a custom filter schema —
`Filter::make('x')->schema([Select::make('f')->boolean()])` — which declares no
reset state, so Filament clears it to `null` where we write `false`. A
**shipped** filter given the same schema is exposed identically: `SelectFilter`'s
seeded `['value' => null]` does not name `f` either, so it is the custom schema
rather than the filter class that reaches the arm. Its cast stores that as `0`
(`false` is not `blank()`, so the nullable arm does not catch it), and a
ternary-shaped query arm reads `0` as an active *false* filter
rather than as cleared. Pinned as characterization rather than fixed, since the
line that closes it is a `resetState(['f' => null])` on that filter. Port the
predicate onto `Field`/`Select` the moment a shipped filter can reach this
fallback with a nullable boolean.

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
first, then [the global search](#search-is-the-global-term-only), Filament's
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

Two of Filament's applications are still missing from these seams, and each is a
body inside a seam rather than a new call site: **per-column** search slots into
`applySearchToTableQuery()` ahead of the global half, and grouping waits on a
layer that does not exist here at all. Both have a **todo** row above. That is
also why the seams were written public and whole before the layers that fill
them: anything needing the lane can ask for it without waiting for its own half.

Filament runs two more inside this seam that this port deliberately runs
elsewhere. Its column loop calls `applyRelationshipAggregates()` and
`applyEagerLoading()` here, so both land on the filtered query — which is the
same clone the **count** takes. Here the aggregate value never touches a query
clone at all ([why](table.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)),
and eager loading runs one step later, on the
[page clone alone](#eager-loading-runs-over-every-column-on-the-page-clone-alone),
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
it. Two consequences when extending this trait or writing another host one. A
guard written against `mixed` is checked only in the contexts a using class
supplies, so a fixture host in this package's tests is worth as much to the
analyser as a shipped page is. And a scoped run over the package that *owns* the
trait cannot see the errors that exist only in a using class's context, which is
why the whole set arrived at once in a lane that had touched neither package —
only the unscoped `composer test:static` sees them.

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

### No table state is persisted in the session

Filament persists three pieces of table state in the Laravel session, keyed by
`md5(static::class)`: the sort (`persistsSortInSession()`, opt-in), the search
and column searches (`persistSearchInSession()`, opt-in), and the per-page
option — that last one **unconditionally**, with no setter to turn it off.
Sylament persists none of them, and ships no `persist*InSession()` setters.

The unconditional one is the load-bearing difference: a Filament user who picks
"50 per page" on one table gets 50 back on their next visit, and Sylament's user
gets the table's default. That is the behaviour to weigh, not the missing
setters. It is left out because session persistence is a decision about *where
table state lives* that this epic could not make in isolation — the same question
the [URL binding](#no-table-state-is-bound-to-the-url) asks, and the two answers
have to agree, since Filament's own precedence (session seeds the property, the
query string then overrides it) only makes sense once both exist. Persisting to
the session also needs a stable per-table key: `md5(static::class)` collides for
two instances of one component class on a page, which is exactly the case the
[host id](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id)
exists for and the host id cannot serve, being regenerated per component
instance rather than per *table identity*.

Provisional, and it is a panels-shaped decision: a panel knows a table's place in
an application and can name it. Until then a host that wants persistence writes
it in its own `mount()`.

### No table state is bound to the URL

Filament binds page, sort, search and filters to the query string through
Livewire's `#[Url]` attribute, disambiguated per table by
`getIdentifiedTableQueryStringPropertyNameFor()` and the table's
`queryStringIdentifier()`. So a Filament table survives a refresh, a bookmark and
a shared link. Sylament's does not: a refresh resets to page 1, unsorted,
unsearched.

UX LiveComponent has `#[LiveProp(url: true)]`, so the mechanism exists — what is
missing is the disambiguation. The attribute takes a **static** binding: the
query-string key is fixed at the property, so two tables on one page bind page to
the same `?tablePage` and fight over it, and Filament's answer (a runtime-computed
name) has no expression in an attribute. Shipping the binding anyway would make
the two-table page silently wrong, which is worse than not having it, and the
tables package has no vocabulary for naming a table within an application —
[`HasQueryStringIdentifier`](table.md) is a **todo** row for exactly that reason.

Provisional, and it reverses with the same panels-level decision as the session
half above. The state itself is already shaped for it: page, sort and search are
each a single scalar prop, which is what a URL binding needs.

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
book.code asc` for a `Book` builder returned from a `Song` table's default sort).

Permanent, and it closes on its own if Filament ever consumes the return — the
two agree again with no change here. It is the third arm of this family the port
declines to carry as written, beside
[`searchable()`'s closure condition](column.md#a-closure-condition-on-searchable-is-evaluated-not-filed-as-a-search-column)
and [`applySort()`'s reversal](column.md#sort-columns-apply-in-declared-order-where-filament-reverses-them).

### The missing-data-source error does not name `relationship()`

Filament throws `"Table [{$livewireClass}] must have a [query()], [relationship()],
or [records()]."` — three ways to give a table data, and the message lists all
three so the reader can pick. Sylament's is
`"Table [%s] must have [records()] or [query()]."`: it names the two lanes that
exist and leaves out the third, which travels with
[relation managers](table.md#the-relation-scoped-table-arm-travels-with-relation-managers).
Naming an API the package does not ship would send a reader
looking for a method they cannot call — which is why the message grew its
`query()` half in the change that landed the lane rather than ahead of it, and why
it grows `relationship()` the same way. Provisional, and mechanically so.

The [relation arm](../roadmap.md#pulled-forward) is what makes that worth
restating rather than repeating: relations reached the tables package in · 20 and
this message did **not** change, because what · 20 shipped is relation-aware
*column paths* — a name that reads, sorts and searches through an association on
a table whose data source is still its own `query()`. The third lane is a
different feature, and the message grows only with it.

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

Two things about that guard. It asks the **table** what its record class is and
tests the record with `instanceof`, rather than asking Doctrine whether the
record's own class is mapped, because the obvious API cannot answer:
`AbstractClassMetadataFactory::isTransient()` returns `false` for an anonymous
class while `getMetadataFor()` **throws** `MappingException` for one, so an
anonymous DTO — the plainest transient object there is — would pass the transient
check and then explode on the metadata read. Reading the declared class also
means an anonymous *subclass* of an entity (a test double) keys by its parent's
mapping instead of blowing up. And the arm adds three refusals of its own, all
`LogicException`: a **composite** identifier, which a single string key cannot
address; an identifier the entity carries **none** of, which is what
`getIdentifierValues()` answers for an entity that was never persisted or
assigned one; and one that is set but empty or unprintable. None is recoverable —
the table would otherwise index rows under a key no action can resolve.

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
`recordKey` for a row-scoped action — so on a query-lane table over an entity
whose identifier is *not* `id`, the rows carry mapping keys while a record action
on one of them carries none and mounts without its record. That is the one place
the two reads disagree today; closing it means giving the actions layer the
table's key rather than teaching it Doctrine, and nothing needs it until a
non-`id` entity meets a record action.

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
`{rootAlias}.{identifierField} = :sylament_record_key`, the identifier field read
off the mapping metadata rather than assumed to be `id`, the key **bound** as a
parameter. A composite identifier refuses with the message
[keying uses](#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key),
a bare table names both lanes, and anything that hydrates to a non-object (a
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
takes neither the [eager joins](#eager-loading-runs-over-every-column-on-the-page-clone-alone)
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
the [contract](#the-contract-is-eleven-methods-of-filaments-forty-six), which grows
only for members a shipped consumer calls, and it ships whole rather than in the
half the closure lane could have answered on its own.

### Eager loading runs over every column, on the page clone alone

Two loops on the page read carry the relation arm's host half, and both differ
from Filament's in where they run and what they run over.

**Eager loading** is a loop over `$table->getColumns()`, calling
[`applyEagerLoading()`](column.md#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality)
on each, placed **after** `getFilteredSortedTableQuery()` has returned and before
the query is paginated. Filament loops over `getVisibleColumns()` inside
`filterTableQuery()`. Two deltas fall out.

The first is **visibility**, and it is an absence rather than a decision: column
visibility (`hidden()`, `visible()`, `toggleable()`) is unported, so
`getColumns()` *is* the visible set here — there is no third state for a loop to
skip. It is listed as a **todo** row in [column.md](column.md), and this loop is
one of the places that will grow a filter when it lands, alongside the
[sort](table.md#getsortablevisiblecolumn-has-no-hidden-arm-to-check) and search
loops that already record the same gap. Until then a to-one column a user cannot
see still costs its share of one `LEFT JOIN`, which is the cheapest way to be
wrong about this.

The second is the **clone**, and it is deliberate. Filament's placement puts the
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
and [neither is ported](#no-table-state-is-persisted-in-the-session).

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

### The mount fill applies defaults and seeds nothing from a passed bag

Filament's mount pass is `$this->getTableFiltersForm()->fill($this->tableFilters)`
— an **argument**, so an incoming bag (from the query string, the session, or a
parent passing `tableFilters` in) is written into the form as raw state and the
schema's own defaults are skipped. Ours is `fill()` with no argument, which runs
`hydrateState(applyDefaults: true)`, and that arm writes the declared default
**over** whatever is already at the path rather than only into a blank one.

So a bag handed to `mountTableFilters()` does not survive the mount at all: a
plain `Filter` is a `Checkbox`, which declares `default(false)` in its own
`setUp()`, so a mount-passed `['featured' => ['isActive' => true]]` comes back
`false`, and a `SelectFilter` with no `default()` has its passed `value` nulled
by [the defaults-fill null-write](../schemas/component.md#a-defaults-fill-nulls-a-defaultless-component-and-the-shared-record-is-what-protects-a-containers-default).
Both bags lose it either way under deferral, since the mount pass then copies
the draft over the applied one.

Nothing is lost today, because the two lanes Filament's argument exists to serve
are both unported — [the session](#no-table-state-is-persisted-in-the-session)
and [the query string](#no-table-state-is-bound-to-the-url) — and there is no
third caller: mount-time seeding of filter state is simply not a supported
entry point here. It is recorded rather than pre-built because the argument form
alone would not be the whole fix — Filament seeds the bag *before* filling, with
a null-when-empty normalisation and a query-string coercion pass, and picking
the branch without a lane asking for it would be guessing at which of those a
consumer wants. Reversed by the first lane that needs to restore a bag, which
brings its own read side with it.

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

_See also: the global [rename table](../renames.md) — the interface⇄trait label
swap (`InteractsWithTable` trait → `HasTable` trait, `HasTable` interface →
`InteractsWithTable` interface) is recorded there._
