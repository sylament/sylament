<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Doctrine;

/**
 * Canonicalises what a driver hands back, so one assertion holds on every
 * platform: MySQL and PostgreSQL return SUM and AVG as DECIMAL strings where
 * SQLite returns numbers.
 */
final class DriverValues
{
    /**
     * @param array<array-key, mixed> $row
     * @return array<array-key, mixed>
     */
    public static function numeric(array $row): array
    {
        return array_map(self::number(...), $row);
    }

    /**
     * The int or float a numeric string spells; any other value unchanged.
     */
    public static function number(mixed $value): mixed
    {
        if (!is_string($value) || !is_numeric($value)) {
            return $value;
        }

        return preg_match('/^[+-]?\d++$/', $value) === 1 ? (int) $value : (float) $value;
    }
}
