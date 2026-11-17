<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Tests\Fixtures\Actions\HookedModalHost;
use Sylament\Tests\Fixtures\Schemas\HookLog;

it("hands its action its modal form's data as its mutation returns it", function (): void {
    $this->createLiveComponent(HookedModalHost::class)
        ->callAction('shout', data: ['name' => 'lamp']);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toContain('{"name":"lamp","tone":"loud"}');
});

it('refuses a mutation that does not answer an array keyed by field name', function (string $action): void {
    expect(fn () => $this->createLiveComponent(HookedModalHost::class)->callAction($action, data: ['name' => 'lamp']))
        ->toThrow(TypeError::class, Action::class . '::mutateDataUsing() must return an array keyed by field name.');
})->with([
    'a list' => ['listing'],
    'a string' => ['scalar'],
]);
