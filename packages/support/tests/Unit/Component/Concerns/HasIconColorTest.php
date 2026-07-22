<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Component\Concerns;

use Sylament\Support\Tests\Fixtures\Component\Concerns\HasIconColorFixture;

it('has no icon color by default', function (): void {
    $fixture = new HasIconColorFixture();

    expect($fixture->getIconColor())->toBeNull()
        ->and($fixture->hasIconColor())->toBeFalse();
});

it('stores a palette name or a custom color array', function (): void {
    expect(new HasIconColorFixture()->iconColor('danger')->getIconColor())->toBe('danger')
        ->and(new HasIconColorFixture()->iconColor(['500' => '#123456'])->getIconColor())->toBe(['500' => '#123456']);
});

it('evaluates an icon color closure', function (): void {
    expect(new HasIconColorFixture()->iconColor(fn (): string => 'success')->getIconColor())
        ->toBe('success');
});

it('reports an icon color for any set value including closures', function (): void {
    expect(new HasIconColorFixture()->iconColor('danger')->hasIconColor())->toBeTrue()
        ->and(new HasIconColorFixture()->iconColor(fn (): ?string => null)->hasIconColor())->toBeTrue();
});
