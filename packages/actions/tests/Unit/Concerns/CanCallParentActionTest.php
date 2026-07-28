<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;

it('defaults to no parent call handler', function (): void {
    expect(Action::make('a')->getParentActionCallLiveComponentClickHandler())->toBeNull();
});

it('round-trips the parent call handler', function (): void {
    expect(Action::make('a')->callParent('callMountedAction')->getParentActionCallLiveComponentClickHandler())->toBe('callMountedAction')
        ->and(Action::make('a')->callParent('x')->callParent(null)->getParentActionCallLiveComponentClickHandler())->toBeNull();
});
