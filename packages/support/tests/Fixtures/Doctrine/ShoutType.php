<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Types\StringType;

final class ShoutType extends StringType
{
    public const string NAME = 'support_shout';
}
