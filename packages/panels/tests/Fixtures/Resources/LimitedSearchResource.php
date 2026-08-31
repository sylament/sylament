<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;

/**
 * Articles under a two-result global search cap. It registers no pages and
 * answers the result URL itself, because an unregistered resource has no
 * mounted routes for the default ladder to generate against.
 */
final class LimitedSearchResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 2;

    public function getGlobalSearchResultUrl(object $record): string
    {
        return '/limited';
    }
}
