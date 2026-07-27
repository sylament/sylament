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
