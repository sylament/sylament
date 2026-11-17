<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\Tally;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host with actions and no schemas, recording in the request's {@see HookLog} what ran:
 * `confirm` opens a chain of modals (`details` under it, three ways of closing above that,
 * a child overlaying it and a modal-less child that halts); others halt or cancel while
 * mounting, are disabled, run without a modal, redirect, write a {@see Tally} row and then
 * stop as their arguments say, or record each step of the call in order. Its methods give
 * the name lookup each shape to tell apart, and its `afterActionCalled()` records too.
 */
#[AsLiveComponent(name: 'Actions:ActionStackHost', template: '@Fixtures/actions/ActionStackHost.html.twig')]
class ActionStackHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function __construct(
        private readonly HookLog $hookLog,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function confirmAction(): Action
    {
        return Action::make('confirm')
            ->modalHeading('Confirm the run')
            ->action(function (): void {
                $this->hookLog->record('confirm');
            })
            ->registerModalActions([
                Action::make('details')
                    ->modalHeading('Details')
                    ->registerModalActions([
                        Action::make('notes')->modalHeading('Notes'),
                        Action::make('discardAll')->modalHeading('Discard all')->cancelParentActions(),
                        Action::make('backToConfirm')->modalHeading('Back to confirm')->cancelParentActions('details'),
                    ]),
                Action::make('overlay')->modalHeading('Overlay')->overlayParentActions(),
                Action::make('hold')->action(function (Action $action): void {
                    $action->halt();
                }),
            ]);
    }

    public function haltOnMountAction(): Action
    {
        return Action::make('haltOnMount')
            ->modalHeading('Halted on mount')
            ->mountUsing(function (Action $action): void {
                $action->halt();
            });
    }

    public function cancelOnMountAction(): Action
    {
        return Action::make('cancelOnMount')
            ->modalHeading('Cancelled on mount')
            ->mountUsing(function (Action $action): void {
                $action->cancel();
            });
    }

    public function lockedAction(): Action
    {
        return Action::make('locked')
            ->disabled()
            ->action(function (): void {
                $this->hookLog->record('locked');
            });
    }

    public function quickAction(): Action
    {
        return Action::make('quick')
            ->successNotificationTitle('Done quickly')
            ->action(function (): void {
                $this->hookLog->record('quick');
            });
    }

    public function leaveAction(): Action
    {
        return Action::make('leave')->successRedirectUrl('/left');
    }

    public function tallyAction(): Action
    {
        return Action::make('tally')
            ->requiresConfirmation()
            ->databaseTransaction()
            ->action(function (Action $action, array $arguments): void {
                $this->entityManager->persist(new Tally());
                $this->entityManager->flush();

                $rollBack = (bool) ($arguments['rollBack'] ?? false);

                if (($arguments['outcome'] ?? null) === 'halt') {
                    $action->halt($rollBack);
                }

                if (($arguments['outcome'] ?? null) === 'cancel') {
                    $action->cancel($rollBack);
                }

                if (($arguments['outcome'] ?? null) === 'throw') {
                    throw new RuntimeException('The tally failed.');
                }
            });
    }

    public function orderedAction(): Action
    {
        return Action::make('ordered')
            ->requiresConfirmation()
            ->before(function (): void {
                $this->hookLog->record('before');
            })
            ->action(function (): void {
                $this->hookLog->record('action');
            })
            ->after(function (): void {
                $this->hookLog->record('after');
            })
            ->successNotificationTitle(function (): string {
                $this->hookLog->record('notification');

                return 'Ordered';
            })
            ->successRedirectUrl(function (): string {
                $this->hookLog->record('redirect');

                return '/ordered';
            });
    }

    public function renameAction(): Action
    {
        return Action::make('rename')->schema([
            TextInput::make('name'),
        ]);
    }

    public function pingAction(): Action
    {
        return Action::make('ping')
            ->modalHeading('Ping')
            ->action(function (?Schema $schema): void {
                $this->hookLog->record($schema instanceof Schema ? "ping: {$schema->getKey()}" : 'ping: no schema');
            });
    }

    public function archiveAction(): Action
    {
        return Action::make('archive')->label('Archive by factory');
    }

    public function archive(): Action
    {
        return Action::make('archive')->label('Archive by method');
    }

    public function purge(): Action
    {
        return Action::make('purge')->label('Purge');
    }

    public function stampAction(): Action
    {
        return Action::make('stampAction')->label('Stamp by its own name');
    }

    public function stampActionAction(): Action
    {
        return Action::make('stampAction')->label('Stamp by the suffixed name');
    }

    public function status(): string
    {
        $this->hookLog->record('status()');

        return 'idle';
    }

    public function lyingAction(): ?Action
    {
        $this->hookLog->record('lyingAction()');

        return null;
    }

    protected function afterActionCalled(Action $action): void
    {
        $this->hookLog->record("afterActionCalled: {$action->getName()}");
    }
}
