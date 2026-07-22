<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Concerns;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasComponentsFixture;

it('evaluates the closure lazily when reading the components', function (): void {
    $first = new Component();
    $components = [$first];
    $fixture = new HasComponentsFixture()->components(function () use (&$components): array {
        return $components;
    });

    $second = new Component();
    $components = [$first, $second];

    expect($fixture->getComponents())->toBe([$first, $second]);
});

it('assigns itself as the container of each component', function (): void {
    $component = new Component();
    $fixture = new HasComponentsFixture()->components([$component]);

    $fixture->getComponents();

    expect($component->getContainer())->toBe($fixture);
});

it('caches the resolved components', function (): void {
    $fixture = new HasComponentsFixture()->components([new Component()]);

    expect($fixture->getComponents())->toBe($fixture->getComponents());
});

it('rebuilds the cache when components() is called again', function (): void {
    $fixture = new HasComponentsFixture()->components([new Component()]);
    $first = $fixture->getComponents();

    $replacement = [new Component()];
    $fixture->components($replacement);

    expect($fixture->getComponents())->toBe($replacement)
        ->and($fixture->getComponents())->not->toBe($first);
});

it('treats a components closure returning null as empty', function (): void {
    $fixture = new HasComponentsFixture()->components(fn (): ?array => null);

    expect($fixture->getComponents())->toBe([]);
});
