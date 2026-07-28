<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * ActionHost subclass recording every afterActionCalled() invocation, plus a
 * halting and a cancelling action to prove the hook stays silent on both.
 * Reuses the parent's template — these tests never render.
 */
#[AsLiveComponent(name: 'AfterActionCalledHost', template: '@ActionsFixtures/components/ActionHost.html.twig')]
class AfterActionCalledHost extends ActionHost
{
    /**
     * @var array<int, Action>
     */
    public array $calledWith = [];

    public function haltingAction(): Action
    {
        return Action::make('halting')
            ->action(fn (Action $action) => $action->halt());
    }

    public function cancellingAction(): Action
    {
        return Action::make('cancelling')
            ->action(fn (Action $action) => $action->cancel());
    }

    protected function afterActionCalled(Action $action): void
    {
        $this->calledWith[] = $action;
    }
}
