<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Service;

use RuntimeException;
use Sylament\Support\Data\ClosureParameter;
use Sylament\Support\Service\Invoker;
use Sylament\Support\Tests\Fixtures\Service\MethodFixture;
use Sylament\Support\Tests\Fixtures\Service\ServiceFixture;
use Symfony\Component\DependencyInjection\Container;

function makeInvoker(?Container $container = null): Invoker
{
    return new Invoker($container ?? new Container());
}

it('calls a closure with no parameters', function (): void {
    $result = makeInvoker()->call(fn (): string => 'ok');

    expect($result)->toBe('ok');
});

it('resolves a parameter from the explicit parameters array by name', function (): void {
    $result = makeInvoker()->call(fn (string $name): string => "hello {$name}", ['name' => 'world']);

    expect($result)->toBe('hello world');
});

it('passes null from the parameters array when the key exists', function (): void {
    $result = makeInvoker()->call(fn (?string $name): string => $name ?? 'fallback', ['name' => null]);

    expect($result)->toBe('fallback');
});

it('resolves a parameter from the typed parameters array by class-string', function (): void {
    $service = new ServiceFixture();

    $result = makeInvoker()->call(
        fn (ServiceFixture $service): ServiceFixture => $service,
        [],
        [ServiceFixture::class => $service],
    );

    expect($result)->toBe($service);
});

it('prefers the explicit parameters array over the parameter resolver', function (): void {
    $resolver = fn (string $name, ?string $typeName): ClosureParameter => ClosureParameter::make('from-resolver');

    $result = makeInvoker()->call(
        fn (string $name): string => $name,
        ['name' => 'from-parameters'],
        [],
        $resolver,
    );

    expect($result)->toBe('from-parameters');
});

it('prefers the typed parameters array over the parameter resolver and the container', function (): void {
    $fromContainer = new ServiceFixture();
    $container = new Container();
    $container->set(ServiceFixture::class, $fromContainer);

    $fromResolver = new ServiceFixture();
    $resolver = fn (string $name, ?string $typeName): ClosureParameter => ClosureParameter::make($fromResolver);

    $fromTyped = new ServiceFixture();

    $result = makeInvoker($container)->call(
        fn (ServiceFixture $service): ServiceFixture => $service,
        [],
        [ServiceFixture::class => $fromTyped],
        $resolver,
    );

    expect($result)->toBe($fromTyped);
});

it('resolves a parameter via the resolver when it returns a ClosureParameter', function (): void {
    $resolver = fn (string $name, ?string $typeName): ?ClosureParameter => $name === 'value'
        ? ClosureParameter::make('resolved')
        : null;

    $result = makeInvoker()->call(fn (string $value): string => $value, [], [], $resolver);

    expect($result)->toBe('resolved');
});

it('passes the parameter name and normalized class type to the resolver', function (): void {
    $seen = [];
    $resolver = function (string $name, ?string $typeName) use (&$seen): ClosureParameter {
        $seen[$name] = $typeName;

        return ClosureParameter::make(null);
    };

    makeInvoker()->call(
        fn (?ServiceFixture $service, ?string $title, mixed $anything): mixed => null,
        [],
        [],
        $resolver,
    );

    expect($seen)->toBe([
        'service' => ServiceFixture::class,
        'title' => null,
        'anything' => null,
    ]);
});

it('normalizes a self type hint against the declaring class', function (): void {
    $fixture = new MethodFixture();
    $other = new MethodFixture();

    $result = makeInvoker()->call(
        $fixture->selfTyped(...),
        [],
        [MethodFixture::class => $other],
    );

    expect($result)->toBe('self:' . spl_object_id($other));
});

it('falls through to other resolution strategies when the resolver returns null', function (): void {
    $resolver = fn (string $name, ?string $typeName): ?ClosureParameter => null;

    $result = makeInvoker()->call(
        fn (string $value = 'default'): string => $value,
        [],
        [],
        $resolver,
    );

    expect($result)->toBe('default');
});

it('resolves a typed object parameter from the container', function (): void {
    $service = new ServiceFixture();
    $container = new Container();
    $container->set(ServiceFixture::class, $service);

    $result = makeInvoker($container)->call(fn (ServiceFixture $s): ServiceFixture => $s);

    expect($result)->toBe($service);
});

it('uses the default value when no other resolution applies', function (): void {
    $result = makeInvoker()->call(fn (string $name = 'fallback'): string => $name);

    expect($result)->toBe('fallback');
});

it('prefers the default value over the null fallback for a nullable parameter', function (): void {
    $result = makeInvoker()->call(fn (?string $name = 'fallback'): string => $name ?? 'was-null');

    expect($result)->toBe('fallback');
});

it('resolves an unresolvable nullable builtin parameter to null', function (): void {
    $result = makeInvoker()->call(fn (?string $title): string => $title ?? 'was-null');

    expect($result)->toBe('was-null');
});

it('resolves an unresolvable mixed parameter to null', function (): void {
    $result = makeInvoker()->call(fn (mixed $anything): string => $anything === null ? 'was-null' : 'was-set');

    expect($result)->toBe('was-null');
});

it('resolves a class-typed nullable parameter to null when the container has no such service', function (): void {
    $result = makeInvoker()->call(fn (?ServiceFixture $service): string => $service instanceof ServiceFixture ? 'was-set' : 'was-null');

    expect($result)->toBe('was-null');
});

it('throws when a parameter cannot be resolved', function (): void {
    makeInvoker()->call(fn (string $name): string => $name);
})->throws(RuntimeException::class, 'Cannot resolve parameter $name');

it('throws when a builtin-typed parameter has no default and no explicit value', function (): void {
    makeInvoker()->call(fn (int $count): int => $count);
})->throws(RuntimeException::class, 'Cannot resolve parameter $count');

it('throws with the type name when a class-typed parameter cannot be resolved', function (): void {
    makeInvoker()->call(fn (ServiceFixture $service): ServiceFixture => $service);
})->throws(RuntimeException::class, 'Cannot resolve parameter ' . ServiceFixture::class . ' $service');

it('invokes an instance method via [object, method] callable', function (): void {
    $fixture = new MethodFixture();

    $result = makeInvoker()->call($fixture->instanceMethod(...), ['value' => 'x']);

    expect($result)->toBe('instance:x');
});

it('invokes a static method via [class, method] callable', function (): void {
    $result = makeInvoker()->call(MethodFixture::staticMethod(...), ['value' => 'y']);

    expect($result)->toBe('static:y');
});

it('invokes an array callable of the form [object, method]', function (): void {
    $callable = new MethodFixture()->asInstanceArrayCallable();

    $result = makeInvoker()->call($callable, ['value' => 'a']);

    expect($result)->toBe('instance:a');
});

it('invokes an array callable of the form [class-string, staticMethod]', function (): void {
    $callable = MethodFixture::asStaticArrayCallable();

    $result = makeInvoker()->call($callable, ['value' => 'b']);

    expect($result)->toBe('static:b');
});

it('invokes a global function passed as a string name', function (): void {
    $result = makeInvoker()->call('strlen', ['string' => 'hello']);

    expect($result)->toBe(5);
});

it('resolves parameters in declaration order regardless of the parameters array order', function (): void {
    $result = makeInvoker()->call(
        fn (string $first, string $second): string => "{$first}-{$second}",
        ['second' => 'b', 'first' => 'a'],
    );

    expect($result)->toBe('a-b');
});

it('combines all resolution strategies for a multi-parameter callable', function (): void {
    $service = new ServiceFixture();
    $container = new Container();
    $container->set(ServiceFixture::class, $service);

    $typed = new MethodFixture();

    $resolver = fn (string $name, ?string $typeName): ?ClosureParameter => $name === 'fromResolver'
        ? ClosureParameter::make('R')
        : null;

    $result = makeInvoker($container)->call(
        fn (string $explicit, MethodFixture $fromTyped, string $fromResolver, ServiceFixture $service, string $fromDefault = 'D'): string => "{$explicit}|" . spl_object_id($fromTyped) . "|{$fromResolver}|" . spl_object_id($service) . "|{$fromDefault}",
        ['explicit' => 'E'],
        [MethodFixture::class => $typed],
        $resolver,
    );

    expect($result)->toBe('E|' . spl_object_id($typed) . '|R|' . spl_object_id($service) . '|D');
});
