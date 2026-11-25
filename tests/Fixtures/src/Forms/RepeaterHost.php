<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Repeaters a user edits through their actions, each recording in the request's
 * {@see HookLog}: `lines` with every structural action on, its `afterStateUpdated` hook,
 * an extra item action and an action placed in its item schema; `folded`, collapsed, with
 * its clone and between-item buttons shown; `bounded` and `unbounded`, item-count limits,
 * the latter's unset again, its between-item button hidden as it ships.
 * `save()` validates the form.
 */
#[AsLiveComponent(name: 'Forms:RepeaterHost', template: '@Fixtures/forms/RepeaterHost.html.twig')]
class RepeaterHost implements InteractsWithActions, InteractsWithSchemas
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
                Repeater::make('lines')
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->addBetweenAction(static fn (Action $action): Action => $action->visible())
                    ->extraItemActions([
                        Action::make('inspect')->action(function (array $schemaState, Repeater $component): void {
                            $this->hookLog->record(sprintf('inspect %s on %s', json_encode($schemaState, JSON_THROW_ON_ERROR), $component->getName()));
                        }),
                    ])
                    ->afterStateUpdated(function (): void {
                        $this->hookLog->record('lines updated');
                    })
                    ->components([
                        TextInput::make('name'),
                        Actions::make([
                            Action::make('ping')->action(function (array $schemaState): void {
                                $this->hookLog->record(sprintf('ping %s', json_encode($schemaState, JSON_THROW_ON_ERROR)));
                            }),
                        ]),
                    ]),
                Repeater::make('folded')
                    ->collapsed()
                    ->cloneable()
                    ->addBetweenAction(static fn (Action $action): Action => $action->visible())
                    ->components([TextInput::make('name')]),
                Repeater::make('bounded')
                    ->minItems(2)
                    ->maxItems(static fn (): int => 3)
                    ->components([TextInput::make('name')]),
                Repeater::make('unbounded')
                    ->maxItems(2)
                    ->maxItems(null)
                    ->components([TextInput::make('name')]),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
