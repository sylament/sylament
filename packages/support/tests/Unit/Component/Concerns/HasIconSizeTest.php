<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Component\Concerns;

use Sylament\Support\Enum\IconSize;
use Sylament\Support\Tests\Fixtures\Component\Concerns\HasIconSizeFixture;

it('has no icon size by default', function (): void {
    $fixture = new HasIconSizeFixture();

    expect($fixture->getIconSize())->toBeNull()
        ->and($fixture->hasIconSize())->toBeFalse();
});

it('stores an IconSize case', function (): void {
    expect(new HasIconSizeFixture()->iconSize(IconSize::Small)->getIconSize())
        ->toBe(IconSize::Small);
});

it('returns a size string raw for the caller to normalize', function (): void {
    // Filament's HasIconSize returns strings unnormalized; consumers run
    // IconSize::tryFrom, letting custom size vocabularies pass through.
    expect(new HasIconSizeFixture()->iconSize('sm')->getIconSize())->toBe('sm');
});

it('evaluates an icon size closure', function (): void {
    expect(new HasIconSizeFixture()->iconSize(fn (): IconSize => IconSize::Large)->getIconSize())
        ->toBe(IconSize::Large);
});

it('reports an icon size for any set value', function (): void {
    expect(new HasIconSizeFixture()->iconSize(IconSize::Small)->hasIconSize())->toBeTrue();
});
