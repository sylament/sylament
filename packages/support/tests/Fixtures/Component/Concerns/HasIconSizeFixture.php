<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\HasIconSize;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasIconSizeFixture
{
    use EvaluatesClosures;
    use HasIconSize;
}
