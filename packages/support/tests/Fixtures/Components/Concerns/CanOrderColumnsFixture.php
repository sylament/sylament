<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\CanOrderColumns;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanOrderColumnsFixture
{
    use CanOrderColumns;
    use EvaluatesClosures;
}
