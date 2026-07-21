<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Support\Enum\Alignment;
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

it('builds an aligned schema via each alignment factory', function (): void {
    $component = new Component();

    $schema = Schema::start([$component]);

    expect($schema->getComponents())->toBe([$component])
        ->and($schema->getAlignment())->toBe(Alignment::Start)
        ->and(Schema::end([])->getAlignment())->toBe(Alignment::End)
        ->and(Schema::center([])->getAlignment())->toBe(Alignment::Center)
        ->and(Schema::between([])->getAlignment())->toBe(Alignment::Between);
});

it('accepts a components closure in the alignment factories', function (): void {
    $component = new Component();

    $schema = Schema::end(fn (): array => [$component]);

    expect($schema->getComponents())->toBe([$component])
        ->and($schema->getAlignment())->toBe(Alignment::End);
});
