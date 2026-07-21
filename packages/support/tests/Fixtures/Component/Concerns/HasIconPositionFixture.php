<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\HasIconPosition;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasIconPositionFixture
{
    use EvaluatesClosures;
    use HasIconPosition;
}
