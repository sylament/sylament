<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Components\Concerns;

use Sylament\Support\Components\Concerns\HasExtraAttributes;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasExtraAttributesFixture
{
    use EvaluatesClosures;
    use HasExtraAttributes;
}
