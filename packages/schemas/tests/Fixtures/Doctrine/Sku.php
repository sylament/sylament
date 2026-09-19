<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use InvalidArgumentException;
use Stringable;

/**
 * A value-object identifier whose printed form is not what the database
 * holds: `AB-123` in PHP, `ab-123` in the column.
 */
final readonly class Sku implements Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        if (preg_match('/^[a-z]{2}-[0-9]+$/iD', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('[%s] is not a SKU.', $value));
        }

        $this->value = mb_strtoupper($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
