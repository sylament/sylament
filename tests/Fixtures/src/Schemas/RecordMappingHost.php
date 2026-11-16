<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

/**
 * A schema host outside any request, for the mapping edges between a schema and its
 * record: `mapping()` registers the schema a test hands its components to, bound to the
 * `data` bag. A test that asserts the validation passes, the record's included, builds it
 * through `validatingRecordMappingHost()`, which wires the container's validator.
 */
class RecordMappingHost implements InteractsWithSchemas
{
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mapping(Component ...$components): Schema
    {
        $schema = $this->makeSchema()->statePath('data')->components($components);
        $this->cacheSchema('schema', $schema);

        return $schema;
    }
}
