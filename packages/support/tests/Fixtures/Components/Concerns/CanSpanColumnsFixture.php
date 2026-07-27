<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\CanSpanColumns;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanSpanColumnsFixture
{
    use CanSpanColumns;
    use EvaluatesClosures;
}
