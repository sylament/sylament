<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;

it('stores a plain dispatch with direction false', function (): void {
    $action = Action::make('undo')->dispatch('undoEditingPost', ['post' => 5]);

    expect($action->getEvent())->toBe('undoEditingPost')
        ->and($action->getEventData())->toBe(['post' => 5])
        ->and($action->getDispatchDirection())->toBeFalse()
        ->and($action->getDispatchToComponent())->toBeNull();
});

it('stores dispatchSelf and dispatchTo directions', function (): void {
    expect(Action::make('a')->dispatchSelf('x')->getDispatchDirection())->toBe('self')
        ->and(Action::make('a')->dispatchTo('App:PostList', 'x')->getDispatchDirection())->toBe('to')
        ->and(Action::make('a')->dispatchTo('App:PostList', 'x')->getDispatchToComponent())->toBe('App:PostList');
});

it('evaluates closure event and event data at read time', function (): void {
    $action = Action::make('a')->dispatch(fn (): string => 'lazy', fn (): array => ['k' => 1]);

    expect($action->getEvent())->toBe('lazy')
        ->and($action->getEventData())->toBe(['k' => 1]);
});
