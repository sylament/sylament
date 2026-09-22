<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\StringType;

/**
 * A type that cannot read its own printed form: it stores `c:<value>` and
 * refuses anything without that prefix on the way back, so a key minted from a
 * record of this type never decodes against its own mapping.
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
