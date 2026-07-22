<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Component\Concerns;

use Sylament\Support\Tests\Fixtures\Component\Concerns\CanBeContainedFixture;

it('is contained by default', function (): void {
    expect(new CanBeContainedFixture()->isContained())->toBeTrue();
});

it('evaluates the contained closure once and memoizes the result', function (): void {
    // Filament's contract: the contained decision is made once per instance, so
    // repeated reads cannot tear the render or re-run an expensive closure.
    $calls = 0;

    $fixture = new CanBeContainedFixture()->contained(function () use (&$calls): bool {
        $calls++;

        return false;
    });

    expect($fixture->isContained())->toBeFalse()
        ->and($fixture->isContained())->toBeFalse()
        ->and($calls)->toBe(1);
});
