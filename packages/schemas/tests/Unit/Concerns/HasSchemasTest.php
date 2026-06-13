<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use RuntimeException;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Service\Invoker;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

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
