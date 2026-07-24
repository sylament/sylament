<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasLifecycleHooks;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasLifecycleHooksFixture
{
    use EvaluatesClosures;
    use HasLifecycleHooks;
}
