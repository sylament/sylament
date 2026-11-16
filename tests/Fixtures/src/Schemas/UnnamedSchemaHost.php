<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

/**
 * A host outside any request that declares no `schemas()`, so its one schema is the
 * default `schema`, bound to the `data` bag.
 */
class UnnamedSchemaHost implements InteractsWithSchemas
{
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('title')->default('Untitled'),
            ]);
    }
}
