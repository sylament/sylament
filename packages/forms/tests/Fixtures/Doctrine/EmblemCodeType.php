<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * A type whose database form differs from its PHP form — the column holds the
 * code in lower case and a record reads it back upper case — so a code typed in
 * lower case is written as typed and read back as another string.
 */
final class EmblemCodeType extends StringType
{
    public const string NAME = 'forms_emblem_code';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : mb_strtolower((string) $value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : mb_strtoupper((string) $value);
    }
}
