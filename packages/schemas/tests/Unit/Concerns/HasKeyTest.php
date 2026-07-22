<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('has no key by default', function (): void {
    $schema = Schema::make();

    // Filament composes the absolute key by imploding the (empty) chain, so a
    // keyless root schema resolves to '' absolutely and null relatively.
    expect($schema->getKey(isAbsolute: false))->toBeNull()
        ->and($schema->getKey())->toBe('');
});

it('returns the set key', function (): void {
    $schema = Schema::make()->key('form');

    expect($schema->getKey(isAbsolute: false))->toBe('form')
        ->and($schema->getKey())->toBe('form');
});

it('strips characters that could break out of an attribute or JS string', function (): void {
    expect(Schema::make()->key("fo<r>m\"'`\x00")->getKey(isAbsolute: false))->toBe('form');
});

it('passes the schema to a key closure', function (): void {
    $schema = Schema::make();

    $received = null;
    $schema->key(function (Schema $schema) use (&$received): string {
        $received = $schema;

        return 'computed-key';
    });

    expect($schema->getKey(isAbsolute: false))->toBe('computed-key')
        ->and($received)->toBe($schema);
});

it('prefixes the absolute key with the parent component inheritance key', function (): void {
    $slot = Schema::make()->key('slot');
    $outer = new Component()->key('outer')->components($slot);
    Schema::make(new HasSchemasFixture())->components([$outer])->getComponents();

    expect($outer->getChildSchema()?->getKey())->toBe('outer.slot');
});

it('falls back to the parent component inheritance key', function (): void {
    $outer = new Component()->key('outer')->components(Schema::make());
    Schema::make(new HasSchemasFixture())->components([$outer])->getComponents();

    expect($outer->getChildSchema()?->getInheritanceKey())->toBe('outer');
});

it('caches the absolute key after the first resolution', function (): void {
    $calls = 0;
    $schema = Schema::make()->key(function () use (&$calls): string {
        $calls++;

        return 'form';
    });

    expect($schema->getKey())->toBe('form')
        ->and($schema->getKey())->toBe('form')
        ->and($calls)->toBe(1);
});
