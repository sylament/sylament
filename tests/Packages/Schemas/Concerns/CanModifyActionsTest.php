<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Selectors;

it('rewrites each action and action group child as it resolves them, and keeps one its hook answers with nothing for', function (): void {
    $render = $this->renderViewComponent(Schema::make()
        ->modifyActionsUsing(static fn (Action $action): ?Action => ($action->getName() === 'archive') ? $action->label('Archive for good') : null)
        ->modifyActionGroupsUsing(static fn (ActionGroup $group): ActionGroup => $group->label('Everything else'))
        ->components([
            Action::make('archive'),
            Action::make('publish'),
            ActionGroup::make([Action::make('export')])->label('More'),
        ]));

    expect($render)
        ->toContainElement(Selectors::ACTION_CELL . ' button', 'Archive for good')
        ->toContainElement(Selectors::ACTION_CELL . ' button', 'Publish')
        ->toContainElement(Selectors::ACTION_CELL . ' button[aria-label="Everything else"]');
});
