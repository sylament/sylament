<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('has no key by default', function (): void {
    // A blank key resolves to null before the container is consulted, so an
    // unattached component is safe to ask for its absolute key.
    $component = new Component();

    expect($component->getKey())->toBeNull()
        ->and($component->getKey(isAbsolute: false))->toBeNull();
});

it('returns the set key', function (): void {
    expect(new Component()->key('name')->getKey(isAbsolute: false))->toBe('name');
});

it('falls back to the state path when no key is set', function (): void {
    // With no explicit key, the key mirrors the state path so id/for wiring resolves.
    expect(new Component()->statePath('foo')->getKey(isAbsolute: false))->toBe('foo');
});

it('uses the state path for a non-inheritable inheritance key', function (): void {
    expect(new Component()->statePath('foo')->key(null, isInheritable: false)->getInheritanceKey(isAbsolute: false))->toBe('foo');
});

it('resets the key with null', function (): void {
    expect(new Component()->key('name')->key(null)->getKey(isAbsolute: false))->toBeNull();
});

it('strips characters that could break out of an attribute or JS string', function (string $raw, string $sanitized): void {
    expect(new Component()->key($raw)->getKey(isAbsolute: false))->toBe($sanitized);
})->with([
    'angle brackets' => ['pro<file>', 'profile'],
    'double quote' => ['pro"file', 'profile'],
    'single quote' => ["pro'file", 'profile'],
    'backtick' => ['pro`file', 'profile'],
    'control characters' => ["pro\x00\x1ffile\x7f", 'profile'],
]);

it('passes the component to a key closure', function (): void {
    $component = new Component();

    $received = null;
    $component->key(function (Component $component) use (&$received): string {
        $received = $component;

        return 'computed-key';
    });

    expect($component->getKey(isAbsolute: false))->toBe('computed-key')
        ->and($received)->toBe($component);
});

it('matches the relative key when the container chain has no keys', function (): void {
    $component = new Component()->key('name');
    Schema::make()->components([$component])->getComponents();

    expect($component->getKey())->toBe('name');
});

it('prefixes the absolute key with the schema key', function (): void {
    $component = new Component()->key('name');
    Schema::make()->key('form')->components([$component])->getComponents();

    expect($component->getKey())->toBe('form.name');
});

it('chains inheritable keys through nested components', function (): void {
    $inner = new Component()->key('inner');
    $outer = new Component()->key('outer')->components([$inner]);
    Schema::make(new HasSchemasFixture())->components([$outer])->getComponents();
    $outer->getChildSchema()?->getComponents();

    expect($inner->getKey())->toBe('outer.inner');
});

it('does not prefix children with a non-inheritable key', function (): void {
    $inner = new Component()->key('inner');
    $outer = new Component()->key('outer', isInheritable: false)->components([$inner]);
    Schema::make(new HasSchemasFixture())->components([$outer])->getComponents();
    $outer->getChildSchema()?->getComponents();

    expect($inner->getKey())->toBe('inner')
        ->and($outer->getKey())->toBe('outer');
});

it('exposes the inheritance key only when inheritable', function (): void {
    expect(new Component()->key('name')->getInheritanceKey(isAbsolute: false))->toBe('name')
        ->and(new Component()->key('name', isInheritable: false)->getInheritanceKey(isAbsolute: false))->toBeNull();
});

it('caches the absolute key after the first resolution', function (): void {
    $calls = 0;
    $component = new Component()->key(function () use (&$calls): string {
        $calls++;

        return 'name';
    });
    Schema::make()->components([$component])->getComponents();

    expect($component->getKey())->toBe('name')
        ->and($component->getKey())->toBe('name')
        ->and($calls)->toBe(1);
});

it('does not cache the relative key', function (): void {
    $calls = 0;
    $component = new Component()->key(function () use (&$calls): string {
        $calls++;

        return 'name';
    });

    $component->getKey(isAbsolute: false);
    $component->getKey(isAbsolute: false);

    expect($calls)->toBe(2);
});

it('composes the live-component key from the host id and the absolute key', function (): void {
    $host = new HasSchemasFixture();
    $component = new Component()->key('name');
    Schema::make($host)->key('form')->components([$component])->getComponents();

    expect($component->getLiveComponentKey())->toBe($host->getSchemasHostId() . '.form.name');
});

it('has no live-component key without a live host', function (): void {
    $component = new Component()->key('name');
    Schema::make()->components([$component])->getComponents();

    expect($component->getLiveComponentKey())->toBeNull();
});

it('has no live-component key without a key', function (): void {
    $component = new Component();
    Schema::make(new HasSchemasFixture())->components([$component])->getComponents();

    expect($component->getLiveComponentKey())->toBeNull();
});
