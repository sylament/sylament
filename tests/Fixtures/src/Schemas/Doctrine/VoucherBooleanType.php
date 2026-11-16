<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Doctrine\DBAL\Types\BooleanType;

/**
 * DBAL's `BooleanType` registered under a name of its own.
 */
final class VoucherBooleanType extends BooleanType
{
    public const string NAME = 'schemas_voucher_boolean';
}
