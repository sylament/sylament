<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\StringType;

/**
 * A doubly-strict type: it stores `c:<value>` and refuses anything without that
 * prefix on the way back, and its writer takes a {@see Cipher} and nothing else,
 * so it refuses its own printed form on read and on write alike. A key minted
 * from a record of this type never decodes against its own mapping, even
 * through the codec's writer fallback that decodes {@see BinaryUuidType}.
 */
final class CipherType extends StringType
{
    public const string NAME = 'cipher';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Cipher) {
            throw InvalidType::new($value, self::NAME, ['null', Cipher::class]);
        }

        return 'c:' . $value;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Cipher
    {
        if ($value === null || $value instanceof Cipher) {
            return $value;
        }

        $printed = is_scalar($value) ? (string) $value : '';

        if (!str_starts_with($printed, 'c:')) {
            throw ValueNotConvertible::new($printed, self::NAME);
        }

        return new Cipher(mb_substr($printed, 2));
    }
}
