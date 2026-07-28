<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\CanCloseFixture;

it('does not close by default', function (): void {
    expect(new CanCloseFixture()->shouldClose())->toBeFalse()
        ->and(new CanCloseFixture()->hasClose())->toBeFalse();
});

it('closes once requested', function (): void {
    expect(new CanCloseFixture()->close()->shouldClose())->toBeTrue();
});

it('evaluates a close closure', function (): void {
    expect(new CanCloseFixture()->close(fn (): bool => true)->shouldClose())->toBeTrue()
        ->and(new CanCloseFixture()->close(fn (): bool => false)->shouldClose())->toBeFalse();
});

it('reports close presence from the raw value, not the evaluated result', function (): void {
    // Filament-exact raw semantics: a closure counts even when it evaluates to
    // false, while an explicit literal false does not (`!== false`).
    expect(new CanCloseFixture()->close(fn (): bool => false)->hasClose())->toBeTrue()
        ->and(new CanCloseFixture()->close(false)->hasClose())->toBeFalse();
});
