<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanBeHidden;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanBeHiddenFixture
{
    use CanBeHidden;
    use EvaluatesClosures;
}
