<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\CanBeDisabledFixture;

it('is enabled by default', function (): void {
    expect(new CanBeDisabledFixture()->isDisabled())->toBeFalse()
        ->and(new CanBeDisabledFixture()->isEnabled())->toBeTrue();
});

it('disables when disabled() is set', function (): void {
    expect(new CanBeDisabledFixture()->disabled()->isDisabled())->toBeTrue();
});

it('disables when the component is hidden', function (): void {
    expect(new CanBeDisabledFixture()->hidden()->isDisabled())->toBeTrue();
});

it('evaluates a disabled closure', function (): void {
    expect(new CanBeDisabledFixture()->disabled(fn (): bool => true)->isDisabled())->toBeTrue();
});

it('reports whether a disabled condition is set', function (): void {
    expect(new CanBeDisabledFixture()->hasDisabled())->toBeFalse()
        ->and(new CanBeDisabledFixture()->disabled()->hasDisabled())->toBeTrue();
});
