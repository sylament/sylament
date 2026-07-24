<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\Enum\ActionStatus;
use Sylament\Support\Exception\Cancel;
use Sylament\Support\Exception\Halt;

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

it('calls the action closure, injecting the record and arguments', function (): void {
    $seen = null;
    $action = Action::make('delete')
        ->record('Ada')
        ->arguments(['soft' => true])
        ->action(function (mixed $record, array $arguments) use (&$seen): void {
            $seen = [$record, $arguments];
        });

    $action->call();

    expect($seen)->toBe(['Ada', ['soft' => true]]);
});

it('defaults the status to Success and flips to Failure on failure()', function (): void {
    expect(Action::make('delete')->getStatus())->toBe(ActionStatus::Success);

    $action = Action::make('delete');
    $action->failure();

    expect($action->getStatus())->toBe(ActionStatus::Failure);
});

it('throws Cancel from cancel() and Halt from halt()', function (): void {
    expect(fn (): mixed => Action::make('delete')->call())->not->toThrow(Halt::class);
    expect(fn () => Action::make('delete')->cancel())->toThrow(Cancel::class);
    expect(fn () => Action::make('delete')->halt())->toThrow(Halt::class);
});
