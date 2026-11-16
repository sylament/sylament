<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Operation;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form run under the operation it is mounted with: a required read-only field beside a
 * writable one, and fields each read-only on an operation given another way: a string,
 * an enum case, a list, or this host's class.
 */
#[AsLiveComponent(name: 'Forms:ReadOnlyForm', template: '@Fixtures/forms/ReadOnlyForm.html.twig')]
final class ReadOnlyForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    #[LiveProp]
    public string $operation = 'create';

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
            ->operation($this->operation)
            ->statePath('data')
            ->components([
                TextInput::make('reference')->readOnly()->required(),
                TextInput::make('title'),
                TextInput::make('readOnlyByString')->readOnlyOn('edit'),
                TextInput::make('readOnlyByCase')->readOnlyOn(Operation::Edit),
                TextInput::make('readOnlyByList')->readOnlyOn(['view', Operation::Edit]),
                TextInput::make('readOnlyByHost')->readOnlyOn(self::class),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
