<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use RuntimeException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\DiscoveryThrowingSchemaHostFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Schemas\Tests\Fixtures\Concerns\SuffixedSchemaHostFixture;

it('declares a single "schema" by default', function (): void {
    expect(new HasSchemasFixture()->schemas())->toBe(['schema']);
});

it('builds the schema from the matching method', function (): void {
    $schema = new HasSchemasFixture()->getSchema();

    expect($schema)->toBeInstanceOf(Schema::class)
        ->and($schema->getComponents())->toHaveCount(1);
});

it('defaults to the "schema" name', function (): void {
    $fixture = new HasSchemasFixture();

    expect($fixture->getSchema())->toBe($fixture->getSchema('schema'));
});

it('caches the schema between calls', function (): void {
    $fixture = new HasSchemasFixture();

    expect($fixture->getSchema())->toBe($fixture->getSchema());
});

it('binds the built schema to the component', function (): void {
    $fixture = new HasSchemasFixture();

    expect($fixture->getSchema()->getLiveComponent())->toBe($fixture);
});

it('throws when the schema method does not exist', function (): void {
    expect(fn (): Schema => new HasSchemasFixture()->getSchema('missing'))
        ->toThrow(RuntimeException::class, 'Schema missing does not exist on component');
});

it('throws when the method does not return a Schema', function (): void {
    expect(fn (): Schema => new HasSchemasFixture()->getSchema('notASchema'))
        ->toThrow(RuntimeException::class, 'Schema must be an instance of ' . Schema::class);
});

it('falls back to the "<name>Schema" method', function (): void {
    $schema = new SuffixedSchemaHostFixture()->getSchema('article');

    expect($schema)->toBeInstanceOf(Schema::class)
        ->and($schema->getStatePath())->toBe('article');
});

it('prefers the bare method over the suffixed one', function (): void {
    expect(new SuffixedSchemaHostFixture()->getSchema('report')->getStatePath())->toBe('bare');
});

it('builds a suffix-resolved declared schema in getCachedSchemas()', function (): void {
    $cachedSchemas = new SuffixedSchemaHostFixture()->getCachedSchemas();

    expect($cachedSchemas)->toHaveKey('article')
        ->and($cachedSchemas['article'])->toBeInstanceOf(Schema::class);
});

it('makes a fresh schema bound to the component', function (): void {
    $fixture = new HasSchemasFixture();
    $schema = $fixture->makeSchema();

    expect($schema)->toBeInstanceOf(Schema::class)
        ->and($schema->getLiveComponent())->toBe($fixture)
        ->and($schema)->not->toBe($fixture->makeSchema());
});

it('builds every declared schema in getCachedSchemas()', function (): void {
    $cachedSchemas = new HasSchemasFixture()->getCachedSchemas();

    expect($cachedSchemas)->toHaveKey('schema')
        ->and($cachedSchemas['schema'])->toBeInstanceOf(Schema::class);
});

it('reuses already cached schemas in getCachedSchemas()', function (): void {
    $fixture = new HasSchemasFixture();
    $schema = $fixture->getSchema();

    expect($fixture->getCachedSchemas()['schema'])->toBe($schema);
});

it('keys a schema by its name when caching it', function (): void {
    $fixture = new HasSchemasFixture();

    expect($fixture->getSchema()->getKey())->toBe('schema');
});

it('keys a suffix-resolved schema by the declared name, not the method name', function (): void {
    expect(new SuffixedSchemaHostFixture()->getSchema('article')->getKey())->toBe('article');
});

it('caches a given schema instance under the given name, keyed by that name', function (): void {
    $fixture = new HasSchemasFixture();
    $schema = $fixture->makeSchema();

    $cached = $fixture->cacheSchema('extra', $schema);

    expect($cached)->toBe($schema)
        ->and($schema->getKey())->toBe('extra')
        ->and($fixture->hasCachedSchema('extra'))->toBeTrue()
        ->and($fixture->getCachedSchemas())->toHaveKey('extra');
});

it('unsets a cached schema when null is passed, without triggering discovery', function (): void {
    $fixture = new HasSchemasFixture();
    $fixture->getSchema();

    expect($fixture->hasCachedSchema('schema'))->toBeTrue();

    $result = $fixture->cacheSchema('schema', null);

    expect($result)->toBeNull()
        ->and($fixture->hasCachedSchema('schema'))->toBeFalse();
});

it('checks membership against the raw cache without discovering the schema', function (): void {
    $fixture = new DiscoveryThrowingSchemaHostFixture();

    expect($fixture->hasCachedSchema('schema'))->toBeFalse();
});

it('resolves a schema by name from the cache', function (): void {
    $fixture = new HasSchemasFixture();
    $schema = $fixture->makeSchema();
    $fixture->cacheSchema('extra', $schema);

    expect($fixture->resolveSchemaByName('extra'))->toBe($schema);
});

it('resolves a schema by name through a matching method', function (): void {
    $fixture = new HasSchemasFixture();

    expect($fixture->resolveSchemaByName('schema'))->toBe($fixture->getSchema());
});

it('resolves a suffix-declared schema by name', function (): void {
    $fixture = new SuffixedSchemaHostFixture();

    expect($fixture->resolveSchemaByName('article'))->toBe($fixture->getSchema('article'));
});

it('never throws resolving an unknown schema name', function (): void {
    expect(new HasSchemasFixture()->resolveSchemaByName('unknown'))->toBeNull();
});

it('refuses a name naming a member of the host contract itself', function (string $name): void {
    $fixture = new HasSchemasFixture();

    expect($fixture->resolveSchemaByName($name))->toBeNull()
        ->and($fixture->hasCachedSchema($name))->toBeFalse();
})->with([
    'two Schema-shaped parameters' => ['validateSchemaRecord'],
    'a Schema first parameter' => ['validateSchema'],
    'a Schema return type' => ['makeSchema'],
    'resolved through the "<name>Schema" suffix' => ['make'],
]);

it('resolves a schema component through a cached schema', function (): void {
    $fixture = new HasSchemasFixture();
    $component = new Component()->statePath('name');
    $schema = Schema::make($fixture)->components([$component]);
    $fixture->cacheSchema('extra', $schema);

    expect($fixture->getSchemaComponent('extra.name'))->toBe($component);
});

it('resolves a schema component through a method-declared schema', function (): void {
    $fixture = new HasSchemasFixture();

    expect($fixture->getSchemaComponent('schema.title'))
        ->toBe($fixture->getSchema()->getComponents()[0]);
});

it('returns null for a schema component key with no dot', function (): void {
    expect(new HasSchemasFixture()->getSchemaComponent('title'))->toBeNull();
});

it('returns null for a schema component key naming an unknown schema', function (): void {
    expect(new HasSchemasFixture()->getSchemaComponent('unknown.title'))->toBeNull();
});
