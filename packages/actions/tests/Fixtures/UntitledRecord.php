<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures;

use Stringable;

/**
 * A record that reads as blank: `blank()` casts a `Stringable` to a string and
 * trims it, so a host binding on `filled()` alone drops this one.
 */
final class UntitledRecord implements Stringable
{
    public function __toString(): string
    {
        return '';
    }
}
