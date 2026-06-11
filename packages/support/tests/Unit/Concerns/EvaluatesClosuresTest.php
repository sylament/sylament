<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Concerns;

use Closure;
use Sylament\Support\Service\Invoker;
use Sylament\Support\Tests\Fixtures\Concerns\EvaluatesClosuresFixture;
use Sylament\Support\Tests\Fixtures\Concerns\EvaluatesClosuresWithResolverFixture;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('returns a non-callable value as-is without touching the Invoker', function (): void {
    Invoker::unsetInstance();

    expect(new EvaluatesClosuresFixture()->evaluate('plain string'))->toBe('plain string')
        ->and(new EvaluatesClosuresFixture()->evaluate(42))->toBe(42)
        ->and(new EvaluatesClosuresFixture()->evaluate(null))->toBeNull();
});

it('invokes a callable value through the Invoker static instance', function (): void {
    $result = new EvaluatesClosuresFixture()->evaluate(fn (): string => 'invoked');

    expect($result)->toBe('invoked');
});

it('forwards explicit parameters to the Invoker', function (): void {
    $result = new EvaluatesClosuresFixture()->evaluate(
        fn (string $name): string => "hi {$name}",
        ['name' => 'world'],
    );

    expect($result)->toBe('hi world');
});

it('uses the subclass resolver to resolve named closure parameters', function (): void {
    $result = new EvaluatesClosuresWithResolverFixture()->evaluate(fn (string $subject): string => $subject);

    expect($result)->toBe('from-subclass');
});

it('falls back to the default resolver which returns null', function (): void {
    $result = new EvaluatesClosuresFixture()->evaluate(fn (string $missing = 'default'): string => $missing);

    expect($result)->toBe('default');
});

it('returns a non-callable value from evaluatable() as-is', function (): void {
    expect(new EvaluatesClosuresFixture()->evaluatable('plain string'))->toBe('plain string')
        ->and(new EvaluatesClosuresFixture()->evaluatable(null))->toBeNull();
});

it('wraps a callable into a Closure via evaluatable()', function (): void {
    $fixture = new EvaluatesClosuresFixture();
    $closure = $fixture->evaluatable(fn (): string => 'wrapped');

    expect($closure)->toBeInstanceOf(Closure::class)
        ->and($closure())->toBe('wrapped');
});
