<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\HasAlignment;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasAlignmentFixture
{
    use EvaluatesClosures;
    use HasAlignment;
}
