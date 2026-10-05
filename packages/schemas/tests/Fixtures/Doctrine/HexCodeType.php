<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;

/**
 * An integer column a record holds as its lower-case hex spelling, read by a
 * lenient reader that takes whatever it is handed as a decimal number: it reads
 * the column's 255 as `ff`, and its own `ff` as `0`, without a word. A scalar
 * identifier type whose reader is not idempotent, so a value read through it
 * twice is a different value — while its writer takes the printed `ff` back to
 * 255 and refuses anything that is not lower-case hex.
 */
final class HexCodeType extends Type
{
    public const string NAME = 'hex_code';

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
