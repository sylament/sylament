<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Component\Concerns;

use Sylament\Support\Component\Concerns\HasExtraAttributes;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasExtraAttributesFixture
{
    use EvaluatesClosures;
    use HasExtraAttributes;
}
