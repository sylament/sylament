<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

/**
 * The markup tables render, for a test to find: the live host's root carrying its props, the
 * records table, its rows, cells and header cells, a column's root and its content wrapper as a
 * link, a click target or neither, the header and toolbar bands with their actions, the search
 * fields and the per-column search row, the selection checkboxes, banner and cells, the
 * filters' trigger, panel, modal, form fields and indicators, the column manager's trigger,
 * dialog, items and controls, the length either modal caps its window at, the grouping settings
 * and a group's header, checkbox and summary row, the summary band and a summarizer's body, the
 * reorder triggers and handles, the editable cells and their errors, and the empty, loading,
 * striped, bulleted and responsive looks; each named by the `sy-ta-` class that is its one
 * hook, by the id the table template gives it under its host id, by its accessible name, or by
 * the Stimulus controller, target, value or live action that wires it or the inline style that
 * sizes it, with a static helper for a family keyed by a record, a column or a value.
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
     * The wrapper of a cell a URL makes a link, the row's record URL or the column's own.
     */
    public const string LINKED_CELL_WRAPPER = 'a' . self::CELL_WRAPPER;

    /**
     * The wrapper of a cell an action makes a click target, the row's record action or the column's own.
     */
    public const string BUTTON_CELL_WRAPPER = 'button' . self::CELL_WRAPPER;

    /**
     * The wrapper of a cell neither a URL nor an action makes clickable.
     */
    public const string PLAIN_CELL_WRAPPER = 'div' . self::CELL_WRAPPER;

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
     * A record's checkbox for the bulk actions, ticked or not, by its accessible name.
     */
    public const string RECORD_CHECKBOX = 'input[type="checkbox"][aria-label^="Select/deselect record"]';

    /**
     * The header's checkbox selecting the current page, by its accessible name.
     */
    public const string PAGE_CHECKBOX = 'input[type="checkbox"][aria-label="Select/deselect all records on this page for bulk actions."]';

    /**
     * The toolbar band's start group, holding the reorder trigger, the toolbar actions and the grouping settings; its class is its one hook.
     */
    public const string TOOLBAR_ACTIONS = '.sy-ta-header-toolbar-actions';

    /**
     * The header band's list of header actions; its class is its one hook.
     */
    public const string HEADER_ACTIONS = '.sy-ta-header-actions';

    /**
     * The wrapper of a record's actions inside its row's actions cell; its class is its one hook.
     */
    public const string RECORD_ACTIONS = '.sy-ta-actions';

    /**
     * One value of a text column's cell, a `span` or, in a list, an `li`; its class is its one hook.
     */
    public const string TEXT_ITEM = '.sy-ta-text-item';

    /**
     * The root of an icon column's cell.
     */
    public const string ICON_COLUMN = '.sy-ta-icon';

    /**
     * The root of an image column's cell.
     */
    public const string IMAGE_COLUMN = '.sy-ta-image';

    /**
     * The root of a colour column's cell.
     */
    public const string COLOR_COLUMN = '.sy-ta-color';

    /**
     * The placeholder a column's partial shows for a blank state; its class is its one hook.
     */
    public const string PLACEHOLDER = '.sy-ta-placeholder';

    /**
     * The label of an active filter's indicator badge, which reads what the indicator says.
     */
    public const string FILTER_INDICATOR_LABEL = self::FILTER_INDICATOR . ' ' . SupportSelectors::BADGE_LABEL;

    /**
     * The badge on the filters trigger counting the active filters; its class is its one hook.
     */
    public const string FILTERS_TRIGGER_BADGE = '.sy-ta-filters-trigger-badge';

    /**
     * The dialog of the modal column manager layout.
     */
    public const string COLUMN_MANAGER_MODAL = 'dialog[id$=".table.column-manager"]';

    /**
     * The label of a column manager item, the column's label; its class is its one hook.
     */
    public const string COLUMN_MANAGER_ITEM_LABEL = '.sy-ta-col-manager-item-label';

    /**
     * A column manager item's checkbox, the column manager controller's target.
     */
    public const string COLUMN_MANAGER_CHECKBOX = 'input[type="checkbox"][data-sylament--tables--column-manager-target="checkbox"]';

    /**
     * The button applying the column manager's draft.
     */
    public const string COLUMN_MANAGER_APPLY = 'button[data-live-action-param="applyTableColumnManager"]';

    /**
     * The control resetting the column manager to the declared columns.
     */
    public const string COLUMN_MANAGER_RESET = '[data-live-action-param="resetTableColumnManager"]';

    /**
     * The footer of the column manager's dropdown panel, holding its apply button and a footer reset; its class is its one hook.
     */
    public const string COLUMN_MANAGER_PANEL_FOOTER = '.sy-ta-col-manager-actions-ctn';

    /**
     * Every cell of the records table's header row in its order: one per visible column, and the selection and actions cells where the table has them.
     */
    public const string HEADER_CELLS = 'thead th';

    /**
     * The heading of a group's header row, prefixed with the group's label unless the group turns that off.
     */
    public const string GROUP_HEADING = '.sy-ta-group-heading';

    /**
     * The clickable block inside a group's header row that holds its heading and description, collapsible or collapsed by its `sy-` state classes.
     */
    public const string GROUP_HEADER = '.sy-ta-group-header';

    /**
     * The select choosing the group a table is grouped by, in either copy of the grouping settings.
     */
    public const string GROUP_BY_SELECT = 'select[aria-label="Group by"]';

    /**
     * The select choosing the direction of the grouping, in either copy of the grouping settings.
     */
    public const string GROUP_DIRECTION_SELECT = 'select[aria-label="Group direction"]';

    /**
     * One entry of a values summarizer's list.
     */
    public const string SUMMARIZER_VALUE = '.sy-ta-values-summary li';

    /**
     * The cell a summary row leads with, heading the row it spans.
     */
    public const string SUMMARY_HEADING = 'th[scope="row"]';

    /**
     * Every row of the table's body: a record's, a group's header and every summary row.
     */
    public const string BODY_ROW = 'tbody tr';

    /**
     * The row of per-column search inputs, rendered while a column the developer shows is searchable on its own input; its class is its one hook.
     */
    public const string COLUMN_SEARCH_ROW = 'tr.sy-ta-column-search-row';

    /**
     * The busy status a deferred table renders in place of its records until it loads.
     */
    public const string LOADING = '[role="status"][aria-busy="true"]';

    /**
     * The live host's root, whose `data-live-props-value` holds its props as the server dehydrated them onto the wire.
     */
    public const string LIVE_PROPS = '[data-live-props-value]';

    /**
     * The table's root carrying its Stimulus controller, mounted on a table that selects, reorders, defers its loading or groups.
     */
    public const string TABLE_CONTROLLER = '[data-controller~="sylament--tables--table"]';

    /**
     * A body or search-row cell, of a record's row or a summary row; its class is its one hook.
     */
    public const string CELL = '.sy-ta-cell';

    /**
     * A column's header cell; its class is its one hook beside the class naming its column (`headerCell()`).
     */
    public const string HEADER_CELL = '.sy-ta-header-cell';

    /**
     * The cell holding the header's or a row's selection checkbox; its class is its one hook.
     */
    public const string SELECTION_CELL = '.sy-ta-selection-cell';

    /**
     * A group's checkbox, in its header row, selecting every record of the group; its class is its one hook.
     */
    public const string GROUP_CHECKBOX = '.sy-ta-group-checkbox';

    /**
     * A row painted as a stripe: every other record's row of a striped table or of its group, and the summary band's rows; the class is the behaviour the stylesheet reads.
     */
    public const string STRIPED = '.sy-striped';

    /**
     * A list a text column or a values summarizer bullets; the class is the behaviour the stylesheet reads.
     */
    public const string BULLETED = '.sy-bulleted';

    /**
     * A search field, the toolbar's or a column's own; its class is its one hook.
     */
    public const string SEARCH_FIELD = '.sy-ta-search-field';

    /**
     * Any cell a column's responsive visibility stamps, hidden from or shown only from a breakpoint.
     */
    public const string RESPONSIVE = '[class*="sy-ta-hidden"], [class*="sy-ta-visible"]';

    /**
     * The toolbar's trigger opening the reorder mode, by its accessible name.
     */
    public const string REORDER_TRIGGER = 'button[aria-label="Reorder records"]';

    /**
     * The toolbar's trigger closing the reorder mode, by its accessible name.
     */
    public const string FINISH_REORDERING_TRIGGER = 'button[aria-label="Finish reordering records"]';

    /**
     * The icon button opening the grouping settings, by its accessible name.
     */
    public const string GROUP_TRIGGER = 'button[aria-label="Group"]';

    /**
     * The live action an indicator's remove button sends.
     */
    public const string REMOVE_FILTER_ACTION = '[data-live-action-param="removeTableFilter"]';

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

    /**
     * The checkbox of the record with this key, by its accessible name.
     */
    public static function recordCheckbox(string $key): string
    {
        return "input[type=\"checkbox\"][aria-label=\"Select/deselect record {$key} for bulk actions.\"]";
    }

    /**
     * The group holding a filter's fields in the filters form, by the filter's name.
     */
    public static function filterGroup(string $filter): string
    {
        return "[id=\"tableFiltersForm.{$filter}\"]";
    }

    /**
     * A filter's control in the filters form, by the filter's name and the field's (`isActive`, `value`, `values`), the id its label points at.
     */
    public static function filterField(string $filter, string $field): string
    {
        return "[id=\"tableFiltersForm.{$filter}.{$field}\"]";
    }

    /**
     * The label of a filter's control in the filters form, by the filter's name and the field's, as `filterField()` takes them.
     */
    public static function filterFieldLabel(string $filter, string $field): string
    {
        return "label[for=\"tableFiltersForm.{$filter}.{$field}\"]";
    }

    /**
     * The button removing an indicator's filter, by the filter's name and, for a chip keyed by a field, that field's.
     */
    public static function removeFilterButton(string $filter, ?string $field = null): string
    {
        $selector = self::REMOVE_FILTER_BUTTON . "[data-live-filter-name-param=\"{$filter}\"]";

        return $field === null ? $selector : $selector . "[data-live-field-param=\"{$field}\"]";
    }

    /**
     * A column manager item's checkbox, by the column's name.
     */
    public static function columnManagerCheckbox(string $column): string
    {
        return self::COLUMN_MANAGER_CHECKBOX . "[value=\"{$column}\"]";
    }

    /**
     * A column manager item's drag handle, by the column's name, while the columns are reorderable.
     */
    public static function columnManagerReorderHandle(string $column): string
    {
        return 'button' . SupportSelectors::SORTABLE_HANDLE . "[aria-label=\"Reorder {$column}\"]";
    }

    /**
     * A summarizer's body in a summary cell, by the `sy-ta-` class that is its one hook: `text` (a sum, an average, a count or a bare summarizer), `range` or `values`.
     */
    public static function summarizer(string $body): string
    {
        return ".sy-ta-{$body}-summary";
    }

    /**
     * The label a summarizer's body leads with, named as `summarizer()` takes it.
     */
    public static function summarizerLabel(string $body): string
    {
        return ".sy-ta-{$body}-summary-label";
    }

    /**
     * The figure of a `text` or `range` summarizer's body: the span beside its label.
     */
    public static function summarizerFigure(string $body): string
    {
        return self::summarizer($body) . " span:not(.sy-ta-{$body}-summary-label)";
    }

    /**
     * The summary row closing the group with this key, percent-encoded as its id carries it.
     */
    public static function groupSummaryRow(string $key): string
    {
        return "tr[id\$=\".table.group-summary.{$key}\"]";
    }

    /**
     * A target of the table's Stimulus controller (`count`, `selectAll`, `groupCheckbox`, `groupDirectionField`, …).
     */
    public static function tableTarget(string $target): string
    {
        return "[data-sylament--tables--table-target=\"{$target}\"]";
    }

    /**
     * A value the table's Stimulus controller reads, or, without one, the value's presence.
     */
    public static function tableValue(string $name, ?string $value = null): string
    {
        return $value === null ? "[data-sylament--tables--table-{$name}-value]" : "[data-sylament--tables--table-{$name}-value=\"{$value}\"]";
    }

    /**
     * A cell a column's `hiddenFrom()` hides from this breakpoint up.
     */
    public static function hiddenFrom(string $breakpoint): string
    {
        return "[class~=\"{$breakpoint}:sy-ta-hidden\"]";
    }

    /**
     * A cell a column's `visibleFrom()` shows only from this breakpoint up.
     */
    public static function visibleFrom(string $breakpoint): string
    {
        return "[class~=\"{$breakpoint}:sy-ta-visible\"]";
    }

    /**
     * Every record's cell of a column, by the id the table template gives it.
     */
    public static function columnCells(string $column): string
    {
        return "td[id\$=\".column.{$column}\"]";
    }

    /**
     * The class naming a column on each of its cells, the column named as `headerCell()` takes it.
     */
    public static function cellClass(string $column): string
    {
        return "[class~=\"sy-ta-cell-{$column}\"]";
    }

    /**
     * The CSS length the filters or column manager modal caps its window at, or, without one, any cap.
     */
    public static function modalWindowCap(?string $length = null): string
    {
        return $length === null ? '[style*="max-width"]' : "[style*=\"max-width: {$length}\"]";
    }
}
