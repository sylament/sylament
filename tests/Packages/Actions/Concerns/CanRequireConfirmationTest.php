<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\ModalChromeHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;

it('opens a confirmation on mount and runs only once it is confirmed', function (): void {
    $tester = $this->createLiveComponent(ModalChromeHost::class)->mountAction('confirmed')->assertActionMounted('confirmed');

    expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN))->toContainElement('h2', 'Confirmed')
        ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);

    $tester->callMountedAction()->assertActionNotMounted();

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['confirmed']);
});
