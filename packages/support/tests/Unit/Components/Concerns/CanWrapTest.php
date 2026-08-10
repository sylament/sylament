<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Tests\Fixtures\Components\Concerns\CanWrapFixture;

it('does not wrap by default', function (): void {
    $fixture = new CanWrapFixture();

    expect($fixture->canWrap())->toBeFalse()
        ->and($fixture->hasWrap())->toBeFalse();
});

it('wraps when enabled and evaluates a closure condition', function (): void {
    expect(new CanWrapFixture()->wrap()->canWrap())->toBeTrue()
        ->and(new CanWrapFixture()->wrap(false)->canWrap())->toBeFalse()
        ->and(new CanWrapFixture()->wrap(fn (): bool => true)->canWrap())->toBeTrue();
});

it('falls back to the default when the condition evaluates to null', function (): void {
    expect(new CanWrapFixture()->wrap(fn (): ?bool => null)->canWrap())->toBeFalse()
        ->and(new CanWrapFixture()->wrap(fn (): ?bool => null)->hasWrap())->toBeTrue();
});
