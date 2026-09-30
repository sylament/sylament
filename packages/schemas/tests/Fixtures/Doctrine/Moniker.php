<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Stringable;

/**
 * A string value object that admits a blank string, the way a constructor
 * default of `''` does — unlike {@see Sku}, which refuses one.
 */
final readonly class Moniker implements Stringable
{
    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
