<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose template nests `<twig:SylamentActions:Modals />` inside another Twig
 * component, so the render stack between the tag and the host is not empty. Proves the
 * component resolves its host by walking that stack rather than by reading the
 * enclosing render context — which would bind to the Section's anonymous component.
 */
#[AsLiveComponent(name: 'NestedActionHost', template: '@ActionsFixtures/components/NestedActionHost.html.twig')]
class NestedActionHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->requiresConfirmation()
            ->modalHeading('Delete?')
            ->action(fn (): true => true);
    }
}
