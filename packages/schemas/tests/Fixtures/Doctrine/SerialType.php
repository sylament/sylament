<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Types\BigIntType;

/**
 * A `bigint` registered under a name of its own, the way an application keys a
 * tenant-scoped sequence: the key's shape check reaches it by class.
 */
final class SerialType extends BigIntType
{
    public const string NAME = 'serial_bigint';
}
