<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\HasIcon;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasIconFixture
{
    use EvaluatesClosures;
    use HasIcon;
}
