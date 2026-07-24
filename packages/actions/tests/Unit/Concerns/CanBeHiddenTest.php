<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\CanBeHiddenFixture;

it('is visible by default', function (): void {
    expect(new CanBeHiddenFixture()->isHidden())->toBeFalse()
        ->and(new CanBeHiddenFixture()->isVisible())->toBeTrue();
});

it('hides when hidden() is set', function (): void {
    expect(new CanBeHiddenFixture()->hidden()->isHidden())->toBeTrue()
        ->and(new CanBeHiddenFixture()->hidden()->isVisible())->toBeFalse();
});

it('hides when visible() is false', function (): void {
    expect(new CanBeHiddenFixture()->visible(false)->isHidden())->toBeTrue();
});

it('evaluates the hidden and visible closures', function (): void {
    expect(new CanBeHiddenFixture()->hidden(fn (): bool => true)->isHidden())->toBeTrue()
        ->and(new CanBeHiddenFixture()->visible(fn (): bool => false)->isHidden())->toBeTrue();
});
