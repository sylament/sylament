<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

/**
 * The roots of tables' columns, named by the `sy-ta-` class that marks each, for an area
 * asserting what a trait puts on them, and the hook on what a table shows only while
 * records are selected.
 *
 * The roots of tables' columns, named by the `sy-ta-` class that marks each, for an area
 * asserting what a trait puts on them, the hook on what a table shows only while records
 * are selected, and a row's checkbox ticked for the bulk actions, by its accessible name and
 * state.
 */
final class Selectors
{
    public const string TEXT_COLUMN = '.sy-ta-text';

    public const string BULK_ONLY = '[data-sy-table-bulk-only]';

    public const string SELECTED_RECORD = 'input[type="checkbox"][aria-label^="Select/deselect record"][checked]';
}
