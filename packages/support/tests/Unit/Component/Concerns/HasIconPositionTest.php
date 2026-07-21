<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Component\Concerns;

use Sylament\Support\Enum\IconPosition;
use Sylament\Support\Service\Invoker;
use Sylament\Support\Tests\Fixtures\Component\Concerns\HasIconPositionFixture;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('defaults to the Before position without reporting one set', function (): void {
    $fixture = new HasIconPositionFixture();

    expect($fixture->getIconPosition())->toBe(IconPosition::Before)
        ->and($fixture->hasIconPosition())->toBeFalse();
});

it('stores an IconPosition case', function (): void {
    expect(new HasIconPositionFixture()->iconPosition(IconPosition::After)->getIconPosition())
        ->toBe(IconPosition::After);
});

it('normalizes a position string to its case', function (): void {
    expect(new HasIconPositionFixture()->iconPosition('after')->getIconPosition())
        ->toBe(IconPosition::After);
});

it('falls back to Before for an unknown position string', function (): void {
    expect(new HasIconPositionFixture()->iconPosition('sideways')->getIconPosition())
        ->toBe(IconPosition::Before);
});

it('falls back to Before for a blank position string', function (): void {
    expect(new HasIconPositionFixture()->iconPosition('  ')->getIconPosition())
        ->toBe(IconPosition::Before);
});

it('evaluates an icon position closure', function (): void {
    expect(new HasIconPositionFixture()->iconPosition(fn (): IconPosition => IconPosition::After)->getIconPosition())
        ->toBe(IconPosition::After);
});

it('reports an icon position for any set value', function (): void {
    expect(new HasIconPositionFixture()->iconPosition('sideways')->hasIconPosition())->toBeTrue();
});
