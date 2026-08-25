<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\BelongsToGroup;
use Sylament\Actions\Concerns\BelongsToLiveComponent;
use Sylament\Actions\Concerns\BelongsToSchemaComponent;
use Sylament\Actions\Concerns\BelongsToTable;
use Sylament\Actions\Concerns\HasSchema;
use Sylament\Actions\Concerns\HasWizard;
use Sylament\Support\Concerns\EvaluatesClosures;

/**
 * The four `BelongsTo*` traits are what `getLiveComponentOrNull()` walks, so a
 * hostless fixture still answers null for the host schema-resolver arm rather
 * than fataling on an undefined method.
 */
class HasSchemaFixture
{
    use BelongsToGroup;
    use BelongsToLiveComponent;
    use BelongsToSchemaComponent;
    use BelongsToTable;
    use EvaluatesClosures;
    use HasSchema;
    use HasWizard;
}
