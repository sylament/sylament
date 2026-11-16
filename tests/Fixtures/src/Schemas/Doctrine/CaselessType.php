<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * A string the column stores lower-cased, so two spellings differing only in case
 * store alike; it reads the column back as stored.
 */
final class CaselessType extends StringType
{
    public const string NAME = 'schemas_caseless';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return is_string($value) ? mb_strtolower($value) : parent::convertToDatabaseValue($value, $platform);
    }
}
