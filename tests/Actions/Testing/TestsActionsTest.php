<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Tests\Fixtures\Actions\ToolbarHost;

it('fails asserting that an action exists when the host has none by that name', function (): void {
    expect(fn () => $this->createLiveComponent(ToolbarHost::class)->assertActionExists('archive'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that an action with name [archive] exists');
});

it('fails asserting that a hidden action is visible', function (): void {
    $tester = $this->createLiveComponent(ToolbarHost::class)->assertActionExists('purge');

    expect(fn () => $tester->assertActionVisible('purge'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that an action with name [purge] is visible');
});
