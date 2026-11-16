<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas\Doctrine;

use Doctrine\DBAL\Types\GuidType;

/**
 * DBAL's `GuidType` registered under a name of its own.
 */
final class VoucherGuidType extends GuidType
{
    public const string NAME = 'schemas_voucher_guid';
}
