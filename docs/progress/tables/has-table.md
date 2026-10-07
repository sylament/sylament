---
description: 'The host axis of the tables package: the trait `Sylament\Tables\Concerns\HasTable` a UX Live Component mixes in to own a `Table`, and the contract… (draft)'
ports:
  - vendor/filament/tables/src/Concerns/InteractsWithTable.php
  - vendor/filament/tables/src/Concerns/HasRecords.php
owns:
  - packages/tables/src/Concerns/CanDeferLoading.php
  - packages/tables/src/Concerns/CanGroupRecords.php
  - packages/tables/src/Concerns/CanSummarizeRecords.php
  - packages/tables/src/Concerns/HasTable.php
---
# HasTable / InteractsWithTable (tables)

The host axis of the tables package: the trait [`Sylament\Tables\Concerns\HasTable`](../../../packages/tables/src/Concerns/HasTable.php) a UX Live Component mixes in to own a [`Table`](table.md), and the contract [`Sylament\Tables\Contracts\InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php) that names what the table may call back into.
Together they port Filament's [`Concerns\InteractsWithTable`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) (trait) and [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) (interface) — the labels are swapped, the same documented interface⇄trait switch schemas and actions already carry, recorded in the [rename table](../renames.md#interface-and-trait-names-are-swapped). ⚠️ Reading either name across the two codebases means checking which side it is on first.

`HasTable` itself is twenty-four methods wide: `table()`, `getTable()`, `makeTable()`, the three filtered-query seams `getFilteredTableQuery()`, `filterTableQuery()` and `getFilteredSortedTableQuery()`, `getTableRecords()`, the `resolveTableRecords()` half it caches and the two keying halves `keyDataSourceRecords()` and `keyTableRecords()` — the second reading `isFieldKeyedRecord()` to tell an array record's own key column from a list index — `getTablePaginator()`, `getTableRecordKey()` with its `resolveQueryRecordKey()` helper that reads an entity's identity off the mapping metadata and its `toRecordKey()` helper that decides which scalars are printable as one, `getTableRecord()` with its `resolveQueryTableRecord()` helper that re-queries for a single one, `getAllTableRecordsCount()`, `fillTableAggregateValues()`, `flushCachedTableRecords()`, `getTableHostId()` and the three query-string members `getTableUrlMapping()` (the per-prop [URL opt-in](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page)), `getTableQueryStringIdentifier()` and `getIdentifiedTableQueryStringPropertyNameFor()`.
It builds the table once per request, resolves its data source once per request — a `records()` closure, or the Doctrine query those three seams assemble — and hands back an array **keyed by record key** — or a [`Paginator`](../support/pagination.md) carrying such an array — plus a by-key lookup that reads fresh whenever the render cache is cold and, on the query lane, every single time, the count of everything behind that page, the flush that empties the cache, and the per-host id every row and cell is stamped with.

Around that core it composes ten host concerns, three for browsing state: [`CanPaginateRecords`](../../../packages/tables/src/Concerns/CanPaginateRecords.php) (the page and per-page state plus the `gotoTablePage()` live action), [`CanSortRecords`](../../../packages/tables/src/Concerns/CanSortRecords.php) (the `column:direction` string and the `sortTable()` cycle) and [`CanSearchRecords`](../../../packages/tables/src/Concerns/CanSearchRecords.php) (the search term) — plus [`HasFilters`](../../../packages/tables/src/Concerns/HasFilters.php) (the two filter bags, the filters form and the five filter live actions), [`HasBulkActions`](../../../packages/tables/src/Concerns/HasBulkActions.php) (the three selection props, the two resolution seams and the `selectAllTableRecords()` live action), three for session, reordering and deferred loading: [`CanPersistTableState`](../../../packages/tables/src/Concerns/CanPersistTableState.php) (the session key, the read hook and the five per-prop writers), [`CanReorderRecords`](../../../packages/tables/src/Concerns/CanReorderRecords.php) (the reorder mode flag, its toggle and the `reorderTable()` write) and [`CanDeferLoading`](../../../packages/tables/src/Concerns/CanDeferLoading.php) (the deferred window's flag and the `loadTable()` action that closes it), and two for columns: [`HasColumns`](../../../packages/tables/src/Concerns/HasColumns.php) (an editable cell's write-back, a column action's call and the request-scoped refusal bag both render through) and [`HasColumnManager`](../../../packages/tables/src/Concerns/HasColumnManager.php) (the applied and draft column bags, their mount and hydrate hooks, and the apply and reset live actions).
Each is a per-file row below rather than a folded one, per [one row per file](../../internals/trail.md#one-file-per-piece-of-code-one-row-per-feature).
What Filament's nineteen-trait host does beyond them — grouping and summaries — is folded here rather than tracked as two rows upstairs, with the features themselves in [`grouping.md`](grouping.md) and [`summarizer.md`](summarizer.md).

The trait presumes no host property beyond the fifteen `#[LiveProp]`s it and its concerns declare: `?string $tableHostId` (the persisted half of [`getTableHostId()`](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id)), `int $tablePage`, `?string $tableSort`, `array $tableColumns` (the column manager's *applied* set, non-writable so the client reaches it only through the manager's own action), the two mode flags `bool $isTableReordering` and `bool $isTableLoaded` — each mutated only by its own `#[LiveAction]`, so both stay checksummed — and the nine **writable** props the chrome binds to: `?string $tableRecordsPerPage`, `?string $tableSearch`, `array $tableColumnSearches` (the [per-column terms](host-can-search-records.md#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept), nested and seeded), `array $tableDeferredColumns` (the manager's unapplied draft), the two [filter bags](host-has-filters.md#the-filter-bags-are-arrays-never-null-and-carry-no-getters), `array $tableFilters` and `array $tableDeferredFilters`, and the three [selection bags](host-has-bulk-actions.md#the-selection-is-server-canonical-and-re-derived-on-every-read), `array $selectedTableRecordKeys` (whose wire name stays Filament's `selectedTableRecords`), `array $deselectedTableRecords` and `bool $isTrackingDeselectedTableRecords`.
Writability is the security line and it is [deliberate on each prop](host-can-paginate-records.md#per-page-is-validated-in-the-getter-on-every-read): page, sort and the two mode flags move only through a `#[LiveAction]` — and a seed, where the prop is [bound to the URL](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) or read back from the session, which is why a seeded page is [floored and capped in the getter](host-can-paginate-records.md#gototablepage-replaces-livewires-paginator-trio) and a seeded sort is [run through `sortTable()`'s own check at mount](host-can-sort-records.md#a-seeded-sort-is-healed-at-mount-where-filaments-is-trusted-to-the-query) — while the four a `data-model` writes are re-validated on every read; and the per-page and search props each dehydrate [in the vocabulary of the element bound to it](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary) rather than through a validating getter, which is also why [neither bag may grow one](host-has-filters.md#the-filter-bags-are-arrays-never-null-and-carry-no-getters).
A host can still mix the trait in beside [`HasSchemas`](../schemas/has-schemas.md) and [`HasActions`](../actions/has-actions.md) — the property names are disjoint, which is what keeps them composable.

## Surface

Every point of this trait and its contract.

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`table(Table $table): Table` — the identity default a host overrides](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::table()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | — |
| [`getTable(): Table` — build once, cache](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::getTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#the-table-is-built-lazily-on-first-gettable-not-in-a-booted-hook) |
| [`makeTable(): Table`](../../../packages/tables/src/Concerns/HasTable.php) | [`InteractsWithTable::makeTable()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php) | done | — | [diverges](#maketable-seeds-nothing-from-deprecated-host-getters) |
| [`getTableRecords()` — resolve the data source once, eager-load, re-key, fill the aggregate store, cache](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | in progress | — | [detail](#the-records-injections-match-filaments-eight) · [semantics](#a-records-closure-resolves-public-container-services-by-type) · [diverges](#gettablerecords-returns-a-keyed-array-or-a-paginator-never-a-collection) · [diverges](#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone) |
| [`getTablePaginator(): ?Paginator` — the carrier, unwrapped for the footer](../../../packages/tables/src/Concerns/HasTable.php) | — (Sylament-only; Filament's records **are** the paginator) | done | — | [pagination.md](../support/pagination.md) · [semantics](#the-paginator-is-a-return-value-not-a-state-holder) |
| [`flushCachedTableRecords()`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::flushCachedTableRecords()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | on the contract — actions' `callMountedAction()` calls it in a `finally` after any mounted action runs on a table host. [detail](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) |
| [`getTableHostId()` + the `$tableHostId` `LiveProp`](../../../packages/tables/src/Concerns/HasTable.php) | [`Livewire\Component::getId()`](../../../vendor/filament/tables/resources/views/index.blade.php) | done | — | [diverges](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) |
| [`getTableRecordKey(object\|array $record): string`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecordKey()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#object-records-are-keyed-by-a-readable-id-rather-than-an-eloquent-key) · [semantics](#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec) · [diverges](#a-query-lane-override-of-gettablerecordkey-the-codec-cannot-read-back-is-refused-where-filaments-misses-in-silence) |
| [`getTableRecord(?string $key)` with its `resolveQueryTableRecord()` query arm](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getTableRecord()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#gettablerecord-reads-fresh-when-the-render-cache-is-cold) · [diverges](#record-resolution-applies-no-limit-1-where-filaments-find-does) |
| [`getAllTableRecordsCount(): int`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords::getAllTableRecordsCount()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [diverges](#the-all-records-count-answers-four-ways-where-filaments-answers-two) · [diverges](host-can-paginate-records.md#an-output-walker-of-the-querys-own-is-refused-by-name-only-where-doctrines-fallback-cannot-serve-a-keyed-root) |
| [The query lane — `getFilteredTableQuery()`, `filterTableQuery()`, `applyFiltersToTableQuery()`, `getFilteredSortedTableQuery()`](../../../packages/tables/src/Concerns/HasTable.php) | [`Concerns\HasRecords`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | done | — | [semantics](#filtertablequery-applies-the-filters-then-the-search) · [diverges](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder) |
| `getTableQueryForExport()` | [`Concerns\HasRecords::getTableQueryForExport()`](../../../vendor/filament/tables/src/Concerns/HasRecords.php) | blocked | the export surface | It bypasses the seams above, re-reading `getTable()->getQuery()` and re-applying filters, search and sorting itself, to assemble a query for Filament's export action — a surface with no counterpart here, the same one [`ExportColumn`](has-cell-state.md#hascellstate-is-relocated-into-the-column-tier) waits on. It lands with that action or not at all |
| [Table state in the URL — the `getTableUrlMapping()` opt-in hook, the six `modifyTable*Prop()` `LiveProp` modifiers, `getTableQueryStringIdentifier()` and `getIdentifiedTableQueryStringPropertyNameFor()`](../../../packages/tables/src/Concerns/HasTable.php) | [`getIdentifiedTableQueryStringPropertyNameFor()`](../../../vendor/filament/tables/src/Concerns/InteractsWithTable.php), the six-attribute `#[Url]` block on [`ListRecords`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) and Livewire's own [`queryStringHandlesPagination()`](../../../vendor/livewire/livewire/src/Features/SupportPagination/HandlesPagination.php) for `page` | done | — | [diverges](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page) · [diverges](#the-query-string-identifier-is-live-on-the-table-and-dead-in-the-modifiers) |
| The `reordering` URL bind | [`#[Url(as: 'reordering')]` redeclared on `ListRecords`](../../../vendor/filament/filament/src/Resources/Pages/ListRecords.php) | skipped | — | A transient UI mode rather than browsing state, and binding it would leave a permanent `reordering=0` in every URL under [the residue constraint](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page). The `grouping` bind beside it is a different answer: it [ships](grouping.md#a-bookmarked-grouping-beats-a-selectable-default-group), because a grouping is browsing state |
| [Deferred loading — `$isTableLoaded`, `loadTable()`, `isTableLoaded()`](../../../packages/tables/src/Concerns/CanDeferLoading.php) | [`CanDeferLoading`](../../../vendor/filament/tables/src/Concerns/CanDeferLoading.php) | done | — | the trait's fourth member, `isTableLoadingDeferred()`, is `@deprecated Override the table() method` and is not ported — what it fronts is [`Table::deferLoading()`](can-defer-loading.md#the-deferred-window-is-server-gated-and-client-closed). [can-defer-loading.md](can-defer-loading.md#the-deferred-window-is-server-gated-and-client-closed) |
| Host polling — `getTablePollingInterval()` | [`CanPollRecords`](../../../vendor/filament/tables/src/Concerns/CanPollRecords.php) | skipped | — | The whole trait is `@deprecated Override the table() method to configure the table.`, one host getter returning null, and the project has never ported a deprecated Filament surface. The interval it fronts is [`Table::poll()`](can-defer-loading.md#the-poll-interval-parses-strictly-and-carries-no-livewire-modifiers), which ships |
| The deprecated `*TableAction` host shims — `mountTableAction()`, `callMountedTableAction()`, `getMountedTableActionRecord()`, `unmountTableAction()`, `getTableRecordAction()`, … | [`Concerns\HasActions`](../../../vendor/filament/tables/src/Concerns/HasActions.php), [`HasRecordAction`](../../../vendor/filament/tables/src/Concerns/HasRecordAction.php) | skipped | — | Every method in both files is a one-line deprecated delegate, and the project has never ported a deprecated Filament surface. What they front is built: the host resolves a table-context mount through actions' [`resolveTableAction()`](../actions/has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) and binds the record through `getTableRecord()` |
| [Grouping — the `$tableGrouping` prop, `groupTable()`, `mountTableGrouping()`, `getTableGrouping()`, the two string readers, `getTableRecordGroupBoundaries()` and `applyGroupingToTableQuery()`](../../../packages/tables/src/Concerns/CanGroupRecords.php) | [`Concerns\CanGroupRecords`](../../../vendor/filament/tables/src/Concerns/CanGroupRecords.php) | done | — | [grouping.md](grouping.md) — `updatedTableGroupColumn()` apart, a Livewire hook named for a property Filament no longer has, whose page reset is [inline in `groupTable()`](grouping.md#a-grouping-change-resets-the-page-and-flushes-the-record-cache) here |
| [Summaries — `getAllTableSummaryQuery()`, `getPageTableSummaryQuery()`, `getGroupedTableSummaryQuery()`, `getTableSummarySelectedState()` and `shouldRenderTrailingGroupedTableSummary()`](../../../packages/tables/src/Concerns/CanSummarizeRecords.php) | [`Concerns\CanSummarizeRecords`](../../../vendor/filament/tables/src/Concerns/CanSummarizeRecords.php) | in progress | — | [summarizer.md](summarizer.md) — the four query members are on [the contract](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six) because the partial reads each one; the probe stays off it, since only the boundary map calls it. `$modifyQueryUsing` and the batched `GROUP BY` it served are [not ported](summarizer.md#the-per-group-figure-is-one-scoped-query-per-group-not-one-group-by) |
| The deprecated host presentation shims — `isTableStriped()`, `getTableEmptyState()`, `getTableEmptyStateActions()`, `getTableEmptyStateDescription()`, `getTableEmptyStateHeading()`, `getTableEmptyStateIcon()` | [`Concerns\CanBeStriped`](../../../vendor/filament/tables/src/Concerns/CanBeStriped.php), [`Concerns\HasEmptyState`](../../../vendor/filament/tables/src/Concerns/HasEmptyState.php) | skipped | — | Both files carry `@deprecated Override the \`table()\` method` on the trait *and* on every member, and the project has never ported a deprecated Filament surface. What they front ships on the table object — `striped()` and the whole empty-state family, [both **done** there](table.md#surface) |
| `HasContent`, `HasHeader` | [`Concerns\HasContent`](../../../vendor/filament/tables/src/Concerns/HasContent.php), [`Concerns\HasHeader`](../../../vendor/filament/tables/src/Concerns/HasHeader.php) | todo | — | — |

Nineteen rows are **done**.
Ten are the trait's own core — the build-once pair with `makeTable()`, the record key, the by-key lookup with its flush, the all-records count, the three filtered-query seams, the host id, and the paginator peek — and nine belong to the state layer: the filter mutations and `resetTableSearch()`, [grouping](grouping.md) and the [grouped selection](grouping.md#the-whole-universe-arm-costs-one-query-per-group-on-the-page) beside it — whose own summaries half stays **in progress**, against [`Count::icons()`](summarizer.md#counticons-ships-as-api-and-refuses-to-summarize) — the [URL binding](#the-url-binding-is-a-per-prop-liveprop-modifier-opted-into-by-the-page), [session persistence](can-persist-table-state.md#the-session-key-is-the-host-class-so-two-instances-of-one-page-share-it), [deferred loading](can-defer-loading.md#the-deferred-window-is-server-gated-and-client-closed) and [reordering](host-can-reorder-records.md#the-reorder-write-is-a-unit-of-work-flush-not-one-mass-case-update), and [per-column search](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column) — the column manager beside it being still **in progress**, against [`ColumnGroup`](column-manager.md#the-columngroup-half-is-the-whole-of-what-is-left).
Those four carry Integration suites of their own — [`TableUrlBindingTest`](../../../packages/tables/tests/Integration/TableUrlBindingTest.php), [`TableSessionPersistenceTest`](../../../packages/tables/tests/Integration/TableSessionPersistenceTest.php), [`TableDeferredLoadingTest`](../../../packages/tables/tests/Integration/TableDeferredLoadingTest.php) and [`TableReorderingTest`](../../../packages/tables/tests/Integration/TableReorderingTest.php), beside the Unit [`CanReorderRecordsTest`](../../../packages/tables/tests/Unit/Concerns/CanReorderRecordsTest.php) that pins the write itself — because each is a `#[PostMount]` or a `#[LiveAction]` whose behaviour only exists once a component is really mounted.
The core ten are covered by [`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php) via the [`TableHost`](../../../packages/tables/tests/Fixtures/Concerns/TableHost.php) fixture, which takes the host's `table()` body as a constructor closure: the cache is proved by identity (`getTable()` returns the same instance) *and* by a configuration counter that must read exactly `1`, so a second build would fail even if it happened to produce an equal object.
`getTableRecords()` is covered on every path in the same file — the fetch-once counter, both data-source `LogicException`s, the object arm keyed by `id`, the array and `Traversable` arms with their `__key` synthesis (from the source key, and an explicit entry winning over it), the string cast, and the last-writer-wins collision.
`getTableRecordKey()`'s three refusals sit beside them: an object whose `id` is null, one with no `id` property at all — the `AccessException` arm — and, called directly because resolution always synthesises one, an array with no key entry.
Its **return** is pinned by calling it directly rather than reading a map key, which PHP would coerce back to an `int`: an integer `id` and a `Stringable` one both come back as the `string` the contract promises.

The query lane is covered in the same file, against the in-memory SQLite [`EntityManagerFactory`](../../../packages/tables/tests/Fixtures/Doctrine/EntityManagerFactory.php) rig rather than a mocked builder, so every case runs through Doctrine's own hydration.
Resolution is pinned end to end — two seeded `Song`s coming back as entities under their identifiers, and a `modifyQueryUsing()` scope narrowing the same query to one — and so is the precedence between the lanes: a table declaring `records()` *and* `query()` resolves the closure, keys its entities by the `id` convention, and never evaluates the query at all, which a counter on the query closure proves.
A second case reads the same both-configured table through the seams instead, where `getFilteredTableQuery()` and `getFilteredSortedTableQuery()` must both answer null — the assertion that keeps the [lane gate](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder) from drifting back to "is there a builder to be had".
The metadata keying has eight cases of its own, none of them reachable by that convention: a `Book`, whose identifier is `code` and which has no `id` anywhere, keying by `sy-001`; a `Shortcut`, whose identifier is two strings, keying three rows that between them carry a dot, a slash, a space, the separator itself and non-ASCII — `plain~about` beside `www=2Eexample=2Ecom~=2Fblog=2F2026=2Fall=20the=20way=20down` and `example=2Ecom~=2Fclanky=2F=C5=BDofia=7EN=C3=A1vratov=C3=A1`; a `Residency`, whose identifier is a `Venue` association plus a season, keying `main-hall~2026-spring` — the association component flattened to the target's own key through the target's own DBAL type; the two composite refusals, a `Shortcut` whose second component is still blank and a `Residency` whose association half was never set; an anonymous subclass of `Shortcut` keying `example=2Ecom~double` by the **table's** record class rather than its own — the one pin on the class `RecordKey::of()` takes as its third argument, since an anonymous class has no mapping to read; and the two identifier refusals, one blank and one never initialised.
The fallback is pinned from the other side, an anonymous object carrying a public `$id` handed to `getTableRecordKey()` on a query-lane table and coming back `'7'` — the case that fails the moment the metadata read stops asking whether the record is one of the table's own entities.
Both keying cases assert array *keys*, and they disagree on type for a reason: `Book`'s survive as strings while `Song`'s `'1'` and `'2'` come back as `int`s, the same PHP coercion the closure lane's `id` cases already record.

`getTableRecord()` is pinned on both cache arms by an evaluation counter, because the difference between them is invisible in the returned record: the **cold** case asserts one evaluation for the lookup and then a *second* on the following `getTableRecords()`, which is what proves the lookup left the cache empty rather than filling it; the **warm** case reads after a render and asserts the counter never moves.
Its two null arms — a `null` key and an unknown one — are asserted in one case.
`flushCachedTableRecords()` is covered by the same counter across a read-flush-read cycle, which reaches `2` only if the flush actually emptied the cache.

Its **query** arm is pinned by what a cached page cannot answer.
The fresh-resolution case renders a page, deletes the second `Song` through a DQL `DELETE` and clears the identity map, then asserts `getTableRecord('2')` is null while the still-warm page keeps both keys — the one shape in which "reads the cache" and "re-queries" differ visibly.
Three more reach past the page the render built: a record on page 3 of a `paginated([2])` table, a record the active search term excludes, and a fetch-joined `Artist` coming back with **all three** of its songs — the case that fails the moment a `setMaxResults(1)` joins the resolution query, which is [the limit this lane does not apply](#record-resolution-applies-no-limit-1-where-filaments-find-does).
The scope flag is read through a `modifyQueryUsing()` closure recording its `isResolvingRecord` argument across a render and then a lookup, asserting exactly `[false, true]`.
Five narrower answers sit beside them: an unknown key coming back null; a bare table naming its three lanes; a `Shortcut` and a `Residency` each resolving by their own key through the per-field narrowing, which is a different method from the keying above and is reached without it; five keys that are malformed for the class (`a~b~c`, `plain` and `=2` failing the arity — `=2` is one component, so it never reaches the escape check — `plain~=61` escaping a safe byte, `plain~=2f` spelling its hex lowercase) answering null with **no statement run at all**, next to a canonical `nope~nope` that costs exactly one and finds nothing — which is what separates "refused before the bind" from "queried and missed"; and the single-identifier lane held to the same rule, where `abc`, `05`, ` 5`, `5abc`, a `7` trailed by a newline and `1~2~3` all answer null before any bind.
Three of them coerced to an integer on the way into an untyped bind: `05` and ` 5` would resolve song 5, and the newline-trailed `7` coerces just as readily — the rig seeds no song 7.

`getAllTableRecordsCount()` has one case carrying all four arms.
Seven seeded songs are counted through the query with nothing rendered; the same table with `alpha` in the search box counts **two**, which is what proves the count reads the [filtered](#filtertablequery-applies-the-filters-then-the-search) query rather than the declared one; a records-lane array counts its own length; a records-lane carrier of two records declaring a total of `23` answers `23`, which is what tells the carrier arm apart from a plain `count()`; and a null-total carrier throws.
Two more cases cover the arms those four cannot reach, both on a **rendered** query-lane table.
One pins the order: seven songs paged at two, a DQL `DELETE` behind the render, and the count answers `7` from the carrier the page was built with — then `6`, and only after `flushCachedTableRecords()`, which is what proves the known total is preferred to a second `COUNT` rather than merely agreeing with one.
The other is the same table in `Simple` mode, whose carrier carries no total at all: the count falls through to the query and still answers `7`, where reading the cached total without the null check would hand a `null` back through an `int` return.

`getTableHostId()` is pinned from the render side rather than here, in two places.
The Integration [`TableTest`](../../../packages/tables/tests/Integration/TableTest.php) reads the id off the host and asserts the `<tr>` and `<td>` the partial emitted carry exactly that prefix.
The Integration [`HasTableTest`](../../../packages/tables/tests/Integration/Concerns/HasTableTest.php) covers the half that prefix is *for*: that the id in `data-live-props-value` is the id stamped on the rows, and that a live round trip comes back with the same one.
Both fail if the [getter/property pairing](#the-host-id-is-a-random-liveprop-rather-than-a-framework-component-id) is broken, which nothing else would catch.

The three browsing concerns are covered in the same Unit [`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php).
Sorting is driven through `sortTable()` rather than by writing `$tableSort`: the three-step cycle on one column (asc → desc → unsorted), a second column starting ascending, an explicit direction winning, the numeric-column string cast, and one case asserting that **all four** refusals leave the state untouched — an undeclared column, an unknown one, a null one, and an invalid direction.
The parse is read the other way round, by setting `$tableSort` directly to a bare column and to `title:bogus`, which is the only way to reach the two arms `sortTable()` cannot produce.
Pagination covers the seeded default (the getter answering `10` while the prop stays `null`, which is what proves it never writes), the unpaginated `null`, and the four normalisation arms — a valid option, `'all'`, an out-of-range number, and outright garbage, the last two falling back to the table default rather than to the forged value.
Search covers the trim (with an NBSP), the blank-as-null read, and the all-whitespace case.
One case ties the three together: page 4 is set, then each of sort, per-page and search is changed in turn, and the page must be back at `1` every time — each change a *real* one, since an echoed value is now [a deliberate no-op](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary).

Two cases pin the halves that only exist together.
The **injection** case declares a `records()` closure taking all six names, mutates sort, page and search first, and asserts the exact array the closure saw — so a renamed injection or a stale read fails on the value, not on an arity error.
The **Paginator** case returns one from the closure and asserts the re-keyed clone comes back from `getTableRecords()`, `getTablePaginator()` and `getTable()->getPaginator()` alike, with `getTableRecord('1')` reaching into it — which is what proves the carrier is unwrapped for lookups rather than special-cased at each call site.
A closure returning an `int` is asserted to name `Paginator` in its `LogicException`, so the widened return type cannot silently accept garbage.

The `#[LiveProp]` and `#[LiveAction]` attributes carry no runtime behaviour a render test would catch, so they are pinned by reflection in [`HasTableLivePropsTest`](../../../packages/tables/tests/Unit/Concerns/HasTableLivePropsTest.php): each state prop by writability, `onUpdated` hook name, `dehydrateWith` bridge name and — since per-column search — `hydrateWith` name, and each live action by its `#[LiveArg]`-marked parameter names.
Every name a prop carries is also asserted to **exist** as a method, because the attribute holds them as strings: renaming the method without the attribute leaves it pointing at nothing, and UX raises that only at runtime.
For the bridge that is a `LogicException` from `dehydrateValue()`, which the Integration render above would catch too; for the hook it is `ensureOnUpdatedMethodExists()`, which nothing here reaches, since no test sends an `updated` payload.
Writability is a security property — a prop that silently became writable is exactly the failure [the validating getter](host-can-paginate-records.md#per-page-is-validated-in-the-getter-on-every-read) exists to survive, and this is the test that would notice.

## Details

### The `records()` injections match Filament's eight

Filament evaluates the data source with eight named injections — `columnSearches`, `filters`, `page`, `recordsPerPage`, `search`, `sort`, `sortColumn`, `sortDirection` — each a closure reading the corresponding piece of host state, so a userland `records()` closure can paginate, sort and filter its own result set.
Sylament passes **all eight**, in the same shape (a `fn ()` per name, so the state is read only if the closure asks for it).
Every one is Filament's name, Filament's arity and Filament's value — `sort` is the same `[$column, $direction]` pair, redundant with the two singular names and kept because a migrating closure destructures it, and `columnSearches` is the same flat, dotted, trimmed map `getTableColumnSearches()` hands the query lane, so `['artist' => ['name' => ' x ']]` reaches the closure as `['artist.name' => 'x']` exactly as it reaches Filament's.

Each comes from the layer that gives it state: `filters` from the filter bags, `columnSearches` from [per-column search](host-can-search-records.md#a-full-seed-replaces-filaments-reserved-key-list-and-a-blank-is-kept).
Two values differ in **type** rather than in content.
`filters` is typed `array` where Filament types it `?array`: our prop is [an array and never null](host-has-filters.md#the-filter-bags-are-arrays-never-null-and-carry-no-getters), so a migrating `fn (?array $filters)` still matches and a `fn (array $filters)` needs no null guard.
And `recordsPerPage` is [nullable here](host-can-paginate-records.md#the-recordsperpage-injection-is-nullable) where Filament types it `int|string`.

Everything the evaluation path resolves without a named injection resolves as before: `$table` — by the parameter name, which is `Table`'s `$evaluationIdentifier`, or by a `Table` type hint — `$liveComponent` by name through [`Table`'s resolver override](table.md), any explicit named or typed injection a caller passes to `evaluate()`, and a public container service [by type](#a-records-closure-resolves-public-container-services-by-type).

## Semantics

### `filterTableQuery()` applies the filters, then the search

The name is Filament's and it is now accurate: the body is `applySearchToTableQuery($this->applyFiltersToTableQuery($query))` — filters first, then [the global search](host-can-search-records.md#search-is-the-global-term-and-one-term-per-column), Filament's order.
`applyFiltersToTableQuery()` runs two passes over the one builder, every visible filter's `applyToBaseQuery()` before every visible filter's `apply()`, and returns the same builder both times.
The composition around it is Filament's as well: `getFilteredTableQuery()` reads [`Table::getQuery()`](has-query.md#every-read-clones-a-memoised-base-query) — a scoped clone, so what comes back is always a builder the caller may mutate — [gated on the lane](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder) and null when there is no query to read, hands it to `filterTableQuery()`, and `getFilteredSortedTableQuery()` adds [the sorting](host-can-sort-records.md#sorting-composes-the-users-order-the-default-and-the-identifier-tiebreaker) and nothing else.

Record resolution takes the same filters and not the rest: `resolveQueryTableRecord()` runs `applyFiltersToTableQuery($query, isResolvingRecord: true)` before narrowing on the identifier, so a row action cannot reach a record outside the filtered set — and the `isResolvingRecord` flag skips the *apply* pass of any filter that called `excludeWhenResolvingRecord()`, never the base pass, which is where a filter's joins live.
Search, sorting and pagination stay unapplied there, as Filament's `resolveTableRecord()` leaves them.

One thing Filament wraps around both passes has no port and needs none.
On a deferring table its `applyFiltersToTableQuery()` re-roots the filters form at `tableFilters`, flushes the schema's cached absolute state paths and child schemas, and puts the path back to `tableDeferredFilters` in a `finally`, so anything resolving state *through the form* during the pass reads the applied bag rather than the draft.
Nothing here resolves it that way: [`getTableFilterState()`](host-has-filters.md#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk) names the applied bag directly, whatever the form happens to be bound to, so there is no path to flip and no cache to invalidate — the seam is the two loops and nothing else.

Both of Filament's remaining applications fill these seams, each a body inside a seam rather than a new call site: **per-column** search sits in `applySearchToTableQuery()` ahead of the global half, and grouping wraps the sorted seam from outside — `applySortingToTableQuery($this->applyGroupingToTableQuery($query))`, so the group order is appended first and every sort after it [breaks ties inside a group](grouping.md#the-group-order-is-appended-first-so-every-later-sort-breaks-ties-inside-a-group).
That is why the seams were written public and whole before the layers that fill them: anything needing the lane could ask for it without waiting for its own half.

Filament runs two more inside this seam that this port deliberately runs elsewhere.
Its column loop calls `applyRelationshipAggregates()` and `applyEagerLoading()` here, so both land on the filtered query — which is the same clone the **count** takes.
Here the aggregate value never touches a query clone at all ([why](has-query.md#an-aggregate-is-a-batched-side-query-and-a-hidden-subselect-never-a-select-on-the-page-query)), and eager loading runs one step later, on the [page clone alone](#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone), so a `COUNT` never carries joins added purely to fill cells.

### A `records()` closure resolves public container services by type

Closure evaluation reaches the container here, which is not obvious from the trait: `$table->evaluate($dataSource, [...])` passes seven named injections and nothing else, yet a `records()` closure type-hinting a repository is handed one.
`SylamentTablesBundle::loadExtension()` registers `Table` as an autowired, autoconfigured service, so `Table::newInstance()` resolves through `ComponentResolver`'s locator, the `#[Required] setInvoker()` setter fires, and the table evaluates through the container's `Invoker` instead of the fallback one built around an empty container.
[`SylamentTablesBundleTest`](../../../packages/tables/tests/Integration/SylamentTablesBundleTest.php) pins both halves: the table's `Invoker` is the container's, and a closure type-hinting a fixture repository receives the container's shared instance.

One boundary comes with it, and it is a support-layer rule rather than a tables one: the `Invoker` holds `service_container`, and `Container::has()` on a compiled container answers for **public** ids only — so a **private** service, which is Symfony's default and what most of Sylament's own services are, still does not resolve by type.
That is the same "only types the container `has()` resolve" constraint [component.md](../support/component.md#closure-resolution-lives-in-a-service) records, and it is why the fixture repository that test injects is `#[Autoconfigure(public: true)]`.

### The paginator is a return value, not a state holder

`getTablePaginator()` looks like it owns paging state and does not: it calls `getTableRecords()` and returns the result only when it happens to be a [`Paginator`](../support/pagination.md), null otherwise.
On the **records** lane the carrier is whatever the userland closure returned — the host neither builds one nor slices anything — so a table is "paginated" for rendering purposes exactly when its closure chose to return one, regardless of what `Table::isPaginated()` says.
`isPaginated()` governs the *inputs* (whether `recordsPerPage` is injected at all); the footer renders off the *output*.
A closure that ignores its injections and returns a plain array on a paginated table therefore renders with no footer, correctly: there are no counts to render.

On the **query** lane the two answers agree, because there the host does the slicing: [`paginateTableQuery()`](host-can-paginate-records.md#the-page-state-has-its-query-lane-and-no-session-or-url-half) builds a carrier exactly when `isPaginated()` is true and the lane returns a plain keyed array when it is not, so a query-lane footer *is* `isPaginated()`.
This method is unchanged by that — it still answers off whatever came back, never asking which lane produced it — which is why one footer condition serves both.

The one place this shape matters is caching.
Both methods read the same `$cachedTableRecords`, so asking for the paginator does not fetch a second time, and a template reading `component.getPaginator` before the row loop costs nothing.

### A host trait is analysed only through a class that composes it

PHPStan skips a trait no analysed class uses, and the repo ignores `trait.unused` globally, so the skip is silent.
This trait sat in exactly that position from the day it landed until panels' `ListRecords` became the first shipped class to `use` it; composing it turned on both the standalone and the per-using-class analysis at once and surfaced **eleven** latent level-max errors here, plus eight in [`HasActions`](../actions/has-actions.md#the-mounted-action-descriptor-is-typed-string-keyed-and-the-client-fills-two-thirds-of-it).
Four were real runtime gaps rather than narrowing noise, and all four are fixed: the two unguarded `(string)` casts on a record key, the unchecked row type behind an `is_iterable()` PHPStan had proved unreachable, and the half-implemented shape check on a [filter slice](host-has-filters.md#one-filter-slice-is-a-plain-array-read-not-a-propertyaccessor-walk).
The rest were type-flow noise — a `blank()` call ordered after the narrowing that makes its `@phpstan-assert-if-false` vacuous, and `evaluate()`'s generic binding `T` to `Closure` when the value handed to it is statically a closure.

The durable half is the rule, not the incident: **a host trait carries no static-analysis coverage of its own** — it inherits whatever its consumers give it.
Two consequences when extending this trait or writing another host one.
Only a class inside an analysed `src/` path turns a trait on: [`phpstan.neon.dist`](../../../phpstan.neon.dist)'s `paths` stop at `packages/*/src`, so the test fixture host [`TableHost`](../../../packages/tables/tests/Fixtures/Concerns/TableHost.php) composed this trait for its whole life and contributed nothing — which is exactly why the eleven errors stayed latent until a class landed in `src/`.
And a scoped run over the package that *owns* the trait cannot see the errors that exist only in a using class's context, which is why the whole set arrived at once in a lane that had touched neither package — only the unscoped `composer test:static` sees them.

### Private trait methods are called across the traits `HasTable` composes

A private trait method is callable from any other trait the same class uses, and the table host relies on that six times.
`CanReorderRecords` calls `HasBulkActions::resolveTableRecordKeyTarget()`; `CanSummarizeRecords` calls `CanPaginateRecords::readTableRecordWindow()`; `HasTable` calls `CanPaginateRecords::getCompositeTableRecordClass()` and `::assertTableQueryCarriesNoOutputWalker()`; `CanPaginateRecords` calls `HasTable::keyTableRecords()`; and `HasFilters` calls `CanPersistTableState::isPersistedTableFilterBag()`, a private **static** reached through `self::`.
Each call is legal only because `HasTable` composes every one of those traits into one host.
Neither file shows the coupling, so renaming, re-scoping or moving one of these methods breaks the other trait, and the break surfaces only through a class that composes both — which is why it takes [the unscoped static analysis](#a-host-trait-is-analysed-only-through-a-class-that-composes-it) to see it.

### An overridden `getTableRecordKey()` keys the rows, and the query lane reads keys back through the codec

`getTableRecordKey()` is public and on the contract, so a host may override it, and what an override moves is one side of a round trip.
It is what a row is **keyed** by: `keyTableRecords()` keys every object row through it, so the row loop, each checkbox's `value`, every mount context and `Table::getRecordKey()` carry its answer.
On the query lane nothing reads that key **back** through it.
`getTableRecord()` — and with it every row action, column action and editable cell — the selection's `in()`/`notIn()`, the key pluck behind select-all, the reorder's re-resolution, the aggregate batch and the page summary's narrowing all decode a key through [`RecordKey`](../schemas/record-key.md) and `IdentifierConstraint`, so the only key they can read back is the one `RecordKey::of()` mints.
On the `records()` lane an object row is keyed **and** read back by the method alone, so an override there is whole; an array row never asks it, being keyed by its `__key` entry.

An override answering any other key on the query lane therefore misses everywhere.
Executed against a host keying three songs as `S1`…`S3`, before the refusal below:

- a row action, a column action or an editable cell on `S1` resolved nothing and did nothing, with no error;
- an explicit selection of `S1` and `S3` narrowed to `1 = 0`, so the bulk action acted on no row;
- select-all filled the selection with the codec's keys, which no rendered checkbox matched, so every box stayed unticked while the bulk action acted on all three;
- **select all, untick `S2`, delete: all three were deleted.**
  `notIn()` answers `null` when no deselected key decodes, and the selection query reads `null` as *nothing left to exclude*, so the unticked row went with the others — on both arms of `fetchSelectedRecords()`;
- a reorder wrote nothing, an aggregate cell showed its default, and the page summary narrowed to `1 = 0`.

**Parity.**
Resolution, selection, select-all, reorder and the deselection widening are Filament's too: its `find()`, `whereKey()`, `whereKeyNot()` and `getKeyName()` never ask the override either, and `whereKeyNot(['S2'])` against an integer key excludes nothing on SQLite and MySQL.
The selectability constraint, the aggregate batch and the page summary miss only here, because Sylament reads keys where Filament reads none: Filament's selectability check re-keys through `getKey()`, its aggregates ride the page query, and its summary pages with `forPage()`.
The strict decode makes the widening silent on PostgreSQL too, where an untyped deselection bind would be an integer-cast error — a 500 rather than a deletion.

**So the query lane refuses it.**
`keyTableRecords()` compares, for every object row that is an instance of the table's [record class](has-query.md#the-record-class-is-read-off-the-base-querys-root-entity), what `getTableRecordKey()` answers with what `RecordKey::of()` answers, and a difference throws a `LogicException` naming the host, the row's class, both keys and the two ways out — answer the codec's key, or list the rows through `records()`.
Every render keys its rows there, so the first render refuses and none of the symptoms above is reachable.
An override answering the codec's key renders as before, however it computes it; an array row, every `records()` row and a query-lane row that is no instance of the record class — a `SELECT NEW` projection, keyed by its `id` — are never compared.
The codec's key is minted once per row, and `getTableRecordKey()` is asked only where the codec answers null or the host overrides the method — detected by the method's declaring file being other than `HasTable.php` — so the comparison costs an overriding table one more mint per row, a few microseconds and no statement, and a table that does not override costs nothing.
Pinned by [`HasTableRecordKeyOverrideTest`](../../../packages/tables/tests/Unit/Concerns/HasTableRecordKeyOverrideTest.php) over a fixture host whose override is a closure.

## Divergences

### The host id is a random `LiveProp`, rather than a framework component id

Filament prefixes every morph key with `$this->getId()` — Livewire's own component id, a framework-generated string that is part of the component's snapshot and therefore stable across a re-render for free.
UX LiveComponent has no equivalent a template can read: the id it stamps on the root element is generated by the frontend controller, not by PHP, so nothing server-side can address it while rendering.

`getTableHostId()` therefore mints its own — `bin2hex(random_bytes(4))`, lazily on first read, into a `#[LiveProp] public ?string $tableHostId` so the *same* eight hex characters come back on every subsequent render of that component instance.
That is the whole requirement: the value is never parsed, never addressed from JS, and never has to be unique across hosts for correctness — it only has to be **the same before and after** a round trip, and different enough between two tables on one page that their row ids do not collide.
Four random bytes give that at a page-realistic number of tables.
It is the same shape actions already ships as [`getActionsHostId()`](../actions/modals.md#the-host-id-is-a-round-tripped-liveprop-not-a-framework-component-id), for the same missing framework id, and the two are deliberately separate values rather than one shared id — the packages do not depend on each other in that direction.

**The getter/property name collision is what makes the lazy fill work, and it is load-bearing.**
`AddLiveAttributesSubscriber::onPreRender` (`vendor/symfony/ux-live-component/src/EventListener/AddLiveAttributesSubscriber.php`) dehydrates every `#[LiveProp]` *before* the component's template body renders, so an id first minted while rendering the table partial would miss that pass entirely: the next request would hydrate `$tableHostId` back to `null`, mint a different value, and every row id would change — the exact failure the key exists to prevent.
It does not happen, because the dehydration pass reads the prop through Symfony's `PropertyAccessor` (`LiveComponentHydrator::dehydrate()`), which prefers the same-named **getter** over the property — so the read that dehydrates is itself the read that mints, and the template later finds the value already there.
Renaming either half, or adding a differently-named getter, breaks morph stability with nothing failing loudly, which is why the Integration [`HasTableTest`](../../../packages/tables/tests/Integration/Concerns/HasTableTest.php) asserts the dehydrated `tableHostId` against the id actually stamped on the rows rather than trusting the reasoning.

The same preference has a **second face**, and it is a liability rather than a convenience: on a prop the client also writes, the getter's answer is the wrong thing to put on the wire, which is what [the two writable props bridge around](host-can-paginate-records.md#the-writable-props-dehydrate-in-their-elements-wire-vocabulary).
One mechanism, opposite signs — worth reading both notes before touching either getter.

Two smaller consequences.
The property is `public` because a `LiveProp` must be, so it is part of the host's public surface and is the one thing this trait adds to it — a host mixing in `HasSchemas` and `HasActions` alongside is unaffected only because the three names are disjoint.
And a host that renders its table *without* being a live component (a plain Twig component, or the unit fixtures) still gets an id: the lazy read works with no hydration behind it, the ids are simply regenerated per request, which is exactly right for a render that never morphs.

Permanent in mechanism, provisional in nothing — if UX ever exposes a server-readable component id, this becomes a one-line delegate and the `LiveProp` goes away.

### The table is built lazily on first `getTable()`, not in a booted hook

Filament assigns `$this->table = $this->table($this->makeTable())` inside `bootedInteractsWithTable()` — a Livewire lifecycle hook that fires on every request, before anything else runs — and `getTable()` is then a plain property read that would `Error` on an unset typed property if the hook had not run.
UX LiveComponent has no `booted*()` counterpart: its lifecycle attributes (`#[PostHydrate]`, `#[PreDehydrate]`) fire only for the hydration path, so a first render — where the component is constructed rather than hydrated — would leave the property unassigned.

`getTable()` therefore builds on first call and memoises into a nullable `$cachedTable`, the same lazy shape [`HasSchemas::$cachedSchemas`](../schemas/has-schemas.md) already uses on the schemas host.
The consequence, and it is the wanted one: the table is never built for a request that does not touch it.
Laziness is no longer *total*, though — `mountTableFilters()` is a `#[PostMount]` hook and calls `getTable()` unconditionally, so a **mounted** host builds its table whether or not anything else asks for it, and only a host reached outside the mount path (a plain `new`, a hydration-only request) still pays nothing.
The one Filament behaviour lost is the guaranteed build order — Filament can rely on the table existing before any other hook runs; here anything that needs it calls `getTable()` and gets it built on the spot.
Permanent, unless UX adds a construct-and-hydrate-alike boot hook.

### `makeTable()` seeds nothing from deprecated host getters

Filament's `makeTable()` is a 38-call `->when()` chain reading `getTableColumns()`, `getTableFilters()`, `getTableEmptyStateHeading()` and friends off the host — every one of them marked `@deprecated Override the table() method to configure the table.` in `Concerns\HasRecords`.
It is a v2-era back-compat path Filament keeps for its own call sites.

Sylament's `makeTable()` is `Table::make($this)` and nothing else.
There is no pre-v4 Sylament code to be compatible with, so the port takes the endpoint Filament's own deprecation notice points at: a host configures its table by overriding `table()`.
This is the same reasoning that kept [`schema()` out](../schemas/component.md#the-schema-alias-is-not-ported) as an alias for `components()`.
Permanent.

### The filtered-query seam is gated on the lane, not on the presence of a builder

Filament's `getFilteredTableQuery()` reads `getTable()->getQuery()` and answers null only when there is no builder to read — the lane question is left to the callers, and only some of them ask it, branching on `hasQuery()` before the seam is ever reached.
So a Filament table declaring **both** a `records()` closure and a `query()` resolves the closure for its rows and still hands the builder back to anything calling the seam directly.
Sylament asks the lane question in the seam itself: no `hasQuery()`, [which is "no records closure is set"](has-query.md#the-query-lane-is-on-exactly-while-no-records-closure-is-set), no query — and `getFilteredSortedTableQuery()` answers null with it.

The gate is there because the seam is public and Filament leaves that question to whoever calls it — seven call sites across six methods, each answering for itself, and not alike.
`getAllTableRecordsCount()` calls the seam bare and counts what comes back; `getAllSelectableTableRecordKeys()` calls it a line *before* it tests `hasQuery()`; `getAllSelectableTableRecordsCount()` manages both in one body, a bare `->get()` on its selectable branch and a null-safe `?->count() ?? $this->cachedTableRecords->count()` at its tail; `getFilteredSortedTableQuery()` and `getAllTableSummaryQuery()` pass the null on.
One of the six settles the lane first: `getGroupedSelectableTableRecordKeys()` tests `hasQuery()` at its top and returns out of the records-lane branch entirely, so its seam call below is reached on the query lane only.
`getTableRecords()` settles it the same way and reaches the seam indirectly, through the sorted one.
(`getTableQueryForExport()` is not among the seven — it bypasses the seam entirely, re-reading `getTable()->getQuery()` and re-applying filters, search and sorting itself.)
Asking in the seam answers the question once, where the table is, rather than at each call site — and the caller that needed the answer is [`getAllTableRecordsCount()`](#the-all-records-count-answers-four-ways-where-filaments-answers-two), the one Filament reaches with no check at all: the null the gate returns is what sends a records-lane table to count what its closure returned instead.
Handing back a query a records-lane table will never execute is not a harmless extra — counted, it runs SQL for rows the render will not show, and a table whose closure ignores its query would be counted against the wrong data source entirely.

Nothing else changes.
`resolveTableRecords()` takes the same branch either way, because it consults the data source first, so the gate is observable only through a direct read of the seam — which is exactly what the pinning case in [`HasTableTest`](../../../packages/tables/tests/Unit/Concerns/HasTableTest.php) does.
Permanent while both lanes exist.

### The URL binding is a per-prop `LiveProp` modifier, opted into by the page

Filament binds seven names to the query string through **two** mechanisms, and the split matters.
Six ride Livewire's `#[Url]` attribute, redeclared on `ListRecords` with a fixed literal each — `sort`, `search`, `filters`, `tab`, `grouping` and `reordering`.
`page` is not one of them: it rides Livewire's separate pagination binding, `HandlesPagination::queryStringHandlesPagination()` returning `['paginators.<name>' => ['history' => true, 'as' => $pageName, 'keep' => false]]`, where `$pageName` is `getTablePaginationPageName()` — which is why `page` is the **only** one of the seven that `getIdentifiedTableQueryStringPropertyNameFor()` and the table's `queryStringIdentifier()` disambiguate per table, and why its `'history' => true` is the "pagination alone" that constraint 1 below names.
Six names ship — the five a routed page needs, `page` (identifier-prefixed), `sort`, `search`, `filters` and `tab`, plus `grouping` — through one mechanism UX makes available and Livewire does not, so the two halves collapse into one hook here.

The lever is `modifier:`, not `fieldName:`.
Each URL-candidate `LiveProp` names a two-line modifier (`modifyTablePageProp()`, `modifyTableSortProp()`, `modifyTableSearchProp()`, `modifyTableColumnSearchesProp()`, `modifyTableFiltersProp()`, and `HasTabs`' `modifyActiveTabProp()`), each asking the host's `getTableUrlMapping(string $prop): ?UrlMapping` and calling `$liveProp->withUrl($mapping)` when it answers one.
`withUrl()` clones the attribute, `LiveComponentMetadata` re-runs modifiers on every metadata read, and the seed, the write and the client-attribute paths all honour the result — so the binding is decided per host, at runtime, which is exactly what an attribute cannot express.
The default is `null`, so a showcase card, a modal-hosted table and a [relation manager](../panels/relation-manager.md) keep today's behaviour with zero edits, and only `ListRecords` and `ManageRelatedRecords` override it.
`fieldName:` was rejected because it is also the **wire** name: renaming `tableSearch` would rename it under three template bindings at once.

Two rules bind an override.
It must stay cheap and side-effect-free, because modifiers run inside `LiveComponentHydrator::hydrate()`; and **no modifier may call `getTable()`**, because that is *before* `#[PostHydrate]` stamps `_sylament_panel` onto the request, so a table built there resolves the default panel and memoises that answer for the rest of the request.
That is why the page-parameter prefix comes from `getTableQueryStringIdentifier()`, a **host** hook a page answers from a static value, rather than from [`Table::queryStringIdentifier()`](table.md#surface) — which still ships as parity surface and is read by `getIdentifiedTableQueryStringPropertyNameFor()` at non-modifier call sites, an [inverted deadness](#the-query-string-identifier-is-live-on-the-table-and-dead-in-the-modifiers) recorded below.

Four constraints of UX's own URL writing are accepted rather than fought, and none is worked around with JavaScript — the binding ships zero new JS.

1. **replaceState only.**
   The URL is written server-side through the `X-Live-Url` response header and applied with `history.replaceState`, so table state is not back-button navigable.
   Livewire pushes state for pagination alone; there is no equivalent hook here.
2. **Residue at defaults.**
   There is no omit-at-default rule: `http_build_query` drops only `null` and `[]`, so after the first live round trip the URL carries `?page=1&search=&tab=<key>` even where nothing was changed.
   A plain first GET never rewrites the URL, so a clean link stays clean until the user interacts.
3. **PHP bracket arrays.**
   A filter bag serializes as `filters[status][value]=x` where Livewire writes JSON.
   The read side matches, so a copied URL round-trips; only its shape differs from Filament's.
4. **`onUpdated` does not fire on a seed.**
   A seeded value is written straight through the property accessor, so `updatedTableSearch()` never runs for it — which is why `?search=` does not reset the page, and why every check the action-side writer runs has to be repeated somewhere the seed passes through.
   `page` is the plainest case: `getTablePage()` both floors and caps it (`max(1, min($this->tablePage, intdiv(PHP_INT_MAX, $perPage)))`), because `?page=` coerces to `int 0` on a non-nullable prop at one end and accepts `PHP_INT_MAX` at the other — where the offset multiplication overflows to a float `setFirstResult()` refuses.
   `sort` is the case that needed a hook of its own; `filters` is [handled at its own seed](host-has-filters.md#a-seeded-filter-bag-fills-the-form-instead-of-the-declared-defaults).

One name is deliberately **not** bound: `reordering`.
Filament binds it; here it is a transient UI mode rather than browsing state, and binding it would leave a permanent `reordering=0` in the URL under constraint 2 above.
`grouping` **is** bound, since it is a prop, and `perPage` and the column state stay session-or-transient exactly as Filament has them.
The per-column search terms are not bound either, and the reason is Filament's: its `ListRecords` declares no `#[Url]` for `tableColumnSearches`, so a column term is transient, or session-held under `persistColumnSearchesInSession()`, and never in the URL.
`modifyTableColumnSearchesProp()` still asks the hook, so a page that wants the terms in its URL answers `getTableUrlMapping('columnSearches')`.

### The query-string identifier is live on the table and dead in the modifiers

Filament ships both halves of the name prefix and only one of them is reachable.
`getIdentifiedTableQueryStringPropertyNameFor()` reads `getTable()->getQueryStringIdentifier()` and nothing else, so the `Table` setter is the live half; the host shim `getTableQueryStringIdentifier()` is declared on `InteractsWithTable` returning null and is **read by no caller anywhere in Filament's source**.
Ours inverts exactly that.
The host hook is the live half — `InteractsWithRelationshipTable` overrides it with the host class's own basename, lower-cased at the first letter, and both routed pages compose the `page` mapping from it (a plain `ListRecords` answers null and gets a bare `?page=`, so only a tier reached *through* another one takes a prefix) — while `getIdentifiedTableQueryStringPropertyNameFor()` reads the `Table` setter first and the host hook second, and has no production caller of its own: Filament's one caller is `getTablePaginationPageName()`, which feeds Livewire's `WithPagination` and has no counterpart here.
It ships as parity surface with a Unit test for its precedence, and nothing else.

The cause is the rule above — a `LiveProp` modifier cannot build a table — and the consequence is worth stating plainly, because it is a behavioural difference rather than bookkeeping: a user who calls `->queryStringIdentifier('foo')` inside `table()` changes what `getIdentifiedTableQueryStringPropertyNameFor()` answers and does **not** change the `?page=` parameter the page binds, because the mapping was resolved before the table existed.
A page wanting a different prefix overrides `getTableQueryStringIdentifier()`.

### `getTableRecords()` returns a keyed array or a `Paginator`, never a `Collection`

Filament returns a `Collection | Paginator | CursorPaginator`.
Sylament returns `array<array-key, object|array<string, mixed>> | Paginator`, where `Paginator` is support's [own carrier](../support/pagination.md) rather than Laravel's.

The **keying** is Filament's whole: `mapWithKeys()`'s contract is ported — each entry re-keyed by the record key, an array record's `__key` entry synthesised from the source array's own key when absent and put through the same printable-key rule the object arm uses either way — so `$records[$key]` is the bare lookup a row-scoped action needs.
It applies to both arms: a returned `Paginator` is re-keyed too, through `withRecords()`, so the carrier that comes back is a **clone** of the one the closure returned and the counts around it are untouched.
`getTableRecord()` unwraps it before the lookup, which is why a row-scoped action works identically on a paginated table.

Only the container differs.
There is no `Collection` to return: the Laravel collection pipeline has no port and nothing in the package consumes one, so a `foreach`-able array carries the same information.
`CursorPaginator` has no counterpart either — cursor paging is a query-lane shape, and the closure lane has nothing to build a cursor from.
The `Traversable` and array arms a closure can produce both fall out of one `foreach`, which is why nothing calls `iterator_to_array()` — a generator yielding the same key twice loses the earlier record either way, and that is the same last-writer-wins rule two records sharing an `id` already get.

Anything else the closure returns is a `LogicException` naming both accepted shapes.
Filament has no such guard, because Eloquent's builder cannot return garbage; a userland closure can, and a table that silently renders empty for a `null` return is the failure worth stopping on.

**The rows inside it are checked the same way**, and that half arrived late — the `is_iterable()` guard above made the keying loop statically unreachable, so [nothing analysed it](#a-host-trait-is-analysed-only-through-a-class-that-composes-it) until a class composed the trait.
A row that is neither an object nor an array used to reach `getTableRecordKey(object|array $record)` and die there on a `TypeError` naming a parameter the caller never wrote; it now throws a `LogicException` naming the table and the type that arrived.
Two more refusals sit beside it, both on array rows.
An array whose keys are not all strings is refused: every column read and the `__key` entry address a record by field name, so a list is not a record, and it used to be accepted and keyed by its position.
And a `__key` entry that is not printable — an array, a non-`Stringable` object, or blank — is refused where the bare `(string)` cast used to turn it into `"Array"` plus a PHP warning, or into a fatal `Error`.
All three are garbage-in cases with no green test between them; the failure they replace was either silent or unattributable.

Permanent for the `Collection` arm; the `Paginator` arm is now shipped rather than pending.

### Object records are keyed by a readable `id`, rather than an Eloquent key

Filament's non-array arm is `$record->getKey()` — every record is a `Model`, and a model always answers with the value of whatever column `$primaryKey` names.
Sylament's record vocabulary has no such base class: a record is any `object`, usually a Doctrine entity but equally a DTO, so there is no `getKey()` to call.
`getTableRecordKey()` therefore reads `id` through schemas' [`PropertyAccessor::getFromRecord()`](../../../packages/schemas/src/State/PropertyAccessor.php), which is the same accessor a [`Column`](column.md) reads its state with — so a public property, a getter, or a `__get` all satisfy it, and `id` is the one convention the package assumes about a record.

Three consequences worth stating.
A `Stringable` id is string-cast before the scalar check, so a `Symfony\Component\Uid\Uuid` or a value object keys a table without unwrapping at the call site.
An id that is missing, unreadable (`AccessException`), or [blank](../support/helpers.md) throws a `LogicException` naming the record class and pointing at the array-record alternative — the key addresses a record across a request boundary, so a silently empty or positional one would attach an action to the wrong row.
And nothing configures the property name on this arm: Filament's `$primaryKey` counterpart is a Doctrine mapping concern, so a record whose identity is not `id` either comes down the query lane below or uses the array form and its `__key` entry.

**On the query lane the mapping answers instead of the convention.**
`resolveQueryRecordKey()` runs before the `id` read and, when it returns a key, is the whole answer: for a table with a `query()`, whose [record class](has-query.md#the-record-class-is-read-off-the-base-querys-root-entity) the record is an instance of, the key is Doctrine's own — `ClassMetadata::getIdentifierValues()`, string-cast through the same `Stringable` unwrap and blank check.
So a `Book` mapped on `code` keys by `code` with nothing configured, which is exactly what the `id` convention could not do.
Everything else falls through to that convention unchanged: array records, every record on the closure lane, and — on the query lane — a DTO or any object that is not the table's entity, so a `SELECT NEW` projection or an object handed straight to `getTableRecordKey()` is keyed by its `id` exactly as before.
A host that overrides the method must answer the same key on this lane for every row the table's record class hydrates, or the first render refuses it ([semantics](#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec)).

Two things about that guard.
It asks the **table** what its record class is and tests the record with `instanceof`, rather than asking Doctrine whether the record's own class is mapped, because the obvious API cannot answer: `AbstractClassMetadataFactory::isTransient()` returns `false` for an anonymous class while `getMetadataFor()` **throws** `MappingException` for one, so an anonymous DTO — the plainest transient object there is — would pass the transient check and then explode on the metadata read.
Reading the declared class also means an anonymous *subclass* of an entity (a test double) keys by its parent's mapping instead of blowing up — pinned by `HasTableTest`'s *keys an unmapped subclass of the record class by the table record class*, because the class now travels as `RecordKey::of()`'s third argument and no other case notices it missing.
A **composite** identifier is no longer refused: the read goes through [`RecordKey::of()`](../schemas/record-key.md), which encodes every identifier field in mapping order into one string — `1~2` for a scalar pair, `main-hall~2026-spring` for a derived identity, whose association component is flattened to its target's own key — so a table over such an entity keys its rows exactly as a single-`id` one does.
A class with **one** identifier field still encodes to the flattened value verbatim, unescaped and unsplit, so `Book`'s `sy-001` above is untouched; the [grammar and its alphabet](../schemas/record-key-contract.md#the-grammar) apply to a composite key only.

The arm keeps two refusals of its own, both `LogicException`: an identifier the entity carries **none** of — or carries only *part* of, which is the composite shape of the same fault and needs saying, because `getIdentifierValues()` silently drops a null component rather than answering it, so counting is the only honest test — and one that is set but empty or unprintable.
Neither is recoverable: the table would otherwise index rows under a key no action can resolve.

The action side has the same-shaped twin, [`InteractsWithRecord::resolveRecordKey()`](../actions/interacts-with-record.md#record-key-resolution-is-hoisted-to-hasactions) — same `id`-through-the-accessor read, same `Stringable` unwrap, same `LogicException` for an array record missing its key entry — differing in what an *unkeyable* record means: it returns `null` where this throws, in both arms.
(The array arm used to disagree in a third way, returning the blank string for a blank `__key` where the object arm threw; both arms now run one printable-key rule, so a blank or unprintable key throws wherever it appears.)
The asymmetry is deliberate and matches Filament's own pair.
A table building its row index cannot proceed without a key, so an unkeyable record is a configuration error worth stopping on; an action assembling a mount context can simply omit `recordKey` and stay mountable.
Keep them in step if the read ever changes: they are two implementations of one convention, not one method and its wrapper.

The twin is deliberately **not** given the metadata arm.
An action has no table to ask, and the direction that matters for a table context already runs through the host: a mounted action carries a *key* back, and [`resolveTableAction()`](../actions/has-actions.md#the-table-branch-reaches-tables-through-a-guarded-soft-reference) turns it into a record with `getTableRecord()`, this trait's own map.
What the twin still answers for itself is the outbound half — `Action::getContext()`'s `recordKey` for a row-scoped action — and that half is **not the twin's alone**: when the action has a table and the record is an instance of the table's [record class](has-query.md#the-record-class-is-read-off-the-base-querys-root-entity), `getContext()` asks the **table** for the key and falls back to `resolveRecordKey()`'s literal `id` only otherwise.
The actions layer was handed the table's key rather than taught Doctrine — [action.md](../actions/action.md#getcontext-drops-the-record-model-gate) carries the other half of this note.

### A query-lane override of `getTableRecordKey()` the codec cannot read back is refused, where Filament's misses in silence

Filament lets a host override `getTableRecordKey()` on an Eloquent table and then reads every key back through `find()`, `whereKey()` and `whereKeyNot()`, which never ask it — so an override answering anything but the model key resolves no record, selects nothing, and after a select-all with a row unticked acts on the unticked row too, all without an error.
Ours failed the same way and in three places more ([the semantics](#an-overridden-gettablerecordkey-keys-the-rows-and-the-query-lane-reads-keys-back-through-the-codec)), and one of the shared failures deletes rows the user deselected, so the query lane refuses such an override by name at the first render instead.
It takes nothing a migrating user could have relied on: no read-back ever honoured the override there, so the refusal only names a table that was already broken.
The `records()` lane keeps Filament's freedom, since it reads keys back through the method itself.
Reverse it only if the query lane grows a host-side decode that pairs with the override.

### `getTableRecord()` reads fresh when the render cache is cold

Filament resolves a record down two lanes that disagree with each other.
The **query** lane runs `$query->find($key)` — a fresh read that never touches `$cachedTableRecords`.
The **records** lane, the one this ports, is `$this->getTable()->getRecords()[$key] ?? null`, which goes through the host's `getTableRecords()` and so both fills and reads the render cache.

Sylament gives the records lane the query lane's semantics: `($this->cachedTableRecords ?? $this->resolveTableRecords())[$key] ?? null` reads a **warm** cache when one exists, and otherwise resolves fresh **without** storing the result.
The records-lane behaviour is wrong for the case the lookup exists to serve.
A record action resolves its record *before* it mutates anything, so a cache filled by that resolution is a pre-mutation snapshot; the re-render that follows the mutation reads that snapshot back and paints a deleted row as though it were still there.
Leaving the cache cold means the render evaluates the data source itself, after the mutation, and sees it.

The price is one extra data-source evaluation per action round trip — the lookup evaluates, the render evaluates again — which is exactly what Filament's query lane pays for the same guarantee.
It is also why [`flushCachedTableRecords()`](../../../packages/tables/src/Concerns/HasTable.php) is a separate member rather than the whole answer: the cold read covers an action that resolves before anything has rendered, the flush covers one that mutates *after* something already has.
Permanent — this diverges from a Filament lane we read as defective rather than from one we have yet to catch up with.

**The query lane has now landed and resolves fresh every time**, exactly as this note predicted.
`resolveQueryTableRecord()` is the whole of it: the branch is taken on [`hasQuery()`](has-query.md#the-query-lane-is-on-exactly-while-no-records-closure-is-set), before the cache is read at all, and what it runs is `Table::getQuery(isResolvingRecord: true)` — the base query with its [scopes](has-query.md#every-read-clones-a-memoised-base-query) applied under that flag, so a scope can tell a row lookup from a render — narrowed by [`IdentifierConstraint::equals()`](../schemas/record-key-contract.md#the-operations-and-their-dql) under `sylament_record_key`, which emits `{rootAlias}.{identifierField} = :sylament_record_key` for a single identifier, exactly as this lane always did, and one `= :sylament_record_key_<j>` per field for a composite one.
Every field name is read off the mapping metadata rather than assumed to be `id`, and every value is **bound** — now typed by its field's own DBAL type, which is the half of the guard that was missing: an untyped `'abc'` against an integer column is a `500` on PostgreSQL, because a DBAL `DriverException` is not an `HttpExceptionInterface` and `PageController::__invoke()`'s render catch, which rethrows a Twig `RuntimeError`'s previous exception only when it is an `HttpExceptionInterface`, does not convert it.
The other half is that a key which is **malformed for the class** — the wrong arity, a non-canonical escape, a component its field's type cannot hold — answers null *without running a query at all*, which is what a row action, a column's own `action()` and an editable cell now get from this method — its three call sites.
Selection, the reorder write and the by-key delete never call it: they narrow through [`IdentifierConstraint::in()`](../schemas/record-key-contract.md#parameter-naming-and-what-an-empty-or-malformed-key-means) instead, where a malformed key is **dropped** from the list and a statement still runs — as `1 = 0` once nothing well-formed is left.
The panel's own `{record}` binding does **not** come through here: [`Resource::resolveRecordRouteBinding()`](../panels/resource.md#a-record-is-resolved-through-the-resources-own-query) narrows through `IdentifierConstraint::equals()` and reaches the same strict decode by its own route, so `/admin/articles/abc/edit` is a 404 that runs no statement rather than a 500.
A bare table names its three lanes, and anything that hydrates to a non-object (a scalar or partial select) answers null.

Three things it deliberately does **not** apply, all Filament-faithful.
No **search**: a term typed into the search box must not make a row's own action miss, so the resolution query carries no search term and a record the search excluded still resolves.
No **pagination**: nothing constrains the page, so a record that left the current page between render and mount resolves anyway.
And no **sorting**, which would only cost an `ORDER BY` on a single-row read.
The one thing Filament's lane applies that this one applies too is **filters**, and they joined it with the layer that gives them state: [`applyFiltersToTableQuery($query, isResolvingRecord: true)`](#filtertablequery-applies-the-filters-then-the-search) runs before the identifier narrowing, so a row action cannot reach a record outside the filtered set.
A fourth thing it does not apply — the row limit Filament's `find()` carries — is a deviation rather than a faithful omission, and has [its own note](#record-resolution-applies-no-limit-1-where-filaments-find-does).

### Record resolution applies no `LIMIT 1` where Filament's `find()` does

Filament resolves with `$query->find($key)`, and Eloquent's `find()` is `whereKey($id)->first()`, which is `limit(1)->get()->first()` — so its lane does ship a row limit.
`resolveQueryTableRecord()` ends at `getOneOrNullResult()` with no `setMaxResults(1)`, which is worth stating because it reads as a missing line.

The limit is free in Eloquent because a `with()` eager load is a **second** query, matched onto the parent models afterwards, so a `LIMIT 1` on the root query cannot reach a relation.
Doctrine's fetch join selects the relation in the **same** query, so the limit truncates it: the root comes back holding one element in a collection that reports itself complete — the row-multiplication trap [the Tools paginator](host-can-paginate-records.md#the-page-state-has-its-query-lane-and-no-session-or-url-half) exists to survive on the page query, except that here it corrupts a single resolved record rather than a page count, silently.

It buys nothing in exchange.
The identifier predicate already admits at most one root, and object hydration returns one entity per identity however many rows the join produced, so `getOneOrNullResult()` cannot throw from row multiplication.
A query that somehow does return two roots raises Doctrine's `NonUniqueResultException` — `AbstractQuery::getOneOrNullResult()` throws on a result count above one — rather than silently answering with the first, which is the failure we want.

Permanent.
The fetch-join resolution case pins it: it asserts all three songs and fails with "actual size 1 matches expected size 3" the moment the limit comes back.

Record resolution takes neither the [eager joins](#eager-loading-runs-over-the-visible-columns-on-the-page-clone-alone) nor the aggregate batch, so a query written by a `query()` closure is the only thing that can fetch-join this clone — which narrows the trap to the case the test covers and widens the argument for keeping the limit off: the port cannot know whether the user's own declaration fetch-joins, so the safe reading is that it might.

### The all-records count answers four ways where Filament's answers two

Filament's `getAllTableRecordsCount()` is two lines: a `LengthAwarePaginator` in `$cachedTableRecords` answers with its `total()`, and everything else is `getFilteredTableQuery()->count()` — a call that assumes a builder is there and fatals on a `records()` table, since that seam [is not lane-gated](#the-filtered-query-seam-is-gated-on-the-lane-not-on-the-presence-of-a-builder) there either.
Sylament's has four arms, and the two extra ones are the two shapes Filament's method cannot meet: a lane that is not a query at all, and a carrier whose total is legitimately unknown.

The order is what makes them readable.
On the query lane a **cached carrier** with a known total answers from itself — no second `COUNT` for a number already paid for — and only that shape short-circuits: a rendered *unpaginated* table caches a plain array, which carries no total to reuse, so it pays the count like a cold one.
Otherwise the answer is a `COUNT` over [`getFilteredTableQuery()`](#filtertablequery-applies-the-filters-then-the-search) through Doctrine's `Tools\Pagination\Paginator`, asked for the `fetchJoinCollection` treatment on the same [join heuristic](host-can-paginate-records.md#the-page-state-has-its-query-lane-and-no-session-or-url-half) `paginateTableQuery()` uses.
That flag is **inert on this call** in the installed ORM and is passed anyway: `Paginator::count()` never reads it — its count query is built with `CountWalker::HINT_DISTINCT`, so a fetch-joined table counts distinct roots either way — and only `getIterator()` branches on it.
One heuristic in one place is worth more than a call site that answers a question nobody asks, and if the flag ever reaches the count path it will already be right.

The same call is fronted by one guard: a **composite** root — two or more identifier fields — whose query carries its own output walker is [refused by name](host-can-paginate-records.md#an-output-walker-of-the-querys-own-is-refused-by-name-only-where-doctrines-fallback-cannot-serve-a-keyed-root) here, as it is in front of the paginator's own total, because `Paginator::count()` falls back to `CountWalker` there and that walker resolves the root through one identifier column.
A **single derived identity** is not refused here: one association identifier is a path `CountWalker` does resolve, and it counts such a table today.
Nothing else on this arm moved either — a keyed root with no such hint counts through `CountOutputWalker` exactly as it always did, which is the composite-safe path the site ledger records as a deliberate no-change.

Both filters and search are applied and pagination is not, which is exactly what "all records" means here: the number a footer's *of N* renders under a filtered page.

The records lane has no query to count, so the answer is whatever the closure returned.
A plain array counts itself.
A carrier answers with its total.
A carrier with a **null** total — support's [simple-pagination shape](../support/pagination.md#the-simple-footer-keeps-the-overview-and-the-item-list-where-filament-drops-both) — throws a `LogicException` naming the limitation, which is the honest answer rather than a gap: a closure that deliberately skipped counting cannot be asked for the count afterwards, and `0` or the page size would be a number the caller would believe.
A query-lane table in `Simple` mode is never in that position — its carrier carries no total either, but the query is still there, so the fall-through counts it.

Nothing in the package reads the method yet.
Filament's two callers are its `assertCountTableRecords()` test helper and panels' `ExposesTableToWidgets`, which hands the number to a page's widgets; neither is ported.
So it stays off the [contract](interacts-with-table.md#the-contract-is-thirty-five-methods-of-filaments-forty-six), which grows only for members a shipped consumer calls, and it ships whole rather than in the half the closure lane could have answered on its own.

### Eager loading runs over the visible columns, on the page clone alone

Two loops on the page read carry the relation arm's host half, and both differ from Filament's in where they run.

**Eager loading** is a loop over [`$table->getVisibleColumns()`](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again), calling [`applyEagerLoading()`](interacts-with-table-query.md#a-to-many-relation-stays-lazy-where-filament-eager-loads-every-cardinality) on each, placed **after** `getFilteredSortedTableQuery()` has returned and before the query is paginated.
Filament loops over the same set inside `filterTableQuery()`, so the set is no longer a delta — one remains.

A to-one column the developer hid, or the user toggled off, therefore costs no share of a `LEFT JOIN`, and the [global search loop](can-sort-records.md#sorting-and-global-search-both-skip-a-hidden-column) skips a hidden column the same way.

One gate sits in front of the whole loop: under [`groupsOnly()`](grouping.md#the-php-gates-of-groupsonly-are-unconditional-where-the-templates-are-not) it is skipped entirely, because no record row renders and nothing reads a cell.
Filament has the same skip in the same spirit and a different place — its loop lives inside `filterTableQuery()`, so the placement is the recorded delta and the gate is not.

The remaining delta is the **clone**, and it is deliberate.
Filament's placement puts the eager loads on the filtered query, which the count path also reads; ours puts them one step later, so they reach the page query and nothing else.
The count clone therefore stays join-free — it counts what the search matched, and a join added to fill cells has no business widening or narrowing it — and record resolution, which builds its own clone through `getQuery(isResolvingRecord: true)`, never passes this seam at all.
A resolved record lazy-loads what a rendered row fetch-joined, which is the honest trade: it is one row, and the alternative is [a fetch join under a row limit](#record-resolution-applies-no-limit-1-where-filaments-find-does).
A test pins both halves by asserting the standalone count and the record resolution each issue exactly one statement with no `LEFT JOIN` in it.

**The aggregate store** is filled by the second loop, `fillTableAggregateValues()`, and it runs from `keyTableRecords()` — the one funnel every arm passes through, paginated and `'all'` alike — because the batch needs the page's keys and those only exist once the records are keyed.
It is a no-op off the query lane (no entity manager to batch against, and a `records()` closure that returns rows the port never queried) and a no-op for a page with no keys.

Filament applies its aggregates on **record resolution** too.
Ours does not, and the reason is that nothing there would read them: a resolved record feeds a row-scoped action, not a rendered cell, and the store is keyed per column for cells.
Revisit with bulk actions or an export surface, which are the two consumers that would want a resolved record carrying its aggregates.

_See also: the global [rename table](../renames.md) — the interface⇄trait label swap (`InteractsWithTable` trait → `HasTable` trait, `HasTable` interface → `InteractsWithTable` interface) is recorded there._
