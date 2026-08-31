<?php

declare(strict_types=1);

use Sylament\Panels\DependencyInjection\Compiler\GlobalSearchProviderPass;
use Sylament\Panels\GlobalSearch\GlobalSearchProviderRegistry;
use Sylament\Panels\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

function globalSearchProviderPassContainer(): ContainerBuilder
{
    $container = new ContainerBuilder();
    $container->setDefinition(GlobalSearchProviderRegistry::class, new Definition(GlobalSearchProviderRegistry::class));

    $definition = new Definition(DefaultGlobalSearchProvider::class);
    $definition->addTag('sylament.panels.global_search_provider');

    $container->setDefinition(DefaultGlobalSearchProvider::class, $definition);

    return $container;
}

/**
 * @return array<string, ServiceClosureArgument>
 */
function globalSearchProviderLocatorMap(ContainerBuilder $container): array
{
    $locator = $container->getDefinition(GlobalSearchProviderRegistry::class)->getArgument('$providers');

    expect($locator)->toBeInstanceOf(Reference::class);

    /** @var Reference $locator */
    $map = $container->getDefinition((string) $locator)->getArgument(0);

    expect($map)->toBeArray();

    /** @var array<string, ServiceClosureArgument> $map */
    return $map;
}

it('collects tagged providers into a class-keyed locator on the registry', function (): void {
    $container = globalSearchProviderPassContainer();

    new GlobalSearchProviderPass()->process($container);

    expect(array_keys(globalSearchProviderLocatorMap($container)))->toBe([DefaultGlobalSearchProvider::class]);
});

it('skips abstract definitions', function (): void {
    $container = globalSearchProviderPassContainer();
    $abstract = new Definition(DefaultGlobalSearchProvider::class);
    $abstract->setAbstract(true);
    $abstract->addTag('sylament.panels.global_search_provider');

    $container->setDefinition('abstract.global_search_provider', $abstract);

    new GlobalSearchProviderPass()->process($container);

    expect(array_keys(globalSearchProviderLocatorMap($container)))->toBe([DefaultGlobalSearchProvider::class]);
});

it('skips tagged definitions that are not providers', function (): void {
    $container = globalSearchProviderPassContainer();
    $foreign = new Definition(stdClass::class);
    $foreign->addTag('sylament.panels.global_search_provider');

    $container->setDefinition('foreign.global_search_provider', $foreign);

    new GlobalSearchProviderPass()->process($container);

    expect(array_keys(globalSearchProviderLocatorMap($container)))->toBe([DefaultGlobalSearchProvider::class]);
});

it('leaves the container alone when no registry is defined', function (): void {
    $container = new ContainerBuilder();
    $definition = new Definition(DefaultGlobalSearchProvider::class);
    $definition->addTag('sylament.panels.global_search_provider');

    $container->setDefinition(DefaultGlobalSearchProvider::class, $definition);

    new GlobalSearchProviderPass()->process($container);

    expect($container->hasDefinition(GlobalSearchProviderRegistry::class))->toBeFalse();
});
