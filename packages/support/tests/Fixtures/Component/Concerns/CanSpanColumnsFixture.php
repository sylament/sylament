<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\CanSpanColumns;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanSpanColumnsFixture
{
    use CanSpanColumns;
    use EvaluatesClosures;
}
