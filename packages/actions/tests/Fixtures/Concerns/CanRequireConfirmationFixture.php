<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanRequireConfirmation;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanRequireConfirmationFixture
{
    use CanRequireConfirmation;
    use EvaluatesClosures;
}
