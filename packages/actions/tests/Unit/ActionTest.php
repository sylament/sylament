<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use LogicException;
use Sylament\Actions\Action;

it('exposes the name passed to make()', function (): void {
    expect(Action::make('delete')->getName())->toBe('delete');
});

it('throws when the name is blank', function (): void {
    expect(fn (): string => Action::make()->getName())->toThrow(LogicException::class);
});

it('round-trips the action closure', function (): void {
    $closure = fn (): string => 'ran';

    expect(Action::make('delete')->action($closure)->getAction())->toBe($closure);
});

it('does not require confirmation by default', function (): void {
    expect(Action::make('delete')->isConfirmationRequired())->toBeFalse();
});

it('requires confirmation once requested', function (): void {
    expect(Action::make('delete')->requiresConfirmation()->isConfirmationRequired())->toBeTrue();
});
