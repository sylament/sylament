---
description: 'The contract `Sylament\Tables\Contracts\InteractsWithTable`: the host methods a table, its columns, filters and actions may call back into. (draft)'
ports:
  - vendor/filament/tables/src/Contracts/HasTable.php
owns:
  - packages/tables/src/Contracts/
---
# InteractsWithTable (tables)

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [`InteractsWithTable` — thirty-five methods](../../../packages/tables/src/Contracts/InteractsWithTable.php) | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | in progress | — | [detail](#the-contract-is-thirty-five-methods-of-filaments-forty-six) |
| Translatable content driver — `getActiveTableLocale()`, `makeFilamentTranslatableContentDriver()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | todo | — | [has-schemas.md](../schemas/has-schemas.md#the-translatable-content-driver-is-not-ported) |

## Details

### The contract is thirty-five methods of Filament's forty-six

`Contracts\HasTable` declares 46 methods — the full host API a Filament table, column, filter, action, or test helper may call back into.
Sylament's `InteractsWithTable` declares the thirty-five the shipped surface actually calls: `getTable()` (the template and the column layer need the table from the host), `getTableRecords()` (which [`Table::getRecords()`](table.md#getrecords-delegates-to-the-host-and-fetches-nothing) delegates to), and three the record actions need — `getTableRecord()`, because a row-scoped action carries a record *key* across the request boundary and nothing else, so the actions layer resolves the record through the contract rather than reaching into the trait, `getTableRecordKey()`, its inverse, which [`Table::getRecordKey()`](table.md#getrecords-delegates-to-the-host-and-fetches-nothing) delegates to so a template can address the row it is rendering, and `getTableHostId()`, which the partial reads directly off the host to prefix its row and cell ids.
The next four are there because a **template** reads each of them: `getTableSortColumn()` and `getTableSortDirection()` (which `Table::getSortColumn()` / `getSortDirection()` delegate to, so a header cell can render its own `aria-sort`), `getTablePaginator()` (`Table::getPaginator()`'s delegate, the footer's data) and `getTableRecordsPerPage()`, which the partial reads **off the host directly** to mark the selected `<option>`.
That last one is the reason [`TemplateHostCallsTest`](../../../packages/tables/tests/Architecture/TemplateHostCallsTest.php) exists: `component.getLiveComponent` is typed `InteractsWithTable`, so any method a template reads off it that lives only on the trait renders fine for a trait-using host and fatals in Twig for any other host satisfying the published interface.
The test scans the shipped templates for `getLiveComponent.<method>` and fails unless the contract declares it — which is how that method was found missing after the first pass declared eight.

The filter layer added the tenth, `getTableSearchIndicator()`, and it is the first member no template reads.
`Table::getSearchIndicator()` — what the indicators row calls for the global-search chip — delegates to the host, and `Table::getLiveComponent()` is typed `InteractsWithTable`, so at PHPStan level max the delegate cannot compile against a method the interface does not declare.
It is the minimum that buys that: the return is **nullable**, which is what lets Filament's separate `hasSearch()` gate stay off the contract — null *is* "nothing is searched for" — and the writable `$tableSearch` prop stays off it too, since the host mints the chip rather than exposing the term.
The raw state a host holds is never the contract's business; what a shipped consumer calls is.

The host-state layer added the eleventh, `getTableFiltersForm()`, for the same reason the tenth arrived and one more.
[`Filters\Concerns\HasSchema::getSchema()`](base-filter.md#getschema-is-unguarded-where-the-state-readers-beside-it-are-not) reads the form off a `getLiveComponent()` typed as this interface, and at level max that call cannot compile until the method is declared here — which is why the two shipped in the same change, and why the filter-side reader was held back a commit rather than guarded.
The second reason is the one that found the ninth: the filters panel reads the form off `getLiveComponent` in a template, which is exactly what `TemplateHostCallsTest` scans for, so a contract-only host would fatal in Twig.
That half has since shipped — the partial's dropdown branch is the reader — and it landed without touching the interface, because the member it needs was already declared.
The two state bags stay off the contract on the same grounds as `$tableSearch`: a consumer reads a *slice* through `getTableFilterState()` / `getTableFilterFormState()`, never the whole bag, and [no getter for either exists at all](host-has-filters.md#the-filter-bags-are-arrays-never-null-and-carry-no-getters).
Those two readers are on Filament's contract and are not on ours yet, because today's only caller is `Filters\Concerns\BelongsToTable`, which reaches them through a `method_exists()` check that names `HasTable` in its failure — a deliberate hold, not an oversight: the check predates this trait and flipping it to a contract call is the filters panel's change to make, alongside the rest of the applying half.

The selection lane adds six, and each one names a consumer outside the trait.
Three are **template** reads, so `TemplateHostCallsTest` enforces them the way it enforced the ninth: `isTableRecordSelected(string $key)` (each checkbox's server-rendered `checked`), `hasSelectedTableRecords()` (the server-side `hidden` on the selection banner and on every bulk-only trigger) and `getAllSelectableTableRecordsCount()` (the banner's ceiling, and the Stimulus value the controller counts against).
Three are **cross-package** reads, and they are the reason the guarded soft reference stays honest: a bulk action in `sylament/actions` resolves its records through `getSelectedTableRecords()`, its query through `getSelectedTableRecordsQuery()` and clears the selection through `deselectAllTableRecords()` — always after an `instanceof Sylament\Tables\Contracts\InteractsWithTable` check against the **published interface**, never through a `method_exists()` probe on a foreign trait.

Two members of the same lane deliberately stayed off.
`getAllSelectableTableRecordKeys()` is called only by the `selectAllTableRecords()` live action beside it, and that action is called only over the wire — neither has a consumer outside the trait, and declaring members without consumers is exactly what this rule forbids.
`getAllTableRecordsCount()` stayed off too, although the selection [now calls it](host-has-bulk-actions.md#the-selection-is-server-canonical-and-re-derived-on-every-read) for the ceiling a total-less paginator cannot give: that call is trait-internal, and the template reads the selection count rather than the record count.

The eighteenth, `flushCachedTableRecords()`, is a **cross-package** read of the same shape as the selection's three: actions' `callMountedAction()` calls it in a `finally` on any host that `instanceof`s this interface, so a table never re-renders from the page of records a mounted action's write just invalidated.
It is the first member that is already public on `HasTable` before it is declared here — the trait has cached and flushed since the query lane — so declaring it cost every trait-using host nothing and `BareTableHost` one more stub.
Its end-to-end pin is a case in `RecordActionsResolutionTest` over the query-lane fixture `QueryRecordActionsTable`, whose record action reads `getTableRecords()` before it writes — and the write **adds** a row rather than mutating the bound one, because the identity map hands the query lane the very instance an action mutated, so only a changed *set* of records tells a stale page from a re-queried one; the action confirms first because a modal-less action runs during the mount, whose render is a request of its own and therefore a fresh host.

The nineteenth and twentieth, `isTableReordering()` and `isTableLoaded()`, are the **tenth's** shape rather than the selection's: no template reads either off `getLiveComponent`, but [`Table::isReordering()`](table.md#surface) and [`Table::isLoaded()`](../../../packages/tables/src/Table/Concerns/CanDeferLoading.php) delegate to them off a `getLiveComponent()` typed as this interface, so at level max the delegates cannot compile until the interface declares them.
What the partial reads is the two `Table` methods — once each, into a `{% set %}` the body then branches on: reordering gates the handle column, the drag mount, the suppressed sort headers, the emptied action sets, the dropped checkbox cells, the selectable-count query (whose every consumer is suppressed with them) and the poll attribute, while loaded gates the four record-touching reads.
Both are on Filament's contract too (`HasTable.php:96` and `:98`), so this is the growth order catching up rather than a divergence — and, like the selection's three, what is published is the **question**: the raw `$isTableReordering` / `$isTableLoaded` props stay off, so a host answering `false` to either without composing `CanReorderRecords` or `CanDeferLoading` renders correctly.

The contract grows a method at a time as the features that need it land — a host must be able to satisfy it, so declaring the pagination, filter and bulk-selection halves before those exist would force every consumer to stub methods nothing calls.
The Doctrine query lane added **none**: every member it shipped — the three filtered-query seams, the query-lane record resolution, `getAllTableRecordsCount()` — is called from inside the trait, from a test, or not yet at all, and no template reads one off `getLiveComponent`.
So that lane left the count where it found it, and `getAllTableRecordsCount()` is the clearest case of the rule at work: Filament's contract declares it, and [ours will](has-table.md#the-all-records-count-answers-four-ways-where-filaments-answers-two) when a consumer here calls it.
Growth is nearly free for implementors, because `HasTable` supplies every member: every tables fixture that mixes the trait in, and the demo's two implementors — `TableShowcaseCard` and the actions page's `DispatchAnywhere` — take an addition without an edit.
One fixture is deliberately outside that, and costs a stub per growth — `BareTableHost` implements the interface **by hand**, to prove a host can satisfy the contract without the trait, so each new member lands there as an explicit `null` stub.
This is a **growth order**, not a divergence: the end state is the same set of members, minus whatever the Eloquent-only rows above turn out not to need.

The twenty-first, `getTablePage()`, joins on the first of those grounds: `TextColumn::rowIndex()` turns a row's position on the page into its position in the whole set, so it multiplies the page by the per-page count, and both reads go through a `getLiveComponent()` typed as this interface.
The host already answered it — [`CanPaginateRecords`](../../../packages/tables/src/Concerns/CanPaginateRecords.php) carries the getter — so the change is the declaration alone, which is the growth order this whole section describes: a member joins when a shipped consumer calls it, never in anticipation.

The twenty-second, `getTableColumnStateError()`, joins on those same grounds one tier down: an editable column's partial paints the refusal a write left against its own cell, and it asks for it through [`CanUpdateState::getUpdateStateError()`](../../../packages/tables/src/Columns/Concerns/CanUpdateState.php), which reaches the host through a `getLiveComponent()` typed as this interface.
The bag it reads lives on [`HasColumns`](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client) and has no Filament counterpart at all — vendor holds that error on the client — so this member is Sylament's own rather than one of the forty-six.

The column manager added the twenty-third, `isTableColumnToggledHidden()`, on the tenth's grounds again: [`CanBeToggled::isToggledHidden()`](column.md#surface) asks the host whether the user has switched a column off, through a `getLiveComponent()` typed as this interface, and [`Table::getVisibleColumns()`](table.md#the-visible-set-asks-each-column-twice-and-is-memoised-until-columns-is-called-again) is what calls it.
What is published is again the **question** rather than the state: `$tableColumns` and `$tableDeferredColumns` stay off the contract, so a host that answers `false` without composing `HasColumnManager` renders every column and is correct.

The manager's chrome added the twenty-fourth, `getTableColumnManagerItems()`, and this one is there for a **template** rather than for a column: the partial renders one row per item, so it reads the list off the host exactly as it reads `getTableRecordsPerPage()`, and [`TemplateHostCallsTest`](../../../packages/tables/tests/Architecture/TemplateHostCallsTest.php) would fail the moment it did so without the declaration.
It publishes the rows rather than the bag they are derived from, so the two column props stay off the contract as before.

Per-column search added the twenty-fifth and twenty-sixth together, one on each of the two grounds.
`getTableColumnSearch(string $column)` is a **template** read — the search row binds each input's `value` to it — so `TemplateHostCallsTest` enforces it the way it enforced the ninth.
`getTableColumnSearchIndicators()` is the tenth's shape: [`Table::getColumnSearchIndicators()`](table.md#surface) delegates to it off a `getLiveComponent()` typed as this interface, so at level max the delegate cannot compile against a method the interface does not declare.
What is published is again the finished answer rather than the state — the writable `$tableColumnSearches` bag stays off, on the same grounds `$tableSearch` does, and so do the two reset actions, which are called only over the wire.

The last **nine** join on the template's grounds rather than on a delegate's — the partial reads all nine off `getLiveComponent`, so `TemplateHostCallsTest` is what would fail if any were dropped.
[Grouping](grouping.md) brings five: `getTableGrouping()` (the resolved `Group` the preamble branches on), `getTableGroupingColumn()` and `getTableGroupingDirection()` (the selector's two `selected` attributes and the direction field's `hidden`), `getTableRecordGroupBoundaries()` (one lookup per row, in place of the trackers Filament's Blade loop carries) and `getGroupedSelectableTableRecordKeys()` (the group checkbox's key list, written into the markup so a tick costs no request).
[Summaries](summarizer.md) bring four: `getAllTableSummaryQuery()`, `getPageTableSummaryQuery()` and `getGroupedTableSummaryQuery()` — the three datasets a band row is over — and `getTableSummarySelectedState()`, the one batched read each row hands to its summarizers.

One member of the same lane deliberately stayed off, for the reason `getAllSelectableTableRecordKeys()` did: `shouldRenderTrailingGroupedTableSummary()` has exactly one caller — `Concerns\CanGroupRecords::closesTrailingTableRecordGroup()`, the boundary map's own gate — and it reaches it through a host it already holds.
The band reads the map rather than the probe.
It is public on `HasTable` and absent from the interface, which is the rule working rather than an omission — `getTableGroupingColumn()` is the counter-example that proves the same rule, since it was added a commit later than its four siblings, when the selector's `selected` attribute turned out to read it.
