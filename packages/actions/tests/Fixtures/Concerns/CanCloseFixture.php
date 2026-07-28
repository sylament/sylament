<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanClose;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanCloseFixture
{
    use CanClose;
    use EvaluatesClosures;
}
