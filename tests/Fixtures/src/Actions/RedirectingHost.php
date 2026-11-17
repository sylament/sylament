<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose actions redirect after they run to `/home` unless they name a URL of their
 * own: `onward` names one for its success, `retry` fails and names one for its failure,
 * and `stay` fails and names none, which the host has no default for.
 */
#[AsLiveComponent(name: 'Actions:RedirectingHost', template: '@Fixtures/actions/RedirectingHost.html.twig')]
class RedirectingHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function homeAction(): Action
    {
        return Action::make('home');
    }

    public function onwardAction(): Action
    {
        return Action::make('onward')->successRedirectUrl(static fn (Action $action): string => "/{$action->getName()}");
    }

    public function retryAction(): Action
    {
        return Action::make('retry')
            ->failureRedirectUrl('/try-again')
            ->action(static fn (Action $action) => $action->failure());
    }

    public function stayAction(): Action
    {
        return Action::make('stay')
            ->successRedirectUrl('/never')
            ->action(static fn (Action $action) => $action->failure());
    }

    public function getDefaultActionSuccessRedirectUrl(Action $action): string
    {
        return '/home';
    }
}
