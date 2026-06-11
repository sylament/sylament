<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\DependencyInjection;

use RuntimeException;
use Sylament\Support\DependencyInjection\StaticInstanceRegistry;
use Sylament\Support\Tests\Fixtures\DependencyInjection\AnotherStaticInstanceFixture;
use Sylament\Support\Tests\Fixtures\DependencyInjection\StaticInstanceFixture;

beforeEach(function (): void {
    StaticInstanceFixture::unsetInstance();
    AnotherStaticInstanceFixture::unsetInstance();
});

it('sets the static instance for every tagged service', function (): void {
    $first = new StaticInstanceFixture();
    $second = new AnotherStaticInstanceFixture();

    new StaticInstanceRegistry([$first, $second])->bootStaticInstances();

    expect(StaticInstanceFixture::getInstance())->toBe($first);
    expect(AnotherStaticInstanceFixture::getInstance())->toBe($second);
});

it('works with empty service iterable', function (): void {
    new StaticInstanceRegistry([])->bootStaticInstances();

    expect(fn (): StaticInstanceFixture => StaticInstanceFixture::getInstance())->toThrow(RuntimeException::class);
});

it('clears the static instance for every tagged service via unsetStaticInstances', function (): void {
    $first = new StaticInstanceFixture();
    $second = new AnotherStaticInstanceFixture();
    $registry = new StaticInstanceRegistry([$first, $second]);

    $registry->bootStaticInstances();
    $registry->unsetStaticInstances();

    expect(fn (): StaticInstanceFixture => StaticInstanceFixture::getInstance())->toThrow(RuntimeException::class)
        ->and(fn (): AnotherStaticInstanceFixture => AnotherStaticInstanceFixture::getInstance())->toThrow(RuntimeException::class);
});
