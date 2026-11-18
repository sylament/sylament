<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\GlobalSearch;

use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;
use Sylament\Panels\GlobalSearch\Providers\Contracts\GlobalSearchProvider;

/**
 * A custom provider, a service autoconfiguration tags: one `Shelf` category naming the term, and nothing at all for
 * the term {@see self::SILENT_TERM}.
 */
final class ShelfGlobalSearchProvider implements GlobalSearchProvider
{
    public const string SILENT_TERM = 'nothing';

    public function getResults(string $query): ?GlobalSearchResults
    {
        if ($query === self::SILENT_TERM) {
            return null;
        }

        return GlobalSearchResults::make()->category('Shelf', [new GlobalSearchResult(title: "Shelf of {$query}", url: '/shelf')]);
    }
}
