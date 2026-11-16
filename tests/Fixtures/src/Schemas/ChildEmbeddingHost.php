<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\Components\LiveComponent;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Record\ParcelLabel;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose schema, bound to a record, embeds one {@see EmbeddedLabelChild} under
 * the key it is mounted with.
 */
#[AsLiveComponent(name: 'Schemas:ChildEmbeddingHost', template: '@Fixtures/schemas/ChildEmbeddingHost.html.twig')]
class ChildEmbeddingHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    #[LiveProp]
    public ?string $childKey = 'ledger';

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->record(new ParcelLabel('Ledger'))
            ->components([
                LiveComponent::make(EmbeddedLabelChild::class, ['label' => 'Seven seeds'])->key($this->childKey),
            ]);
    }
}
