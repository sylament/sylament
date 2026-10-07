# Tables — port trail

Ports [`filament/tables`](../../../vendor/filament/tables) into [`packages/tables`](../../../packages/tables): the table builder, from the query and its columns, filters and actions to grouping and summaries.

## Files

<!-- trail-index:start -->
- [base-filter.md](base-filter.md)
- [can-aggregate-related-records.md](can-aggregate-related-records.md)
- [can-defer-loading.md](can-defer-loading.md)
- [can-paginate-records.md](can-paginate-records.md)
- [can-persist-table-state.md](can-persist-table-state.md)
- [can-search-records.md](can-search-records.md)
- [can-sort-records.md](can-sort-records.md)
- [checkbox-column.md](checkbox-column.md)
- [color-column.md](color-column.md)
- [column-manager.md](column-manager.md)
- [column.md](column.md)
- [filter.md](filter.md)
- [grouping.md](grouping.md)
- [has-actions.md](has-actions.md)
- [has-cell-state.md](has-cell-state.md)
- [has-columns.md](has-columns.md)
- [has-empty-state.md](has-empty-state.md)
- [has-filters.md](has-filters.md)
- [has-query.md](has-query.md)
- [has-table.md](has-table.md)
- [host-can-paginate-records.md](host-can-paginate-records.md)
- [host-can-reorder-records.md](host-can-reorder-records.md)
- [host-can-search-records.md](host-can-search-records.md)
- [host-can-sort-records.md](host-can-sort-records.md)
- [host-has-bulk-actions.md](host-has-bulk-actions.md)
- [host-has-filters.md](host-has-filters.md)
- [icon-column.md](icon-column.md)
- [image-column.md](image-column.md)
- [indicator.md](indicator.md)
- [interacts-with-table-query.md](interacts-with-table-query.md)
- [interacts-with-table.md](interacts-with-table.md)
- [select-column.md](select-column.md)
- [select-filter.md](select-filter.md)
- [summarizer.md](summarizer.md)
- [table.md](table.md)
- [tables-bundle.md](tables-bundle.md)
- [ternary-filter.md](ternary-filter.md)
- [text-column.md](text-column.md)
- [text-input-column.md](text-input-column.md)
- [toggle-column.md](toggle-column.md)
- [view-column.md](view-column.md)
<!-- trail-index:end -->

## Unported

| Filament | Status | Blocked by | Detail |
| --- | --- | --- | --- |
| [`Columns/BadgeColumn.php`](../../../vendor/filament/tables/src/Columns/BadgeColumn.php) | skipped | — | [deprecated for `TextColumn::badge()`](../README.md#statuses) |
| [`Columns/BooleanColumn.php`](../../../vendor/filament/tables/src/Columns/BooleanColumn.php) | skipped | — | [deprecated for `IconColumn::boolean()`](../README.md#statuses) |
| [`Columns/ColumnGroup.php`](../../../vendor/filament/tables/src/Columns/ColumnGroup.php) | todo | — | — |
| [`Columns/Layout/Component.php`](../../../vendor/filament/tables/src/Columns/Layout/Component.php) | todo | — | — |
| [`Columns/Layout/Grid.php`](../../../vendor/filament/tables/src/Columns/Layout/Grid.php) | todo | — | — |
| [`Columns/Layout/Panel.php`](../../../vendor/filament/tables/src/Columns/Layout/Panel.php) | todo | — | — |
| [`Columns/Layout/Split.php`](../../../vendor/filament/tables/src/Columns/Layout/Split.php) | todo | — | — |
| [`Columns/Layout/Stack.php`](../../../vendor/filament/tables/src/Columns/Layout/Stack.php) | todo | — | — |
| [`Columns/Layout/View.php`](../../../vendor/filament/tables/src/Columns/Layout/View.php) | todo | — | — |
| [`Filters/MultiSelectFilter.php`](../../../vendor/filament/tables/src/Filters/MultiSelectFilter.php) | skipped | — | [deprecated for `SelectFilter::multiple()`](../README.md#statuses) |
| [`Filters/QueryBuilder.php`](../../../vendor/filament/tables/src/Filters/QueryBuilder.php) | todo | — | — |
| [`Filters/QueryBuilder/Constraints/`](../../../vendor/filament/tables/src/Filters/QueryBuilder/Constraints) | todo | — | — |
| [`Filters/TrashedFilter.php`](../../../vendor/filament/tables/src/Filters/TrashedFilter.php) | skipped | — | [delete-action.md](../actions/delete-action.md#there-is-no-soft-delete-arm-to-hide-behind) |
| [`Columns/TagsColumn.php`](../../../vendor/filament/tables/src/Columns/TagsColumn.php) | skipped | — | [deprecated for `TextColumn`](../README.md#statuses) |
| [`Table/Concerns/CanBeStackedOnMobile.php`](../../../vendor/filament/tables/src/Table/Concerns/CanBeStackedOnMobile.php) | blocked | the layout columns (`Columns\Layout`) | [detail](table.md#the-card-layout-settings-wait-on-the-layout-columns) |
| [`Table/Concerns/HasArguments.php`](../../../vendor/filament/tables/src/Table/Concerns/HasArguments.php) | todo | — | — |
| [`Table/Concerns/HasContent.php`](../../../vendor/filament/tables/src/Table/Concerns/HasContent.php) | blocked | the layout columns (`Columns\Layout`) | [detail](table.md#the-card-layout-settings-wait-on-the-layout-columns) |
| [`Columns/Concerns/BelongsToGroup.php`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToGroup.php) | todo | — | — |
| [`Columns/Concerns/BelongsToLayout.php`](../../../vendor/filament/tables/src/Columns/Concerns/BelongsToLayout.php) | todo | — | — |
| [`Columns/Concerns/CanBeCopied.php`](../../../vendor/filament/tables/src/Columns/Concerns/CanBeCopied.php) | skipped | — | [deprecated for support's `CanBeCopied`](../README.md#statuses) |
| [`Columns/Concerns/HasSpace.php`](../../../vendor/filament/tables/src/Columns/Concerns/HasSpace.php) | todo | — | — |
| [`TableComponent.php`](../../../vendor/filament/tables/src/TableComponent.php) | todo | — | — |
| [`Commands/`](../../../vendor/filament/tables/src/Commands) | todo | — | — |
| [`Testing/TestsActions.php`](../../../vendor/filament/tables/src/Testing/TestsActions.php) | todo | — | — |
| [`Testing/TestsBulkActions.php`](../../../vendor/filament/tables/src/Testing/TestsBulkActions.php) | todo | — | — |
| [`Testing/TestsColumns.php`](../../../vendor/filament/tables/src/Testing/TestsColumns.php) | todo | — | — |
| [`Testing/TestsFilters.php`](../../../vendor/filament/tables/src/Testing/TestsFilters.php) | todo | — | — |
| [`Testing/TestsRecords.php`](../../../vendor/filament/tables/src/Testing/TestsRecords.php) | todo | — | — |
| [`Testing/TestsSummaries.php`](../../../vendor/filament/tables/src/Testing/TestsSummaries.php) | todo | — | — |
| [`View/TablesRenderHook.php`](../../../vendor/filament/tables/src/View/TablesRenderHook.php) | todo | — | — |
| [`View/Components/Columns/Summarizers/CountComponent/`](../../../vendor/filament/tables/src/View/Components/Columns/Summarizers/CountComponent) | todo | — | [`Count::icons()`](summarizer.md#counticons-ships-as-api-and-refuses-to-summarize) |
| [`views/components/columns/layout.blade.php`](../../../vendor/filament/tables/resources/views/components/columns/layout.blade.php) | todo | — | — |
| [`resources/css/columns/layout/`](../../../vendor/filament/tables/resources/css/columns/layout) | todo | — | — |
| [`resources/css/content.css`](../../../vendor/filament/tables/resources/css/content.css) | todo | — | — |
| [`resources/lang/`](../../../vendor/filament/tables/resources/lang) | todo | — | — |
