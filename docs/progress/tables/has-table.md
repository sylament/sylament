---
description: 'The tables host trait `HasTable`: building the table, resolving and keying its records, the all-records count, the query seams, the host id and URL hooks'
ports:
  - vendor/filament/tables/src/Concerns/InteractsWithTable.php
  - vendor/filament/tables/src/Concerns/HasRecords.php
  - vendor/filament/tables/src/Concerns/CanPollRecords.php
  - vendor/filament/tables/src/Concerns/HasActions.php
  - vendor/filament/tables/src/Concerns/HasRecordAction.php
  - vendor/filament/tables/src/Concerns/CanBeStriped.php
  - vendor/filament/tables/src/Concerns/HasEmptyState.php
  - vendor/filament/tables/src/Concerns/HasContent.php
  - vendor/filament/tables/src/Concerns/HasHeader.php
owns:
  - packages/tables/src/Concerns/HasTable.php
---
# HasTable (tables)

[`Sylament\Tables\Concerns\HasTable`](../../../packages/tables/src/Concerns/HasTable.php) is the trait a UX Live Component mixes in to own a [`Table`](table.md); what the table may call back into is the contract in [interacts-with-table.md](interacts-with-table.md).
It ports Filament's [`Concerns\InteractsWithTable`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) and [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php), with the interface and trait labels swapped against Filament's ([rename table](../renames.md#interface-and-trait-names-are-swapped)), so a name read across the two codebases needs its side checked first.
Each host concern it composes has its own file, named `host-<trait>.md` where a `Table\Concerns` trait shares its name.
A host can mix it in beside [`HasSchemas`](../schemas/has-schemas.md) and [`HasActions`](../actions/has-actions.md), because their property names are disjoint.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`table(Table $table): Table`, the identity default a host overrides](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::table()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | — |
| [`getTable(): Table`, build once and cache](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::getTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#the-table-is-built-lazily-on-first-gettable-not-in-a-booted-hook) |
| [`makeTable(): Table`](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::makeTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#maketable-seeds-nothing-from-deprecated-host-getters) |
| `bootedInteractsWithTable()` / `mountInteractsWithTable()` | [`InteractsWithTable`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | skipped | — | [diverges](#the-table-is-built-lazily-on-first-gettable-not-in-a-booted-hook) · [can-persist-table-state.md](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it) |
| [`getTableRecords()`: resolve the data source once, eager-load, key, fill the aggregate store, cache](../../../packages/tables/src/Concerns/HasTable.php) | [`HasRecords::getTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#the-records-closure-gets-filaments-eight-injections-three-of-them-typed-differently) · [semantics](#a-records-closure-resolves-public-container-services-by-type) · [diverges](#gettablerecords-returns-a-keyed-array-or-a-paginator-never-a-collection) · [diverges](#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone) |
| `hydratePivotRelationForTableRecords()` | [`HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | skipped | — | [table.md](table.md#surface) |
| [`getTablePaginator(): ?Paginator`, the carrier unwrapped for the footer](../../../packages/tables/src/Concerns/HasTable.php) | — (Sylament-only; Filament's records are the paginator) | done | — | [pagination.md](../support/pagination.md) · [semantics](#the-paginator-is-a-return-value-not-a-state-holder) |
| [`flushCachedTableRecords()`](../../../packages/tables/src/Concerns/HasTable.php) | [`HasRecords::flushCachedTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [interacts-with-table.md](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) · [has-actions.md](../actions/has-actions.md#every-mounted-action-run-flushes-a-table-hosts-records-cache) |
| [`getTableHostId()` + the `$tableHostId` `LiveProp`](../../../packages/tables/src/Concerns/HasTable.php) | [`Livewire\Component::getId()`, as `index.blade.php` reads it](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) |
| [`getTableRecordKey(object\|array $record): string`](../../../packages/tables/src/Concerns/HasTable.php) | [`HasRecords::getTableRecordKey()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key) · [semantics](#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec) · [diverges](#a-query-lane-override-of-gettablerecordkey-the-codec-cannot-read-back-is-refused-where-filaments-misses-in-silence) |
| [`getTableRecord(?string $key)` with its `resolveQueryTableRecord()` query arm](../../../packages/tables/src/Concerns/HasTable.php) | [`HasRecords::getTableRecord()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#gettablerecord-reads-fresh-when-the-render-cache-is-cold) · [diverges](#record-resolution-applies-no-limit-1-where-filaments-find-does) |
| [`getAllTableRecordsCount(): int`](../../../packages/tables/src/Concerns/HasTable.php) | [`HasRecords::getAllTableRecordsCount()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#the-all-records-count-answers-four-ways-where-filaments-answers-two) · [host-can-paginate-records.md](host-can-paginate-records.md#an-output-walker-of-the-querys-own-is-refused-by-name-only-where-doctrines-fallback-cannot-serve-a-keyed-root) |
| [The query seams: `getFilteredTableQuery()`, `filterTableQuery()`, `getFilteredSortedTableQuery()`](../../../packages/tables/src/Concerns/HasTable.php) | [`HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [semantics](#filtertablequery-applies-the-filters-then-the-search) · [diverges](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder) |
| `getTableQueryForExport()` | [`HasRecords::getTableQueryForExport()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | blocked | the export surface | [detail](#gettablequeryforexport-waits-on-an-export-surface) |
| [Table state in the URL: the `getTableUrlMapping()` opt-in hook, the six `modifyTable*Prop()` `LiveProp` modifiers, `getTableQueryStringIdentifier()` and `getIdentifiedTableQueryStringPropertyNameFor()`](../../../packages/tables/src/Concerns/HasTable.php) | [`getIdentifiedTableQueryStringPropertyNameFor()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) and the `#[Url]` block on [`ListRecords`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) | done | — | [diverges](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) · [diverges](#the-query-string-identifier-is-live-on-the-table-and-dead-in-the-modifiers) |
| The `reordering` URL bind | [`#[Url(as: 'reordering')]` on `ListRecords`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) | skipped | — | [diverges](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) |
| `resetTable()` | [`InteractsWithTable::resetTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | todo | — | — |
| Host polling: `getTablePollingInterval()` | [`CanPollRecords`](../../../vendor/filament/tables/src/Concerns/CanPollRecords.php) | skipped | — | [diverges](#the-deprecated-host-shims-are-not-ported-because-each-points-at-a-surface-that-ships) |
| The deprecated `*TableAction` host shims: `mountTableAction()`, `callMountedTableAction()`, `getMountedTableActionRecord()`, `unmountTableAction()`, `getTableRecordAction()`, … | [`Concerns\HasActions`](../../../vendor/filament/tables/src/Concerns/HasActions.php), [`HasRecordAction`](../../../vendor/filament/tables/src/Concerns/HasRecordAction.php) | skipped | — | [diverges](#the-deprecated-host-shims-are-not-ported-because-each-points-at-a-surface-that-ships) · [has-actions.md](../actions/has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) |
| The deprecated host presentation shims: `isTableStriped()`, `getTableEmptyState()`, `getTableEmptyStateActions()`, `getTableEmptyStateDescription()`, `getTableEmptyStateHeading()`, `getTableEmptyStateIcon()` | [`Concerns\CanBeStriped`](../../../vendor/filament/tables/src/Concerns/CanBeStriped.php), [`Concerns\HasEmptyState`](../../../vendor/filament/tables/src/Concerns/HasEmptyState.php) | skipped | — | [diverges](#the-deprecated-host-shims-are-not-ported-because-each-points-at-a-surface-that-ships) |
| The deprecated host content and header shims: `getTableContent()`, `getTableContentGrid()`, `getTableContentFooter()`, `getTableDescription()`, `getTableHeader()`, `getTableHeaderActions()`, … | [`Concerns\HasContent`](../../../vendor/filament/tables/src/Concerns/HasContent.php), [`Concerns\HasHeader`](../../../vendor/filament/tables/src/Concerns/HasHeader.php) | skipped | — | [diverges](#the-deprecated-host-shims-are-not-ported-because-each-points-at-a-surface-that-ships) |
| The deprecated record shims: `getTableQuery()`, `allowsDuplicates()`, `getTableRecordTitle()`, `getTableModelLabel()`, `getTablePluralModelLabel()` | [`InteractsWithTable`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php), [`HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | skipped | — | [diverges](#the-deprecated-host-shims-are-not-ported-because-each-points-at-a-surface-that-ships) |

## Details

### `getTableQueryForExport()` waits on an export surface

Filament's `getTableQueryForExport()` bypasses the seams, re-reading `getTable()->getQuery()` and re-applying filters, search and sorting itself, to assemble the query its export action reads.
No export action or exporter exists in `packages/`, the same surface [`ExportColumn`](has-cell-state.md#hascellstate-is-relocated-into-the-column-tier) waits on, so the member lands with that action.

## Semantics

### `filterTableQuery()` applies the filters, then the search

The body is `applySearchToTableQuery($this->applyFiltersToTableQuery($query))`: filters first, then [the search](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column), Filament's order.
`applyFiltersToTableQuery()` runs two passes over the one builder, every filter's `applyToBaseQuery()` before every filter's `apply()`, and returns the same builder both times.
`getFilteredTableQuery()` reads [`Table::getQuery()`](has-query.md#every-read-clones-a-memoised-base-query), a scoped clone the caller may mutate, [gated on the lane](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder), and hands it to `filterTableQuery()`; `getFilteredSortedTableQuery()` wraps that in `applySortingToTableQuery($this->applyGroupingToTableQuery($query))`, so the group order is appended first and every sort after it [breaks ties inside a group](grouping.md#the-group-order-is-appended-first-so-every-later-sort-breaks-ties-inside-a-group).

Record resolution takes the filters and not the rest: `resolveQueryTableRecord()` runs `applyFiltersToTableQuery($query, isResolvingRecord: true)` before narrowing on the identifier, so a row action cannot reach a record outside the filtered set.
The `isResolvingRecord` flag skips the apply pass of any filter that called `excludeWhenResolvingRecord()`, never the base pass, which is where a filter's joins live.

On a deferring table Filament's `applyFiltersToTableQuery()` re-roots the filters form at `tableFilters` around both passes and restores `tableDeferredFilters` in a `finally`, so anything resolving state through the form reads the applied bag.
Nothing here resolves it that way: [`getTableFilterState()`](host-has-filters.md#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) names the applied bag directly, so there is no path to flip and no cache to invalidate.

Filament's `filterTableQuery()` also calls each visible column's `applyRelationshipAggregates()` and `applyEagerLoading()`, so both land on the filtered query, the same clone the count takes.
Here the aggregate value never touches a query clone ([why](has-query.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)), and eager loading runs one step later, on the [page clone alone](#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone), so a `COUNT` never carries joins added to fill cells.

### A `records()` closure resolves public container services by type

`$table->evaluate($dataSource, [...])` passes eight named injections and nothing else, yet a `records()` closure type-hinting a repository is handed one.
`SylamentTablesBundle::loadExtension()` registers `Table` as an autowired service, so `Table::newInstance()` resolves through `ComponentResolver`'s locator, the `#[Required] setInvoker()` setter fires, and the table evaluates through the container's `Invoker` rather than the fallback built around an empty container.
[`SylamentTablesBundleTest`](../../../packages/tables/tests/Integration/SylamentTablesBundleTest.php) pins both halves: the table's `Invoker` is the container's, and a closure type-hinting a fixture repository receives the container's shared instance.

The `Invoker` holds `service_container`, and `Container::has()` on a compiled container answers for public ids only, so a private service, Symfony's default, does not resolve by type.
That is support's rule ([component.md](../support/component.md#closure-resolution-lives-in-a-service)), and it is why the fixture repository is `#[Autoconfigure(public: true)]`.

### The paginator is a return value, not a state holder

`getTablePaginator()` calls `getTableRecords()` and returns the result only when it is a [`Paginator`](../support/pagination.md), null otherwise.
On the records lane the carrier is whatever the closure returned, so a table is paginated for rendering exactly when its closure returns one, whatever `Table::isPaginated()` says: `isPaginated()` governs the inputs (whether `recordsPerPage` is injected), and the footer renders off the output.
A closure that returns a plain array on a paginated table therefore renders with no footer, correctly, since there are no counts to render.

On the query lane the two answers agree, because [`paginateTableQuery()`](host-can-paginate-records.md#a-paginated-query-lane-table-pages-through-doctrines-tools-paginator-and-only-a-collection-join-switches-it-to-identifiers-first) builds a carrier exactly when `isPaginated()` is true, so one footer condition serves both lanes.
Both methods read the same `$cachedTableRecords`, so asking for the paginator before the row loop fetches nothing twice.

### A host trait is analysed only through a class that composes it

PHPStan skips a trait no analysed class uses, and the repo ignores `trait.unused` globally, so the skip is silent: a host trait carries no static-analysis coverage of its own and inherits whatever its consumers give it.
Only a class inside an analysed path turns it on, and [`phpstan.neon.dist`](../../../phpstan.neon.dist) analyses `packages/*/src` and no tables test directory, so the fixture host [`TableHost`](../../../packages/tables/tests/Fixtures/Concerns/TableHost.php) contributes nothing; panels' `ListRecords`, `ManageRelatedRecords` and `RelationManager` are what analyse this trait.
A scoped run over the package that owns the trait cannot see errors that exist only in a using class's context, so only the unscoped `composer test:static` sees them.

### Private trait methods are called across the traits `HasTable` composes

A private trait method is callable from any other trait the same class uses, and the table host relies on that.
`CanReorderRecords` calls `HasBulkActions::resolveTableRecordKeyTarget()`; `CanSummarizeRecords` calls `CanPaginateRecords::readTableRecordWindow()`; `HasTable` calls `CanPaginateRecords::getCompositeTableRecordClass()` and `::assertTableQueryCarriesNoOutputWalker()`; `CanPaginateRecords` calls `HasTable::keyTableRecords()`; and `HasFilters` calls `CanPersistTableState::isPersistedTableFilterBag()`, a private static reached through `self::`.
Each call is legal only because `HasTable` composes every one of those traits into one host.
Neither file shows the coupling, so renaming, re-scoping or moving one of these methods breaks the other trait, and the break surfaces only through a class that composes both, which takes [the unscoped static analysis](#a-host-trait-is-analysed-only-through-a-class-that-composes-it) to see.

### An overridden `getTableRecordKey()` keys the rows, and the query lane reads keys back through the codec

`getTableRecordKey()` is public and on the contract, so a host may override it, and an override moves one side of a round trip.
It is what a row is keyed by: `keyTableRecords()` keys every object row through it, so the row loop, each checkbox's `value`, every mount context and `Table::getRecordKey()` carry its answer.
On the query lane nothing reads that key back through it: `getTableRecord()`, and with it every row action, column action and editable cell, the selection's `in()`/`notIn()`, the select-all key pluck, the reorder's re-resolution, the aggregate batch and the page summary's narrowing all decode a key through [`RecordKey`](../schemas/record-key.md) and `IdentifierConstraint`, so the only key they can read back is the one `RecordKey::of()` mints.
On the `records()` lane an object row is keyed and read back by the method alone, so an override there is whole; an array row never asks it, being keyed by its `__key` entry.

Without the refusal below, an override answering any other key on the query lane misses everywhere.
Row actions, column actions and editable cells resolve nothing, an explicit selection narrows to `1 = 0`, select-all fills the selection with keys no rendered checkbox matches, a reorder writes nothing, and an aggregate cell shows its default.
A select-all with one row unticked acts on every row, because `notIn()` answers null when no deselected key decodes and the selection query reads null as nothing left to exclude.
Resolution, selection, select-all, reorder and the deselection widening fail the same way in Filament, whose `find()`, `whereKey()`, `whereKeyNot()` and `getKeyName()` never ask the override; the selectability constraint, the aggregate batch and the page summary miss only here, because Sylament reads keys where Filament's selectability re-keys through `getKey()`, its aggregates ride the page query and its summary pages with `forPage()`.

So the query lane refuses it.
`keyTableRecords()` compares, for every object row that is an instance of the table's [record class](has-query.md#the-record-class-is-read-off-the-base-querys-root-entity), what `getTableRecordKey()` answers with what `RecordKey::of()` answers, and a difference throws a `LogicException` naming the host, the row's class, both keys and the two ways out: answer the codec's key, or list the rows through `records()`.
Every render keys its rows there, so the first render refuses.
An array row, every `records()` row and a query-lane row that is no instance of the record class, such as a `SELECT NEW` projection keyed by its `id`, are never compared.
`getTableRecordKey()` is asked only where the codec answers null or the host overrides the method, detected by the method's declaring file being other than `HasTable.php`, so a table that does not override pays nothing.
[`HasTableRecordKeyOverrideTest`](../../../packages/tables/tests/Unit/Concerns/HasTableRecordKeyOverrideTest.php) pins it over a fixture host whose override is a closure.

## Divergences

### The host id is a random `LiveProp`, rather than a framework component id

Filament prefixes every morph key with `$this->getId()`, Livewire's own component id, which is part of the snapshot and so stable across a re-render.
UX LiveComponent has no equivalent a template can read: the id on the root element is generated by the frontend controller, so nothing server-side can address it while rendering.

`getTableHostId()` mints its own, `bin2hex(random_bytes(4))`, lazily on first read, into `#[LiveProp] public ?string $tableHostId`, so the same eight hex characters come back on every later render of that component.
The value is never parsed or addressed from JS; it only has to be the same before and after a round trip, and different enough between two tables on one page that their row ids do not collide.
Actions ships the same shape as [`getActionsHostId()`](../actions/modals.md#the-host-id-is-a-round-tripped-liveprop-not-a-framework-component-id), and the two are separate values because the packages do not depend on each other in that direction.

The getter/property name collision is what makes the lazy fill work.
`AddLiveAttributesSubscriber::onPreRender` dehydrates every `#[LiveProp]` before the component's template body renders, so an id first minted while rendering the table partial would miss that pass, and the next request would hydrate null and mint a different id for every row.
It does not happen, because `LiveComponentHydrator::dehydrate()` reads the prop through Symfony's `PropertyAccessor`, which prefers the same-named getter, so the read that dehydrates is the read that mints.
Renaming either half breaks morph stability with nothing failing loudly, which is why the Integration [`HasTableTest`](../../../packages/tables/tests/Integration/Concerns/HasTableTest.php) asserts the dehydrated `tableHostId` against the id stamped on the rows.
On a prop the client also writes, the same preference puts the getter's answer on the wire, which is what [the writable props bridge around](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary).

A host rendered without being a live component, a plain Twig component or a unit fixture, still gets an id: the lazy read works with no hydration behind it, and the id is regenerated per request, which is right for a render that never morphs.
If UX exposes a server-readable component id, this becomes a one-line delegate and the `LiveProp` goes away.

### The table is built lazily on first `getTable()`, not in a booted hook

Filament assigns `$this->table = $this->table($this->makeTable())` inside `bootedInteractsWithTable()`, a Livewire hook that fires on every request before anything else, and `getTable()` is then a plain property read.
UX LiveComponent has no `booted*()` counterpart: `#[PostHydrate]` and `#[PreDehydrate]` fire only on the hydration path, so a first render, where the component is constructed rather than hydrated, would leave the property unassigned.

`getTable()` therefore builds on first call and memoises into a nullable `$cachedTable`, the lazy shape [`HasSchemas`](../schemas/has-schemas.md) uses for its schemas.
A mounted host builds its table regardless, because the `#[PostMount]` hook `mountTableFilters()` calls `getTable()` unconditionally; a host reached outside the mount path, a plain `new` or a hydration-only request that touches nothing, never builds it.
What is lost is Filament's guaranteed build order: anything that needs the table calls `getTable()` and gets it built on the spot.
The mount-only half that `mountInteractsWithTable()`'s flag gates in `bootedInteractsWithTable()`, the session reads and the state seeding, becomes `#[PostMount]` hooks on the concerns that own each piece, beside `#[PostHydrate]` hooks such as the column manager's.
Permanent, unless UX adds a boot hook that runs on construction and hydration alike.

### `makeTable()` seeds nothing from deprecated host getters

Filament's `makeTable()` is a `->when()` chain reading `getTableColumns()`, `getTableFilters()`, `getTableEmptyStateHeading()` and the rest off the host, every one of them `@deprecated Override the table() method to configure the table.`
Sylament's `makeTable()` is `Table::make($this)` and nothing else: there is no earlier Sylament API to stay compatible with, so the port takes the endpoint Filament's deprecation notice points at, and a host configures its table by overriding `table()`.
The same reasoning keeps [`schema()` out](../schemas/component.md#the-schema-alias-is-not-ported) as an alias for `components()`.

### The deprecated host shims are not ported, because each points at a surface that ships

Filament marks whole host traits and members `@deprecated`: `CanPollRecords`, `CanBeStriped`, `HasEmptyState`, `HasContent`, `HasHeader` and `HasRecordAction` trait and member alike, `HasRecords`' `allowsDuplicates()`, `getTableRecordTitle()`, `getTableModelLabel()` and `getTablePluralModelLabel()`, `CanDeferLoading::isTableLoadingDeferred()` and `InteractsWithTable::getTableQuery()`, each with `Override the table() method to configure the table.`
`Concerns\HasActions`' members are deprecated delegates naming their replacement on actions' host, `mountAction()`, `callMountedAction()`, `getMountedAction()`, `unmountAction()` and the rest, apart from `getTableActions()` and `getTableActionsColumnLabel()`, which carry the same `table()` notice.
None is ported, because the port configures a table only through `table()`, so each shim's replacement is tracked where it lives: the table methods, such as `poll()` ([can-defer-loading.md](can-defer-loading.md#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers)), `striped()`, the empty-state family, `query()`, `recordTitle()` and `recordAction()`, on [`Table`](table.md#surface), and actions' own host methods, through which a table-context mount resolves with `getTableRecord()` ([has-actions.md](../actions/has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference)).

### The `records()` closure gets Filament's eight injections, three of them typed differently

Filament evaluates the data source with eight named injections, `columnSearches`, `filters`, `page`, `recordsPerPage`, `search`, `sort`, `sortColumn` and `sortDirection`, each a `fn ()` reading host state only if the closure asks for it, and the port passes the same eight in the same shape.
`sort` is the same `[$column, $direction]` pair, redundant with the singular names and kept because a migrating closure destructures it, and `columnSearches` is the same flat, dotted, trimmed map `getTableColumnSearches()` hands the query lane, so `['artist' => ['name' => ' x ']]` reaches the closure as `['artist.name' => 'x']`.
Three types differ: `filters` is typed `array` where Filament's is `?array`, because the prop is [an array and never null](host-has-filters.md#the-filter-bags-are-arrays-never-null-and-carry-no-getters); `page` is `int` where Filament's is `int|string`; and `recordsPerPage` is [nullable](host-can-paginate-records.md#the-recordsperpage-injection-is-nullable) where Filament's is `int|string`.
A migrating `fn (?array $filters)` still matches.

Everything else the evaluation resolves without a named injection: `$table`, by the parameter name that is `Table`'s `$evaluationIdentifier` or by a `Table` type hint, `$liveComponent` by name through [`Table`'s resolver override](table.md), any explicit named or typed injection a caller passes to `evaluate()`, and a public container service [by type](#a-records-closure-resolves-public-container-services-by-type).

### The filtered-query seam is gated on the lane, not on the presence of a builder

Filament's `getFilteredTableQuery()` answers null only when there is no builder to read, and leaves the lane question to its callers, which answer it unalike: `getAllTableRecordsCount()` counts what comes back with no check, `getAllSelectableTableRecordKeys()` calls the seam before it tests `hasQuery()`, and `getGroupedSelectableTableRecordKeys()` tests `hasQuery()` first.
So a Filament table declaring both a `records()` closure and a `query()` resolves the closure for its rows and still hands the builder to anything calling the seam directly.
Sylament asks in the seam: no [`hasQuery()`](has-query.md#the-query-lane-is-on-exactly-while-no-records-closure-is-set), no query, and `getFilteredSortedTableQuery()` answers null with it.

The caller that needs it is [`getAllTableRecordsCount()`](#the-all-records-count-answers-four-ways-where-filaments-answers-two): the null sends a records-lane table to count what its closure returned, where a builder counted would run SQL for rows the render does not show, against a data source the table does not use.
`resolveTableRecords()` takes the same branch either way, because it consults the data source first, so the gate is observable only through a direct read of the seam, which [`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php) pins.
Permanent while both lanes exist.

### The URL binding is a per-prop `LiveProp` modifier, opted into by the page

Filament binds seven names to the query string through two mechanisms.
Six ride Livewire's `#[Url]` attribute, redeclared on `ListRecords` with a fixed literal each: `sort`, `search`, `filters`, `tab`, `grouping` and `reordering`.
`page` rides Livewire's pagination binding, `HandlesPagination::queryStringHandlesPagination()` returning `['paginators.<name>' => ['history' => true, 'as' => $pageName, 'keep' => false]]` with `$pageName` from `getTablePaginationPageName()`, which is why `page` is the only name `getIdentifiedTableQueryStringPropertyNameFor()` disambiguates per table.
Six names ship, `page` (identifier-prefixed), `sort`, `search`, `filters`, `tab` and `grouping`, through one mechanism UX offers and Livewire does not.

The lever is `modifier:`, not `fieldName:`.
Each URL-candidate `LiveProp` names a two-line modifier, `modifyTablePageProp()`, `modifyTableSortProp()`, `modifyTableSearchProp()`, `modifyTableColumnSearchesProp()`, `modifyTableFiltersProp()`, `modifyTableGroupingProp()` and `HasTabs`' `modifyActiveTabProp()`, each asking the host's `getTableUrlMapping(string $prop): ?UrlMapping` and calling `$liveProp->withUrl($mapping)` when it answers one.
`withUrl()` clones the attribute, `LiveComponentMetadata` re-runs modifiers on every metadata read, and the seed, the write and the client-attribute paths all honour the result, so the binding is decided per host at runtime, which an attribute cannot express.
The default is null, so a showcase card, a modal-hosted table and a [relation manager](../panels/relation-manager.md) bind nothing, and only `ListRecords` and `ManageRelatedRecords` override it.
`fieldName:` would not do, because it is also the wire name: renaming `tableSearch` would rename it under every template binding at once.

Two rules bind an override.
It stays cheap and side-effect-free, because modifiers run inside `LiveComponentHydrator::hydrate()`; and no modifier may call `getTable()`, because that runs before `#[PostHydrate]` stamps `_sylament_panel` onto the request, so a table built there resolves the default panel and memoises that answer for the rest of the request.
That is why the page-parameter prefix comes from `getTableQueryStringIdentifier()`, a host hook a page answers from a static value, rather than from [`Table::queryStringIdentifier()`](table.md#surface), which [the next entry](#the-query-string-identifier-is-live-on-the-table-and-dead-in-the-modifiers) covers.

Four constraints of UX's URL writing are accepted rather than worked around, and the binding ships no JavaScript.

1. **replaceState only.**
   The URL is written server-side through the `X-Live-Url` response header and applied with `history.replaceState`, so table state is not back-button navigable, where Livewire pushes state for pagination.
2. **Residue at defaults.**
   There is no omit-at-default rule: `http_build_query` drops only `null` and `[]`, so after the first live round trip the URL carries `?page=1&search=&tab=<key>` even where nothing changed.
   A plain first GET never rewrites the URL, so a clean link stays clean until the user interacts.
3. **PHP bracket arrays.**
   A filter bag serializes as `filters[status][value]=x` where Livewire writes JSON.
   The read side matches, so a copied URL round-trips; only its shape differs from Filament's.
4. **`onUpdated` does not fire on a seed.**
   A seeded value is written straight through the property accessor, so `updatedTableSearch()` never runs for it, which is why `?search=` does not reset the page, and why every check the action-side writer runs is repeated where the seed passes through.
   `getTablePage()` both floors and caps the page, because `?page=` coerces to `int 0` on a non-nullable prop at one end and accepts `PHP_INT_MAX` at the other, where the offset multiplication overflows to a float `setFirstResult()` refuses.
   `sort` is [healed at mount](host-can-sort-records.md#a-seeded-sort-is-healed-at-mount-where-filaments-is-trusted-to-the-query), and `filters` is [handled at its own seed](host-has-filters.md#a-seeded-filter-bag-fills-the-form-instead-of-the-declared-defaults).

`reordering` is not bound: it is a transient UI mode rather than browsing state, and binding it would leave a permanent `reordering=0` in every URL under constraint 2.
`grouping` is bound, because a grouping is browsing state ([grouping.md](grouping.md#a-bookmarked-grouping-beats-a-selectable-default-group)), and `perPage` and the column state stay session-or-transient as Filament has them.
The per-column search terms are not bound either, because Filament's `ListRecords` declares no `#[Url]` for `tableColumnSearches`; `modifyTableColumnSearchesProp()` still asks the hook, so a page that wants the terms in its URL answers `getTableUrlMapping('columnSearches')`.

### The query-string identifier is live on the table and dead in the modifiers

Filament ships both halves of the name prefix and only one is reachable.
`getIdentifiedTableQueryStringPropertyNameFor()` reads `getTable()->getQueryStringIdentifier()` and nothing else, so the `Table` setter is the live half, and the host's `getTableQueryStringIdentifier()` returns null and is read by no caller in Filament's source.
Ours inverts that.
The host hook is the live half: `InteractsWithRelationshipTable` overrides it with the host class's basename, lower-cased at the first letter, and both routed pages compose the `page` mapping from it, so a plain `ListRecords`, answering null, binds a bare `?page=` and only a tier reached through another takes a prefix.
`getIdentifiedTableQueryStringPropertyNameFor()` reads the `Table` setter first and the host hook second, and has no production caller: Filament's one caller is `getTablePaginationPageName()`, which feeds Livewire's `WithPagination` and has no counterpart here.

The cause is the rule above, that a `LiveProp` modifier cannot build a table, and the consequence is behavioural: `->queryStringIdentifier('foo')` inside `table()` changes what `getIdentifiedTableQueryStringPropertyNameFor()` answers and not the `?page=` parameter the page binds, because the mapping is resolved before the table exists.
A page wanting a different prefix overrides `getTableQueryStringIdentifier()`.

### `getTableRecords()` returns a keyed array or a `Paginator`, never a `Collection`

Filament returns a `Collection | Paginator | CursorPaginator`.
Sylament returns `array<array-key, object|array<string, mixed>> | Paginator`, where `Paginator` is support's [own carrier](../support/pagination.md).

The keying is Filament's `mapWithKeys()` contract: each entry is re-keyed by the record key, and an array record's `__key` entry is synthesised from the source array's own key when absent and put through the same printable-key rule the object arm uses, so `$records[$key]` is the lookup a row-scoped action needs.
A returned `Paginator` is re-keyed too, through `withRecords()`, so the carrier that comes back is a clone of the closure's with the counts untouched, and `getTableRecord()` unwraps it before the lookup.

There is no `Collection` to return, since the Laravel collection pipeline has no port and nothing in the package consumes one, and no `CursorPaginator`, since the closure lane has nothing to build a cursor from.
The `Traversable` and array arms both fall out of one `foreach`, so a generator yielding the same key twice loses the earlier record, the same last-writer-wins rule two records sharing an `id` get.

Anything else the closure returns is a `LogicException` naming both accepted shapes; Filament has no such guard, because Eloquent's builder cannot return garbage, while a userland closure can.
The rows inside are checked the same way.
A row that is neither an object nor an array throws a `LogicException` naming the table and the type that arrived.
An array whose keys are not all strings is refused, because every column read and the `__key` entry address a record by field name.
A `__key` entry that is not printable, an array, a non-`Stringable` object or a blank, is refused.

### Object records are keyed by a readable `id`, rather than an Eloquent key

Filament's non-array arm is `$record->getKey()`, since every record is a `Model`.
Sylament's record is any `object`, usually a Doctrine entity but equally a DTO, so there is no `getKey()` to call, and `getTableRecordKey()` reads `id` through schemas' [`PropertyAccessor::getFromRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php), the accessor a [`Column`](column.md) reads its state with, so a public property, a getter or a `__get` all satisfy it.

A `Stringable` id is string-cast before the scalar check, so a `Symfony\Component\Uid\Uuid` keys a table without unwrapping.
An id that is missing, unreadable (`AccessException`) or [blank](../support/helpers.md) throws a `LogicException` naming the record class and pointing at the array-record alternative, because the key addresses a record across a request boundary.
Nothing configures the property name on this arm: a record whose identity is not `id` comes down the query lane below or uses the array form and its `__key` entry.

On the query lane the mapping answers instead.
`resolveQueryRecordKey()` runs before the `id` read and, when it returns a key, is the whole answer: for a table with a `query()` whose [record class](has-query.md#the-record-class-is-read-off-the-base-querys-root-entity) the record is an instance of, the key is [`RecordKey::of()`](../schemas/record-key.md)'s, so a `Book` mapped on `code` keys by `code` with nothing configured.
Everything else falls through to the `id` convention: array records, every record on the closure lane, and on the query lane any object that is not the table's entity, such as a `SELECT NEW` projection or an object handed straight to `getTableRecordKey()`.
A host that overrides the method must answer the same key on this lane for every row the record class hydrates, or the first render refuses it ([semantics](#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec)).

The guard asks the table what its record class is and tests the record with `instanceof`, rather than asking Doctrine whether the record's own class is mapped, because `AbstractClassMetadataFactory::isTransient()` returns false for an anonymous class while `getMetadataFor()` throws `MappingException` for one, so an anonymous DTO would pass the transient check and fail on the metadata read.
Reading the declared class also keys an anonymous subclass of an entity, a test double, by its parent's mapping, because the class travels as `RecordKey::of()`'s third argument.
A composite identifier encodes every field in mapping order into one string, `1~2` for a scalar pair and `main-hall~2026-spring` for a derived identity whose association component is flattened to its target's key; a class with one identifier field encodes to the flattened value verbatim, so the [grammar and its alphabet](../schemas/record-key-contract.md#the-grammar) apply to a composite key only.

The action side has a same-shaped twin, [`InteractsWithRecord::resolveRecordKey()`](../actions/interacts-with-record.md#resolverecordkey-reads-id-and-answers-null-for-a-record-with-no-usable-key): the same `id` read through the accessor, the same `Stringable` unwrap, the same `LogicException` for an array record missing its key entry, but it returns null for an unkeyable object where this throws.
The asymmetry matches Filament's own pair: a table building its row index cannot proceed without a key, while an action assembling a mount context can omit `recordKey` and stay mountable.
They are two implementations of one convention, so a change to the read changes both.
The private `toRecordKey()` both arms here share is narrower than the codec's [printable rule](../schemas/record-key-contract.md#the-api): it unwraps a `Stringable` and refuses null, a blank string and a non-scalar, while `IdentifierFields::printable()` also unwraps a `BackedEnum`, prints a `bool` as `'1'`/`'0'` where `toRecordKey()` prints `false` as an empty string, and refuses a non-UTF-8 string or a NUL byte that `toRecordKey()` passes.

The twin has no metadata arm, because an action has no table to ask: a mounted action carries a key back, and [`resolveTableAction()`](../actions/has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) turns it into a record with `getTableRecord()`.
For the outbound half, `Action::getContext()`'s `recordKey` for a row-scoped action, `getContext()` asks the table for the key when the action has a table and the record is an instance of its record class, and falls back to `resolveRecordKey()`'s `id` only otherwise ([action.md](../actions/action.md#getcontext-drops-the-record-model-gate)).

### A query-lane override of `getTableRecordKey()` the codec cannot read back is refused, where Filament's misses in silence

Filament lets a host override `getTableRecordKey()` on an Eloquent table and then reads every key back through `find()`, `whereKey()` and `whereKeyNot()`, which never ask it, so an override answering anything but the model key resolves no record, selects nothing, and after a select-all with a row unticked acts on the unticked row too, all without an error.
The port would fail the same way and in three places more ([the semantics](#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec)), and one shared failure deletes rows the user deselected, so the query lane refuses such an override by name at the first render.
It takes nothing a migrating user could rely on, since no read-back honours the override there.
The `records()` lane keeps Filament's freedom, since it reads keys back through the method itself.
Reverse it only if the query lane grows a host-side decode that pairs with the override.

### `getTableRecord()` reads fresh when the render cache is cold

Filament resolves a record down two lanes that disagree: the query lane runs `$query->find($key)`, a fresh read that never touches `$cachedTableRecords`, and the records lane is `$this->getTable()->getRecords()[$key] ?? null`, which goes through `getTableRecords()` and so fills and reads the render cache.

Sylament gives the records lane the query lane's semantics: `($this->cachedTableRecords ?? $this->resolveTableRecords())[$key] ?? null` reads a warm cache when one exists and otherwise resolves fresh without storing the result.
A record action resolves its record before it mutates anything, so a cache filled by that resolution is a pre-mutation snapshot, and the re-render after the mutation would paint a deleted row as still there; leaving the cache cold makes the render evaluate the data source after the mutation.
The price is one extra data-source evaluation per action round trip, which is what Filament's query lane pays for the same guarantee.
[`flushCachedTableRecords()`](../../../packages/tables/src/Concerns/HasTable.php) is a separate member because the cold read covers an action that resolves before anything has rendered, and the flush covers one that mutates after something has.
Permanent: this diverges from a Filament lane read as defective.

The query lane resolves fresh every time.
`resolveQueryTableRecord()` takes the branch on [`hasQuery()`](has-query.md#the-query-lane-is-on-exactly-while-no-records-closure-is-set) before the cache is read, and runs `Table::getQuery(isResolvingRecord: true)`, the base query with its [scopes](has-query.md#every-read-clones-a-memoised-base-query) applied under that flag so a scope can tell a row lookup from a render, narrowed by [`IdentifierConstraint::equals()`](../schemas/record-key-contract.md#the-operations-and-their-dql) under `sylament_record_key`.
Every field name is read off the mapping metadata, and every value is bound typed by its field's DBAL type, because an untyped `'abc'` against an integer column is a driver error on PostgreSQL.
A key malformed for the class, of the wrong arity, with a non-canonical escape or with a component its field's type cannot hold, answers null without running a query; that is what a row action, a column's own `action()` and an editable cell, the method's three call sites, get from it.
Selection, the reorder write and the by-key delete never call it: they narrow through [`IdentifierConstraint::in()`](../schemas/record-key-contract.md#parameter-naming-and-what-an-empty-or-malformed-key-means), where a malformed key is dropped from the list and a statement still runs.
The panel's `{record}` binding does not come through here either: [`Resource::resolveRecordRouteBinding()`](../panels/resource.md#a-record-is-resolved-through-the-resources-own-query) reaches the same strict decode by its own route.
A bare table names its three lanes, and anything that hydrates to a non-object (a scalar or partial select) answers null.

It applies no search, so a record the search box excludes still resolves for its row action; no pagination, so a record that left the current page between render and mount resolves; and no sorting, which would only cost an `ORDER BY` on a single-row read; all three as Filament's lane does.
It applies the filters, as Filament's does, through [`applyFiltersToTableQuery($query, isResolvingRecord: true)`](#filtertablequery-applies-the-filters-then-the-search) before the identifier narrowing.
The row limit Filament's `find()` carries is a deviation with [its own entry](#record-resolution-applies-no-limit-1-where-filaments-find-does).

### Record resolution applies no `LIMIT 1` where Filament's `find()` does

Filament resolves with `$query->find($key)`, and Eloquent's `find()` is `whereKey($id)->first()`, which is `limit(1)->get()->first()`.
`resolveQueryTableRecord()` ends at `getOneOrNullResult()` with no `setMaxResults(1)`, which reads as a missing line and is not.

The limit is free in Eloquent because a `with()` eager load is a second query matched onto the parents afterwards.
Doctrine's fetch join selects the relation in the same query, so a limit truncates it: the root comes back holding one element in a collection that reports itself complete, the row-multiplication trap [the Tools paginator](host-can-paginate-records.md#a-paginated-query-lane-table-pages-through-doctrines-tools-paginator-and-only-a-collection-join-switches-it-to-identifiers-first) exists to survive on the page query, here corrupting a single resolved record silently.

It buys nothing in exchange.
The identifier predicate admits at most one root, and object hydration returns one entity per identity however many rows the join produced, so `getOneOrNullResult()` cannot throw from row multiplication; a query that does return two roots raises Doctrine's `NonUniqueResultException` rather than answering with the first.
Record resolution takes neither the [eager joins](#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone) nor the aggregate batch, so only a `query()` closure can fetch-join this clone, and the port cannot know whether the user's declaration does.
Permanent; [`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php)'s fetch-join resolution case asserts all three of an `Artist`'s songs and fails the moment the limit comes back.

### The all-records count answers four ways where Filament's answers two

Filament's `getAllTableRecordsCount()` is two lines: a `LengthAwarePaginator` in `$cachedTableRecords` answers with its `total()`, and everything else is `getFilteredTableQuery()->count()`, which fatals on a `records()` table, since that seam [is not lane-gated](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder) there.
Sylament's has four arms, the two extra being a lane that is not a query and a carrier whose total is legitimately unknown.

On the query lane a cached carrier with a known total answers from itself, with no second `COUNT` for a number already paid for; a rendered unpaginated table caches a plain array, which carries no total, so it pays the count like a cold one.
Otherwise the answer is a `COUNT` over [`getFilteredTableQuery()`](#filtertablequery-applies-the-filters-then-the-search) through Doctrine's `Tools\Pagination\Paginator`, constructed with the same `fetchJoinCollection` [join heuristic](host-can-paginate-records.md#a-paginated-query-lane-table-pages-through-doctrines-tools-paginator-and-only-a-collection-join-switches-it-to-identifiers-first) `paginateTableQuery()` uses.
That flag is inert on this call in the installed ORM: `Paginator::count()` never reads it, its count query counting distinct roots through `CountWalker::HINT_DISTINCT` either way, and only `getIterator()` branches on it; it is passed so one heuristic lives in one place.
A composite root, two or more identifier fields, whose query carries its own output walker is [refused by name](host-can-paginate-records.md#an-output-walker-of-the-querys-own-is-refused-by-name-only-where-doctrines-fallback-cannot-serve-a-keyed-root) in front of the count, because `Paginator::count()` falls back to `CountWalker` there and that walker resolves the root through one identifier column; a single derived identity is a path `CountWalker` resolves, and counts.
Filters and search are applied and pagination is not, which is what all records means: the number a footer's of N renders under a filtered page.

The records lane has no query to count, so a plain array counts itself and a carrier answers with its total.
A carrier with a null total, support's [simple-pagination shape](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both), throws a `LogicException` naming the limitation, because a closure that skipped counting cannot be asked for the count afterwards, and `0` or the page size would be a number the caller believes.
A query-lane table in `Simple` mode is never in that position: its carrier carries no total either, but the query is there, so the fall-through counts it.

Filament's two callers are its `assertCountTableRecords()` test helper and panels' `ExposesTableToWidgets`, which hands the number to a page's widgets, and neither is ported.
Its one caller here is `HasBulkActions::getAllSelectableTableRecordsCount()`, which falls back to it when the cached carrier has no total, a trait of the same host, so the method stays off the [contract](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six).

### Eager loading runs over the visible columns, on the page clone alone

Eager loading is a loop over [`$table->getVisibleColumns()`](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again), calling [`applyEagerLoading()`](interacts-with-table-query.md#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality) on each, after `getFilteredSortedTableQuery()` returns and before the query is paginated.
Filament loops over the same visible set, and skips the loop under `groupsOnly()` as the port does ([grouping.md](grouping.md#the-php-gates-of-groupsonly-are-unconditional-where-the-templates-are-not)), so a to-one column the developer hid or the user toggled off costs no `LEFT JOIN` in either, and the [global search loop](can-sort-records.md#sorting-and-global-search-both-skip-a-hidden-column) skips a hidden column the same way.

The delta is the clone.
Filament's loop lives inside `filterTableQuery()`, so its eager loads land on the filtered query the count path also reads; ours run one step later, so they reach the page query and nothing else.
The count clone stays join-free, counting what the search matched, and record resolution, which builds its own clone through `getQuery(isResolvingRecord: true)`, never passes this seam, so a resolved record lazy-loads what a rendered row fetch-joined: it is one row, and the alternative is [a fetch join under a row limit](#record-resolution-applies-no-limit-1-where-filaments-find-does).
A test pins both halves by asserting the standalone count and the record resolution each issue exactly one statement with no `LEFT JOIN`.

The aggregate store is filled by `fillTableAggregateValues()`, called from `keyTableRecords()`, the funnel every arm passes through, because the batch needs the page's keys and those exist only once the records are keyed.
It is a no-op off the query lane, for a page with no keys, and under `groupsOnly()`, where Filament fires its aggregate selects regardless.
Filament also applies its aggregates on record resolution, and the port does not, because a resolved record feeds a row-scoped action, not a rendered cell, and the store is keyed per column for cells; a consumer that reads aggregates off a resolved record, such as an export, would reverse it.
