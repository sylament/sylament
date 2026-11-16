<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Doctrine\DBAL\Types\BigIntType;

/**
 * DBAL's `BigIntType` registered under a name of its own.
 */
final class VoucherBigIntType extends BigIntType
{
    public const string NAME = 'schemas_voucher_bigint';
}
