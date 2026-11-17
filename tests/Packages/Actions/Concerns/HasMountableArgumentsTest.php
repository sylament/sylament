<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\Selectors;

it('mounts with the arguments it was invoked with, leaving the action it was invoked on without them', function (): void {
    $action = Action::make('edit')->arguments(['tone' => 'soft']);
    $invoked = $action(['line' => 5]);

    $plain = $this->renderViewComponent($action);

    expect($this->renderViewComponent($invoked))->toContainElement(Selectors::LIVE_CLICK . Selectors::liveParameter('name', 'edit') . Selectors::liveParameter('arguments', '{"line":5}'), 'Edit')
        ->and($plain)->toContainElement(Selectors::LIVE_CLICK . Selectors::liveParameter('name', 'edit'), 'Edit')
        ->and($plain->filter('[data-live-arguments-param]'))->toHaveCount(0);
});
