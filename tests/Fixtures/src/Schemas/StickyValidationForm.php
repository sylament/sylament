<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use LogicException;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Set;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose errors outlive the request that raised them: a required `slug` a hook
 * on `title` writes, and a `details` section that validates on its own and that
 * `showDetails` takes out of the form and puts back.
 */
#[AsLiveComponent(name: 'Schemas:StickyValidationForm', template: '@Fixtures/schemas/StickyValidationForm.html.twig')]
class StickyValidationForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp(writable: true)]
    public bool $showDetails = true;

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('title')
                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('slug', $state === null ? null : mb_strtolower($state))),
                TextInput::make('slug')->required(),
                Section::make()
                    ->key('details')
                    ->visible(fn (): bool => $this->showDetails)
                    ->components([
                        TextInput::make('city')->required(),
                    ]),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }

    #[LiveAction]
    public function saveDetails(): void
    {
        $details = $this->getSchemaComponent('form.details');

        if (!$details instanceof Component) {
            throw new LogicException('The form has no details section.');
        }

        $this->validateSchema($details->getChildSchema() ?? throw new LogicException('The details section has no child schema.'));
    }
}
