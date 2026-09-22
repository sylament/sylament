<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Stringable;

/**
 * A value-object identifier whose database form carries a prefix the printed
 * form does not — the shape a binary-backed uuid has, where reading the value
 * back from what a key prints is not something the type can do.
 */
final readonly class Cipher implements Stringable
{
    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
