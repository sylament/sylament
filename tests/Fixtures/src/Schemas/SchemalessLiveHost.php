<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host that implements no schema contract.
 */
#[AsLiveComponent(name: 'Schemas:SchemalessLiveHost', template: '@Fixtures/schemas/SchemalessLiveHost.html.twig')]
class SchemalessLiveHost
{
    use DefaultActionTrait;
}
