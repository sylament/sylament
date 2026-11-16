<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Stringable;

/**
 * The value {@see CipherType} holds: it prints its value, which its column stores
 * behind a prefix.
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
