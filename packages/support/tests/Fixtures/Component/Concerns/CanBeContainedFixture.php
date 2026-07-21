<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\CanBeContained;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanBeContainedFixture
{
    use CanBeContained;
    use EvaluatesClosures;
}
