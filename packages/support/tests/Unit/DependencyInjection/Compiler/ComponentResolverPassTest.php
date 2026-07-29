<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\DependencyInjection\Compiler;

use Sylament\Support\DependencyInjection\Compiler\ComponentResolverPass;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Support\Tests\Fixtures\DependencyInjection\ResolvableComponentFixture;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

function componentResolverContainer(): ContainerBuilder
{
    $container = new ContainerBuilder();
    $container->setDefinition(
        'sylament_support.component_resolver',
        new Definition(ComponentResolver::class, ['$components' => null]),
    );

    return $container;
}

it('marks tagged component definitions as non-shared', function (): void {
    $container = componentResolverContainer();
    $container->setDefinition(ResolvableComponentFixture::class, new Definition(ResolvableComponentFixture::class))
        ->addTag('sylament.component');

    new ComponentResolverPass()->process($container);

    expect($container->getDefinition(ResolvableComponentFixture::class)->isShared())->toBeFalse();
});

it('replaces the resolver locator argument with a reference', function (): void {
    $container = componentResolverContainer();
    $container->setDefinition(ResolvableComponentFixture::class, new Definition(ResolvableComponentFixture::class))
        ->addTag('sylament.component');

    new ComponentResolverPass()->process($container);

    expect($container->getDefinition('sylament_support.component_resolver')->getArgument('$components'))
        ->toBeInstanceOf(Reference::class);
});

it('keys the locator by class name, not service id', function (): void {
    $container = componentResolverContainer();
    $container->setDefinition('some.custom.id', new Definition(ResolvableComponentFixture::class))
        ->addTag('sylament.component');

    new ComponentResolverPass()->process($container);

    $locatorId = (string) $container->getDefinition('sylament_support.component_resolver')->getArgument('$components');

    expect(array_keys($container->getDefinition($locatorId)->getArgument(0)))
        ->toBe([ResolvableComponentFixture::class]);
});

it('skips tagged definitions without a class', function (): void {
    $container = componentResolverContainer();
    $container->setDefinition('no_class', new Definition())->addTag('sylament.component');

    new ComponentResolverPass()->process($container);

    $locatorId = (string) $container->getDefinition('sylament_support.component_resolver')->getArgument('$components');

    expect($container->getDefinition($locatorId)->getArgument(0))->toBe([]);
});

it('does nothing when the resolver service is absent', function (): void {
    $container = new ContainerBuilder();
    $container->setDefinition(ResolvableComponentFixture::class, new Definition(ResolvableComponentFixture::class))
        ->addTag('sylament.component');

    new ComponentResolverPass()->process($container);

    expect($container->getDefinition(ResolvableComponentFixture::class)->isShared())->toBeTrue();
});
