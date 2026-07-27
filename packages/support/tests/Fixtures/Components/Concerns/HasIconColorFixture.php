<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\HasIconColor;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasIconColorFixture
{
    use EvaluatesClosures;
    use HasIconColor;
}
