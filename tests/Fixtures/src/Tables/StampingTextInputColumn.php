<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Columns\TextInputColumn;
use Sylament\Tests\Fixtures\Schemas\TaggingStateCast;

/**
 * A text input column carrying a cast of its own, which tags the state it converts with
 * the direction it ran in, so a cell shows whether a value went through it and which way.
 */
class StampingTextInputColumn extends TextInputColumn
{
    public function getStateCasts(): array
    {
        return [TaggingStateCast::First];
    }
}
