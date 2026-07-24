<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanNotify;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanNotifyFixture
{
    use CanNotify;
    use EvaluatesClosures;
}
