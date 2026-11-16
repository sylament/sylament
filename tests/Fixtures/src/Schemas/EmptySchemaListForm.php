<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose `schemas()` lists none, as panels' `Page` does.
 */
#[AsLiveComponent(name: 'Schemas:EmptySchemaListForm', template: '@Fixtures/schemas/EmptySchemaListForm.html.twig')]
class EmptySchemaListForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    public function schemas(): array
    {
        return [];
    }
}
