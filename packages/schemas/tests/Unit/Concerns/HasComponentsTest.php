<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasComponentsFixture;
use Sylament\Support\Service\Invoker;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('has no components by default', function (): void {
    expect(new HasComponentsFixture()->getComponents())->toBe([]);
});

it('sets an array of components', function (): void {
    $components = [new Component(), new Component()];
    $fixture = new HasComponentsFixture()->components($components);

    expect($fixture->getComponents())->toBe($components);
});

it('returns itself from components() for chaining', function (): void {
    $fixture = new HasComponentsFixture();

    expect($fixture->components([new Component()]))->toBe($fixture);
});

it('accepts a closure that resolves to components', function (): void {
    $components = [new Component()];
    $fixture = new HasComponentsFixture()->components(fn (): array => $components);

    expect($fixture->getComponents())->toBe($components);
});

it('evaluates the closure lazily when reading the components', function (): void {
    $first = new Component();
    $components = [$first];
    $fixture = new HasComponentsFixture()->components(function () use (&$components): array {
        return $components;
    });

    $second = new Component();
    $components = [$first, $second];

    expect($fixture->getComponents())->toBe([$first, $second]);
});
