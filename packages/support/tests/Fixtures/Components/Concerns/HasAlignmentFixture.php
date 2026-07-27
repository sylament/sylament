<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\HasAlignment;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasAlignmentFixture
{
    use EvaluatesClosures;
    use HasAlignment;
}
