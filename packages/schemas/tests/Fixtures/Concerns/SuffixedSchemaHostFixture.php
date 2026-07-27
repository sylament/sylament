<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Concerns;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

/**
 * A host naming its builders with Filament's `<name>Schema` suffix. The `report`
 * pair declares both shapes for the same name so the bare-wins precedence is
 * observable through the state path each builder sets.
 */
class SuffixedSchemaHostFixture implements InteractsWithSchemas
{
    use HasSchemas;

    public function schemas(): array
    {
        return ['article'];
    }

    public function articleSchema(Schema $schema): Schema
    {
        return $schema->statePath('article');
    }

    public function report(Schema $schema): Schema
    {
        return $schema->statePath('bare');
    }

    public function reportSchema(Schema $schema): Schema
    {
        return $schema->statePath('suffixed');
    }
}
