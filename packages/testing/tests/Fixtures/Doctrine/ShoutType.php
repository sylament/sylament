<?php

declare(strict_types=1);

namespace Sylament\Testing\Tests\Fixtures\Doctrine;

use Doctrine\DBAL\Types\StringType;

final class ShoutType extends StringType
{
    public const string NAME = 'testing_shout';
}
