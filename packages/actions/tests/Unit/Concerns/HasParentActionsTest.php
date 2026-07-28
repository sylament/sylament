<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;

it('defaults every parent-action knob off', function (): void {
    $action = Action::make('child');

    expect($action->shouldCancelAllParentActions())->toBeFalse()
        ->and($action->getParentActionToCancelTo())->toBeNull()
        ->and($action->getParentActionsToCancelOnClose())->toBeFalse()
        ->and($action->shouldCancelParentActionsOnClose())->toBeFalse()
        ->and($action->shouldCancelAllParentActionsOnClose())->toBeFalse()
        ->and($action->getParentActionToCancelToOnClose())->toBeNull()
        ->and($action->shouldOverlayParentActions())->toBeFalse();
});

it('round-trips the cancel knobs, closures and strings included', function (): void {
    expect(Action::make('a')->cancelParentActions()->shouldCancelAllParentActions())->toBeTrue()
        ->and(Action::make('a')->cancelParentActions('edit')->getParentActionToCancelTo())->toBe('edit')
        ->and(Action::make('a')->cancelParentActions('edit')->shouldCancelAllParentActions())->toBeFalse()
        ->and(Action::make('a')->cancelParentActions(fn (): bool => true)->shouldCancelAllParentActions())->toBeTrue()
        ->and(Action::make('a')->overlayParentActions()->shouldOverlayParentActions())->toBeTrue()
        ->and(Action::make('a')->overlayParentActions(fn (): bool => false)->shouldOverlayParentActions())->toBeFalse();
});

it('normalizes the on-close value to bool or string', function (): void {
    expect(Action::make('a')->cancelParentActionsOnClose()->getParentActionsToCancelOnClose())->toBeTrue()
        ->and(Action::make('a')->cancelParentActionsOnClose('edit')->getParentActionsToCancelOnClose())->toBe('edit')
        ->and(Action::make('a')->cancelParentActionsOnClose('edit')->getParentActionToCancelToOnClose())->toBe('edit')
        ->and(Action::make('a')->cancelParentActionsOnClose(false)->getParentActionsToCancelOnClose())->toBeFalse()
        ->and(Action::make('a')->cancelParentActionsOnClose()->shouldCancelAllParentActionsOnClose())->toBeTrue();
});
