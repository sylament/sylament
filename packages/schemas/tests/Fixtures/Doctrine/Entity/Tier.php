<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

enum Tier: int
{
    case Low = 1;
    case High = 7;
}
