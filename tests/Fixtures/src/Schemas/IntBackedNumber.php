<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

enum IntBackedNumber: int
{
    case One = 1;
    case Two = 2;
    case Ten = 10;
}
