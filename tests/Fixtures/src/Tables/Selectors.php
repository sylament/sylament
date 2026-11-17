<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

/**
 * The markup tables render: the roots of its columns, named by the `sy-ta-` class that marks
 * each, for an area asserting what a trait puts on them, and a row's checkbox ticked for the
 * bulk actions, by its accessible name and state.
 */
final class Selectors
{
    public const string TEXT_COLUMN = '.sy-ta-text';

    public const string SELECTED_RECORD = 'input[type="checkbox"][aria-label^="Select/deselect record"][checked]';
}
