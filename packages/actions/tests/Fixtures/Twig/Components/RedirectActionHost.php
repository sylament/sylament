<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The redirect rig: one action carrying its own success URL, two leaning on the
 * host hooks, and one carrying no redirect at all so the unchanged pipeline stays
 * pinned. The hooks answer per action name, so the plain action still gets null.
 */
#[AsLiveComponent(name: 'RedirectActionHost', template: '@ActionsFixtures/components/RedirectActionHost.html.twig')]
class RedirectActionHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    #[LiveProp]
    public bool $called = false;

    public function saveAction(): Action
    {
        return Action::make('save')
            ->successRedirectUrl('/after')
            ->action(fn (): true => $this->called = true);
    }

    public function hostDefaultAction(): Action
    {
        return Action::make('hostDefault')
            ->action(fn (): true => $this->called = true);
    }

    public function hostFailureAction(): Action
    {
        return Action::make('hostFailure')
            ->action(function (Action $action): void {
                $action->failure();
            });
    }

    public function plainAction(): Action
    {
        return Action::make('plain')
            ->action(fn (): string => 'plain result');
    }

    public function getDefaultActionSuccessRedirectUrl(Action $action): ?string
    {
        return $action->getName() === 'hostDefault' ? '/from-host' : null;
    }

    public function getDefaultActionFailureRedirectUrl(Action $action): ?string
    {
        return $action->getName() === 'hostFailure' ? '/from-host-failure' : null;
    }
}
