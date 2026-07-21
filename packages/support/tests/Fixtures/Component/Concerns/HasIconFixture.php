<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\HasIcon;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasIconFixture
{
    use EvaluatesClosures;
    use HasIcon;
}
