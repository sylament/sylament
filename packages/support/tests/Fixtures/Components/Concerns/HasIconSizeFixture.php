<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\HasIconSize;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasIconSizeFixture
{
    use EvaluatesClosures;
    use HasIconSize;
}
