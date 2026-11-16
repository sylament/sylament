<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Operation;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form run under the operation it is mounted with, whose fields each hide, show or
 * disable themselves on an operation given another way: a string, an enum case, a
 * list, or this host's class.
 */
#[AsLiveComponent(name: 'Schemas:OperationForm', template: '@Fixtures/schemas/OperationForm.html.twig')]
class OperationForm implements InteractsWithSchemas
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
                TextInput::make('hiddenByString')->hiddenOn('edit'),
                TextInput::make('hiddenByCase')->hiddenOn(Operation::Edit),
                TextInput::make('hiddenByList')->hiddenOn(['view', Operation::Edit]),
                TextInput::make('hiddenByHost')->hiddenOn(self::class),
                TextInput::make('hiddenByUnknown')->hiddenOn('archive'),
                TextInput::make('visibleByCase')->visibleOn(Operation::Edit),
                TextInput::make('disabledByString')->disabledOn('edit'),
                TextInput::make('disabledByCase')->disabledOn(Operation::Edit),
                TextInput::make('disabledByList')->disabledOn(['view', Operation::Edit]),
                TextInput::make('disabledByHost')->disabledOn(self::class),
            ]);
    }
}
