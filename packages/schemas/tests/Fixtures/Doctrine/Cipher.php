<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Stringable;

/**
 * A value-object identifier whose database form carries a prefix the printed
 * form does not, and whose type accepts nothing but this object on the way in —
 * so, unlike a binary-backed uuid, the printed form cannot be read back even
 * through the type's writer.
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
