<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * A type whose database form differs from its PHP form — the column holds the
 * code in lower case and a record reads it back upper case — so a key minted
 * from a raw column value is a different string from the one the record mints.
 */
final class LockerCodeType extends StringType
{
    public const string NAME = 'locker_code';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : mb_strtolower((string) $value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : mb_strtoupper((string) $value);
    }
}
