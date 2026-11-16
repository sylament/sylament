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
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Two schemas that each fail while their required fields are blank: `form`, whose
 * `details` section validates on its own as well, and `settings`. Each validation and
 * a reset of one schema's errors, or of all, is a live action.
 */
#[AsLiveComponent(name: 'Schemas:ScopedValidationForm', template: '@Fixtures/schemas/ScopedValidationForm.html.twig')]
class ScopedValidationForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $preferences = [];

    public function schemas(): array
    {
        return ['form', 'settings'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('code')->required(),
                Section::make()
                    ->key('details')
                    ->components([
                        TextInput::make('city')->required(),
                    ]),
            ]);
    }

    public function settings(Schema $schema): Schema
    {
        return $schema
            ->statePath('preferences')
            ->components([
                TextInput::make('theme')->required(),
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

    #[LiveAction]
    public function saveSettings(): void
    {
        $this->validateSchema($this->getSchema('settings'));
    }

    #[LiveAction]
    public function clearErrors(#[LiveArg] ?string $schemaKey = null): void
    {
        $this->clearSchemaValidation($schemaKey);
    }
}
