<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\DependencyInjection;

use RuntimeException;
use Sylament\Support\Tests\Fixtures\DependencyInjection\AnotherStaticInstanceFixture;
use Sylament\Support\Tests\Fixtures\DependencyInjection\StaticInstanceFixture;

beforeEach(function (): void {
    StaticInstanceFixture::unsetInstance();
    AnotherStaticInstanceFixture::unsetInstance();
});

it('throws when no instance has been set', function (): void {
    StaticInstanceFixture::getInstance();
})->throws(RuntimeException::class, 'has no instance set');

it('returns the instance previously set via setInstance', function (): void {
    $fixture = new StaticInstanceFixture();

    StaticInstanceFixture::setInstance($fixture);

    expect(StaticInstanceFixture::getInstance())->toBe($fixture);
});

it('overwrites a previously set instance', function (): void {
    $first = new StaticInstanceFixture();
    $second = new StaticInstanceFixture();

    StaticInstanceFixture::setInstance($first);
    StaticInstanceFixture::setInstance($second);

    expect(StaticInstanceFixture::getInstance())->toBe($second);
});

it('throws when the stored instance is not of the same class', function (): void {
    StaticInstanceFixture::setInstance(new AnotherStaticInstanceFixture());

    StaticInstanceFixture::getInstance();
})->throws(RuntimeException::class);

it('keeps each using-class instance isolated', function (): void {
    $fixture = new StaticInstanceFixture();

    StaticInstanceFixture::setInstance($fixture);

    expect(StaticInstanceFixture::getInstance())->toBe($fixture)
        ->and(fn (): AnotherStaticInstanceFixture => AnotherStaticInstanceFixture::getInstance())
        ->toThrow(RuntimeException::class);
});

it('clears the stored instance via unsetInstance', function (): void {
    StaticInstanceFixture::setInstance(new StaticInstanceFixture());

    StaticInstanceFixture::unsetInstance();

    expect(fn (): StaticInstanceFixture => StaticInstanceFixture::getInstance())
        ->toThrow(RuntimeException::class);
});
