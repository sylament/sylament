<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\CanBeCopied;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanBeCopiedFixture
{
    use CanBeCopied;
    use EvaluatesClosures;
}
