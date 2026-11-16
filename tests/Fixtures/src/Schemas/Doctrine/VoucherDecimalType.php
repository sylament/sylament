<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Doctrine\DBAL\Types\DecimalType;

/**
 * DBAL's `DecimalType` registered under a name of its own.
 */
final class VoucherDecimalType extends DecimalType
{
    public const string NAME = 'schemas_voucher_decimal';
}
