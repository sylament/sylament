<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components\Concerns;

/**
 * The int-backed enum with no label contract — its options key off the backing
 * value and label off the case name.
 */
enum OptionWeight: int
{
    case Light = 1;

    case Heavy = 2;
}
