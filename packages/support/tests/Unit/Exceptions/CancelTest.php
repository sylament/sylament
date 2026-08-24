<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Exception;

use Sylament\Support\Exceptions\Cancel;
use Throwable;

it('is a throwable control-flow signal', function (): void {
    expect(new Cancel())->toBeInstanceOf(Throwable::class);
});

it('is catchable as Cancel', function (): void {
    $caught = false;

    try {
        throw new Cancel();
    } catch (Cancel) {
        $caught = true;
    }

    expect($caught)->toBeTrue();
});

it('does not roll back the database transaction by default', function (): void {
    expect(new Cancel()->shouldRollbackDatabaseTransaction())->toBeFalse();
});

it('carries the rollback flag fluently', function (): void {
    $exception = new Cancel();

    expect($exception->rollBackDatabaseTransaction())->toBe($exception)
        ->and($exception->shouldRollbackDatabaseTransaction())->toBeTrue()
        ->and($exception->rollBackDatabaseTransaction(false)->shouldRollbackDatabaseTransaction())->toBeFalse();
});
