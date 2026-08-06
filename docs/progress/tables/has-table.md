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

`HasTable` itself is fifteen methods wide: `table()`, `getTable()`,
`makeTable()`, the three filtered-query seams `getFilteredTableQuery()`,
`filterTableQuery()` and `getFilteredSortedTableQuery()`, `getTableRecords()`,
the `resolveTableRecords()` half it caches and the `keyTableRecords()` half that
re-keys, `getTablePaginator()`, `getTableRecordKey()` with its
`resolveQueryRecordKey()` helper that reads an entity's identity off the mapping
metadata, `getTableRecord()`, `flushCachedTableRecords()` and `getTableHostId()`.
It builds the table once per request, resolves its data source once per
request — a `records()` closure, or
the Doctrine query those three seams assemble — and hands back an array **keyed
by record key** — or a [`Paginator`](../support/pagination.md) carrying such an
array — plus a by-key lookup that reads fresh whenever the render cache is cold,
the flush that empties it, and the per-host id every row and cell is stamped
with.

Around that core it composes three host concerns, all landed by the table
upgrades epic:
[`CanPaginateRecords`](../../../packages/tables/src/Concerns/CanPaginateRecords.php)
(the page and per-page state plus the `gotoTablePage()` live action),
[`CanSortRecords`](../../../packages/tables/src/Concerns/CanSortRecords.php)
(the `column:direction` string and the `sortTable()` cycle) and
[`CanSearchRecords`](../../../packages/tables/src/Concerns/CanSearchRecords.php)
(the search term). Each is a per-file row below rather than a folded one, per
[one row per file](../README.md#one-row-per-file-one-file-per-thing). What
Filament's nineteen-trait host does beyond them — filters, bulk actions,
selection, reordering, the column manager, and the whole query-lane half of the
three above — is unported and listed below.

The trait presumes no host property beyond the five `#[LiveProp]`s it and its
concerns declare: `?string $tableHostId` (the persisted half of
[`getTableHostId()`](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id)),
`int $tablePage`, `?string $tableSort`, and the two **writable** props the
chrome binds to, `?string $tableRecordsPerPage` and `?string $tableSearch`.
Writability is the security line and it is
[deliberate on each prop](#per-page-is-validated-in-the-getter-on-every-read):
page and sort move only through a `#[LiveAction]`, so they stay checksummed,
while the two a `data-model` writes are re-validated on every read. A host can
still mix the trait in beside [`HasSchemas`](../schemas/has-schemas.md) and
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
  — the 46-method interface this reduces to nine

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
| [`getTableRecords()` — resolve the data source once, re-key, cache](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | in progress | — | [detail](#the-records-injections-stop-at-six-of-filaments-eight) · [semantics](#a-records-closure-resolves-public-container-services-by-type) · [diverges](#the-missing-data-source-error-does-not-name-relationship) · [diverges](#gettablerecords-returns-a-keyed-array-or-a-paginator-never-a-collection) |
| [`getTablePaginator(): ?Paginator` — the carrier, unwrapped for the footer](../../../packages/tables/src/Concerns/HasTable.php) | — (Sylament-only; Filament's records **are** the paginator) | done | — | [pagination.md](../support/pagination.md) · [semantics](#the-paginator-is-a-return-value-not-a-state-holder) |
| [`InteractsWithTable` — nine methods](../../../packages/tables/src/Contracts/InteractsWithTable.php) | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | in progress | — | [detail](#the-contract-is-nine-methods-of-filaments-forty-six) |
| [`flushCachedTableRecords()`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::flushCachedTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | — |
| [`getTableHostId()` + the `$tableHostId` `LiveProp`](../../../packages/tables/src/Concerns/HasTable.php) | [`Livewire\Component::getId()`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) |
| [`getTableRecordKey(object\|array $record): string`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecordKey()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key) |
| [`getTableRecord(?string $key)`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecord()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#gettablerecord-reads-fresh-when-the-render-cache-is-cold) |
| `getAllTableRecordsCount()` | [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | todo | — | Filament's paginated arm is answerable here (`getTablePaginator()?->getTotalRecordCount()`) but its other arm is `getFilteredTableQuery()->count()`, the query lane's; nothing reads the method yet, so it ships whole or not at all |
| [The query lane — `getFilteredTableQuery()`, `filterTableQuery()`, `getFilteredSortedTableQuery()`, `getTableQueryForExport()`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | in progress | — | [detail](#the-filtered-query-seams-are-wired-before-the-layers-that-fill-them) · [diverges](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder) |
| [`CanPaginateRecords` — `$tablePage`, `$tableRecordsPerPage`, `gotoTablePage()`, `getTablePage()`, `getTableRecordsPerPage()`, `updatedTableRecordsPerPage()`](../../../packages/tables/src/Concerns/CanPaginateRecords.php) | [`Concerns\CanPaginateRecords`](../../../vendor/filament/tables/src/Concerns/CanPaginateRecords.php) | in progress | — | [detail](#the-page-state-has-no-query-lane-and-no-session-half) · [diverges](#gototablepage-replaces-livewires-paginator-trio) · [diverges](#per-page-is-validated-in-the-getter-on-every-read) · [diverges](#the-recordsperpage-injection-is-nullable) · [diverges](#no-table-state-is-persisted-in-the-session) |
| [`CanSortRecords` — `$tableSort`, `sortTable()`, `getTableSortColumn()`, `getTableSortDirection()`, `applySortingToTableQuery()`](../../../packages/tables/src/Concerns/CanSortRecords.php) | [`Concerns\CanSortRecords`](../../../vendor/filament/tables/src/Concerns/CanSortRecords.php) | in progress | — | [detail](#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) · [diverges](#sorttable-ignores-a-column-the-table-did-not-declare-sortable) · [semantics](#a-column-name-containing-a-colon-cannot-be-cycled) · [diverges](#no-table-state-is-persisted-in-the-session) |
| [`CanSearchRecords` — `$tableSearch`, `updatedTableSearch()`, `getTableSearch()`](../../../packages/tables/src/Concerns/CanSearchRecords.php) | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | in progress | — | [detail](#search-is-the-global-term-only) · [diverges](#no-table-state-is-persisted-in-the-session) |
| Table state in the URL — `#[Url]`-style query-string binding for page, sort and search | [`getIdentifiedTableQueryStringPropertyNameFor()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | todo | — | [diverges](#no-table-state-is-bound-to-the-url) |
| Per-column search — `$tableColumnSearches`, `updatedTableColumnSearches()`, the reserved keys | [`Concerns\CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | todo | — | [detail](#search-is-the-global-term-only) |
| Deferred loading — `$isTableLoaded`, `loadTable()`, `isTableLoaded()` | [`CanDeferLoading`](../../../vendor/filament/tables/src/Concerns/CanDeferLoading.php) | todo | — | [table.md](table.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) |
| Host polling — `getTablePollingInterval()` | [`CanPollRecords`](../../../vendor/filament/tables/src/Concerns/CanPollRecords.php) | skipped | — | The whole trait is `@deprecated Override the table() method to configure the table.`, one host getter returning null, and the project has never ported a deprecated Filament surface. The interval it fronts is `Table::poll()`, a **todo** row in [table.md](table.md#loading-affordances-are-deferred-with-the-javascript-that-would-drive-them) |
| Filters — the `tableFiltersForm` schema, `tableFilters` state, the indicators | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | todo | — | — |
| The deprecated `*TableAction` host shims — `mountTableAction()`, `callMountedTableAction()`, `getMountedTableActionRecord()`, `unmountTableAction()`, `getTableRecordAction()`, … | [`Concerns\HasActions`](../../../vendor/filament/tables/src/Concerns/HasActions.php), [`HasRecordAction`](../../../vendor/filament/tables/src/Concerns/HasRecordAction.php) | skipped | — | Every method in both files is a one-line deprecated delegate, and the project has never ported a deprecated Filament surface. What they front is built: the host resolves a table-context mount through actions' [`resolveTableAction()`](../actions/has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) and binds the record through `getTableRecord()` |
| Bulk actions and selection — `getSelectedTableRecords()`, `getAllSelectableTableRecordKeys()`, `deselectAllTableRecords()` | [`Concerns\HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php) | blocked | table bulk actions | — |
| Grouping and summaries | [`CanGroupRecords`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php), [`CanSummarizeRecords`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | todo | — | — |
| Reordering, striping, the column manager, `HasContent`, `HasHeader` | [`CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) and four siblings | todo | — | — |
| Translatable content driver — `getActiveTableLocale()`, `makeFilamentTranslatableContentDriver()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | todo | — | [has-schemas.md](../schemas/has-schemas.md#the-translatable-content-driver-is-not-ported) |

The eight **done** rows are the build-once core, the record key, the by-key
lookup with its flush, the host id, and the paginator peek. They are covered by
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
search is changed in turn, and the page must be back at `1` every time.

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
it was applied to; the closure arms of both setters, one naming a column and one
switching the tiebreaker off; both tiebreaker suppressions, the qualified key and
the joined `artist.id` one; `defaultKeySort(false)` leaving no key ordering at
all; and a `query()`-authored `orderBy` staying in front of everything the table
adds. One more pins the composite-identifier guard: a `CompositePair` query
composes no `ORDER BY` at all rather than throwing on a single-identifier read.

The `#[LiveProp]` and `#[LiveAction]` attributes carry no runtime behaviour a
render test would catch, so they are pinned by reflection in
[`HasTableLivePropsTest`](../../../packages/tables/tests/Unit/Concerns/HasTableLivePropsTest.php):
each of the four state props by writability *and* `onUpdated` hook name, and each
live action by its `#[LiveArg]`-marked parameter names. Writability is a security
property — a prop that silently became writable is exactly the failure
[the validating getter](#per-page-is-validated-in-the-getter-on-every-read)
exists to survive, and this is the test that would notice.

## Details

Specifics for rows that are not finished — what is left and what the next step
is. Delete a subsection when its row flips to **done**; the row and any
divergence stay behind.

### The `records()` injections stop at six of Filament's eight

Filament evaluates the data source with eight named injections —
`columnSearches`, `filters`, `page`, `recordsPerPage`, `search`, `sort`,
`sortColumn`, `sortDirection` — each a closure reading the corresponding piece of
host state, so a userland `records()` closure can paginate, sort and filter its
own result set. Sylament passes **six**, in the same shape (a `fn ()` per name,
so the state is read only if the closure asks for it): `page`, `recordsPerPage`,
`search`, `sort`, `sortColumn` and `sortDirection`. Every one is
Filament's name, Filament's arity and Filament's value — `sort` is the same
`[$column, $direction]` pair, redundant with the two singular names and kept
because a migrating closure destructures it.

The two missing names have no state behind them yet, which is the same reason
all eight were missing before this epic. `columnSearches` waits on per-column
search (its **todo** row above) and `filters` on the filter layer (likewise), and
each arrives in the change that lands the feature it reads. One value differs
rather than being absent: `recordsPerPage` is
[nullable here](#the-recordsperpage-injection-is-nullable) where Filament types
it `int|string`.

Everything the evaluation path resolves without a named injection resolves as
before: `$table` — by the parameter name, which is `Table`'s
`$evaluationIdentifier`, or by a `Table` type hint — `$liveComponent` by name
through [`Table`'s resolver override](table.md), any explicit named or typed
injection a caller passes to `evaluate()`, and a public container service
[by type](#a-records-closure-resolves-public-container-services-by-type).

### The filtered-query seams are wired before the layers that fill them

`getFilteredTableQuery()`, `filterTableQuery()` and `getFilteredSortedTableQuery()`
ship with Filament's names, signatures and composition, and — apart from sorting
— with none of the applications those signatures exist to hold.
`getFilteredTableQuery()` answers null unless the table is on the query lane —
[a gate Filament's seam does not have](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder)
— then reads [`Table::getQuery()`](table.md#every-read-clones-a-memoised-base-query)
— a scoped clone, so what comes back is always a builder the caller may mutate —
answers null again when there is no query to read, and otherwise hands it to
`filterTableQuery()`, which today returns it untouched.
`getFilteredSortedTableQuery()` adds
[the sorting](#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker)
and nothing else. So a query-lane table resolves **every** record its query
selects, in the order the sort composition asked for, and outside their own
composition `resolveTableRecords()` is their only caller.

They land first, and public, on purpose: each layer still to come is a body
inside one of them rather than a new call site, and anything that needs the lane
can ask for it before its own half exists. Filament's
`filterTableQuery()` applies filters, the global and per-column search, and every
visible column's relationship aggregates and eager loading; its
`getFilteredSortedTableQuery()` adds grouping, then sorting. Of those, **search**
arrives with `applySearchToTableQuery()`, inside the Doctrine
[`query()` lane](../roadmap.md#pulled-forward) this epic is; filters, grouping,
aggregates and eager loading each wait on a layer that does not exist here at all
and each has a **todo** row above. `getTableQueryForExport()` is not ported: it
assembles a query for Filament's export action, which has no counterpart here, and
it is the one member of this row with no epic behind it.

### The page state has no query lane and no session half

`Concerns\CanPaginateRecords` here is the closure lane's half of Filament's
trait: the page number, the per-page option, the reset-to-page-one hook, and the
two getters the `records()` closure is handed. Three groups of Filament members
are not in it.

`paginateTableQuery()` is the **query lane** — it calls `$query->paginate()` /
`simplePaginate()` / `cursorPaginate()` on an Eloquent builder and returns
Laravel's paginator — and it lands with the Doctrine
[`query()` lane](../roadmap.md#pulled-forward). Its `PaginationMode` switch comes
with it, tracked as [`table.md`](table.md#three-pagination-setters-configure-machinery-the-closure-lane-does-not-have)'s
row: on the closure lane the userland closure decides how it slices, so a mode
enum would configure nothing. The **session** half
(`getTablePerPageSessionKey()`, the `session()->put()` in the updated hook) is a
[deliberate omission](#no-table-state-is-persisted-in-the-session). The
**query-string** page name (`getTablePaginationPageName()`, which feeds Livewire's
`WithPagination`) is the [URL binding](#no-table-state-is-bound-to-the-url) that
has a **todo** row of its own. The two `@deprecated` host getters
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
and by nothing the user clicked. Filament's arm discards it the same way.

Last is `defaultKeySort()`, on by default: the root alias's identifier field,
read from the mapping metadata rather than assumed to be `id`, appended in the
same direction the default sort ran in. It is what makes a page boundary stable
when the sorted column has ties. Two things suppress it. Any existing ordering
whose path **is** the qualified key, and — Filament-faithfully — any ordering
whose path merely *ends* in the same field name, so a query already ordered by
`artist.id` gets no `song.id` appended; the heuristic is Filament's
`afterLast('.')` comparison, kept because a query ordering by a joined key has
almost always chosen its own tiebreaker. It also no-ops rather than throwing when
the table's records carry a composite identifier, or when the builder names no
root entity to read metadata from — a query that cannot be keyed by one column
cannot be tiebroken by one either.

The two halves of that qualified key are read from different builders, which only
matters after a replacement. The identifier **field** comes from the table's
[record class](table.md#the-record-class-is-read-off-the-base-querys-root-entity),
memoised off the declared base query, while the root **alias** comes off the
builder actually in hand; Filament reads both off the effective one
(`$query->getModel()->getQualifiedKeyName()`). They can only disagree when a
default-sort closure hands back a builder over a *different* entity, which the
lane rules out for other reasons already — the record class is what keys every
row — so this is recorded rather than guarded.

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

`$tableSearch` and its trimmed getter are ported. Filament's trait carries a
second, larger half — `$tableColumnSearches`, `updatedTableColumnSearches()`,
`getReservedTableColumnSearchKeys()` and the per-column query application — which
is **per-column search**, a feature with a UI of its own (an input in each header
cell) rather than a variant of this one. It has its own **todo** row above, and
it is what the missing `columnSearches` injection reads.

The query application (`applySearchToTableQuery()`, `applyGlobalSearchToTableQuery()`,
the `generate_search_term_expression()` helpers, `shouldSplitSearchTerms()`)
is the [`query()` lane](../roadmap.md#pulled-forward)'s, exactly as with sorting:
here the `search` injection hands the closure a string and the closure decides
what matching means. Filament's search **indicators**
(`getTableSearchIndicator()`, `getTableColumnSearchIndicators()`) return
`Filters\Indicator` objects and wait on the filter layer that defines that class.

### The contract is nine methods of Filament's forty-six

`Contracts\HasTable` declares 46 methods — the full host API a Filament table,
column, filter, action, or test helper may call back into. Sylament's
`InteractsWithTable` declares the nine the shipped surface actually calls:
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
row and cell ids. The table upgrades epic added the last four, every one of them
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

The contract grows a method at a time as the
features that need it land — a host must be able to satisfy it, so declaring the
pagination, filter and bulk-selection halves before those exist would force every
consumer to stub methods nothing calls. Growth is free for implementors while
`HasTable` supplies every member: every host implementing the contract today —
the four tables fixtures (`TableHost`, `TableActionsHost`, `SongsTable`,
`RecordActionsTable`) and the demo's `TableShowcaseCard` — mixes the trait in, so
an addition reaches them without an edit. This is a **growth order**, not a
divergence: the end state is the same set of members, minus whatever the
Eloquent-only rows above turn out not to need.

## Semantics

Faithful ports subtle enough that the next reader would misread the code.
Permanent, and addressable by anchor.

### A `records()` closure resolves public container services by type

Closure evaluation reaches the container here, which is not obvious from the
trait: `$table->evaluate($dataSource, [...])` passes six named injections and
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
[`Paginator`](../support/pagination.md), null otherwise. The carrier is whatever
the userland `records()` closure returned — the host neither builds one nor slices
anything — so a table is "paginated" for rendering purposes exactly when its
closure chose to return one, regardless of what `Table::isPaginated()` says.
`isPaginated()` governs the *inputs* (whether `recordsPerPage` is injected at
all); the footer renders off the *output*. A closure that ignores its injections
and returns a plain array on a paginated table therefore renders with no footer,
correctly: there are no counts to render.

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
schemas host. Two consequences, both wanted: the table is never built for a
request that does not touch it, and a host that never calls `getTable()` pays
nothing. The one Filament behaviour lost is the guaranteed build order — Filament
can rely on the table existing before any other hook runs; here anything that
needs it calls `getTable()` and gets it built on the spot. Permanent, unless UX
adds a construct-and-hydrate-alike boot hook.

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
table is, rather than at each call site — and the first caller waiting on it
here is the count arm `getAllTableRecordsCount()` above, the one Filament
reaches with no check at all. Handing back a query a records-lane table will
never execute is not a harmless extra: counted, it runs SQL for rows the render
will not show.

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

Two consequences, both intended. The prop stays `null` until the user picks
something — the getter seeds from the table's default without writing, which is
what the demo's select renders as its selected option and what
[`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php)
asserts by checking the getter answers `10` while the property is still null.
And the type is `?string` rather than Filament's `int|string`, because that is
what a `<select>` posts; the normalisation is the cast, and it lives in one
place.

`updatedTableRecordsPerPage()` and `updatedTableSearch()` are kept as `onUpdated`
hooks anyway, for the half validation cannot do: resetting to page 1 and
flushing the record cache when the user changes either. Filament's same-named
methods are Livewire's magic `updated<Property>` hooks; here the binding is
explicit in the attribute, which is the ordinary Livewire → UX translation.

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

### The missing-data-source error does not name `relationship()`

Filament throws `"Table [{$livewireClass}] must have a [query()], [relationship()],
or [records()]."` — three ways to give a table data, and the message lists all
three so the reader can pick. Sylament's is
`"Table [%s] must have [records()] or [query()]."`: it names the two lanes that
exist and leaves out the third, which sits on
[the Doctrine relation layer](table.md#the-relation-arm-of-the-query-lane-waits-on-a-doctrine-relation-layer)
no epic carries yet. Naming an API the package does not ship would send a reader
looking for a method they cannot call — which is why the message grew its
`query()` half in the change that landed the lane rather than ahead of it, and why
it grows `relationship()` the same way. Provisional, and mechanically so.

### `getTableRecords()` returns a keyed array or a `Paginator`, never a `Collection`

Filament returns a `Collection | Paginator | CursorPaginator`. Sylament returns
`array<array-key, object|array<string, mixed>> | Paginator`, where `Paginator` is
support's [own carrier](../support/pagination.md) rather than Laravel's.

The **keying** is Filament's whole: `mapWithKeys()`'s contract is ported — each
entry re-keyed by the record key, an array record's `__key` entry synthesised
from the source array's own key when absent and cast to `string` either way — so
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
mapping instead of blowing up. And the arm adds two refusals of its own, both
`LogicException`: a **composite** identifier, which a single string key cannot
address, and an identifier that is set but empty or unprintable. Neither is
recoverable — the table would otherwise index rows under a key no action can
resolve.

The action side has the same-shaped twin,
[`InteractsWithRecord::resolveRecordKey()`](../actions/action.md#record-key-resolution-is-hoisted-to-hasactions)
— same `id`-through-the-accessor read, same `Stringable` unwrap, same
`LogicException` for an array record missing its key entry — differing in what
an *unkeyable* record means: it returns `null` where this throws, and it
returns `null` for a blank key in the array arm where this returns the blank
string. The asymmetry is deliberate and matches Filament's own pair. A table
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
read as defective rather than from one we have yet to catch up with, and the
Doctrine [`query()` lane](../roadmap.md#pulled-forward) will resolve exactly this
way when it lands.

_See also: the global [rename table](../renames.md) — the interface⇄trait label
swap (`InteractsWithTable` trait → `HasTable` trait, `HasTable` interface →
`InteractsWithTable` interface) is recorded there._
