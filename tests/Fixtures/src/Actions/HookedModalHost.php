<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Modal actions whose hooks and closures record in the request's {@see HookLog}, beside
 * the lifecycle events {@see ActionEventRecorder} records there for this host: `log` sets
 * every lifecycle hook, `breakAfter` throws from its after hook, `prime` replaces the
 * mount, `draft` and `fill` fill their form from its defaults and from a closure given the
 * modal's schema, and `shout`, `listing` and `scalar` mutate their data, the last two into
 * something that is not keyed by field.
 */
#[AsLiveComponent(name: 'Actions:HookedModalHost', template: '@Fixtures/actions/HookedModalHost.html.twig')]
class HookedModalHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function schemas(): array
    {
        return [];
    }

    public function logAction(): Action
    {
        return Action::make('log')
            ->schema([TextInput::make('name')])
            ->beforeFormFilled(fn () => $this->hookLog->record('beforeFormFilled'))
            ->afterFormFilled(fn () => $this->hookLog->record('afterFormFilled'))
            ->beforeFormValidated(fn () => $this->hookLog->record('beforeFormValidated'))
            ->afterFormValidated(fn () => $this->hookLog->record('afterFormValidated'))
            ->before(fn () => $this->hookLog->record('before'))
            ->action(fn () => $this->hookLog->record('action'))
            ->after(fn () => $this->hookLog->record('after'));
    }

    public function breakAfterAction(): Action
    {
        return Action::make('breakAfter')
            ->action(fn () => $this->hookLog->record('action'))
            ->after(static fn (): never => throw new RuntimeException('The after hook broke.'));
    }

    public function primeAction(): Action
    {
        return Action::make('prime')
            ->schema([TextInput::make('name')->default('Untitled')])
            ->mountUsing(fn () => $this->hookLog->record('primed'));
    }

    public function draftAction(): Action
    {
        return Action::make('draft')
            ->schema([TextInput::make('name')->default('Untitled'), TextInput::make('note')]);
    }

    public function fillAction(): Action
    {
        return Action::make('fill')
            ->schema([TextInput::make('name')->default('Untitled'), TextInput::make('note')])
            ->fillForm(static fn (Schema $schema): array => ['note' => "Filled for {$schema->getOperation()}"]);
    }

    public function shoutAction(): Action
    {
        return Action::make('shout')
            ->schema([TextInput::make('name')])
            ->mutateDataUsing(static fn (array $data): array => [...$data, 'tone' => 'loud'])
            ->action(fn (array $data) => $this->hookLog->record(json_encode($data, JSON_THROW_ON_ERROR)));
    }

    public function listingAction(): Action
    {
        return Action::make('listing')
            ->schema([TextInput::make('name')])
            ->mutateDataUsing(static fn (array $data): array => array_values($data));
    }

    public function scalarAction(): Action
    {
        return Action::make('scalar')
            ->schema([TextInput::make('name')])
            ->mutateDataUsing(static fn (array $data): string => 'not an array');
    }
}
