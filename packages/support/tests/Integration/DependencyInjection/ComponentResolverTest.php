<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Integration\DependencyInjection;

use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Support\Tests\Fixtures\DependencyInjection\ComponentDependencyFixture;
use Sylament\Support\Tests\Fixtures\DependencyInjection\ResolvableComponentFixture;

it('autowires a registered component subclass through make()', function (): void {
    $this::getContainer();

    expect(ResolvableComponentFixture::make()->dependency)
        ->toBeInstanceOf(ComponentDependencyFixture::class);
});

it('returns a fresh instance on every make()', function (): void {
    $this::getContainer();

    expect(ResolvableComponentFixture::make())->not->toBe(ResolvableComponentFixture::make());
});

it('exposes the container-built resolver through the static instance', function (): void {
    $container = $this::getContainer();

    expect(ComponentResolver::resolve())->toBe($container->get(ComponentResolver::class));
});
