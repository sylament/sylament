<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

class HasSchemasFixture implements InteractsWithSchemas
{
    use HasSchemas;

    public function schema(Schema $schema): Schema
    {
        return $schema->components([
            new Component(),
        ]);
    }

    public function notASchema(Schema $schema): string
    {
        return 'not a schema';
    }
}
