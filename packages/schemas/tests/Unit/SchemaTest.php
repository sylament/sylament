<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Enum\Alignment;

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

it('resolves the $container closure parameter to itself', function (): void {
    $schema = Schema::make();

    $result = $schema->evaluate(fn ($container) => $container);

    expect($result)->toBe($schema);
});

it('resolves the $liveComponent closure parameter to the bound live component', function (): void {
    $liveComponent = new HasSchemasFixture();
    $schema = Schema::make($liveComponent);

    $result = $schema->evaluate(fn ($liveComponent) => $liveComponent);

    expect($result)->toBe($liveComponent);
});

it('resolves the $liveComponent closure parameter to null on a standalone schema', function (): void {
    $schema = Schema::make();

    $result = $schema->evaluate(fn ($liveComponent): string => $liveComponent === null ? 'unbound' : 'bound');

    expect($result)->toBe('unbound');
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
