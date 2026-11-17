<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Fieldset;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Get;
use Sylament\Schemas\State\Set;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\TaggingStateCast;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose two schema actions record in the request's {@see HookLog} what their
 * closures were handed: `peek`, in an action row inside the `address` fieldset, its state,
 * the form's, its schema and a sibling field through `$get`, then writes that field through
 * `$set`; `stamp`, a header action of the `Notes` section, its state, its component and the
 * section's tagged `note` field through a `Get` and a `Set` it names otherwise.
 */
#[AsLiveComponent(name: 'Actions:AddressStateForm', template: '@Fixtures/actions/AddressStateForm.html.twig')]
class AddressStateForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Fieldset::make('Address')->statePath('address')->components([
                    TextInput::make('street'),
                    Actions::make([
                        Action::make('peek')->action(function (mixed $state, mixed $schemaState, ?Schema $schema, Get $get, Set $set): void {
                            $this->hookLog->record(json_encode(['state' => $state, 'schemaState' => $schemaState, 'schema' => $schema?->getKey(), 'get' => $get('street')], JSON_THROW_ON_ERROR));
                            $set('street', 'Side Street');
                        }),
                    ]),
                ]),
                Section::make('Notes')
                    ->headerActions([
                        Action::make('stamp')->action(function (mixed $state, ?Component $schemaComponent, Get $read, Set $write): void {
                            $this->hookLog->record(json_encode(['state' => $state, 'schemaComponent' => get_debug_type($schemaComponent), 'read' => $read('note')], JSON_THROW_ON_ERROR));
                            $write('note', 'stamped');
                        }),
                    ])
                    ->components([TextInput::make('note')->stateCast(TaggingStateCast::First)]),
            ]);
    }
}
