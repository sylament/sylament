<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\HookedModalHost;
use Sylament\Tests\Fixtures\Schemas\HookLog;

it('runs its form hooks while mounting and validating, then its before hook, its action and its after hook between the lifecycle events', function (): void {
    $tester = $this->createLiveComponent(HookedModalHost::class)->mountAction('log');

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeFormFilled', 'afterFormFilled']);

    $tester->setActionData(['name' => 'Lamp'])->callMountedAction();

    expect($this::getContainer()->get(HookLog::class)->hooks)
        ->toBe(['beforeFormValidated', 'afterFormValidated', 'calling log', 'before', 'action', 'after', 'called log']);
});

it('announces the action was called even when its after hook throws', function (): void {
    expect(fn () => $this->createLiveComponent(HookedModalHost::class)->callAction('breakAfter'))
        ->toThrow(RuntimeException::class, 'The after hook broke.')
        ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe(['calling breakAfter', 'action', 'called breakAfter']);
});

it('announces nothing for an action built without the container', function (): void {
    $hookLog = $this::getContainer()->get(HookLog::class);
    $host = new HookedModalHost();
    $host->setHookLog($hookLog);

    new Action()->name('built')->liveComponent($host)->callBefore();
    Action::make('made')->liveComponent($host)->callBefore();

    expect($hookLog->hooks)->toBe(['calling made']);
});
