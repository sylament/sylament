<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\HasVerticalAlignment;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasVerticalAlignmentFixture
{
    use EvaluatesClosures;
    use HasVerticalAlignment;
}
