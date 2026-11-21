<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function Symfony\Component\Translation\t;

#[AsLiveComponent(name: 'Actions:ToolbarHost', template: '@Fixtures/actions/ToolbarHost.html.twig')]
class ToolbarHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function purgeAction(): Action
    {
        return Action::make('purge')->hidden();
    }

    public function archiveAction(): Action
    {
        return Action::make('archive')->successNotificationTitle('Archived');
    }

    public function archiveTranslatedAction(): Action
    {
        return Action::make('archiveTranslated')->successNotificationTitle(t('notification.title', [], 'fixtures-actions'));
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')
            ->failureNotificationTitle('Could not restore')
            ->action(function (Action $action): void {
                $action->failure();
            });
    }

    #[LiveAction]
    public function fail(): never
    {
        throw new RuntimeException('The toolbar failed.');
    }
}
