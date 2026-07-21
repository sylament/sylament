<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Component\Concerns;

use Sylament\Support\Service\Invoker;
use Sylament\Support\Tests\Fixtures\Component\Concerns\CanGrowFixture;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('defers to the given default while unset', function (): void {
    expect(new CanGrowFixture()->canGrow())->toBeTrue()
        ->and(new CanGrowFixture()->canGrow(default: false))->toBeFalse();
});

it('grows regardless of the default once grow() is set', function (): void {
    expect(new CanGrowFixture()->grow()->canGrow(default: false))->toBeTrue();
});

it('does not grow after grow(false)', function (): void {
    expect(new CanGrowFixture()->grow(false)->canGrow())->toBeFalse();
});

it('evaluates the grow condition closure', function (): void {
    expect(new CanGrowFixture()->grow(fn (): bool => true)->canGrow(default: false))->toBeTrue()
        ->and(new CanGrowFixture()->grow(fn (): bool => false)->canGrow())->toBeFalse();
});

it('returns control to the default with grow(null)', function (): void {
    expect(new CanGrowFixture()->grow(false)->grow(null)->canGrow())->toBeTrue();
});
