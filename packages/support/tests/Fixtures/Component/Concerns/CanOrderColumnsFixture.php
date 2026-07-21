<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\CanOrderColumns;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanOrderColumnsFixture
{
    use CanOrderColumns;
    use EvaluatesClosures;
}
