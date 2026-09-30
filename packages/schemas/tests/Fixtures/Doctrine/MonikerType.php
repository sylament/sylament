<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\StringType;

/**
 * Converts a {@see Moniker} and nothing else to the database, and any string —
 * a blank one included — back into one.
 */
final class MonikerType extends StringType
{
    public const string NAME = 'moniker';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Moniker) {
            throw InvalidType::new($value, self::NAME, ['null', Moniker::class]);
        }

        return (string) $value;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Moniker
    {
        if ($value === null || $value instanceof Moniker) {
            return $value;
        }

        if (!is_string($value)) {
            throw ValueNotConvertible::new($value, self::NAME);
        }

        return new Moniker($value);
    }
}
