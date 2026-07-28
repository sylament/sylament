<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\DependencyInjection;

use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Support\Tests\Fixtures\DependencyInjection\ComponentDependencyFixture;
use Sylament\Support\Tests\Fixtures\DependencyInjection\ResolvableComponentFixture;
use Symfony\Component\DependencyInjection\ServiceLocator;

it('instantiates a class directly when it has no locator', function (): void {
    $component = new ComponentResolver()->instantiate(ResolvableComponentFixture::class);

    expect($component)->toBeInstanceOf(ResolvableComponentFixture::class)
        ->and($component->dependency)->toBeNull();
});

it('instantiates a class directly when the locator does not know it', function (): void {
    $resolver = new ComponentResolver(new ServiceLocator([]));

    expect($resolver->instantiate(ResolvableComponentFixture::class)->dependency)->toBeNull();
});

it('instantiates a class through the locator when it knows it', function (): void {
    $resolver = new ComponentResolver(new ServiceLocator([
        ResolvableComponentFixture::class => fn (): ResolvableComponentFixture => new ResolvableComponentFixture(new ComponentDependencyFixture()),
    ]));

    expect($resolver->instantiate(ResolvableComponentFixture::class)->dependency)
        ->toBeInstanceOf(ComponentDependencyFixture::class);
});

it('lazily creates a locator-less instance when no kernel has booted', function (): void {
    expect(ComponentResolver::resolve())->toBeInstanceOf(ComponentResolver::class)
        ->and(ComponentResolver::resolve())->toBe(ComponentResolver::resolve());
});

it('prefers an instance set by the container over the lazy fallback', function (): void {
    $fromContainer = new ComponentResolver(new ServiceLocator([]));
    ComponentResolver::setInstance($fromContainer);

    expect(ComponentResolver::resolve())->toBe($fromContainer);
});
