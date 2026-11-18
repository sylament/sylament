<?php

declare(strict_types=1);

use Sylament\Panels\DependencyInjection\Compiler\GlobalSearchProviderPass;
use Sylament\Panels\GlobalSearch\GlobalSearchProviderRegistry;
use Sylament\Panels\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Sylament\Tests\Fixtures\Panels\GlobalSearch\ShelfGlobalSearchProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;

it('collects every tagged provider into the registry by class, skipping an abstract definition and a service that is no provider', function (): void {
    $container = new ContainerBuilder();
    $container->register(GlobalSearchProviderRegistry::class, GlobalSearchProviderRegistry::class)->setPublic(true);
    $container->register('app.shelf_search', ShelfGlobalSearchProvider::class)->addTag('sylament.panels.global_search_provider');
    $container->register('app.abstract_search', DefaultGlobalSearchProvider::class)->setAbstract(true)->addTag('sylament.panels.global_search_provider');
    $container->register('app.not_a_provider', ArrayObject::class)->addTag('sylament.panels.global_search_provider');
    $container->addCompilerPass(new GlobalSearchProviderPass());
    $container->compile();

    $registry = $container->get(GlobalSearchProviderRegistry::class);

    expect($registry->get(ShelfGlobalSearchProvider::class)->getResults('maps')?->getCategories())->toHaveKey('Shelf')
        ->and($registry->has(DefaultGlobalSearchProvider::class))->toBeFalse()
        ->and($registry->has(ArrayObject::class))->toBeFalse();
});
