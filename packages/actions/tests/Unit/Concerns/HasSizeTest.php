<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasSizeFixture;
use Sylament\Support\Enum\Size;

it('has no size by default', function (): void {
    expect(new HasSizeFixture()->getSize())->toBeNull();
});

it('stores a Size case', function (): void {
    expect(new HasSizeFixture()->size(Size::Small)->getSize())->toBe(Size::Small);
});

it('passes a size string through as-is', function (): void {
    expect(new HasSizeFixture()->size('sm')->getSize())->toBe('sm');
});

it('falls back to the default size', function (): void {
    expect(new HasSizeFixture()->defaultSize(Size::Large)->getSize())->toBe(Size::Large);
});

it('lets an explicit size win over the default size', function (): void {
    expect(new HasSizeFixture()->size(Size::Small)->defaultSize(Size::Large)->getSize())
        ->toBe(Size::Small);
});

it('evaluates a size closure', function (): void {
    expect(new HasSizeFixture()->size(fn (): Size => Size::ExtraLarge)->getSize())
        ->toBe(Size::ExtraLarge);
});

it('reports whether a size is set', function (): void {
    expect(new HasSizeFixture()->hasSize())->toBeFalse()
        ->and(new HasSizeFixture()->size(Size::Small)->hasSize())->toBeTrue()
        ->and(new HasSizeFixture()->defaultSize(Size::Large)->hasSize())->toBeTrue();
});
