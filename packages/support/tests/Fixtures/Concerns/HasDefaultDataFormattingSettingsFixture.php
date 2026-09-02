<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Fixtures\Concerns;

use Sylament\Support\Concerns\EvaluatesClosures;
use Sylament\Support\Concerns\HasDefaultDataFormattingSettings;

class HasDefaultDataFormattingSettingsFixture
{
    use EvaluatesClosures;
    use HasDefaultDataFormattingSettings;
}
