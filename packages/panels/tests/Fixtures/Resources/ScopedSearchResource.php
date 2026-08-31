<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;

/**
 * Articles narrowed to the published ones through `modifyGlobalSearchQuery()`.
 * It answers the result URL itself for the reason
 * {@see LimitedSearchResource} records.
 */
final class ScopedSearchResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    public function modifyGlobalSearchQuery(QueryBuilder $query, string $search): void
    {
        $query
            ->andWhere('record.status = :sylament_fixture_status')
            ->setParameter('sylament_fixture_status', 'published');
    }

    public function getGlobalSearchResultUrl(object $record): string
    {
        return '/scoped';
    }
}
