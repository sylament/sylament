<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Stringable;
use Symfony\Component\Uid\Uuid;

/**
 * A uuid stored as its sixteen raw bytes: the shape of ramsey/uuid-doctrine's
 * `UuidBinaryType`, mimicked over symfony/uid, which this package already
 * requires — ramsey is deliberately not a dependency. The reader takes only the
 * database form, so it cannot read the printed form a key carries, while the
 * writer takes that printed form too: a type that refuses its printed form on
 * read only, unlike {@see CipherType}, which refuses it both ways. ramsey's two
 * reader arms that answer null for an empty or a non-string value are left out,
 * since no key reaches them — a blank component is refused before any type is
 * consulted.
 */
final class BinaryUuidType extends Type
{
    public const string NAME = 'binary_uuid';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBinaryTypeDeclarationSQL(['length' => 16, 'fixed' => true]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Uuid
    {
        if ($value === null || $value instanceof Uuid) {
            return $value;
        }

        // PostgreSQL hands a bytea column back as a stream, SQLite as a string.
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        if (!is_string($value) || mb_strlen($value, '8bit') !== 16) {
            throw ValueNotConvertible::new(is_scalar($value) ? (string) $value : get_debug_type($value), self::NAME);
        }

        try {
            return Uuid::fromBinary($value);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw ValueNotConvertible::new($value, self::NAME, previous: $invalidArgumentException);
        }
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Uuid) {
            return $value->toBinary();
        }

        if (!is_string($value) && !$value instanceof Stringable) {
            throw InvalidType::new($value, self::NAME, ['null', 'string', Stringable::class, Uuid::class]);
        }

        try {
            return Uuid::fromString((string) $value)->toBinary();
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw ValueNotConvertible::new((string) $value, self::NAME, previous: $invalidArgumentException);
        }
    }

    public function getBindingType(): ParameterType
    {
        return ParameterType::BINARY;
    }
}
