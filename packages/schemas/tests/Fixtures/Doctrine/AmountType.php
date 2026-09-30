<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Types\DecimalType;

/**
 * A `decimal` registered under a name of its own: the key's shape check reaches
 * it by class.
 */
final class AmountType extends DecimalType
{
    public const string NAME = 'amount_decimal';
}
