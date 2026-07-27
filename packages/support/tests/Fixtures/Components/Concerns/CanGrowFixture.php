<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\CanGrow;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanGrowFixture
{
    use CanGrow;
    use EvaluatesClosures;
}
