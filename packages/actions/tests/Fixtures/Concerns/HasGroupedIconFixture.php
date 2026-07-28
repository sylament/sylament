<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasGroupedIcon;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasGroupedIconFixture
{
    use EvaluatesClosures;
    use HasGroupedIcon;
}
