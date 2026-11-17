<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

/**
 * The roots of tables' columns, named by the `sy-ta-` class that marks each, for an area
 * asserting what a trait puts on them, and the hook on what a table shows only while
 * records are selected.
 */
final class Selectors
{
    public const string TEXT_COLUMN = '.sy-ta-text';

    public const string BULK_ONLY = '[data-sy-table-bulk-only]';
}
