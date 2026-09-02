<?php

declare(strict_types=1);

use Sylament\Panels\Pages\SimplePage;
use Sylament\Support\Enums\Width;

final class NarrowSimplePage extends SimplePage
{
    protected Width|string|null $maxWidth = Width::Medium;
}

final class RawWidthSimplePage extends SimplePage
{
    protected Width|string|null $maxWidth = 'max-w-[42rem]';
}

it('names no card width of its own by default', function (): void {
    expect(new class extends SimplePage {}->getMaxWidth())->toBeNull();
});

it('answers the width a simple page names, enum or raw class alike', function (): void {
    expect(new NarrowSimplePage()->getMaxWidth())->toBe(Width::Medium)
        ->and(new RawWidthSimplePage()->getMaxWidth())->toBe('max-w-[42rem]');
});
