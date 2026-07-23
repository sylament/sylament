<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Concerns;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\StateField;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * A host carrying two independently-bound schemas, each on its own LiveProp bag —
 * proving the trait presumes no single `data` shape and several forms can coexist.
 */
class MultiSchemaHostFixture implements InteractsWithSchemas
{
    use HasSchemas;

    #[LiveProp(writable: true)]
    public array $editData = [];

    #[LiveProp(writable: true)]
    public array $filterData = [];

    public function schemas(): array
    {
        return ['editForm', 'filterForm'];
    }

    public function editForm(Schema $schema): Schema
    {
        return $schema->statePath('editData')->components([StateField::make('title')]);
    }

    public function filterForm(Schema $schema): Schema
    {
        return $schema->statePath('filterData')->components([StateField::make('search')]);
    }
}
