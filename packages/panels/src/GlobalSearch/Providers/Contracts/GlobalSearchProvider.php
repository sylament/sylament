<?php

declare(strict_types=1);

namespace Sylament\Panels\GlobalSearch\Providers\Contracts;

use Sylament\Panels\GlobalSearch\GlobalSearchResults;

interface GlobalSearchProvider
{
    /**
     * `null` says the provider answered nothing at all — distinct from a
     * result set that came back with no categories in it.
     */
    public function getResults(string $query): ?GlobalSearchResults;
}
