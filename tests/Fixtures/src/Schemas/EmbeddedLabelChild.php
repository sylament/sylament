<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A child a schema embeds: it renders its `label` prop, and says so when anything
 * assigned its public `record`, which no prop it declares fills.
 */
#[AsLiveComponent(name: 'Schemas:EmbeddedLabelChild', template: '@Fixtures/schemas/EmbeddedLabelChild.html.twig')]
class EmbeddedLabelChild
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $label = '';

    public ?object $record = null;
}
