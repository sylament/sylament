<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

/**
 * A host outside any request whose `check()` registers a schema over the `data` bag,
 * bound to the record it is handed and filled from it, for the record check that ends
 * `getData()`. A test wires the validator through the `#[Required]` setter.
 */
class RecordCheckHost implements InteractsWithSchemas
{
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function check(object $record, Component ...$components): Schema
    {
        $schema = $this->makeSchema()->statePath('data')->record($record)->components($components);
        $this->cacheSchema('schema', $schema);
        $schema->fill($record);

        return $schema;
    }
}
