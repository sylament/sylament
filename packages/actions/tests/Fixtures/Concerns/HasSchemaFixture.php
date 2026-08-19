<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasSchema;
use Sylament\Actions\Concerns\HasWizard;
use Sylament\Support\Concerns\EvaluatesClosures;

class HasSchemaFixture
{
    use EvaluatesClosures;
    use HasSchema;
    use HasWizard;
}
