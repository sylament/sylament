<?php

declare(strict_types=1);

use Sylament\Panels\DependencyInjection\Compiler\ResourcePass;
use Sylament\Panels\Resources\ResourceRegistry;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\BareResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\RouterInterface;

it('collects every concrete resource tagged as one into the registry, by class', function (): void {
    $container = new ContainerBuilder();
    $container->register(ResourceRegistry::class, ResourceRegistry::class)->setPublic(true);
    $container->register('a_resource', ArticleResource::class)->addTag('sylament.panels.resource');
    $container->register('an_abstract_definition', BareResource::class)->setAbstract(true)->addTag('sylament.panels.resource');
    $container->register('not_a_resource', stdClass::class)->addTag('sylament.panels.resource');
    $container->addCompilerPass(new ResourcePass());
    $container->compile();

    $registry = $container->get(ResourceRegistry::class);

    expect($registry->has(ArticleResource::class))->toBeTrue()
        ->and($registry->get(ArticleResource::class))->toBeInstanceOf(ArticleResource::class)
        ->and($registry->has(BareResource::class))->toBeFalse()
        ->and($registry->has(stdClass::class))->toBeFalse();
});

it('leaves a container with no registry as it found it', function (): void {
    $container = new ContainerBuilder();
    $container->register('a_resource', ArticleResource::class)->addTag('sylament.panels.resource');
    $definitions = array_keys($container->getDefinitions());

    new ResourcePass()->process($container);

    expect(array_keys($container->getDefinitions()))->toBe($definitions);
});

it("resolves a resource no panel lists, while each panel's own list decides what it mounts", function (): void {
    $routes = $this::getContainer()->get(RouterInterface::class)->getRouteCollection();

    expect($this::getContainer()->get(ResourceRegistry::class)->has(BareResource::class))->toBeTrue()
        ->and($routes->get('sylament.resource-routes.resources.scoped-articles.index')?->getPath())->toBe('/resource-routes/scoped-articles')
        ->and($routes->get('sylament.admin.resources.scoped-articles.index'))->toBeNull();
});
