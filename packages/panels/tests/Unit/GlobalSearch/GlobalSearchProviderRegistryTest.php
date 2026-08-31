<?php

declare(strict_types=1);

use Sylament\Panels\GlobalSearch\GlobalSearchProviderRegistry;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;
use Sylament\Panels\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Symfony\Component\DependencyInjection\ServiceLocator;

function globalSearchProviderRegistry(GlobalSearchProvider ...$providers): GlobalSearchProviderRegistry
{
    $factories = [];

    foreach ($providers as $provider) {
        $factories[$provider::class] = fn (): GlobalSearchProvider => $provider;
    }

    return new GlobalSearchProviderRegistry(new ServiceLocator($factories));
}

function globalSearchStubProvider(): GlobalSearchProvider
{
    return new class implements GlobalSearchProvider {
        public function getResults(string $query): GlobalSearchResults
        {
            return GlobalSearchResults::make();
        }
    };
}

it('answers the provider registered under a class', function (): void {
    $provider = globalSearchStubProvider();
    $registry = globalSearchProviderRegistry($provider);

    expect($registry->has($provider::class))->toBeTrue()
        ->and($registry->get($provider::class))->toBe($provider);
});

it('throws for a class nothing registered, naming the tag that collects providers', function (): void {
    globalSearchProviderRegistry()->get(GlobalSearchProvider::class);
})->throws(LogicException::class, 'sylament.panels.global_search_provider');
