<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\CanGrow;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanGrowFixture
{
    use CanGrow;
    use EvaluatesClosures;
}
