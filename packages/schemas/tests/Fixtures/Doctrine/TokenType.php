<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Types\GuidType;

/**
 * A `guid` registered under a name of its own: the key's shape check reaches it
 * by class, on a platform that declares a guid type.
 */
final class TokenType extends GuidType
{
    public const string NAME = 'token_guid';
}
