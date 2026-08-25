<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\InteractsWithRecord;
use Sylament\Schemas\Components\Concerns\CanAccessProperties;
use Sylament\Support\Concerns\EvaluatesClosures;

class InteractsWithRecordFixture
{
    use CanAccessProperties;
    use EvaluatesClosures;
    use InteractsWithRecord;
}
