<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Exception;

use Sylament\Support\Exceptions\Halt;
use Throwable;

it('is a throwable control-flow signal', function (): void {
    expect(new Halt())->toBeInstanceOf(Throwable::class);
});

it('is catchable as Halt', function (): void {
    $caught = false;

    try {
        throw new Halt();
    } catch (Halt) {
        $caught = true;
    }

    expect($caught)->toBeTrue();
});

it('does not roll back the database transaction by default', function (): void {
    expect(new Halt()->shouldRollbackDatabaseTransaction())->toBeFalse();
});

it('carries the rollback flag fluently', function (): void {
    $exception = new Halt();

    expect($exception->rollBackDatabaseTransaction())->toBe($exception)
        ->and($exception->shouldRollbackDatabaseTransaction())->toBeTrue()
        ->and($exception->rollBackDatabaseTransaction(false)->shouldRollbackDatabaseTransaction())->toBeFalse();
});
