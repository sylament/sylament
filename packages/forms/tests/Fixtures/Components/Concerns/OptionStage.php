<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Contracts\HasDescription;

/**
 * The pure `UnitEnum` describing its own cases — no backing value, so the
 * derived description map keys off the case name.
 */
enum OptionStage implements HasDescription
{
    case Draft;

    case Live;

    public function getDescription(): string
    {
        return match ($this) {
            self::Draft => 'Still being written',
            self::Live => 'Published to the world',
        };
    }
}
