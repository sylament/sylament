<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;

/**
 * An integer column a record holds as its lower-case hex spelling. The reader takes
 * whatever it is handed as a decimal number, so it reads the column's 255 as `ff` and
 * its own `ff` as `0` without a word: a scalar identifier type whose reader is not
 * idempotent. The writer takes lower-case hex only.
 */
final class HexCodeType extends Type
{
    public const string NAME = 'schemas_hex_code';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || preg_match('/^[0-9a-f]+$/D', $value) !== 1) {
            throw InvalidType::new($value, self::NAME, ['null', 'string']);
        }

        return (int) hexdec($value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return dechex(is_scalar($value) ? (int) $value : 0);
    }

    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }
}
