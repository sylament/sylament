<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\NestedModalHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Schemas\HookLog;

it('closes only its own modal once it runs, unless it cancels its parents, every one or back to the one it names', function (string $path, array $stillMounted): void {
    $tester = $this->createLiveComponent(NestedModalHost::class)->mountAction($path)->callMountedAction();

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([array_last(explode('.', $path))]);

    $stillMounted === [] ? $tester->assertActionNotMounted() : $tester->assertActionMounted($stillMounted);
})->with([
    'as it ships' => ['outer.inner', ['outer']],
    'cancelling every parent' => ['outer.cancelling', []],
    'cancelling back to a named parent' => ['outer.middle.deep', ['outer']],
]);

it("closes its parent's modal while it is open, unless it overlays it", function (): void {
    $render = fn (string $path) => $this->createLiveComponent(NestedModalHost::class)->mountAction($path)->render()->crawler();

    expect($render('outer.overlaying'))->toContainElement(Selectors::mountedModal(0) . '[open]')
        ->toContainElement(Selectors::mountedModal(1) . '[open]')
        ->and($render('outer.inner'))->toContainElement(Selectors::mountedModal(0) . ':not([open])')
        ->toContainElement(Selectors::mountedModal(1) . '[open]');
});

it('cancels its parents when its modal is dismissed, if told to', function (): void {
    $dismissal = fn (string $path, string $name) => $this->createLiveComponent(NestedModalHost::class)->mountAction($path)->render()->crawler()
        ->filter("[id$=\"-w-{$name}\"]")->attr('data-live-cancel-parent-actions-param');

    expect($dismissal('outer.closing', 'closing'))->toBe('true')
        ->and($dismissal('outer.inner', 'inner'))->toBe('false');
});
