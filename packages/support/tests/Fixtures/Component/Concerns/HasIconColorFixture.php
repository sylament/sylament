<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\HasIconColor;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasIconColorFixture
{
    use EvaluatesClosures;
    use HasIconColor;
}
