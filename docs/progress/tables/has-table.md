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

The trait is six methods wide today: `table()`, `getTable()`, `makeTable()`,
`getTableRecords()`, the `resolveTableRecords()` half it caches, and
`getTableRecordKey()`. It builds the table once per request, resolves the
`records()` closure once per request, and hands back an array **keyed by record
key**. Everything else Filament's nineteen-trait host does — pagination,
sorting, searching, filters, bulk actions, selection, reordering, the column
manager — is unported and listed below.

The trait presumes no state shape. It declares no `#[LiveProp]` and reads no host
property, so a host can mix it in beside
[`HasSchemas`](../schemas/has-schemas.md) and
[`HasActions`](../actions/has-actions.md) without collision.

**Status:** in progress — see the
[status vocabulary](../README.md#statuses).

Reference — the Filament source this is diffed against:

- [`tables/src/Concerns/InteractsWithTable.php`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php)
  — the host trait, reduced here to its build-and-fetch core
- [`tables/src/Concerns/HasRecords.php`](../../../vendor/filament/tables/src/Concerns/HasRecords.php)
  — the host-side record resolution `getTableRecords()` ports
- [`tables/src/Contracts/HasTable.php`](../../../vendor/filament/tables/src/Contracts/HasTable.php)
  — the 46-method interface this reduces to two

## Surface

Every point of this trait and its contract. _Blocked by_ names what has to land
first. _Detail_ carries either **detail** → the write-up in
[Details](#details), deleted once the row is **done**, or **diverges** → the
permanent note in [Divergences](#divergences).

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`table(Table $table): Table` — the identity default a host overrides](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::table()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | — |
| [`getTable(): Table` — build once, cache](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::getTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#the-table-is-built-lazily-on-first-gettable-not-in-a-booted-hook) |
| [`makeTable(): Table`](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::makeTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#maketable-seeds-nothing-from-deprecated-host-getters) |
| [`getTableRecords()` — evaluate the data source once, re-key, cache](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | in progress | — | [diverges](#the-records-closure-receives-no-named-injections-yet) · [diverges](#the-missing-data-source-error-names-only-records) · [diverges](#gettablerecords-returns-a-keyed-array-not-a-collection-or-paginator) |
| [`InteractsWithTable` — `getTable()` + `getTableRecords()`](../../../packages/tables/src/Contracts/InteractsWithTable.php) | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | in progress | — | [detail](#the-contract-is-two-methods-of-filaments-forty-six) |
| `flushCachedTableRecords()` | [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | todo | — | [detail](#nothing-invalidates-the-record-cache-yet) |
| [`getTableRecordKey(object\|array $record): string`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecordKey()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key) |
| `getTableRecord(?string $key)` | [`Concerns\HasRecords::getTableRecord()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | blocked | table actions (M1 · 6) | — |
| `getAllTableRecordsCount()` | [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | blocked | table upgrades ([pulled forward · 11](../roadmap.md#pulled-forward)) | — |
| The query lane — `getFilteredTableQuery()`, `getFilteredSortedTableQuery()`, `getTableQueryForExport()` | [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | blocked | Doctrine `query()` lane ([pulled forward · 12](../roadmap.md#pulled-forward)) | — |
| Pagination — `WithPagination`, `getTablePage()`, `getTableRecordsPerPage()` | [`CanPaginateRecords`](../../../vendor/filament/tables/src/Concerns/CanPaginateRecords.php), [`CanDeferLoading`](../../../vendor/filament/tables/src/Concerns/CanDeferLoading.php), [`CanPollRecords`](../../../vendor/filament/tables/src/Concerns/CanPollRecords.php) | blocked | table upgrades ([pulled forward · 11](../roadmap.md#pulled-forward)) | — |
| Sorting and searching — `sortTable()`, `getTableSearch()`, the session persistence | [`CanSortRecords`](../../../vendor/filament/tables/src/Concerns/CanSortRecords.php), [`CanSearchRecords`](../../../vendor/filament/tables/src/Concerns/CanSearchRecords.php) | blocked | table upgrades ([pulled forward · 11](../roadmap.md#pulled-forward)) | — |
| Filters — the `tableFiltersForm` schema, `tableFilters` state, the indicators | [`Concerns\HasFilters`](../../../vendor/filament/tables/src/Concerns/HasFilters.php) | todo | — | — |
| Actions and bulk actions — mounting, record resolution, selection | [`Concerns\HasActions`](../../../vendor/filament/tables/src/Concerns/HasActions.php), [`HasBulkActions`](../../../vendor/filament/tables/src/Concerns/HasBulkActions.php), [`HasRecordAction`](../../../vendor/filament/tables/src/Concerns/HasRecordAction.php) | blocked | table actions (M1 · 6) | — |
| Grouping and summaries | [`CanGroupRecords`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php), [`CanSummarizeRecords`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | todo | — | — |
| Reordering, striping, the column manager, `HasContent`, `HasHeader` | [`CanReorderRecords`](../../../vendor/filament/tables/src/Concerns/CanReorderRecords.php) and four siblings | todo | — | — |
| Translatable content driver — `getActiveTableLocale()`, `makeFilamentTranslatableContentDriver()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | todo | — | [has-schemas.md](../schemas/has-schemas.md#the-translatable-content-driver-is-not-ported) |

The four **done** rows are the build-once core plus the record key. They are
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

## Details

Specifics for rows that are not finished — what is left and what the next step
is. Delete a subsection when its row flips to **done**; the row and any
divergence stay behind.

### The contract is two methods of Filament's forty-six

`Contracts\HasTable` declares 46 methods — the full host API a Filament table,
column, filter, action, or test helper may call back into. Sylament's
`InteractsWithTable` declares the two the shipped surface actually calls:
`getTable()` (the template and the column layer need the table from the host) and
`getTableRecords()` (which [`Table::getRecords()`](table.md#getrecords-delegates-to-the-host-and-fetches-nothing)
delegates to). The contract grows a method at a time as the features that need it
land — a host must be able to satisfy it, so declaring the pagination, filter and
bulk-selection halves before those exist would force every consumer to stub
methods nothing calls. This is a **growth order**, not a divergence: the end
state is the same set of members, minus whatever the Eloquent-only rows above
turn out not to need.

### Nothing invalidates the record cache yet

`$cachedTableRecords` is filled on first read and never cleared — there is no
`flushCachedTableRecords()`, because within one request nothing yet changes the
record set after it has been read. That stops being true with table actions
(M1 · 6): a Delete action mutates the data *during* the request that then
re-renders the table, so the flush lands in the same change as the action that
needs it, and is deliberately not added speculatively.

## Divergences

Deliberate deviations from Filament — permanent, and the reason they hold. Say
what would reverse the decision when it is provisional.

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

### The `records()` closure receives no named injections yet

Filament evaluates the data source with eight named injections —
`columnSearches`, `filters`, `page`, `recordsPerPage`, `search`, `sort`,
`sortColumn`, `sortDirection` — each a closure reading the corresponding piece of
host state, so a userland `records()` closure can paginate, sort and filter its
own result set. Sylament evaluates it with none: `$table->evaluate($dataSource)`.

Nothing is dropped in principle; there is simply no state behind any of those
names yet. Pagination and sorting are
[pulled forward · 11](../roadmap.md#pulled-forward), which that epic's text names
explicitly — the closure lane gains `page`, `recordsPerPage`, `sort`,
`sortColumn` and `sortDirection` there — and the search and filter names follow
their own features. A closure written today still receives everything the
evaluation path resolves without those names: `$table` — by the parameter name,
which is `Table`'s `$evaluationIdentifier`, or by a `Table` type hint —
`$liveComponent` by name through
[`Table`'s resolver override](table.md), any explicit named or typed injection a
future caller passes to `evaluate()`, and a public container service by type
(below). So the injection *mechanism* is live and proven by
[`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php)'s
`$table` case; only the table-state names are missing. Provisional by
construction — each name arrives with the state it reads.

The **container** arm is live, and it arrived with the bundle wiring rather than
with anything in this divergence. `SylamentTablesBundle::loadExtension()`
registers `Table` as an autowired, autoconfigured service, so
`Table::newInstance()` resolves through `ComponentResolver`'s locator, the
`#[Required] setInvoker()` setter fires, and the table evaluates its closures
through the container's `Invoker` instead of the fallback one built around an
empty container. A `records()` closure may therefore type-hint a service and be
handed it —
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

### The missing-data-source error names only `records()`

Filament throws `"Table [{$livewireClass}] must have a [query()], [relationship()],
or [records()]."` — three ways to give a table data, and the message lists all
three so the reader can pick. Sylament's message is
`"Table [%s] must have [records()]."`, because `records()` is the only one that
exists: the Doctrine `query()` lane is
[pulled forward · 12](../roadmap.md#pulled-forward) and `relationship()` sits on
the Doctrine relation layer behind it. Naming an API the package does not ship
would send a reader looking for a method they cannot call. The message grows the
other names in the same change as the lanes themselves — provisional, and
mechanically so.

### `getTableRecords()` returns a keyed array, not a `Collection` or `Paginator`

Filament returns a `Collection | Paginator | CursorPaginator`. Sylament returns a
plain PHP `array<array-key, object|array<string, mixed>>`.

The **keying** is no longer a divergence: `mapWithKeys()`'s contract is ported
whole — each entry re-keyed by the record key, an array record's `__key` entry
synthesised from the source array's own key when absent and cast to `string`
either way — so `$records[$key]` is the bare lookup a row-scoped action needs.
Only the container differs. There is no `Collection` to return: the Laravel
collection pipeline has no port and nothing in the package consumes one, so a
`foreach`-able array carries the same information; and there is no
`Paginator` arm because pagination is
[pulled forward · 11](../roadmap.md#pulled-forward). The `Traversable` and array
arms a closure can produce both fall out of one `foreach`, which is why nothing
calls `iterator_to_array()` — a generator yielding the same key twice loses the
earlier record either way, and that is the same last-writer-wins rule two records
sharing an `id` already get.

Provisional in one direction only: the return type gains a paginated arm when
pagination lands. A `Collection` port is not planned.

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
name: Filament's `$primaryKey` counterpart is a Doctrine mapping concern, so it
arrives, if at all, with the [`query()` lane](../roadmap.md#pulled-forward) that
can read the metadata rather than guess. Until then a record whose identity is
not `id` uses the array form and its `__key` entry.

_See also: the global [rename table](../renames.md) — the interface⇄trait label
swap (`InteractsWithTable` trait → `HasTable` trait, `HasTable` interface →
`InteractsWithTable` interface) is recorded there._
