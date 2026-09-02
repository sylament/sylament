<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\HasPlaceholder;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasPlaceholderFixture
{
    use EvaluatesClosures;
    use HasPlaceholder;
}
