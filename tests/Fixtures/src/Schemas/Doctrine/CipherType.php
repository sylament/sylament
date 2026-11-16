<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\StringType;

/**
 * A type that refuses its own printed form both ways: it stores `c:<value>`, reads
 * nothing without that prefix and writes nothing but a {@see Cipher}. It counts its
 * writer's calls, which a decode reaches only for a component the reader refused.
 */
final class CipherType extends StringType
{
    public const string NAME = 'schemas_cipher';

    public int $writes = 0;

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        $this->writes++;

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

        $stored = is_scalar($value) ? (string) $value : '';

        if (!str_starts_with($stored, 'c:')) {
            throw ValueNotConvertible::new($stored, self::NAME);
        }

        return new Cipher(mb_substr($stored, 2));
    }
}
