<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Concerns;

use Sylament\Support\Evaluation\Invoker;
use RuntimeException;
use Sylament\Support\Tests\Fixtures\Concerns\EvaluatesClosuresFixture;
use Sylament\Support\Tests\Fixtures\Concerns\EvaluatesClosuresWithIdentifierFixture;
use Sylament\Support\Tests\Fixtures\Concerns\EvaluatesClosuresWithResolverFixture;
use Sylament\Support\Tests\Fixtures\Evaluation\ServiceFixture;

it('returns a non-Closure value as-is without touching the Invoker', function (): void {
    Invoker::unsetInstance();

    expect(new EvaluatesClosuresFixture()->evaluate('plain string'))->toBe('plain string')
        ->and(new EvaluatesClosuresFixture()->evaluate(42))->toBe(42)
        ->and(new EvaluatesClosuresFixture()->evaluate(null))->toBeNull();
});

it('keeps a callable-string literal instead of invoking it', function (): void {
    // Regression: only Closure instances are evaluated. A literal string that
    // happens to name a function ('strtoupper', 'date', …) is a value, not a
    // callback — Filament's `instanceof Closure` check, not is_callable().
    Invoker::unsetInstance();

    expect(new EvaluatesClosuresFixture()->evaluate('strtoupper'))->toBe('strtoupper');
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

it('forwards typed parameters to the Invoker', function (): void {
    $service = new ServiceFixture();

    $result = new EvaluatesClosuresFixture()->evaluate(
        fn (ServiceFixture $service): ServiceFixture => $service,
        [],
        [ServiceFixture::class => $service],
    );

    expect($result)->toBe($service);
});

it('resolves the evaluation identifier to the instance for an untyped parameter', function (): void {
    $fixture = new EvaluatesClosuresWithIdentifierFixture();

    $result = $fixture->evaluate(fn ($fixture) => $fixture);

    expect($result)->toBe($fixture);
});

it('resolves a self-typed parameter to the instance regardless of its name', function (): void {
    $fixture = new EvaluatesClosuresWithIdentifierFixture();

    $result = $fixture->evaluate(
        fn (EvaluatesClosuresWithIdentifierFixture $instance): EvaluatesClosuresWithIdentifierFixture => $instance,
    );

    expect($result)->toBe($fixture);
});

it('uses the subclass by-type resolver for a class-typed parameter', function (): void {
    $fixture = new EvaluatesClosuresWithIdentifierFixture();

    $result = $fixture->evaluate(fn (ServiceFixture $service): ServiceFixture => $service);

    expect($result)->toBe($fixture->typedService);
});

it('prefers the by-name resolver over the by-type resolver', function (): void {
    $fixture = new EvaluatesClosuresWithIdentifierFixture();

    $result = $fixture->evaluate(fn (ServiceFixture $named): ServiceFixture => $named);

    expect($result)->toBe($fixture->namedService);
});

it('prefers the by-type resolver over the evaluation identifier', function (): void {
    $fixture = new EvaluatesClosuresWithIdentifierFixture();

    $result = $fixture->evaluate(fn (ServiceFixture $fixture): ServiceFixture => $fixture);

    expect($result)->toBe($fixture->typedService);
});

it('prefers explicit parameters over the evaluation identifier', function (): void {
    $fixture = new EvaluatesClosuresWithIdentifierFixture();

    $result = $fixture->evaluate(fn (string $fixture): string => $fixture, ['fixture' => 'explicit']);

    expect($result)->toBe('explicit');
});

it('treats a resolver ClosureParameter wrapping null as resolved', function (): void {
    $fixture = new EvaluatesClosuresWithIdentifierFixture();

    $result = $fixture->evaluate(fn (?string $resolvedNull = 'default'): string => $resolvedNull ?? 'was-null');

    expect($result)->toBe('was-null');
});

it('resolves an unresolvable untyped parameter to null when no identifier matches', function (): void {
    $result = new EvaluatesClosuresFixture()->evaluate(fn (mixed $anything): string => $anything === null ? 'was-null' : 'was-set');

    expect($result)->toBe('was-null');
});

it('names the evaluating class in the unresolvable error', function (): void {
    new EvaluatesClosuresFixture()->evaluate(fn (string $missing): string => $missing);
})->throws(RuntimeException::class, 'An attempt was made to evaluate a closure for [' . EvaluatesClosuresFixture::class . '], but [$missing] was unresolvable.');
