<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\GlobalSearch;

use Sylament\Panels\GlobalSearch\GlobalSearchResult;
use Sylament\Panels\GlobalSearch\GlobalSearchResults;
use Sylament\Panels\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * A custom provider, a service autoconfiguration tags: one `Shelf` category naming the term, nothing at all for
 * the term {@see self::SILENT_TERM}, and for {@see self::CATALOGUE_TERM} a result whose title and one labelled detail
 * are catalogue messages.
 */
final class ShelfGlobalSearchProvider implements GlobalSearchProvider
{
    public const string SILENT_TERM = 'nothing';

    public const string CATALOGUE_TERM = 'catalogue';

    public function getResults(string $query): ?GlobalSearchResults
    {
        if ($query === self::SILENT_TERM) {
            return null;
        }

        if ($query === self::CATALOGUE_TERM) {
            return GlobalSearchResults::make()->category('Shelf', [new GlobalSearchResult(
                title: new TranslatableMessage('search.title', ['term' => $query], 'fixtures-panels'),
                url: '/shelf',
                details: ['Count' => new TranslatableMessage('search.detail', ['words' => 2], 'fixtures-panels')],
            )]);
        }

        return GlobalSearchResults::make()->category('Shelf', [new GlobalSearchResult(title: "Shelf of {$query}", url: '/shelf')]);
    }
}
