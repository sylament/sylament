<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanBeOutlined;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanBeOutlinedFixture
{
    use CanBeOutlined;
    use EvaluatesClosures;
}
