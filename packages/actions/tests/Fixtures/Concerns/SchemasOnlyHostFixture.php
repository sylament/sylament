<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;

/**
 * A host implementing only the schemas contract — it cannot mount actions, so
 * the action-side live-component chain must refuse it.
 */
class SchemasOnlyHostFixture implements InteractsWithSchemas
{
    use HasSchemas;
}
