<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State\Casts;

enum CastWeight: int
{
    case Light = 1;
    case Heavy = 2;
}
