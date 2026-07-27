<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\HasIconPosition;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasIconPositionFixture
{
    use EvaluatesClosures;
    use HasIconPosition;
}
