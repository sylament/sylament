<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components\Concerns;

/**
 * The pure `UnitEnum` — no backing value, so it derives options by case name
 * and is refused by the state casts.
 */
enum OptionSize
{
    case Small;

    case Large;
}
