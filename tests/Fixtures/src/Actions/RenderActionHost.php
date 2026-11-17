<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Renders its actions through `renderAction()`, each call in a slot of its own: `delete`
 * bound to arguments and then plain, a name it does not resolve, the hidden `purge`, and
 * `scoped`, visible only for the arguments `{id: 5}`, bound and then plain.
 */
#[AsLiveComponent(name: 'Actions:RenderActionHost', template: '@Fixtures/actions/RenderActionHost.html.twig')]
class RenderActionHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function deleteAction(): Action
    {
        return Action::make('delete');
    }

    public function purgeAction(): Action
    {
        return Action::make('purge')->hidden();
    }

    public function scopedAction(): Action
    {
        return Action::make('scoped')->visible(fn (array $arguments): bool => ($arguments['id'] ?? null) === 5);
    }
}
