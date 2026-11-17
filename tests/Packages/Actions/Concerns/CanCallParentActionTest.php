<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\Selectors;

it("calls its parent's handler on click, with its arguments", function (): void {
    expect($this->renderViewComponent(Action::make('submitAnother')->callParent('callMountedAction')->arguments(['another' => true])))
        ->toContainElement(Selectors::LIVE_CLICK . Selectors::liveParameter('action', 'callMountedAction') . Selectors::liveParameter('arguments', '{"another":true}'), 'Submit another');
});
