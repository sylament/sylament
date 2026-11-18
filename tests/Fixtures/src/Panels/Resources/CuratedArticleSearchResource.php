<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Doctrine\ORM\QueryBuilder;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListArticles;

/**
 * A searched article shaped by every query hook: its record query hides a title starting `Archived`, its global search
 * query orders by title, its search hook hides a title starting `Secret`, and two hits at most are read; a title
 * holding `draft` answers no URL, and every other hit links to the index, its one page.
 */
final class CuratedArticleSearchResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $pluralRecordLabel = 'curated articles';

    protected static int $globalSearchResultsLimit = 2;

    public function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
        ];
    }

    public function getRecordQuery(): QueryBuilder
    {
        return parent::getRecordQuery()->andWhere("record.title NOT LIKE 'Archived%'");
    }

    public function getGlobalSearchRecordQuery(): QueryBuilder
    {
        return parent::getGlobalSearchRecordQuery()->orderBy('record.title');
    }

    public function modifyGlobalSearchQuery(QueryBuilder $query, string $search): void
    {
        $query->andWhere("record.title NOT LIKE 'Secret%'");
    }

    public function getGlobalSearchResultUrl(object $record): ?string
    {
        /** @var Article $record */
        return str_contains($record->title, 'draft') ? null : parent::getGlobalSearchResultUrl($record);
    }
}
