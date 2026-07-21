<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit;

use Sylament\Schemas\Schema;
use Sylament\Support\Service\Invoker;
use Symfony\Component\DependencyInjection\Container;

beforeEach(function (): void {
    Invoker::setInstance(new Invoker(new Container()));
});

afterEach(function (): void {
    Invoker::unsetInstance();
});

it('resolves the $schema closure parameter to itself', function (): void {
    $schema = Schema::make();

    $result = $schema->evaluate(fn ($schema) => $schema);

    expect($result)->toBe($schema);
});

it('resolves a self-typed closure parameter to itself', function (): void {
    $schema = Schema::make();

    $result = $schema->evaluate(fn (Schema $instance): Schema => $instance);

    expect($result)->toBe($schema);
});
