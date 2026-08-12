<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanCustomizeProcess;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanCustomizeProcessFixture
{
    use CanCustomizeProcess;
    use EvaluatesClosures;
}
