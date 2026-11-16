<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Casts\BooleanStateCast;
use Sylament\Schemas\State\Set;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose writer fields each make one `$set` call when the client changes them: a
 * flag stored as an int, a field and a group recording their update hooks in the
 * request's {@see HookLog}, a closure value, a path no field owns, and two groups writing
 * their own child, one searching inside itself. Every field a writer writes sits before
 * it, so the host's own update pass has already passed it and runs no hook of its own.
 */
#[AsLiveComponent(name: 'Schemas:WriterForm', template: '@Fixtures/schemas/WriterForm.html.twig')]
class WriterForm implements InteractsWithSchemas
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
                TextInput::make('target')
                    ->afterStateUpdated(function (): void {
                        $this->hookLog->record('target');
                    }),
                Group::make([
                    TextInput::make('inner')
                        ->afterStateUpdated(function (): void {
                            $this->hookLog->record('inner');
                        }),
                ])
                    ->statePath('box')
                    ->afterStateUpdated(function (): void {
                        $this->hookLog->record('box');
                    }),
                TextInput::make('subscribed')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
                TextInput::make('echo'),
                Group::make([
                    TextInput::make('trigger'),
                    TextInput::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
                ])
                    ->statePath('settings')
                    ->afterStateUpdated(static fn (Set $set): mixed => $set('settings.flag', false)),
                Group::make([
                    TextInput::make('trigger'),
                    TextInput::make('flag')->stateCast(new BooleanStateCast(isStoredAsInt: true)),
                ])
                    ->statePath('options')
                    ->afterStateUpdated(static fn (Set $set): mixed => $set->skipComponentsChildContainersWhileSearching(condition: false)('options.flag', false)),
                TextInput::make('cast')
                    ->afterStateUpdated(static fn (Set $set): mixed => $set('subscribed', true)),
                TextInput::make('quiet')
                    ->afterStateUpdated(static fn (Set $set): mixed => $set('target', 'Quiet')),
                TextInput::make('loud')
                    ->afterStateUpdated(static fn (Set $set): mixed => $set('box.inner', 'Loud', shouldCallUpdatedHooks: true)),
                TextInput::make('evaluated')
                    ->afterStateUpdated(static fn (Set $set): mixed => $set('echo', $set('target', static fn (): string => 'From a closure'))),
                TextInput::make('stray')
                    ->afterStateUpdated(static fn (Set $set): mixed => $set('extra', 'Written')),
            ]);
    }
}
