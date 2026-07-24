<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\InteractsWithRecord;
use Sylament\Support\Concerns\EvaluatesClosures;

class InteractsWithRecordFixture
{
    use EvaluatesClosures;
    use InteractsWithRecord;
}
