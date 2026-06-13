<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit;

use Sylament\Schemas\Components\Component;
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

it('makes a Schema instance', function (): void {
    expect(Schema::make())->toBeInstanceOf(Schema::class);
});

it('binds the live component passed to make()', function (): void {
    $liveComponent = new HasSchemasFixture();

    expect(Schema::make($liveComponent)->getLiveComponent())->toBe($liveComponent);
});

it('has no live component when made without one', function (): void {
    expect(Schema::make()->getLiveComponent())->toBeNull();
});

it('uses the schema template by default', function (): void {
    expect(Schema::make()->getTemplate())->toBe('@SylamentSchemas/components/Schema.html.twig');
});

it('has no components by default', function (): void {
    expect(Schema::make()->getComponents())->toBe([]);
});

it('holds the components assigned to it', function (): void {
    $components = [new Component(), new Component()];

    expect(Schema::make()->components($components)->getComponents())->toBe($components);
});
