<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanBeDisabled;
use Sylament\Actions\Concerns\CanBeHidden;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanBeDisabledFixture
{
    use CanBeDisabled;
    use CanBeHidden;
    use EvaluatesClosures;
}
