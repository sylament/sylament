<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

/**
 * A host declaring a second named schema for an `EmbeddedSchema` to pull in, so
 * the embed's render can be told apart from the schema it renders inside.
 */
class EmbeddedSchemaHostFixture implements InteractsWithSchemas
{
    use HasSchemas;

    public function schemas(): array
    {
        return ['schema', 'form'];
    }

    public function schema(Schema $schema): Schema
    {
        return $schema;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([Text::make('embedded form content')]);
    }
}
