<?php

declare(strict_types=1);

namespace Sylament\Panels\GlobalSearch;

use LogicException;
use Psr\Container\ContainerInterface;
use Sylament\Panels\GlobalSearch\Providers\Contracts\GlobalSearchProvider;

final readonly class GlobalSearchProviderRegistry
{
    public function __construct(
        private ContainerInterface $providers,
    ) {
    }

    /**
     * @param class-string<GlobalSearchProvider> $class
     */
    public function get(string $class): GlobalSearchProvider
    {
        if (! $this->providers->has($class)) {
            throw new LogicException(sprintf('The global search provider %s is not a registered service — register it in the application (or bundle) service configuration so the `sylament.panels.global_search_provider` compiler pass can see it.', $class));
        }

        $provider = $this->providers->get($class);

        if (! $provider instanceof GlobalSearchProvider) {
            throw new LogicException(sprintf('The global search provider locator returned a non-provider service for %s.', $class));
        }

        return $provider;
    }

    /**
     * @param class-string<GlobalSearchProvider> $class
     */
    public function has(string $class): bool
    {
        return $this->providers->has($class);
    }
}
