<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Set;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose fields each show one part of the state engine: a group default, a
 * formatted default, an update hook writing a field placed before it and asking for that
 * field's hook, which records in the request's {@see HookLog}, a value trimmed for
 * validation only, fields kept out of the saved state, and one sharing its path with a
 * saved one. `fillDefaults()` runs a defaults fill over whatever the bag holds.
 */
#[AsLiveComponent(name: 'Schemas:StateForm', template: '@Fixtures/schemas/StateForm.html.twig')]
class StateForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
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
                Group::make([
                    TextInput::make('name'),
                    TextInput::make('surname'),
                ])->statePath('author')->default(['name' => 'Ada']),
                TextInput::make('nickname')
                    ->default('ada')
                    ->formatStateUsing(static fn (?string $state): string => ucfirst((string) $state)),
                TextInput::make('slug')
                    ->afterStateUpdated(function (?string $state): void {
                        $this->hookLog->record("slug: {$state}");
                    }),
                TextInput::make('title')
                    ->afterStateUpdated(static fn (Set $set, ?string $state): mixed => $set('slug', mb_strtolower((string) $state), shouldCallUpdatedHooks: true)),
                TextInput::make('code')
                    ->required()
                    ->maxLength(2)
                    ->mutateStateForValidationUsing(static fn (?string $state): string => mb_trim((string) $state)),
                TextInput::make('locked')->disabled(),
                TextInput::make('tag'),
                TextInput::make('tag')->dehydrated(false),
                Section::make()
                    ->dehydrated(false)
                    ->components([TextInput::make('note')]),
            ]);
    }

    #[LiveAction]
    public function fillDefaults(): void
    {
        $this->getSchema('form')->fill();
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}
