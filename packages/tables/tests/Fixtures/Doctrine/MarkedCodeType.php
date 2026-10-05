<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\StringType;

/**
 * A plain string the column stores as `m:<value>`, read by a strict reader that
 * refuses anything without the prefix. Its writer takes the printed form, so a
 * key decodes through the writer, while a value read through the reader twice
 * throws.
 */
final class MarkedCodeType extends StringType
{
    public const string NAME = 'tables_marked_code';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return 'm:' . $value;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        $stored = is_scalar($value) ? (string) $value : '';

        if (!str_starts_with($stored, 'm:')) {
            throw ValueNotConvertible::new($stored, self::NAME);
        }

        return mb_substr($stored, 2);
    }
}
