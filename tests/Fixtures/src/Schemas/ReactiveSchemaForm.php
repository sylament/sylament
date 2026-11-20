<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Set;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose update hooks record in the request's {@see HookLog}: `title` with the
 * value it held before the request, `body` and the `address` group by name, and `name`
 * inside the `rename` action's modal schema. `secret` is hidden and keeps the state it
 * was given, and a change to `moved` sets the `address` container to `null`.
 */
#[AsLiveComponent(name: 'Schemas:ReactiveSchemaForm', template: '@Fixtures/schemas/ReactiveSchemaForm.html.twig')]
class ReactiveSchemaForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = ['secret' => 'kept'];

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
                TextInput::make('title')
                    ->live()
                    ->afterStateUpdated(function (?string $old, ?string $state): void {
                        $this->hookLog->record("title: {$old} -> {$state}");
                    }),
                TextInput::make('body')
                    ->afterStateUpdated(function (): void {
                        $this->hookLog->record('body');
                    }),
                TextInput::make('secret')->hidden(),
                TextInput::make('moved')
                    ->afterStateUpdated(static fn (Set $set): mixed => $set('address', null)),
                Group::make([
                    TextInput::make('city'),
                ])
                    ->statePath('address')
                    ->afterStateUpdated(function (): void {
                        $this->hookLog->record('address');
                    }),
            ]);
    }

    public function renameAction(): Action
    {
        return Action::make('rename')
            ->schema([
                TextInput::make('name')
                    ->live()
                    ->afterStateUpdated(function (?string $state): void {
                        $this->hookLog->record("name: {$state}");
                    }),
            ])
            ->action(static fn (): null => null);
    }
}
