<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'ActionHost', template: '@ActionsFixtures/components/ActionHost.html.twig')]
class ActionHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    /**
     * Exposed as a LiveProp so the closure's side effect survives the round-trip
     * and can be read back in the harness (the `LiveStateHost::$savedState` idiom).
     */
    #[LiveProp]
    public bool $called = false;

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->requiresConfirmation()
            ->action(fn (): true => $this->called = true);
    }
}
