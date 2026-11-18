<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

/**
 * The markup tables render: the roots of its columns, named by the `sy-ta-` class that marks
 * each, for an area asserting what a trait puts on them, and a row's checkbox ticked for the
 * bulk actions, by its accessible name and state; the records table by its element; a record's
 * row and cell, a group's header row and the summary rows by the ids the table template gives
 * them under its host id; a column's header cell by the class naming its column, and its sort
 * trigger and sort state by the live action it sends and its `aria-sort`; the search inputs by
 * the live model they bind; the filters and column manager triggers, an indicator's remove
 * button, the remove-all button and a record's reorder handle by their accessible names; the
 * filters modal by its id, the column manager by its Stimulus controller and the selection
 * banner by the controller's target; and the header band, the toolbar band, a cell's wrapper,
 * the filters panel, an active-filter indicator and the empty state by the `sy-ta-` class that
 * is their one hook.
 */
final class Selectors
{
    public const string TEXT_COLUMN = '.sy-ta-text';

    public const string SELECTED_RECORD = 'input[type="checkbox"][aria-label^="Select/deselect record"][checked]';

    /**
     * The records table, the one `<table>` a table host renders.
     */
    public const string TABLE = 'table';

    /**
     * A record's row, by the id `assertCanSeeTableRecords()` reads, never a group header or summary row.
     */
    public const string ROW = 'tr[id*=".table.records."]';

    /**
     * A group's header row.
     */
    public const string GROUP_HEADER_ROW = 'tr[id*=".table.groups."]';

    /**
     * A group's summary row.
     */
    public const string GROUP_SUMMARY_ROW = 'tr[id*=".table.group-summary."]';

    /**
     * The header band above the table, rendered only when it holds a heading, a description or header actions; its class is its one hook.
     */
    public const string HEADER = '.sy-ta-header';

    /**
     * The toolbar band above the table, rendered only when it holds search, filters, toolbar actions, reordering, grouping or the column manager; its class is its one hook.
     */
    public const string TOOLBAR = '.sy-ta-header-toolbar';

    /**
     * The `<a>`, `<button>` or `<div>` wrapping a column's content inside its cell (inside `cell()`); its class is its one hook.
     */
    public const string CELL_WRAPPER = '.sy-ta-col';

    /**
     * The table's global search input.
     */
    public const string SEARCH = 'input[type="search"][data-model$="|tableSearch"]';

    /**
     * The button or summary icon that opens the filters.
     */
    public const string FILTERS_TRIGGER = '[aria-label="Filter"]';

    /**
     * The filters panel of a dropdown, above-content or below-content layout.
     */
    public const string FILTERS = '.sy-ta-filters';

    /**
     * The dialog of the modal filters layout.
     */
    public const string FILTERS_MODAL = 'dialog[id$=".table.filters"]';

    /**
     * An active filter's indicator badge, whose label `Support\Selectors::BADGE_LABEL` reads.
     */
    public const string FILTER_INDICATOR = '.sy-ta-filter-indicator';

    /**
     * The button removing one removable indicator's filter.
     */
    public const string REMOVE_FILTER_BUTTON = 'button[aria-label="Remove filter"]';

    /**
     * The button removing every removable filter at once.
     */
    public const string REMOVE_ALL_FILTERS_BUTTON = 'button[aria-label="Remove all filters"]';

    /**
     * The default empty state a table without records shows.
     */
    public const string EMPTY_STATE = '.sy-ta-empty-state';

    /**
     * The banner counting the selected records, with its "Select all" and "Deselect all" buttons.
     */
    public const string SELECTION_BANNER = '[data-sylament--tables--table-target="banner"]';

    /**
     * The button opening the column manager.
     */
    public const string COLUMN_MANAGER_TRIGGER = 'button[aria-label="Column manager"]';

    /**
     * The column manager's items, in its dropdown or its modal.
     */
    public const string COLUMN_MANAGER = '[data-controller~="sylament--tables--column-manager"]';

    /**
     * The root of a `TextInputColumn` cell; its class is its one hook beside the shared controller.
     */
    public const string TEXT_INPUT_COLUMN = '.sy-ta-text-input';

    /**
     * The root of a `SelectColumn` cell; its class is its one hook beside the shared controller.
     */
    public const string SELECT_COLUMN = '.sy-ta-select';

    /**
     * The root of a `CheckboxColumn` cell; its class is its one hook beside the shared controller.
     */
    public const string CHECKBOX_COLUMN = '.sy-ta-checkbox';

    /**
     * The root of a `ToggleColumn` cell; its class is its one hook beside the shared controller.
     */
    public const string TOGGLE_COLUMN = '.sy-ta-toggle';

    /**
     * An editable cell's root, by the Stimulus controller that commits its control and repaints it.
     */
    public const string EDITABLE_CELL = '[data-controller~="sylament--tables--editable-cell"]';

    /**
     * An editable cell's control, by the action committing it to the host on `change`.
     */
    public const string EDITABLE_CONTROL = '[data-action~="change->sylament--tables--editable-cell#commit"]';

    /**
     * The message a refused write paints under its cell's control; its class is its one hook.
     */
    public const string CELL_ERROR = '.sy-ta-cell-error';

    /**
     * A column's root laid out inline by `inline()`; the class is the behaviour the stylesheet reads.
     */
    public const string INLINE = '.sy-inline';

    /**
     * The row of the record with this key.
     */
    public static function row(string $key): string
    {
        return "tr[id\$=\".table.records.{$key}\"]";
    }

    /**
     * The cell of a column in the row of the record with this key.
     */
    public static function cell(string $key, string $column): string
    {
        return "td[id\$=\".table.record.{$key}.column.{$column}\"]";
    }

    /**
     * The header cell of a column, by its name with each dot-separated segment in kebab case (`author.first-name`).
     */
    public static function headerCell(string $column): string
    {
        return "th[class~=\"sy-ta-header-cell-{$column}\"]";
    }

    /**
     * The control sorting the table by a sortable column.
     */
    public static function sortTrigger(string $column): string
    {
        return "[data-live-action-param=\"sortTable\"][data-live-column-param=\"{$column}\"]";
    }

    /**
     * The header cell of a sortable column in a sort state: `ascending`, `descending`, or `none` while another column or none sorts; the column is named as `headerCell()` takes it.
     */
    public static function sortState(string $column, string $state): string
    {
        return self::headerCell($column) . "[aria-sort=\"{$state}\"]";
    }

    /**
     * A column's own search input, in the search row an individually searchable column adds.
     */
    public static function columnSearch(string $column): string
    {
        return "input[type=\"search\"][data-model\$=\"|tableColumnSearches.{$column}\"]";
    }

    /**
     * The header row of the group with this title.
     */
    public static function groupHeaderRow(string $title): string
    {
        return self::GROUP_HEADER_ROW . "[data-sy-group=\"{$title}\"]";
    }

    /**
     * The summary row of the current page (`page`) or of every record (`all`).
     */
    public static function summaryRow(string $scope): string
    {
        return "tr[id\$=\".table.summary.{$scope}\"]";
    }

    /**
     * The drag handle of the record with this key, while the table is reordering.
     */
    public static function reorderHandle(string $key): string
    {
        return "button[data-sortable-handle][aria-label=\"Reorder item {$key}\"]";
    }

    /**
     * An editable cell's root publishing this display state, the value its controller repaints the control from.
     */
    public static function editableCellState(string $state): string
    {
        return self::EDITABLE_CELL . "[data-sylament--tables--editable-cell-state-value=\"{$state}\"]";
    }

    /**
     * An editable cell's root, by the column and the record key its controller posts back.
     */
    public static function editableCell(string $column, string $record): string
    {
        return self::EDITABLE_CELL . "[data-sylament--tables--editable-cell-column-value=\"{$column}\"][data-sylament--tables--editable-cell-record-value=\"{$record}\"]";
    }
}
