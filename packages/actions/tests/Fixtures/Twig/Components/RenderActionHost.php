<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Exercises the `renderAction()` Twig function: its template renders one
 * resolvable action, the same action with bound arguments, one name with no
 * factory method, and one hidden action, each wrapped in a marker `<span>` so
 * the empty-output cases are assertable.
 */
#[AsLiveComponent(name: 'RenderActionHost', template: '@ActionsFixtures/components/RenderActionHost.html.twig')]
class RenderActionHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function deleteAction(): Action
    {
        return Action::make('delete');
    }

    public function hiddenAction(): Action
    {
        return Action::make('hidden')->hidden();
    }
}
