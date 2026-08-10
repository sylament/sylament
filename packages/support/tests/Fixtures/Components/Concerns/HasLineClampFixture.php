<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\HasLineClamp;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasLineClampFixture
{
    use EvaluatesClosures;
    use HasLineClamp;
}
