<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\StringType;
use InvalidArgumentException;

/**
 * Strict the way a domain identifier type is: it converts a {@see Sku} and
 * nothing else to the database, and refuses a string that is not one on the
 * way back — so a key component handed to it bare is an error, not a miss.
 */
final class SkuType extends StringType
{
    public const string NAME = 'sku';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Sku) {
            throw InvalidType::new($value, self::NAME, ['null', Sku::class]);
        }

        return mb_strtolower((string) $value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Sku
    {
        if ($value === null || $value instanceof Sku) {
            return $value;
        }

        try {
            return new Sku(is_scalar($value) ? (string) $value : '');
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw ValueNotConvertible::new($value, self::NAME, previous: $invalidArgumentException);
        }
    }
}
