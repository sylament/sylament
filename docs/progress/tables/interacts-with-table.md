---
description: The contract `Contracts\InteractsWithTable`, the host methods a table, its columns, filters and templates and the actions package may call back into.
ports:
  - vendor/filament/tables/src/Contracts/HasTable.php
owns:
  - packages/tables/src/Contracts/
---
# InteractsWithTable (tables)

[`Sylament\Tables\Contracts\InteractsWithTable`](../../../packages/tables/src/Contracts/InteractsWithTable.php) ports Filament's `Contracts\HasTable`, with the interface and trait labels swapped against Filament's ([rename table](../renames.md#interface-and-trait-names-are-swapped)); [`HasTable`](has-table.md) is the trait that implements it.

## Surface

| Feature | Ports | Status | Blocked by | Detail |
| --- | --- | --- | --- | --- |
| [Filament's members a consumer outside the trait calls: `getTable()`, `getTableRecords()`, `getTableRecord()`, `getTableRecordKey()`, `isTableReordering()`, `isTableLoaded()`, `getTableSortColumn()`, `getTableSortDirection()`, `getTableGrouping()`, `getTableRecordsPerPage()`, `getTablePage()`, `getTableSearchIndicator()`, `getTableColumnSearchIndicators()`, `getAllTableSummaryQuery()`, `getPageTableSummaryQuery()`, `getTableFiltersForm()`, `getSelectedTableRecords()`, `getSelectedTableRecordsQuery()`, `getAllSelectableTableRecordsCount()`, `deselectAllTableRecords()`, `isTableColumnToggledHidden()`](../../../packages/tables/src/Contracts/InteractsWithTable.php) | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | done | — | [semantics](#a-member-is-on-the-contract-when-something-outside-the-host-trait-calls-it) |
| [Members of the port's own: `getTableHostId()`, `flushCachedTableRecords()`, `getTablePaginator()`, `getTableGroupingColumn()`, `getTableGroupingDirection()`, `getTableRecordGroupBoundaries()`, `getGroupedSelectableTableRecordKeys()`, `getGroupedTableSummaryQuery()`, `getTableSummarySelectedState()`, `getTableColumnStateError()`, `isTableRecordSelected()`, `hasSelectedTableRecords()`, `getTableColumnManagerItems()`, `getTableColumnSearch()`](../../../packages/tables/src/Contracts/InteractsWithTable.php) | — (Sylament-only) | done | — | [semantics](#a-member-is-on-the-contract-when-something-outside-the-host-trait-calls-it) |
| Members only the trait or the wire calls: `getAllSelectableTableRecordKeys()`, `getAllTableRecordsCount()`, `getFilteredTableQuery()`, `getFilteredSortedTableQuery()`, `parseTableFilterName()`, `callTableColumnAction()`, `toggleTableReordering()`, `resetTableSearch()`, `resetTableColumnSearch()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | skipped | — | [diverges](#a-member-only-the-trait-or-the-wire-calls-stays-off-the-contract) |
| `getTableFilterState()` / `getTableFilterFormState()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | todo | — | [detail](#the-filter-state-readers-are-reached-through-a-method_exists-probe) |
| `hasTableSearch()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | skipped | — | [can-search-records.md](can-search-records.md#hassearch-is-not-ported-because-a-null-search-indicator-answers-it) |
| The mounted-action members: `getMountedTableAction()`, `getMountedTableActionForm()`, `getMountedTableActionRecord()`, `getMountedTableBulkAction()`, `getMountedTableBulkActionForm()`, `callMountedTableAction()`, `mountTableAction()`, `replaceMountedTableAction()`, `mountTableBulkAction()`, `replaceMountedTableBulkAction()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | skipped | — | [has-table.md](has-table.md#the-deprecated-host-shims-are-not-ported-because-each-points-at-a-surface-that-ships) |
| `getTableQueryForExport()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | blocked | the export surface | [has-table.md](has-table.md#gettablequeryforexport-waits-on-an-export-surface) |
| Translatable content driver — `getActiveTableLocale()`, `makeFilamentTranslatableContentDriver()` | [`Contracts\HasTable`](../../../vendor/filament/tables/src/Contracts/HasTable.php) | todo | — | [has-schemas.md](../schemas/has-schemas.md#the-translatable-content-driver-is-not-ported) |

## Details

### The filter-state readers are reached through a `method_exists()` probe

`getTableFilterState()` and `getTableFilterFormState()` exist on the host's [`HasFilters`](host-has-filters.md), and `Filters\Concerns\BelongsToTable::getState()` / `getFormState()` read them off the live component behind a `method_exists()` check whose failure names `HasTable`.
What is missing is their declaration here: once declared, the two readers reach them through the published interface, the way the actions package reaches the selection.

## Semantics

### A member is on the contract when something outside the host trait calls it

A member is on the contract when something outside the trait calls it, through one of three channels.
A **template** reads it off `component.getLiveComponent`, which is typed `InteractsWithTable`, so a method that lives only on the trait renders for a trait-using host and fatals in Twig for any other host satisfying the interface; [`TemplateHostCallsTest`](../../../packages/tables/tests/Architecture/TemplateHostCallsTest.php) scans the shipped templates for `getLiveComponent.<method>` and fails unless the contract declares it.
A **delegate** on `Table`, a column or a filter calls it off a `getLiveComponent()` typed as this interface — `Table::getSortColumn()`, `Table::isLoaded()` or `CanBeToggled::isToggledHidden()` — so at PHPStan level max the delegate cannot compile until the interface declares it.
And a **cross-package** read in `sylament/actions` calls it after an `instanceof InteractsWithTable` check against the published interface, never through a `method_exists()` probe on a foreign trait: a bulk action resolves its records through `getSelectedTableRecords()` and its query through `getSelectedTableRecordsQuery()` and clears the selection through `deselectAllTableRecords()`, and `HasActions::callMountedAction()` calls `flushCachedTableRecords()` in a `finally`, so a table never re-renders from the page of records a mounted action's write just invalidated.

What is published is the **question**, never the state behind it.
The writable or raw props — `$tableSearch`, `$tableColumnSearches`, `$tableFilters`, `$tableColumns`, `$isTableReordering`, `$isTableLoaded` — stay off, so a host answering `false` or null without composing the trait that holds the prop renders correctly.
`getTableSearchIndicator()` is nullable for the same reason: null *is* "nothing is searched for", which keeps Filament's separate `hasSearch()` gate off the contract.

Fourteen members are the port's own, each the answer a shipped consumer needs where Filament reads Livewire state or keeps it on the client.
`getTableColumnStateError()` is one: the refusal an editable cell paints lives on [`HasColumns`](has-columns.md#the-refusal-is-request-scoped-and-server-rendered-where-filaments-lives-on-the-client), where vendor holds that error in the browser.

`HasTable` supplies every member, so a trait-using host takes a new one without an edit.
The [`BareTableHost`](../../../packages/tables/tests/Fixtures/Concerns/BareTableHost.php) fixture implements the interface **by hand**, to prove a host can satisfy the contract without the trait.

## Divergences

### A member only the trait or the wire calls stays off the contract

Filament's contract declares the host's live actions and its trait-internal seams beside the members other code calls.
The port leaves off every member nothing outside the trait calls: the live actions `callTableColumnAction()`, `toggleTableReordering()`, `resetTableSearch()` and `resetTableColumnSearch()` are called only over the wire, and `getAllSelectableTableRecordKeys()`, `getAllTableRecordsCount()`, `getFilteredTableQuery()`, `getFilteredSortedTableQuery()` and `parseTableFilterName()` only from inside the trait.
`shouldRenderTrailingGroupedTableSummary()` stays off on the same grounds: its one caller is `Concerns\CanGroupRecords::closesTrailingTableRecordGroup()`, which reaches it through the host it already holds.
Declaring a member without a consumer would force every hand-written host to stub a method nothing calls; a member joins when a consumer outside the trait calls it.
