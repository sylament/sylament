<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasSize;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasSizeFixture
{
    use EvaluatesClosures;
    use HasSize;
}
