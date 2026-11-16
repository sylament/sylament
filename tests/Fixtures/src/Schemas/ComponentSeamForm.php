<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form holding a component on each side of the two browser-callable seams: one with
 * exposed methods, one accepting uploads and a hidden twin, and a field that accepts none.
 */
#[AsLiveComponent(name: 'Schemas:ComponentSeamForm', template: '@Fixtures/schemas/ComponentSeamForm.html.twig')]
class ComponentSeamForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                ExposedMethodField::make('greeter'),
                UploadReceivingField::make('attachment'),
                UploadReceivingField::make('hiddenAttachment')->hidden(),
                TextInput::make('plain'),
            ]);
    }
}
