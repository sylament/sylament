<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Concerns;

use RuntimeException;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

/**
 * A host whose declared builder throws if it is ever invoked, so a test can
 * assert `hasCachedSchema()` answers a pure membership question against the
 * raw cache without discovering (building) the schema — unlike Filament's
 * `hasCachedSchema()`, which calls `getCachedSchemas()` and would build this
 * and blow up.
 */
class DiscoveryThrowingSchemaHostFixture implements InteractsWithSchemas
{
    use HasSchemas;

    public function schema(Schema $schema): Schema
    {
        throw new RuntimeException('schema() should not be called by hasCachedSchema().');
    }
}
