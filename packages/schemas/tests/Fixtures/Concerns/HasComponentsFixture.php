<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Concerns;

use Sylament\Schemas\Concerns\HasComponents;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasComponentsFixture
{
    use EvaluatesClosures;
    use HasComponents;
}
