<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Tests\Fixtures\Components\Concerns\HasLineClampFixture;

it('has no line clamp by default', function (): void {
    $fixture = new HasLineClampFixture();

    expect($fixture->getLineClamp())->toBeNull()
        ->and($fixture->hasLineClamp())->toBeFalse();
});

it('stores a line clamp and evaluates a closure', function (): void {
    expect(new HasLineClampFixture()->lineClamp(2)->getLineClamp())->toBe(2)
        ->and(new HasLineClampFixture()->lineClamp(fn (): int => 3)->getLineClamp())->toBe(3);
});

it('reports a line clamp for any set value including one evaluating to null', function (): void {
    expect(new HasLineClampFixture()->lineClamp(fn (): ?int => null)->hasLineClamp())->toBeTrue();
});
