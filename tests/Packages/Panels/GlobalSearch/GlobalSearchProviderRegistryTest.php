<?php

declare(strict_types=1);

use Sylament\Panels\GlobalSearch\GlobalSearchProviderRegistry;
use Sylament\Panels\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Sylament\Tests\Fixtures\Panels\GlobalSearch\ShelfGlobalSearchProvider;

it('hands back the provider service registered for a class, and refuses one no service registers, naming it', function (): void {
    $registry = $this::getContainer()->get(GlobalSearchProviderRegistry::class);

    expect($registry->get(DefaultGlobalSearchProvider::class))->toBeInstanceOf(DefaultGlobalSearchProvider::class)
        ->and($registry->get(ShelfGlobalSearchProvider::class)->getResults('maps')?->getCategories())->toHaveKey('Shelf')
        ->and($registry->has(ShelfGlobalSearchProvider::class))->toBeTrue()
        ->and($registry->has('App\GlobalSearch\UnregisteredProvider'))->toBeFalse()
        ->and(fn () => $registry->get('App\GlobalSearch\UnregisteredProvider'))->toThrow(LogicException::class, 'The global search provider App\GlobalSearch\UnregisteredProvider is not a registered service');
});
