<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host whose template places the modals tag inside a section component rather than at its
 * own root, with one action, `peek`, that opens a modal.
 */
#[AsLiveComponent(name: 'Actions:SectionedModalsHost', template: '@Fixtures/actions/SectionedModalsHost.html.twig')]
class SectionedModalsHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function peekAction(): Action
    {
        return Action::make('peek')->modalHeading('Peek');
    }
}
