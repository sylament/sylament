<?php

declare(strict_types=1);

use Sylament\Panels\Pages\SimplePage;

final class TopbarlessSimplePage extends SimplePage
{
    protected bool $hasTopbar = false;
}

it('keeps the topbar on a simple page by default', function (): void {
    expect(new class extends SimplePage {}->hasTopbar())->toBeTrue();
});

it('lets a simple page drop the topbar', function (): void {
    expect(new TopbarlessSimplePage()->hasTopbar())->toBeFalse();
});
