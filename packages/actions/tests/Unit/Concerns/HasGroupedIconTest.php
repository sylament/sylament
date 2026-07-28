<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasGroupedIconFixture;

it('has no grouped icon by default', function (): void {
    $fixture = new HasGroupedIconFixture();

    expect($fixture->getGroupedIcon())->toBeNull()
        ->and($fixture->hasGroupedIcon())->toBeFalse();
});

it('round-trips the grouped icon', function (): void {
    $fixture = new HasGroupedIconFixture()->groupedIcon('heroicons:pencil');

    expect($fixture->getGroupedIcon())->toBe('heroicons:pencil')
        ->and($fixture->hasGroupedIcon())->toBeTrue();
});

it('evaluates a grouped icon closure', function (): void {
    expect(new HasGroupedIconFixture()->groupedIcon(fn (): string => 'heroicons:pencil')->getGroupedIcon())
        ->toBe('heroicons:pencil');
});

it('reports the raw grouped icon value through hasGroupedIcon()', function (): void {
    $fixture = new HasGroupedIconFixture()->groupedIcon(fn (): ?string => null);

    expect($fixture->getGroupedIcon())->toBeNull()
        ->and($fixture->hasGroupedIcon())->toBeTrue();
});
