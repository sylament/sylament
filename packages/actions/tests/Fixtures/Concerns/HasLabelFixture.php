<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasLabel;
use Sylament\Actions\Concerns\HasName;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasLabelFixture
{
    use EvaluatesClosures;
    use HasLabel;
    use HasName;
}
