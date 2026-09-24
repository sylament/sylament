<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

/**
 * A plain string the column stores lower-cased, so two values a record can
 * hold — `AB` and `ab` — are one stored key, while the string read back is
 * the column's.
 */
final class CaselessType extends StringType
{
    public const string NAME = 'caseless';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return mb_strtolower($value);
    }
}
