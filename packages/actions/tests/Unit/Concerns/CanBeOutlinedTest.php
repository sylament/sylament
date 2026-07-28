<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\CanBeOutlinedFixture;

it('is not outlined by default', function (): void {
    $fixture = new CanBeOutlinedFixture();

    expect($fixture->isOutlined())->toBeFalse()
        ->and($fixture->hasOutlined())->toBeFalse();
});

it('is outlined once outlined() is called', function (): void {
    expect(new CanBeOutlinedFixture()->outlined()->isOutlined())->toBeTrue();
});

it('evaluates an outlined closure', function (): void {
    expect(new CanBeOutlinedFixture()->outlined(fn (): bool => false)->isOutlined())->toBeFalse();
});

it('reports the raw outlined value through hasOutlined()', function (): void {
    $fixture = new CanBeOutlinedFixture()->outlined(fn (): ?bool => null);

    expect($fixture->isOutlined())->toBeFalse()
        ->and($fixture->hasOutlined())->toBeTrue();
});
