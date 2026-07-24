<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\CanRequireConfirmationFixture;

it('does not require confirmation by default', function (): void {
    expect(new CanRequireConfirmationFixture()->isConfirmationRequired())->toBeFalse();
});

it('requires confirmation once requested', function (): void {
    expect(new CanRequireConfirmationFixture()->requiresConfirmation()->isConfirmationRequired())->toBeTrue();
});

it('evaluates a confirmation closure', function (): void {
    expect(new CanRequireConfirmationFixture()->requiresConfirmation(fn (): bool => true)->isConfirmationRequired())->toBeTrue();
});
