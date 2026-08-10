<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\CanWrap;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanWrapFixture
{
    use CanWrap;
    use EvaluatesClosures;
}
