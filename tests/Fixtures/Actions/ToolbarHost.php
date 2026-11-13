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

#[AsLiveComponent(name: 'Actions:ToolbarHost', template: '@Fixtures/actions/ToolbarHost.html.twig')]
class ToolbarHost implements InteractsWithActions
{
    use DefaultActionTrait;
    use HasActions;

    public function purgeAction(): Action
    {
        return Action::make('purge')->hidden();
    }

    #[LiveAction]
    public function fail(): never
    {
        throw new RuntimeException('The toolbar failed.');
    }
}
