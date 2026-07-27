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
