<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\HookedModalHost;
use Sylament\Tests\Fixtures\Schemas\HookLog;

it("fills its modal's form from the fields' defaults when it is mounted", function (): void {
    $this->createLiveComponent(HookedModalHost::class)
        ->mountAction('draft')
        ->assertActionDataSet(['name' => 'Untitled', 'note' => null]);
});

it("fills its modal's form with what fillForm() answers, the closure handed the modal's own schema", function (): void {
    $this->createLiveComponent(HookedModalHost::class)
        ->mountAction('fill')
        ->assertActionDataSet(['note' => 'Filled for fill']);
});

it('runs what mountUsing() gives it instead of the default fill', function (): void {
    $this->createLiveComponent(HookedModalHost::class)
        ->mountAction('prime')
        ->assertActionMounted('prime')
        ->assertActionDataSet(['name' => null]);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['primed']);
});
