<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasSchemaFixture;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;

it('returns null when no schema is set', function (): void {
    $fixture = new HasSchemaFixture();

    expect($fixture->getSchema(Schema::make()))->toBeNull();
});

it('returns null when the schema evaluates to an empty array', function (): void {
    $fixture = new HasSchemaFixture()->schema([]);

    expect($fixture->getSchema(Schema::make()))->toBeNull();
});

it('wraps an array of components into the given schema', function (): void {
    $component = new Component();
    $fixture = new HasSchemaFixture()->schema([$component]);

    $result = $fixture->getSchema(Schema::make());

    expect($result)->toBeInstanceOf(Schema::class)
        ->and($result->getComponents())->toBe([$component]);
});

it('evaluates a closure schema, receiving the schema being built as a named injection', function (): void {
    $received = null;
    $fixture = new HasSchemaFixture()->schema(function (Schema $schema) use (&$received): array {
        $received = $schema;

        return [Text::make('hi')];
    });

    $outer = Schema::make();
    $result = $fixture->getSchema($outer);

    expect($received)->toBe($outer)
        ->and($result)->toBeInstanceOf(Schema::class)
        ->and($result->getComponents())->toHaveCount(1);
});

it('propagates disabledSchema() to the returned schema', function (): void {
    $fixture = new HasSchemaFixture()
        ->schema([new Component()])
        ->disabledSchema();

    $result = $fixture->getSchema(Schema::make());

    expect($fixture->isSchemaDisabled())->toBeTrue()
        ->and($result->isDisabled())->toBeTrue();
});

it('leaves the returned schema enabled when disabledSchema() is unset', function (): void {
    $fixture = new HasSchemaFixture()->schema([new Component()]);

    $result = $fixture->getSchema(Schema::make());

    expect($result->isDisabled())->toBeFalse();
});

it('defaults hasFormWrapper to true', function (): void {
    $fixture = new HasSchemaFixture();

    expect($fixture->hasFormWrapper())->toBeTrue();
});

it('lets formWrapper(false) override the default', function (): void {
    $fixture = new HasSchemaFixture()->formWrapper(false);

    expect($fixture->hasFormWrapper())->toBeFalse();
});

it('accepts a closure returning an already-built schema, not just a component array', function (): void {
    $prepared = Schema::make()->components([new Component()]);

    $fixture = new HasSchemaFixture()->schema(fn (): Schema => $prepared);

    expect($fixture->getSchema(Schema::make()))->toBe($prepared);
});

it('applies disabledSchema() to a closure-returned schema too', function (): void {
    $fixture = new HasSchemaFixture()
        ->schema(fn (): Schema => Schema::make()->components([new Component()]))
        ->disabledSchema();

    expect($fixture->getSchema(Schema::make())->isDisabled())->toBeTrue();
});
