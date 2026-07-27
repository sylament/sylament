<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\CanBeContained;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanBeContainedFixture
{
    use CanBeContained;
    use EvaluatesClosures;
}
