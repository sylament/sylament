<?php

declare(strict_types=1);

use Sylament\Panels\DependencyInjection\Compiler\ResourcePass;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

function resourcePassContainer(): ContainerBuilder
{
    $container = new ContainerBuilder();
    $container->setDefinition(ResourceRegistry::class, new Definition(ResourceRegistry::class));

    $definition = new Definition(ArticleResource::class);
    $definition->addTag('sylament.panels.resource');

    $container->setDefinition(ArticleResource::class, $definition);

    return $container;
}

/**
 * @return array<string, ServiceClosureArgument>
 */
function resourceLocatorMap(ContainerBuilder $container): array
{
    $locator = $container->getDefinition(ResourceRegistry::class)->getArgument('$resources');

    expect($locator)->toBeInstanceOf(Reference::class);

    /** @var Reference $locator */
    $map = $container->getDefinition((string) $locator)->getArgument(0);

    expect($map)->toBeArray();

    /** @var array<string, ServiceClosureArgument> $map */
    return $map;
}

it('collects tagged resources into a class-keyed locator on the registry', function (): void {
    $container = resourcePassContainer();

    new ResourcePass()->process($container);

    expect(array_keys(resourceLocatorMap($container)))->toBe([ArticleResource::class]);
});

it('skips abstract definitions', function (): void {
    $container = resourcePassContainer();
    $abstract = new Definition(Resource::class);
    $abstract->setAbstract(true);
    $abstract->addTag('sylament.panels.resource');

    $container->setDefinition('abstract.resource', $abstract);

    new ResourcePass()->process($container);

    expect(array_keys(resourceLocatorMap($container)))->toBe([ArticleResource::class]);
});

it('skips tagged definitions that are not resources', function (): void {
    $container = resourcePassContainer();
    $foreign = new Definition(stdClass::class);
    $foreign->addTag('sylament.panels.resource');

    $container->setDefinition('foreign.resource', $foreign);

    new ResourcePass()->process($container);

    expect(array_keys(resourceLocatorMap($container)))->toBe([ArticleResource::class]);
});

it('leaves the container alone when no registry is defined', function (): void {
    $container = new ContainerBuilder();
    $definition = new Definition(ArticleResource::class);
    $definition->addTag('sylament.panels.resource');

    $container->setDefinition(ArticleResource::class, $definition);

    new ResourcePass()->process($container);

    expect($container->hasDefinition(ResourceRegistry::class))->toBeFalse();
});
