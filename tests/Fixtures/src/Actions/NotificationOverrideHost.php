<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Notifications\Notification;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose actions override the notification a call sends, one shape each, as their
 * names say: the success arm's first, then the failure arm's, whose actions fail.
 */
#[AsLiveComponent(name: 'Actions:NotificationOverrideHost', template: '@Fixtures/actions/NotificationOverrideHost.html.twig')]
class NotificationOverrideHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function replacedAction(): Action
    {
        return Action::make('replaced')
            ->successNotificationTitle('Saved')
            ->successNotification(Notification::make()->info()->title('Filed'));
    }

    public function retitledAction(): Action
    {
        return Action::make('retitled')
            ->successNotificationTitle('Saved')
            ->successNotification(static fn (Notification $notification): Notification => $notification->title('Saved twice')->body('Both copies'));
    }

    public function keptAction(): Action
    {
        return Action::make('kept')
            ->successNotificationTitle('Saved')
            ->successNotification(static fn (): null => null);
    }

    public function titledAction(): Action
    {
        return Action::make('titled')
            ->successNotification(static fn (Notification $notification): Notification => $notification->title('Saved at last'));
    }

    public function untitledAction(): Action
    {
        return Action::make('untitled');
    }

    public function silencedAction(): Action
    {
        return Action::make('silenced')
            ->successNotificationTitle('Saved')
            ->successNotification(null);
    }

    public function untitledByClosureAction(): Action
    {
        return Action::make('untitledByClosure')
            ->successNotificationTitle('Saved')
            ->successNotification(static fn (Notification $notification): Notification => $notification->title(null));
    }

    public function failureReplacedAction(): Action
    {
        return $this->failing('failureReplaced')
            ->failureNotificationTitle('Not saved')
            ->failureNotification(Notification::make()->warning()->title('Filed for later'));
    }

    public function failureRetitledAction(): Action
    {
        return $this->failing('failureRetitled')
            ->failureNotificationTitle('Not saved')
            ->failureNotification(static fn (Notification $notification): Notification => $notification->title('Not saved twice')->body('Neither copy'));
    }

    public function failureKeptAction(): Action
    {
        return $this->failing('failureKept')
            ->failureNotificationTitle('Not saved')
            ->failureNotification(static fn (): null => null);
    }

    public function failureUntitledAction(): Action
    {
        return $this->failing('failureUntitled');
    }

    public function failureSilencedAction(): Action
    {
        return $this->failing('failureSilenced')
            ->failureNotificationTitle('Not saved')
            ->failureNotification(null);
    }

    public function partlyFailedAction(): Action
    {
        return Action::make('partlyFailed')
            ->failureNotificationTitle('Partly saved')
            ->action(static function (Action $action): void {
                $action->reportBulkProcessingSuccessfulRecordsCount(1);
                $action->failure();
            });
    }

    private function failing(string $name): Action
    {
        return Action::make($name)->action(static function (Action $action): void {
            $action->failure();
        });
    }
}
